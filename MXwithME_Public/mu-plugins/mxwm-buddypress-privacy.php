<?php
/**
 * Plugin Name: MXWM BuddyPress Privacy (Core)
 * Description: Núcleo centralizado de privacidad y sincronización ACF ↔ BuddyPress ↔ bbPress para "proyecto".
 * Version: 1.3.0
 * Author: MXWM Team
 * Requires PHP: 8.1
 *
 * NOTAS:
 * - Este mu-plugin sustituye la lógica MXWM del tema. Deja en el tema SOLO UI/UX.
 * - Irreversibilidad real: private/hidden NUNCA vuelve a public.
 * - Lectura híbrida: primero $_POST (ACF frontend), fallback a get_field().
 * - Compatibilidad meta-keys: mxwm_group_id ↔ _mxwm_grupo_id (legacy).
 */

defined('ABSPATH') || exit;

// ============================================================================
// ⚙️ AJUSTES Y FLAGS
// ============================================================================
if (!defined('MXWM_DEFER_PRIVACY')) {
    // false = aplicar estado inmediatamente; true = diferir hasta aprobación (pending→publish)
    define('MXWM_DEFER_PRIVACY', false);
}

// ============================================================================
// 🔧 CHEQUEOS DE ENTORNO
// ============================================================================
add_action('plugins_loaded', function () {
    // Se asume BuddyPress activo en el proyecto; si no, se detiene la infraestructura.
    if (!function_exists('groups_get_group')) {
        // error_log('MXWM PRIVACY: BuddyPress no disponible, deteniendo sistema.'); // 🧩 Debug disponible
        return;
    }

    // Cargar Hooks principales
    mxwm_privacy_register_hooks();
}, 5);

// ============================================================================
// 🧩 COMPATIBILIDAD DE META-KEYS (tema ↔ mu-plugin)
// ============================================================================
if (!function_exists('mxwm_get_group_id_for_project')) {
    /**
     * Obtiene el group_id asociado a un proyecto, leyendo ambas meta keys y sincronizándolas.
     */
    function mxwm_get_group_id_for_project(int $post_id): int
    {
        $id_a = (int) get_post_meta($post_id, 'mxwm_group_id', true);     // nueva
        $id_b = (int) get_post_meta($post_id, '_mxwm_grupo_id', true);    // legacy

        $group_id = $id_b ?: $id_a;

        if ($group_id) {
            if (!$id_b) update_post_meta($post_id, '_mxwm_grupo_id', $group_id);
            if (!$id_a) update_post_meta($post_id, 'mxwm_group_id', $group_id);
        }

        return $group_id;
    }
}

// ============================================================================
// 🧰 HELPERS (normalización y utilidades BP/bbP)
// ============================================================================
/**
 * Normaliza un valor de estado ACF/POST a BuddyPress: public | private | hidden
 */
function mxwm_privacy_normalize_group_status(?string $raw): string
{
    $v = strtolower(trim((string) $raw));
    // eliminar emojis / palabras extra (quedarnos con la primera palabra alfanumérica)
    $v = preg_replace('/[^a-záéíóúñü ]/u', '', $v);
    $v = trim(explode(' ', $v)[0]);

    return match ($v) {
        'privado', 'private' => 'private',
        'oculto', 'hidden'   => 'hidden',
        default              => 'public',
    };
}

/**
 * Limpia caché del grupo en BuddyPress.
 */
function mxwm_privacy_flush_group_cache(int $group_id): void
{
    if (function_exists('groups_delete_group_cache')) {
        groups_delete_group_cache($group_id);
    }
    wp_cache_delete('groups_group_' . $group_id, 'bp');
    // error_log("🔄 MXWM CACHE: Invalidada para grupo {$group_id}"); // 🧩 Debug disponible
}

/**
 * Actualiza el estado del foro asociado (bbPress) según estado del grupo.
 * Mapea: public→publish, private|hidden→private (bbPress no tiene hidden).
 */
function mxwm_privacy_sync_forum_status(int $group_id, string $bp_status): void
{
    if (!function_exists('bbp_get_forum')) {
        return;
    }
    $forum_id = (int) groups_get_groupmeta($group_id, 'forum_id', true);
    if (!$forum_id) return;

    $post_status = ($bp_status === 'public') ? 'publish' : 'private';
    $current = get_post_status($forum_id);
    if ($current === $post_status) return;

    wp_update_post([
        'ID'          => $forum_id,
        'post_status' => $post_status,
    ]);
    // error_log("🧵 MXWM FORUM: {$forum_id} → {$post_status} por estado grupo {$bp_status}"); // 🧩 Debug disponible
}

/**
 * Crea (si no existe) o actualiza el estado de un grupo BP.
 * Aplica irreversibilidad: private/hidden NUNCA vuelve a public.
 */
function mxwm_privacy_apply_group_status(int $post_id, bool $should_have_group, string $target_status): void
{
    if (!function_exists('groups_get_group')) return;

    $group_id = mxwm_get_group_id_for_project($post_id);
    $target   = mxwm_privacy_normalize_group_status($target_status);

    if (!$should_have_group) {
        // Por ahora NO eliminamos grupos si el checkbox se desactiva; podrías manejarlo si lo deseas.
        // error_log("ℹ️ MXWM: Proyecto {$post_id} sin grupo activo."); // 🧩 Debug disponible
        return;
    }

    // 1) Crear grupo si no existe
    if (!$group_id) {
        $post = get_post($post_id);
        if (!$post) return;

        $new_group = [
            'name'        => $post->post_title ?: ('Proyecto ' . $post_id),
            'description' => $post->post_content ?: '',
            'status'      => $target, // public|private|hidden
        ];

        // Intento de creación usando API BP
        $group_id = (int) groups_create_group($new_group);
        if ($group_id > 0) {
            update_post_meta($post_id, 'mxwm_group_id', $group_id);
            update_post_meta($post_id, '_mxwm_grupo_id', $group_id);
            mxwm_privacy_flush_group_cache($group_id);
            // error_log("✅ MXWM: Grupo {$group_id} creado para proyecto {$post_id} con estado {$target}"); // 🧩 Debug disponible
        } else {
            // error_log("❌ MXWM: Falló creación de grupo para proyecto {$post_id}"); // 🧩 Debug disponible
            return;
        }
    }

    // 2) Actualizar estado respetando irreversibilidad
    $group = groups_get_group(['group_id' => $group_id]);
    if (!$group || empty($group->id)) return;

    $from = strtolower($group->status ?: 'public');
    $to   = $target;

    if (in_array($from, ['private', 'hidden'], true) && $to === 'public') {
        // error_log("🚫 MXWM: Bloqueado cambio {$from} → {$to} (grupo {$group_id})"); // 🧩 Debug disponible
        $to = $from; // mantener
    }

    if ($from !== $to) {
        $group->status = $to;
        $group->save();
        mxwm_privacy_flush_group_cache($group_id);
        mxwm_privacy_sync_forum_status($group_id, $to);
        // error_log("🔁 MXWM: Grupo {$group_id} actualizado {$from} → {$to}"); // 🧩 Debug disponible
    } else {
        // error_log("ℹ️ MXWM: Grupo {$group_id} ya está en {$to}"); // 🧩 Debug disponible
    }
}

// ============================================================================
// 📌 REGISTRO DE HOOKS PRINCIPALES
// ============================================================================
function mxwm_privacy_register_hooks(): void
{
    // ------------------------------------------------------------------------
    // A) ACOPLAMIENTO ACF → aplica inmediatamente (lectura híbrida)
    // ------------------------------------------------------------------------
    add_action('acf/save_post', function ($post_id) {

// =============================================================
// 🧩 MXWM DIAGNÓSTICO ACF → GRUPO - SFOT 16 OCT 25
// =============================================================
error_log("🚀 MXWM PRIVACY HOOK ACTIVADO para post_id={$post_id}");

// Verificar tipo de post
$post_type = get_post_type($post_id);
error_log("🧩 MXWM DEBUG: post_type={$post_type}");
if ($post_type !== 'proyecto') {
    error_log("🧩 MXWM DEBUG: Tipo de post no es proyecto → {$post_type}. Saliendo.");
    return;
}

// Verificar autosave, revisión o AJAX
if (wp_is_post_autosave($post_id) || wp_is_post_revision($post_id)) {
    error_log("🧩 MXWM DEBUG: Autosave/revisión detectado → cancelado.");
    return;
}
if (wp_doing_ajax()) {
    error_log("🧩 MXWM DEBUG: AJAX detectado → cancelado.");
    return;
}

// -----------------------------------------------
// Lectura híbrida de campos
// -----------------------------------------------
$post_activar_grupo = $_POST['activar_grupo'] ?? '(no en POST)';
$post_estado        = $_POST['estado_grupo'] ?? '(no en POST)';

$acf_activar_grupo = function_exists('get_field') ? get_field('activar_grupo', $post_id) : '(no ACF)';
$acf_estado        = function_exists('get_field') ? get_field('estado_grupo', $post_id)  : '(no ACF)';

// Mostrar lo que llegó
error_log("🧩 MXWM DEBUG: POST activar_grupo={$post_activar_grupo} | ACF activar_grupo={$acf_activar_grupo}");
error_log("🧩 MXWM DEBUG: POST estado_grupo={$post_estado} | ACF estado_grupo={$acf_estado}");

// -----------------------------------------------
// Determinar activación real y estado final
// -----------------------------------------------
$activar_grupo = in_array($post_activar_grupo, ['1', 'true', 'on'], true) || $acf_activar_grupo;
$estado_final = $post_estado ?: $acf_estado ?: 'publico';
error_log("🧩 MXWM DEBUG: activar_grupo interpretado=" . ($activar_grupo ? 'true' : 'false') . " | estado_final={$estado_final}");

// -----------------------------------------------
// Ver si entra al aplicador
// -----------------------------------------------
if (!$activar_grupo) {
    error_log("🧩 MXWM DEBUG: activar_grupo es FALSE → no se intentará crear ni actualizar grupo.");
} else {
    error_log("🧩 MXWM DEBUG: Se llamará a mxwm_privacy_apply_group_status() con estado_final={$estado_final}");
}

        // Solo tipo "proyecto"
        if (get_post_type($post_id) !== 'proyecto') return;

        // Evitar autosave/revisión y AJAX
        if (wp_is_post_autosave($post_id) || wp_is_post_revision($post_id)) return;
        if (wp_doing_ajax()) return;

        // 1) Lectura híbrida (POST → fallback ACF)
        $post_activar_grupo = isset($_POST['activar_grupo']) ? (string) $_POST['activar_grupo'] : null;
        $post_estado        = isset($_POST['estado_grupo'])  ? (string) $_POST['estado_grupo']  : null;

        // get_field como respaldo (si ACF ya persistió)
        if (!function_exists('get_field')) {
            $acf_activar_grupo = null;
            $acf_estado        = null;
        } else {
            $acf_activar_grupo = get_field('activar_grupo', $post_id);
            $acf_estado        = get_field('estado_grupo',  $post_id);
        }

        // Normalizar activar_grupo (checkbox/true_false puede venir como '1', true o null)
        $activar_grupo = null;
        if ($post_activar_grupo !== null) {
            $activar_grupo = in_array($post_activar_grupo, ['1', 'true', 'on'], true);
        } elseif ($acf_activar_grupo !== null) {
            $activar_grupo = (bool) $acf_activar_grupo;
        } else {
            $activar_grupo = false;
        }

        // Normalizar estado final (usa POST si viene, si no ACF, si no 'public')
        $estado_raw   = $post_estado ?: $acf_estado ?: 'publico';
        $estado_final = mxwm_privacy_normalize_group_status($estado_raw);
        error_log("🧩 MXWM DEBUG: POST[activar_grupo]=" . var_export($_POST['activar_grupo'] ?? 'NO_SET', true));
        error_log("🧩 MXWM DEBUG: ACF activar_grupo=" . var_export($acf_activar_grupo, true));
        error_log("🧩 MXWM DEBUG: activar_grupo final=" . var_export($activar_grupo, true));
        error_log("🧩 MXWM DEBUG: estado_final=" . var_export($estado_final, true));


        // 2) Si se utiliza privacidad diferida, solo almacenamos intención y salimos
        if (MXWM_DEFER_PRIVACY) {
            update_post_meta($post_id, 'mxwm_cambio_privacidad_pendiente', $estado_final);
            // error_log("⏸️ MXWM: Diferido estado '{$estado_final}' en proyecto {$post_id}"); // 🧩 Debug disponible
            return;
        }

        // 3) Aplicación inmediata
        // 3) Aplicación inmediata
$project_id = (int) $post_id;
$group_id   = mxwm_get_group_id_for_project($project_id);

// ===========================================================
// 🧱 Lógica de creación o actualización de grupo
// ===========================================================
if ($activar_grupo) {

    if (!$group_id) {
        // ========================
        // CREACIÓN DEL GRUPO BP
        // ========================
        if (function_exists('groups_create_group')) {
            $post = get_post($project_id);
            if ($post && $post->ID) {
                $creator_id = (int) $post->post_author;
                $bp_status  = mxwm_privacy_normalize_group_status($estado_final);

                $group_name = get_the_title($post->ID);
                $group_desc = wp_strip_all_tags($post->post_content);

                $new_group_id = groups_create_group([
                    'creator_id'  => $creator_id ?: get_current_user_id(),
                    'name'        => $group_name ?: sprintf('Proyecto #%d', $post->ID),
                    'description' => $group_desc ?: '',
                    'status'      => $bp_status,
                ]);

                if ($new_group_id && !is_wp_error($new_group_id)) {
                    // Guardar ambas meta keys
                    update_post_meta($post->ID, 'mxwm_group_id', $new_group_id);
                    update_post_meta($post->ID, '_mxwm_grupo_id', $new_group_id);

                    // Aplicar estado
                    mxwm_privacy_apply_group_status($new_group_id, true, $bp_status);

                    // Limpiar caché y sincronizar foro
                    mxwm_privacy_flush_group_cache($new_group_id);
                    mxwm_privacy_sync_forum_status($new_group_id, $bp_status);

                    error_log("✅ MXWM: Grupo {$new_group_id} creado para proyecto {$post->ID} con estado '{$bp_status}'");
                } else {
                    error_log("❌ MXWM: Error al crear grupo para proyecto {$post->ID}");
                }
            }
        }
    } else {
        // Ya existe → aplicar estado normalmente
        mxwm_privacy_apply_group_status($group_id, true, $estado_final);
    }

} else {
    // Si desactivan grupo, no hacemos nada
    error_log("ℹ️ MXWM: Proyecto {$project_id} sin grupo activo");
}


    }, 25); // prioridad media-baja: damos tiempo a otros callbacks y aún capturamos $_POST

    // ------------------------------------------------------------------------
    // B) (Opcional) Diferir cambio hasta aprobación pending→publish
    // ------------------------------------------------------------------------
    if (MXWM_DEFER_PRIVACY) {
        add_action('transition_post_status', function ($new_status, $old_status, $post) {

            if ($post->post_type !== 'proyecto') return;
            if ($new_status === 'publish' && $old_status !== 'publish') {

                $pending = get_post_meta($post->ID, 'mxwm_cambio_privacidad_pendiente', true);
                if (empty($pending)) return;

                $activar_grupo = (bool) get_field('activar_grupo', $post->ID);
                $estado_final  = mxwm_privacy_normalize_group_status($pending);

                mxwm_privacy_apply_group_status((int) $post->ID, $activar_grupo, $estado_final);
                delete_post_meta($post->ID, 'mxwm_cambio_privacidad_pendiente');
                // error_log("✅ MXWM: Aplicado diferido {$estado_final} al aprobar proyecto {$post->ID}"); // 🧩 Debug disponible
            }

        }, 10, 3);
    }

    // ------------------------------------------------------------------------
    // C) Guardia de irreversibilidad a nivel BuddyPress (última línea de defensa)
    // ------------------------------------------------------------------------
    add_action('groups_group_before_save', function ($group) {
        if (empty($group->id)) return;

        $existing = groups_get_group(['group_id' => (int) $group->id]);
        if (!$existing || empty($existing->id)) return;

        $from = strtolower($existing->status ?: 'public');
        $to   = strtolower($group->status  ?: 'public');

        if (in_array($from, ['private', 'hidden'], true) && $to === 'public') {
            // Mantener estado
            $group->status = $from;

            if (function_exists('bp_core_add_message')) {
                bp_core_add_message(
                    __('Este grupo es Privado/Oculto. Por seguridad, no puede volver a Público.', 'mxwm'),
                    'error'
                );
            }
            // error_log("🚫 MXWM [PRIVACY] Bloqueado {$from} → {$to} (grupo {$group->id})"); // 🧩 Debug disponible
        }
    }, 3);

    // Limpieza de caché tras guardar
    add_action('groups_group_after_save', function ($group) {
        if (empty($group->id)) return;
        mxwm_privacy_flush_group_cache((int) $group->id);
    }, 10);
}

// ============================================================================
// 🧭 COMPATIBILIDAD BuddyPress 12+
// - Reemplaza funciones obsoletas en el tema si todavía se usan:
//   bp_core_get_user_domain() → bp_members_get_user_url()
//   bp_get_group_permalink()  → bp_get_group_url()
//   (Estos reemplazos deben aplicarse en el tema/plantillas, no aquí.)
// ============================================================================

