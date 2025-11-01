<?php
defined('ABSPATH') || exit;

/**
 * Sincroniza el campo ACF 'estado_grupo' con BuddyPress al guardar.
 * Soporta 2 contextos:
 * 1. Guardar desde grupo BP (post_id = group_{id})
 * 2. Guardar desde proyecto (post_id = proyecto_id)
 */

// Hook 1: Guardar desde ACF grupo directo
add_action('acf/save_post', 'mxwm_sync_privacidad_grupo', 20);

// Hook 2: Guardar desde proyecto
add_action('acf/save_post', 'mxwm_sync_privacidad_desde_proyecto', 20);

// Hook 3: Fallback desde BuddyPress
add_action('groups_group_after_save', 'mxwm_sync_privacidad_group_after_save', 20, 1);

/**
 * NUEVO: Sincronizar desde PROYECTO
 */
function mxwm_sync_privacidad_desde_proyecto($post_id) {
    // Solo procesar si es un entero (post ID de proyecto)
    if (!is_int($post_id)) return;
    
    // Solo procesar proyectos
    if (get_post_type($post_id) !== 'proyecto') return;
    
    // Obtener el grupo asociado
    $grupo_id = get_post_meta($post_id, '_mxwm_grupo_id', true);
    if (!$grupo_id) {
        MXWM_Helpers::log("Proyecto {$post_id} no tiene grupo asociado", 'debug');
        return;
    }
    
    // Obtener el estado del grupo desde ACF del proyecto
    if (function_exists('get_field')) {
        $estado_grupo = get_field('estado_grupo', $post_id);
    } else {
        $estado_grupo = get_post_meta($post_id, 'estado_grupo', true);
    }
    
    if (empty($estado_grupo)) {
        MXWM_Helpers::log("Proyecto {$post_id}: campo estado_grupo vacío", 'debug');
        return;
    }
    
    MXWM_Helpers::log("Proyecto {$post_id}: sincronizando grupo {$grupo_id} a estado '{$estado_grupo}'", 'info');
    
    // Sincronizar el grupo
    mxwm_sync_group_status($grupo_id, $estado_grupo);
}

/**
 * Sincronizar desde grupo BP directo
 */
function mxwm_sync_privacidad_group_after_save($group) {
    if (empty($group) || !isset($group->id)) return;
    $post_id = 'group_' . intval($group->id);
    mxwm_sync_privacidad_grupo($post_id);
}

/**
 * Sincronizar desde ACF con formato group_{id}
 */
function mxwm_sync_privacidad_grupo($post_id) {
    if (is_int($post_id)) return;
    if (strpos($post_id, 'group_') !== 0) return;

    $group_id = intval(str_replace('group_', '', $post_id));
    if (!$group_id) return;

    if (function_exists('get_field')) {
        $valor_acf = get_field('estado_grupo', $post_id);
    } else {
        $valor_acf = get_post_meta($post_id, 'estado_grupo', true);
    }

    if (empty($valor_acf)) return;
    
    MXWM_Helpers::log("Grupo {$group_id}: sincronizando desde ACF group_{id} a estado '{$valor_acf}'", 'info');
    
    mxwm_sync_group_status($group_id, $valor_acf);
}

/**
 * FUNCIÓN PRINCIPAL: Sincronizar estado del grupo
 */
function mxwm_sync_group_status($group_id, $nuevo_estado) {
    $status_final = MXWM_Helpers::sanitize_privacy_value($nuevo_estado);

    if (!function_exists('groups_get_group')) return;
    $group = groups_get_group($group_id);
    if (empty($group)) return;

    // Si ya está en ese estado, no hacer nada
    if (isset($group->status) && $group->status === $status_final) {
        MXWM_Helpers::log("Grupo {$group_id} ya en estado {$status_final}", 'debug');
        return;
    }

    // Verificar si es irreversible
    $is_irrev = groups_get_groupmeta($group_id, 'mxwm_irreversible');
    if ($is_irrev && $group->status === 'private' && $status_final !== 'private') {
        if (!current_user_can('override_group_privacy_lock')) {
            MXWM_Helpers::log("Bloqueando intento de revertir grupo {$group_id}", 'warn');
            return;
        }
    }

     // Evitar que un grupo privado vuelva a ser público
     $current_group = groups_get_group($group_id, true);
     if ($current_group->status === 'private' && $nuevo_estado !== 'private') {
     error_log("⚠️ [MXWM PRIVACY] Intento de revertir grupo privado: bloqueado. (ID $group_id)");
     return false;
}


    // Actualizar el grupo
    $result = groups_edit_base_group_details(
        $group_id,
        $group->name,
        $group->description,
        $status_final
    );

    if ($result) {
        groups_update_groupmeta($group_id, 'mxwm_privacidad_sincronizada', current_time('mysql'));
        MXWM_Helpers::log("Grupo {$group_id} sincronizado a {$status_final}", 'info');

        // Marcar como irreversible si es transición public -> private
        if ($status_final === 'private' && (!$is_irrev && (isset($group->status) && $group->status === 'public'))) {
            groups_update_groupmeta($group_id, 'mxwm_irreversible', 1);
            groups_update_groupmeta($group_id, 'mxwm_fecha_irreversible', current_time('mysql'));
            MXWM_Helpers::log("Grupo {$group_id} marcado como irreversible", 'info');
        }
    } else {
        MXWM_Helpers::log("Error al sincronizar grupo {$group_id} a {$status_final}", 'error');
    }
}
/**
 * Forzar invalidación del caché de BuddyPress después de actualizar el estado del grupo.
 * Compatibilidad con BP >= 2.9 (recibe objeto o ID)
 */
function mxwm_refresh_bp_group_cache($group) {
    // Aceptar tanto objeto BP_Groups_Group como ID numérico
    $group_id = is_object($group) && isset($group->id) ? $group->id : (int) $group;

    if (empty($group_id)) {
        error_log("⚠️ MXWM CACHE: No se pudo determinar ID de grupo en refresh.");
        return;
    }

    if (function_exists('bp_groups_clear_group_object_cache')) {
        bp_groups_clear_group_object_cache($group_id);
        error_log("🔄 MXWM CACHE: Caché de grupo {$group_id} invalidada (bp_groups_clear_group_object_cache).");
    } else {
        wp_cache_delete('groups_get_group_' . $group_id, 'bp');
        wp_cache_delete('bp_groups_' . $group_id, 'bp');
        wp_cache_delete($group_id, 'bp_groups');
        error_log("🔄 MXWM CACHE: Caché de grupo {$group_id} invalidada manualmente.");
    }
}


add_action('groups_group_after_save', 'mxwm_refresh_bp_group_cache');
add_action('groups_settings_updated', 'mxwm_refresh_bp_group_cache');
