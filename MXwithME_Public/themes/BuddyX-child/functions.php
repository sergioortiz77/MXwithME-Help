<?php
/**
 * Functions.php - Ultra Mínimo y Seguro - VERSIÓN CORREGIDA
 * MXWM-PMP-FRONTEND-FIX-DIC2024-FINAL-CORREGIDO
 * SISTEMA-PRIVACIDAD-SINCRONIZACION-v2.2
 */

error_log("MXWM TEST: Functions.php cargado correctamente");

// ============================================================================
// POST TYPE PROYECTO (SOLO LO ESENCIAL)
// ============================================================================

function mxwm_register_proyecto_post_type() {
    register_post_type('proyecto', array(
        'labels' => array(
            'name' => 'Proyectos',
            'singular_name' => 'Proyecto',
            'add_new_item' => 'Añadir Nuevo Proyecto',
        ),
        'public' => true,
        'has_archive' => 'proyectos',
        'rewrite' => array('slug' => 'proyecto'),
        'supports' => array('title', 'editor', 'thumbnail', 'author', 'comments'),
        'menu_icon' => 'dashicons-portfolio',
        'show_in_rest' => true,
    ));
}
add_action('init', 'mxwm_register_proyecto_post_type', 0);

// ============================================================================
// ESTRUCTURA PMP SIMPLE (NIVELES 1-5)
// ============================================================================

function mxwm_get_pmp_levels_config() {
    return array(
        '1' => array(
            'name' => 'PMP Básico',
            'max_projects' => 1,
            'gallery' => false,
            'groups' => false,
            'forums' => false,
            'video' => false,
            'comments' => true,
            'price' => 0
        ),
        '2' => array(
            'name' => 'PMP Pro',
            'max_projects' => 2,
            'gallery' => true,
            'groups' => false,
            'forums' => false,
            'video' => false,
            'comments' => true,
            'price' => 200
        ),
        '3' => array(
            'name' => 'PMP Premium',
            'max_projects' => 3,
            'gallery' => true,
            'groups' => true,
            'forums' => false,
            'video' => true,
            'comments' => false,
            'price' => 397
        ),
        '4' => array(
            'name' => 'PMP Elite',
            'max_projects' => 6,
            'gallery' => true,
            'groups' => true,
            'forums' => true,
            'video' => true,
            'comments' => false,
            'price' => 557
        ),
        '5' => array(
            'name' => 'PMP Enterprise',
            'max_projects' => 9, 
            'gallery' => true,
            'groups' => true,
            'forums' => true,
            'video' => true,
            'comments' => false,
            'custom_features' => true,
            'price' => 1997
        )
    );
}

// ============================================================================
// VERIFICACIÓN DE DEPENDENCIAS (NUEVO - CRÍTICO)
// ============================================================================

function mxwm_verificar_dependencias_foros() {
    $errores = array();
    
    if (!function_exists('buddypress')) {
        $errores[] = 'BuddyPress no está activo';
    }
    
    if (!function_exists('bbp_insert_forum')) {
        $errores[] = 'bbPress no está activo o no tiene las funciones necesarias';
    }
    
    if (!function_exists('pmpro_getMembershipLevelForUser')) {
        $errores[] = 'Paid Memberships Pro no está activo';
    }
    
    if (!empty($errores)) {
        error_log('MXWM ERROR: ' . implode(', ', $errores));
        return false;
    }
    
    return true;
}

// ============================================================================
// FUNCIONES BASE PMP (MANTENIDAS)
// ============================================================================

// Validar si usuario puede crear más proyectos
function mxwm_can_user_create_project($user_id = null) {
    if (!$user_id) {
        $user_id = get_current_user_id();
    }
    
    $user_pmp_level = mxwm_get_user_pmp_level($user_id);
    
    if (!$user_pmp_level) {
        return false;
    }
    
    $config = mxwm_get_pmp_levels_config();
    $max_projects = $config[$user_pmp_level]['max_projects'];
    
    // -1 significa ilimitados
    if ($max_projects == -1) {
        return true;
    }
    
    // Contar proyectos actuales del usuario
    $current_projects = count(get_posts(array(
        'post_type' => 'proyecto',
        'author' => $user_id,
        'post_status' => array('publish', 'pending', 'draft'),
        'numberposts' => -1
    )));
    
    return ($current_projects < $max_projects);
}

// ============================================================================
// VALIDACIÓN DE LÍMITE DE PROYECTOS - CORRECCIÓN CRÍTICA
// ============================================================================

add_action('acf/save_post', 'mxwm_validar_limite_proyectos', 5);
function mxwm_validar_limite_proyectos($post_id) {
    if (get_post_type($post_id) !== 'proyecto' || wp_is_post_autosave($post_id)) {
        return;
    }
    
    $user_id = get_current_user_id();
    $user_level = mxwm_get_user_pmp_level($user_id);
    
    // Solo validar para niveles 1-3
    if ($user_level && intval($user_level) <= 3) {
        $config = mxwm_get_pmp_levels_config();
        $max_projects = $config[$user_level]['max_projects'];
        
        // Contar proyectos actuales (excluyendo el actual si es edición)
        $current_projects = count(get_posts(array(
            'post_type' => 'proyecto',
            'author' => $user_id,
            'post_status' => array('publish', 'pending', 'draft'),
            'numberposts' => -1,
            'exclude' => array($post_id)
        )));
        
        if ($current_projects >= $max_projects) {
            wp_die(
                '❌ Has alcanzado el límite máximo de ' . $max_projects . ' proyectos para tu nivel actual. ' .
                '<br><br><a href="' . home_url('/mis-proyectos/') . '">Volver a Mis Proyectos</a>'
            );
        }
    }
}

// Verificar si usuario puede usar feature específica
function mxwm_user_can_use_feature($feature, $user_id = null) {
    if (!$user_id) {
        $user_id = get_current_user_id();
    }
    
    $user_pmp_level = mxwm_get_user_pmp_level($user_id);
    
    if (!$user_pmp_level) {
        return false;
    }
    
    $config = mxwm_get_pmp_levels_config();
    
    return isset($config[$user_pmp_level][$feature]) && $config[$user_pmp_level][$feature] === true;
}

function mxwm_get_user_pmp_level($user_id = null) {
    if (!$user_id) {
        $user_id = get_current_user_id();
    }
    
    if (!$user_id) {
        return false;
    }
    
    // Admin tiene acceso total
    if (user_can($user_id, 'administrator')) {
        return '5';
    }
    
    // LEER DIRECTAMENTE DE PMPRO (no del meta field)
    if (function_exists('pmpro_getMembershipLevelForUser')) {
        $level = pmpro_getMembershipLevelForUser($user_id);
        
        if ($level && isset($level->id)) {
            return (string)$level->id;
        }
    }
    
    // Fallback: revisar meta field
    $pmp_level = get_user_meta($user_id, 'pmp_level', true);
    
    if (!empty($pmp_level) && in_array($pmp_level, ['1', '2', '3', '4', '5'])) {
        return $pmp_level;
    }
    
    return false;
}

// ============================================================================
// SISTEMA DE SINCRONIZACIÓN UNIFICADO - CON VALIDACIÓN DE RESTRICCIONES
// ============================================================================

/**
 * SINCRONIZACIÓN PRINCIPAL - ÚNICO PUNTO DE ENTRADA
 */
add_action('acf/save_post', 'mxwm_sincronizacion_unificada', 20);
function mxwm_sincronizacion_unificada($post_id) {
    // Solo procesar proyectos
    if (get_post_type($post_id) !== 'proyecto') {
        return;
    }
    
    // Solo si hay datos de ACF y no es autosave
    if (!isset($_POST['acf']) || wp_is_post_autosave($post_id) || wp_is_post_revision($post_id)) {
        return;
    }
    
    // No procesar en AJAX
    if (wp_doing_ajax()) {
        return;
    }
    
    error_log("🔧 MXWM SINCRONIZACIÓN: Iniciando para proyecto {$post_id}");
    
    // Obtener valores actuales de ACF
    $activar_grupo = get_field('activar_grupo', $post_id);
    $estado_grupo = get_field('estado_grupo', $post_id);
    $activar_foro = get_field('activar_foro', $post_id); 
    $estado_foro = get_field('estado_foro', $post_id);
    
    // Obtener estado anterior para validar restricciones
    $estado_grupo_anterior = get_post_meta($post_id, '_mxwm_estado_grupo_anterior', true);
    
    error_log("📋 MXWM DATOS: activar_grupo={$activar_grupo}, estado_grupo={$estado_grupo}, estado_anterior={$estado_grupo_anterior}");
    
    // ============================================
    // VALIDACIÓN DE RESTRICCIONES IRREVERSIBLES
    // ============================================
    
    if ($estado_grupo_anterior === 'privado' && $estado_grupo !== 'privado') {
        error_log("🚫 MXWM BLOQUEO: Intento de cambio IRREVERSIBLE desde PRIVADO - Proyecto {$post_id}");
        
        // Revertir al estado privado
        update_field('estado_grupo', 'privado', $post_id);
        
        // Mostrar error al usuario
        if (!is_admin()) {
            wp_die(
                '❌ Error: No puedes cambiar un grupo PRIVADO. Esta configuración es irreversible por seguridad de los miembros. ' .
                '<br><br><a href="' . get_permalink($post_id) . '">Volver al proyecto</a>'
            );
        }
        
        return;
    }
    
    // ============================================
    // SINCRONIZACIÓN GRUPO BUDDYPRESS
    // ============================================
    
    $grupo_id = get_post_meta($post_id, '_mxwm_grupo_id', true);
    if ($grupo_id && function_exists('groups_get_group')) {
        $grupo = groups_get_group($grupo_id);
        
        if ($grupo) {
            // Mapeo de estados
            $estados_bp = [
                'publico' => 'public',
                'privado' => 'private',
                'oculto' => 'hidden'
            ];
            
            if (isset($estados_bp[$estado_grupo])) {
                $nuevo_estado = $estados_bp[$estado_grupo];
                
                // Solo actualizar si cambió
                if ($grupo->status !== $nuevo_estado) {
                    $resultado = groups_edit_base_group_details([
                        'group_id' => $grupo_id,
                        'status' => $nuevo_estado
                    ]);
                    
                    if ($resultado) {
                        error_log("✅ MXWM GRUPO: {$grupo_id} actualizado a {$nuevo_estado} ({$estado_grupo})");
                        
                        // Si cambió a privado, marcar como irreversible
                        if ($estado_grupo === 'privado') {
                            update_post_meta($post_id, '_mxwm_grupo_irreversible', true);
                            groups_update_groupmeta($grupo_id, '_mxwm_privacidad_irreversible', true);
                            error_log("🔐 MXWM: Grupo {$grupo_id} marcado como IRREVERSIBLE");
                        }
                    } else {
                        error_log("❌ MXWM GRUPO: Error actualizando grupo {$grupo_id}");
                    }
                } else {
                    error_log("ℹ️ MXWM GRUPO: {$grupo_id} ya está en estado {$nuevo_estado}");
                }
            }
        }
    }
    
    // ============================================
    // SINCRONIZACIÓN FORO BBPRESS
    // ============================================
    
    $foro_id = get_post_meta($post_id, '_mxwm_foro_id', true);
    if ($foro_id && $activar_foro && function_exists('bbp_get_forum')) {
        $foro = get_post($foro_id);
        
        if ($foro) {
            $estados_bbp = [
                'publico' => 'publish',
                'privado' => 'private',
                'oculto' => 'private' // bbPress no tiene "hidden", usamos private
            ];
            
            if (isset($estados_bbp[$estado_foro])) {
                $nuevo_estado = $estados_bbp[$estado_foro];
                
                // Solo actualizar si cambió
                if ($foro->post_status !== $nuevo_estado) {
                    $resultado = wp_update_post([
                        'ID' => $foro_id,
                        'post_status' => $nuevo_estado
                    ]);
                    
                    if (!is_wp_error($resultado)) {
                        // Actualizar también el meta de bbPress
                        update_post_meta($foro_id, '_bbp_status', $nuevo_estado);
                        error_log("✅ MXWM FORO: {$foro_id} actualizado a {$nuevo_estado} ({$estado_foro})");
                    } else {
                        error_log("❌ MXWM FORO: " . $resultado->get_error_message());
                    }
                } else {
                    error_log("ℹ️ MXWM FORO: {$foro_id} ya está en estado {$nuevo_estado}");
                }
            }
        }
    }
    
    // Guardar estado actual para futuras validaciones
    if ($estado_grupo) {
        update_post_meta($post_id, '_mxwm_estado_grupo_anterior', $estado_grupo);
    }
    
    error_log("🏁 MXWM SINCRONIZACIÓN: Completada para proyecto {$post_id}");
}

/**
 * SINCRONIZACIÓN CUANDO SE APRUEBA EL PROYECTO
 */
add_action('pending_to_publish', 'mxwm_sincronizar_al_aprobar_proyecto', 10, 1);
function mxwm_sincronizar_al_aprobar_proyecto($post) {
    if ($post->post_type !== 'proyecto') return;
    
    error_log("🎉 MXWM APROBACIÓN: Sincronizando proyecto {$post->ID}");
    mxwm_sincronizacion_unificada($post->ID);
}

/**
 * INICIALIZAR ESTADO ANTERIAL AL CARGAR PROYECTO
 */
add_action('acf/load_value', 'mxwm_inicializar_estado_anterior', 10, 3);
function mxwm_inicializar_estado_anterior($value, $post_id, $field) {
    if ($field['name'] === 'estado_grupo' && !get_post_meta($post_id, '_mxwm_estado_grupo_anterior', true)) {
        update_post_meta($post_id, '_mxwm_estado_grupo_anterior', $value ?: 'publico');
    }
    return $value;
}

// ============================================================================
// REGISTRO DE SHORTCODES - ¡CRÍTICO! AÑADIR ESTO
// ============================================================================

function mxwm_registrar_shortcodes() {
    add_shortcode('mxwm_botones_gestion', 'mxwm_botones_gestion_proyecto_shortcode');
    add_shortcode('mxwm_estadisticas', 'mxwm_estadisticas_usuario_shortcode');
}
add_action('init', 'mxwm_registrar_shortcodes');

// ============================================================================
// SHORTCODE PARA BOTONES DE GESTIÓN DE PROYECTOS - VERSIÓN CORREGIDA
// ============================================================================

function mxwm_botones_gestion_proyecto_shortcode($atts) {
    $atts = shortcode_atts(array(
        'proyecto_id' => get_the_ID(),
    ), $atts);
    
    $proyecto_id = $atts['proyecto_id'];
    
    // VERIFICACIÓN CORREGIDA - Solo mostrar si el usuario es el autor o admin
    $proyecto = get_post($proyecto_id);
    $current_user_id = get_current_user_id();
    
    if (!$proyecto) {
        return '<p>Proyecto no encontrado.</p>';
    }
    
    // Verificar permisos: autor del proyecto O administrador
    $es_autor = ($proyecto->post_author == $current_user_id);
    $es_admin = current_user_can('manage_options');
    
    if (!$es_autor && !$es_admin) {
        return '<p>No tienes permisos para gestionar este proyecto.</p>';
    }
    
    $estado_actual = get_post_status($proyecto_id);
    $output = '<div class="mxwm-botones-gestion" style="background: #fff; padding: 20px; border-radius: 10px; border: 1px solid #ddd; margin: 20px 0;">';
    $output .= '<h4 style="margin-top: 0; color: #333; border-bottom: 2px solid #007cba; padding-bottom: 10px;">Gestionar Proyecto</h4>';
    $output .= '<div style="display: flex; gap: 10px; flex-wrap: wrap;">';
    
    // Botón Editar (siempre visible para autor/admin)
    $output .= '<a href="' . home_url('/editar-proyecto-frontend/?proyecto_id=' . $proyecto_id) . '" class="button" style="background: #007cba; border: 1px solid #007cba; color: #fff; padding: 10px 15px; border-radius: 5px; cursor: pointer; font-weight: 500; text-decoration: none;">✏️ Editar Proyecto</a>';
    
    // Botón Pausar (solo si está publicado)
    if ($estado_actual === 'publish') {
        $output .= '<form method="post" style="margin: 0;">';
        $output .= wp_nonce_field('mxwm_pausar_proyecto_' . $proyecto_id, 'mxwm_nonce', true, false);
        $output .= '<input type="hidden" name="accion" value="pausar_proyecto">';
        $output .= '<input type="hidden" name="proyecto_id" value="' . $proyecto_id . '">';
        $output .= '<button type="submit" class="button" style="background: #ffb900; border: 1px solid #ffb900; color: #000; padding: 10px 15px; border-radius: 5px; cursor: pointer; font-weight: 500;">⏸️ Pausar Proyecto</button>';
        $output .= '</form>';
    }
    
    // Botón Activar (solo si está pausado)
    if ($estado_actual === 'draft') {
        $output .= '<form method="post" style="margin: 0;">';
        $output .= wp_nonce_field('mxwm_activar_proyecto_' . $proyecto_id, 'mxwm_nonce', true, false);
        $output .= '<input type="hidden" name="accion" value="activar_proyecto">';
        $output .= '<input type="hidden" name="proyecto_id" value="' . $proyecto_id . '">';
        $output .= '<button type="submit" class="button" style="background: #00a32a; border: 1px solid #00a32a; color: #fff; padding: 10px 15px; border-radius: 5px; cursor: pointer; font-weight: 500;">▶️ Activar Proyecto</button>';
        $output .= '</form>';
    }
    
    // Botón Eliminar (siempre visible)
    $output .= '<form method="post" style="margin: 0;" onsubmit="return confirm(\'¿Estás seguro de que quieres eliminar este proyecto? Esta acción no se puede deshacer.\');">';
    $output .= wp_nonce_field('mxwm_eliminar_proyecto_' . $proyecto_id, 'mxwm_nonce', true, false);
    $output .= '<input type="hidden" name="accion" value="eliminar_proyecto">';
    $output .= '<input type="hidden" name="proyecto_id" value="' . $proyecto_id . '">';
    $output .= '<button type="submit" class="button" style="background: #d63638; border: 1px solid #d63638; color: #fff; padding: 10px 15px; border-radius: 5px; cursor: pointer; font-weight: 500;">🗑️ Eliminar Proyecto</button>';
    $output .= '</form>';
    
    $output .= '</div>';
    
    // Estado actual con mejor información
    $estados = array(
        'publish' => '🟢 Activo (Público)',
        'draft' => '🟡 Pausado (Solo tú puedes verlo)', 
        'pending' => '🟠 Pendiente de revisión',
        'private' => '🔒 Privado'
    );

    $estado_texto = $estados[$estado_actual] ?? $estado_actual;

    // Mensaje adicional para proyectos pausados
    if ($estado_actual === 'draft') {
        $estado_texto .= '<br><small style="color: #666;">Los proyectos pausados no son visibles para otros usuarios.</small>';
    }

    $output .= '<p style="margin: 15px 0 0 0; font-size: 0.9em; color: #666;">Estado actual: ' . $estado_texto . '</p>';
    $output .= '</div>';
    
    return $output;
}

// ============================================================================
// SHORTCODE PARA ESTADÍSTICAS DE USUARIO - VERSIÓN CORREGIDA
// ============================================================================

function mxwm_estadisticas_usuario_shortcode($atts) {
    if (!is_user_logged_in()) {
        return '<p>Inicia sesión para ver tus estadísticas.</p>';
    }
    
    $user_id = get_current_user_id();
    $user_level = mxwm_get_user_pmp_level($user_id);
    $config = mxwm_get_pmp_levels_config();
    
    // Contar proyectos del usuario
    $proyectos_count = count(get_posts(array(
        'post_type' => 'proyecto',
        'author' => $user_id,
        'post_status' => array('publish', 'pending', 'draft'),
        'numberposts' => -1
    )));
    
    $max_projects = $config[$user_level]['max_projects'] ?? 0;
    
    $output = '<div class="mxwm-estadisticas" style="background: #f8f9fa; padding: 20px; border-radius: 10px; border: 1px solid #dee2e6; margin: 20px 0;">';
    $output .= '<h4 style="margin-top: 0; color: #333; border-bottom: 2px solid #007cba; padding-bottom: 10px;">Tus Estadísticas</h4>';
    
    $output .= '<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">';
    
    // Nivel actual
    $output .= '<div style="text-align: center; padding: 15px; background: #fff; border-radius: 8px; border: 1px solid #e9ecef;">';
    $output .= '<div style="font-size: 0.9em; color: #666; margin-bottom: 5px;">Nivel Actual</div>';
    $output .= '<div style="font-size: 1.2em; font-weight: bold; color: #007cba;">' . ($config[$user_level]['name'] ?? 'N/A') . '</div>';
    $output .= '</div>';
    
    // Proyectos
    $output .= '<div style="text-align: center; padding: 15px; background: #fff; border-radius: 8px; border: 1px solid #e9ecef;">';
    $output .= '<div style="font-size: 0.9em; color: #666; margin-bottom: 5px;">Proyectos</div>';
    $output .= '<div style="font-size: 1.2em; font-weight: bold; color: ' . ($proyectos_count >= $max_projects ? '#d63638' : '#00a32a') . ';">';
    $output .= $proyectos_count . ' / ' . ($max_projects == -1 ? '∞' : $max_projects) . '</div>';
    $output .= '</div>';
    
    // Características
    $output .= '<div style="text-align: center; padding: 15px; background: #fff; border-radius: 8px; border: 1px solid #e9ecef;">';
    $output .= '<div style="font-size: 0.9em; color: #666; margin-bottom: 5px;">Características</div>';
    $output .= '<div style="font-size: 1em; font-weight: bold; color: #6c757d;">';
    
    $features = [];
    if ($config[$user_level]['gallery']) $features[] = 'Galería';
    if ($config[$user_level]['groups']) $features[] = 'Grupos';
    if ($config[$user_level]['forums']) $features[] = 'Foros';
    if ($config[$user_level]['video']) $features[] = 'Video';
    
    $output .= implode(', ', $features) ?: 'Básico';
    $output .= '</div>';
    $output .= '</div>';
    
    $output .= '</div>';
    
    // Mensaje si alcanzó el límite - ENLACE CORREGIDO
    if ($max_projects != -1 && $proyectos_count >= $max_projects) {
        $output .= '<div style="margin-top: 15px; padding: 10px; background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 5px; color: #856404;">';
        $output .= '⚠️ Has alcanzado el límite de proyectos para tu nivel. <a href="' . home_url('/membership-account/') . '" style="color: #007cba; font-weight: bold;">Mejorar nivel</a>';
        $output .= '</div>';
    }
    
    $output .= '</div>';
    
    return $output;
}

// ============================================================================
// MENÚS BUDDYPRESS MÍNIMOS
// ============================================================================

function mxwm_setup_buddypress_menus() {
    if (!function_exists('bp_is_active') || !is_user_logged_in()) {
        return;
    }
    
    $current_user_id = get_current_user_id();
    $user_pmp_level = mxwm_get_user_pmp_level($current_user_id);
    
    // Si no tiene nivel PMP, asignar nivel 1 (básico) automáticamente
    if (!$user_pmp_level) {
        update_user_meta($current_user_id, 'pmp_level', '1');
        $user_pmp_level = '1';
    }
    
    // Menú Mis Proyectos
    bp_core_new_nav_item(array(
        'name' => 'Mis Proyectos',
        'slug' => 'mis-proyectos',
        'screen_function' => 'mxwm_mis_proyectos_redirect',
        'position' => 30
    ));
    
    // Menú Crear Proyecto
    bp_core_new_nav_item(array(
        'name' => 'Crear Proyecto',
        'slug' => 'crear-proyecto',
        'screen_function' => 'mxwm_crear_proyecto_redirect',
        'position' => 31
    ));
}

function mxwm_crear_proyecto_redirect() {
    wp_redirect(home_url('/crear-proyecto-frontend/'));
    exit;
}

function mxwm_mis_proyectos_redirect() {
    wp_redirect(home_url('/mis-proyectos/'));
    exit;
}

add_action('bp_setup_nav', 'mxwm_setup_buddypress_menus', 100);

// ============================================================================
// CONTENIDO DE MIS PROYECTOS - BUDDYPRESS FRONTEND - VERSIÓN CORREGIDA
// ============================================================================

function mxwm_mis_proyectos_content() {
    $current_user_id = get_current_user_id();
    
    $proyectos = get_posts(array(
        'post_type' => 'proyecto',
        'author' => $current_user_id,
        'numberposts' => 10,
        'post_status' => array('publish', 'draft', 'pending')
    ));
    
    echo '<div style="background: #fff; padding: 2rem; border-radius: 10px; max-width: 800px; margin: 0 auto;">';
    echo '<h2>Mis Proyectos</h2>';
    
    if ($proyectos) {
        echo '<ul style="list-style: none; padding: 0;">';
        foreach ($proyectos as $proyecto) {
            $estado = ($proyecto->post_status == 'publish') ? '✅ Publicado' : '⏳ Pendiente';
            
            echo '<li style="border: 1px solid #e0e0e0; padding: 1rem; margin-bottom: 1rem; border-radius: 5px;">';
            
            // Mostrar título según el estado del proyecto - CORREGIDO
            if ($proyecto->post_status == 'publish') {
                echo '<h4 style="margin: 0 0 10px 0;"><a href="' . get_permalink($proyecto->ID) . '">' . $proyecto->post_title . '</a></h4>';
            } else {
                echo '<h4 style="margin: 0 0 10px 0;">' . $proyecto->post_title . '</h4>';
                echo '<p style="margin: 5px 0; color: #ff6b00; font-size: 0.9em;">👁️ Este proyecto no es visible públicamente</p>';
            }
            
            echo '<p style="margin: 5px 0; color: #666;">Estado: ' . $estado . '</p>';
            echo '<p style="margin: 5px 0;">' . wp_trim_words($proyecto->post_content, 20) . '</p>';
            echo '<div style="margin-top: 10px;">';
            
            // Solo mostrar "Ver" si el proyecto está publicado - CORREGIDO
            if ($proyecto->post_status == 'publish') {
                echo '<a href="' . get_permalink($proyecto->ID) . '" style="margin-right: 15px; color: #007cba;">👁️ Ver</a>';
            } else {
                echo '<span style="margin-right: 15px; color: #ccc; cursor: not-allowed;">👁️ Ver (No disponible)</span>';
            }
            
            echo '<a href="' . home_url('/editar-proyecto-frontend/?proyecto_id=' . $proyecto->ID) . '" style="color: #007cba;">✏️ Editar</a>';
            echo '</div>';
            echo '</li>';
        }
        echo '</ul>';
    } else {
        echo '<p>No tienes proyectos aún. <a href="' . home_url('/crear-proyecto-frontend/') . '">Crear tu primer proyecto</a></p>';
    }
    
    echo '</div>';
}

// ============================================================================
// SHORTCODE BÁSICO PARA GALERÍA (SOLO SI ACF ESTÁ ACTIVO)
// ============================================================================

if (function_exists('get_field')) {
    function mxwm_proyecto_gallery_shortcode($atts) {
        $atts = shortcode_atts(array(
            'proyecto_id' => get_the_ID(),
            'columns' => 3
        ), $atts);
        
        $galeria = get_field('galeria_proyecto', $atts['proyecto_id']);
        
        if (!$galeria || !is_array($galeria)) {
            return '<p>No hay imágenes en la galería.</p>';
        }
        
        $output = '<div style="display: grid; grid-template-columns: repeat(' . $atts['columns'] . ', 1fr); gap: 20px;">';
        
        foreach ($galeria as $imagen) {
            $img_id = is_array($imagen) ? $imagen['ID'] : $imagen;
            $img_url = wp_get_attachment_image_src($img_id, 'medium');
            
            if ($img_url) {
                $output .= '<div style="border-radius: 10px; overflow: hidden;">';
                $output .= '<img src="' . $img_url[0] . '" style="width: 100%; height: 200px; object-fit: cover;">';
                $output .= '</div>';
            }
        }
        
        $output .= '</div>';
        return $output;
    }
    add_shortcode('mxwm_galeria', 'mxwm_proyecto_gallery_shortcode');
}

// ============================================================================
// PÁGINA DE ESTADO SIMPLE
// ============================================================================

function mxwm_add_simple_status_menu() {
    add_management_page(
        'Estado PMP',
        'Estado PMP',
        'manage_options',
        'mxwm-status',
        'mxwm_simple_status_page'
    );
}
add_action('admin_menu', 'mxwm_add_simple_status_menu');

function mxwm_simple_status_page() {
    echo '<div class="wrap">';
    echo '<h1>Estado del Sistema PMP</h1>';
    echo '<p><strong>Código:</strong> MXWM-PMP-FRONTEND-FIX-DIC2024-FINAL-CORREGIDO</p>';
    echo '<p>Post Type Proyecto: ' . (post_type_exists('proyecto') ? '✅ OK' : '❌ Error') . '</p>';
    echo '<p>BuddyPress: ' . (function_exists('bp_is_active') ? '✅ Activo' : '❌ Inactivo') . '</p>';
    echo '<p>ACF: ' . (function_exists('get_field') ? '✅ Activo' : '❌ Inactivo') . '</p>';
    echo '<p>bbPress: ' . (function_exists('bbp_insert_forum') ? '✅ Activo' : '❌ Inactivo') . '</p>';
    echo '<p>PMPro: ' . (function_exists('pmpro_getMembershipLevelForUser') ? '✅ Activo' : '❌ Inactivo') . '</p>';
    echo '<p><a href="' . admin_url('options-permalink.php') . '" class="button">Actualizar Permalinks</a></p>';
    echo '</div>';
}

// ============================================================================
// SOLUCIÓN ACF CAMPOS CONDICIONALES - MXWM
// ============================================================================

function evaluar_campo_condicional_acf($field_name, $post_id = null) {
    if (!function_exists('get_field_object')) {
        return false;
    }
    
    $post_id = $post_id ?: get_the_ID();
    if (!$post_id) {
        return false;
    }
    
    $field_object = get_field_object($field_name, $post_id, false);
    if (!$field_object) {
        return false;
    }
    
    if (!isset($field_object['conditional_logic']) || !$field_object['conditional_logic']) {
        return get_field($field_name, $post_id);
    }
    
    $conditional_logic = $field_object['conditional_logic'];
    foreach ($conditional_logic as $rule_group) {
        $group_result = true;
        
        foreach ($rule_group as $rule) {
            $condition_value = get_field($rule['field'], $post_id);
            $target_value = $rule['value'];
            $operator = $rule['operator'];
            
            $condition_met = false;
            switch ($operator) {
                case '==':
                    $condition_met = ($condition_value == $target_value);
                    break;
                case '!=':
                    $condition_met = ($condition_value != $target_value);
                    break;
                default:
                    $condition_met = false;
            }
            
            if (!$condition_met) {
                $group_result = false;
                break;
            }
        }
        
        if ($group_result) {
            return get_field($field_name, $post_id);
        }
    }
    
    return false;
}

// ============================================================================
// REGISTRO DE VARIABLES DE QUERY
// ============================================================================

function mxwm_register_query_vars($vars) {
    $vars[] = 'proyecto';
    return $vars;
}
add_filter('query_vars', 'mxwm_register_query_vars');

// ============================================================================
// ASIGNACIÓN AUTOMÁTICA NIVEL BÁSICO
// ============================================================================

/**
 * Asignar Nivel 1 (Básico) automáticamente a usuarios nuevos
 * Evita usuarios "huérfanos" sin membresía
 * Basado en documentación oficial PMPro
 */
function mxwm_asignar_nivel_basico_automatico($user_id) {
    // Verificar que PMPro esté activo
    if (!function_exists('pmpro_hasMembershipLevel')) {
        return;
    }
    
    // ID del nivel básico (verificar en Memberships → Membership Levels)
    $default_level_id = 1;
    
    // Solo asignar si el usuario NO tiene ya un nivel
    // Esto previene sobrescribir niveles de pago
    if (!pmpro_hasMembershipLevel(NULL, $user_id)) {
        pmpro_changeMembershipLevel($default_level_id, $user_id);
    }
}
add_action('user_register', 'mxwm_asignar_nivel_basico_automatico', 10, 1);

// ============================================================================
// CREACIÓN AUTOMÁTICA DE GRUPOS BUDDYPRESS (NIVEL 3+) - VERSIÓN MEJORADA
// ============================================================================

function mxwm_crear_grupo_automatico($proyecto_id, $post, $update) {
    // Solo ejecutar en proyectos
    if ($post->post_type !== 'proyecto') {
        return;
    }

    // CRÍTICO: Solo crear grupo si el proyecto está PUBLICADO
    // No crear en "pending" o "draft"
    if ($post->post_status !== 'publish') {
        return;
    }
    
    // Verificar dependencias CRÍTICAS
    if (!mxwm_verificar_dependencias_foros()) {
        error_log('MXWM: Dependencias faltantes para crear grupo');
        return;
    }
    
    // Solo crear grupo si checkbox está activado
    $activar_grupo = get_field('activar_grupo', $proyecto_id);
    if (!$activar_grupo) {
        return;
    }
    
    // Verificar que el usuario tenga nivel 3+
    $user_id = $post->post_author;
    $user_pmp_level = mxwm_get_user_pmp_level($user_id);
    
    if (!$user_pmp_level || $user_pmp_level < 3) {
        return; // No tiene permisos para crear grupos
    }
    
    // Verificar si ya existe un grupo asociado
    $grupo_id_existente = get_post_meta($proyecto_id, '_mxwm_grupo_id', true);
    if ($grupo_id_existente && groups_get_group($grupo_id_existente)) {
        return; // Ya tiene grupo, no crear otro
    }
    
    // CREAR GRUPO - IMPORTANTE: enable_forum = 0 por defecto
    $titulo_proyecto = $post->post_title;
    $descripcion_proyecto = get_field('proposito_en_breve', $proyecto_id) ?: wp_trim_words($post->post_content, 20);
    
    $args_grupo = array(
        'creator_id' => $user_id,
        'name' => $titulo_proyecto,
        'description' => $descripcion_proyecto,
        'slug' => sanitize_title($titulo_proyecto . '-' . $proyecto_id),
        'status' => 'public',
        'enable_forum' => 0, // CRÍTICO: Desactivado por defecto
        'date_created' => bp_core_current_time()
    );
    
    $grupo_id = groups_create_group($args_grupo);
    
    if ($grupo_id) {
        // Asociar grupo con proyecto
        update_post_meta($proyecto_id, '_mxwm_grupo_id', $grupo_id);
        
        // Asociar proyecto con grupo (meta en BuddyPress)
        groups_update_groupmeta($grupo_id, 'proyecto_asociado', $proyecto_id);
        
        // Asegurar que el creador sea admin del grupo
        groups_join_group($grupo_id, $user_id);
        
        // SOLO PARA NIVEL 4+: Crear foro automáticamente si checkbox activado
        if ($user_pmp_level >= 4) {
            $activar_foro = get_field('activar_foro', $proyecto_id);
            if ($activar_foro) {
                // Usar hook diferido para evitar conflictos
                wp_schedule_single_event(time() + 5, 'mxwm_crear_foro_diferido', array($grupo_id));
                error_log("MXWM: Foro programado para grupo {$grupo_id}");
            } else {
                // NIVEL 4 PERO SIN FORO: Asegurar que el foro esté desactivado
                global $wpdb;
                $wpdb->update(
                    $wpdb->prefix . 'bp_groups',
                    array('enable_forum' => 0),
                    array('id' => $grupo_id),
                    array('%d'),
                    array('%d')
                );
                error_log("MXWM: Foro desactivado para grupo {$grupo_id} (nivel 4 sin checkbox)");
            }
        } else {
            // NIVEL 3: Asegurar que el foro esté desactivado
            global $wpdb;
            $wpdb->update(
                $wpdb->prefix . 'bp_groups',
                array('enable_forum' => 0),
                array('id' => $grupo_id),
                array('%d'),
                array('%d')
            );
            error_log("MXWM: Foro desactivado para grupo {$grupo_id} (nivel 3)");
        }
        
        error_log("MXWM: Grupo {$grupo_id} creado para proyecto {$proyecto_id}, usuario nivel {$user_pmp_level}");

        // Configurar imagen del grupo (usar imagen_principal del proyecto)
        $imagen_principal = get_field('imagen_principal', $proyecto_id);
        if ($imagen_principal) {
            $attachment_id = is_array($imagen_principal) ? $imagen_principal['ID'] : $imagen_principal;
            if ($attachment_id) {
                // Obtener ruta del archivo
                $file_path = get_attached_file($attachment_id);
                
                if ($file_path && file_exists($file_path)) {
                    // Copiar archivo a directorio temporal
                    $upload_dir = wp_upload_dir();
                    $temp_file = $upload_dir['basedir'] . '/buddypress/temp/' . basename($file_path);
                    
                    // Crear directorio si no existe
                    wp_mkdir_p(dirname($temp_file));
                    
                    // Copiar archivo
                    if (copy($file_path, $temp_file)) {
                        // Simular $_FILES para bp_core_avatar_handle_upload
                        $_FILES['file'] = array(
                            'name' => basename($file_path),
                            'type' => mime_content_type($file_path),
                            'tmp_name' => $temp_file,
                            'error' => 0,
                            'size' => filesize($file_path)
                        );
                        
                        // Subir avatar al grupo
                        bp_core_avatar_handle_upload(
                            $_FILES,
                            'groups_avatar_upload_dir'
                        );
                        
                        // Limpiar archivo temporal
                        @unlink($temp_file);
                    }
                }
            }
        }
    } else {
        error_log("MXWM ERROR: No se pudo crear grupo para proyecto {$proyecto_id}");
    }
}
add_action('save_post', 'mxwm_crear_grupo_automatico', 20, 3);

// ============================================================================
// SISTEMA DE FOROS - VERSIÓN COMPLETAMENTE CORREGIDA
// ============================================================================

/**
 * CREAR FORO DE FORA DIFERIDA - EVITA CONFLICTOS DE TIMING
 */
add_action('mxwm_crear_foro_diferido', 'mxwm_crear_foro_para_grupo_nuevo');
function mxwm_crear_foro_para_grupo_nuevo($group_id) {
    if (!mxwm_verificar_dependencias_foros()) {
        error_log('MXWM ERROR: Dependencias faltantes para crear foro diferido');
        return;
    }
    
    $group = groups_get_group($group_id);
    if (!$group) {
        error_log("MXWM ERROR: No se pudo obtener grupo {$group_id} para foro diferido");
        return;
    }
    
    // Verificar si ya tiene foro
    $foro_existente = groups_get_groupmeta($group_id, 'forum_id', true);
    if (!empty($foro_existente)) {
        error_log("MXWM: Grupo {$group_id} ya tiene foro, omitiendo creación");
        return;
    }
    
    // Verificar nivel del creador
    $creator_level = mxwm_get_user_pmp_level($group->creator_id);
    if (!$creator_level || $creator_level < 4) {
        error_log("MXWM: Usuario nivel {$creator_level} no puede crear foros");
        return;
    }
    
    // Verificar proyecto asociado y checkbox
    $proyecto_id = groups_get_groupmeta($group_id, 'proyecto_asociado', true);
    if (!$proyecto_id) {
        error_log("MXWM ERROR: Grupo {$group_id} no tiene proyecto asociado");
        return;
    }
    
    $activar_foro = get_field('activar_foro', $proyecto_id);
    if (!$activar_foro) {
        error_log("MXWM: Proyecto {$proyecto_id} no tiene activar_foro marcado");
        return;
    }
    
    // CREAR FORO
    $foro_id = mxwm_crear_foro_bbpress($group_id, $group);
    
    if ($foro_id && !is_wp_error($foro_id)) {
        // Guardar forum_id como ENTERO (formato correcto)
        groups_update_groupmeta($group_id, 'forum_id', $foro_id);
        
        // Activar foro en el grupo
        global $wpdb;
        $wpdb->update(
            $wpdb->prefix . 'bp_groups',
            array('enable_forum' => 1),
            array('id' => $group_id),
            array('%d'),
            array('%d')
        );
        
        // Guardar referencia en proyecto
        update_post_meta($proyecto_id, '_mxwm_foro_id', $foro_id);
        
        error_log("MXWM SUCCESS: Foro {$foro_id} creado para grupo {$group_id}");
    } else {
        error_log("MXWM ERROR: No se pudo crear foro para grupo {$group_id}");
        if (is_wp_error($foro_id)) {
            error_log("MXWM ERROR DETAIL: " . $foro_id->get_error_message());
        }
    }
}

/**
 * FUNCIÓN AUXILIAR CORREGIDA: Crear foro bbPress
 */
function mxwm_crear_foro_bbpress($group_id, $group) {
    if (!function_exists('bbp_insert_forum')) {
        error_log('MXWM ERROR: bbp_insert_forum no disponible');
        return false;
    }
    
    // Mapeo de privacidad
    $status_map = array(
        'public'  => 'publish',
        'private' => 'private',
        'hidden'  => 'private'
    );
    
    $forum_status = isset($status_map[$group->status]) ? $status_map[$group->status] : 'publish';
    
    // Crear foro
    $forum_args = array(
        'post_title'   => $group->name,
        'post_content' => $group->description,
        'post_status'  => $forum_status,
        'post_author'  => $group->creator_id
    );
    
    $forum_id = bbp_insert_forum($forum_args);
    
    if ($forum_id && !is_wp_error($forum_id)) {
        // Establecer relación grupo-foro
        update_post_meta($forum_id, '_bbp_group_ids', array($group_id));
        groups_update_groupmeta($group_id, '_bbp_forum_id', $forum_id);
        
        return $forum_id;
    }
    
    return false;
}

/**
 * SINCRONIZACIÓN PRIVACIDAD GRUPO↔FORO - CORREGIDA
 */
add_action('groups_group_settings_edited', 'mxwm_sincronizar_privacidad_grupo_foro', 20, 1);
function mxwm_sincronizar_privacidad_grupo_foro($group_id) {
    if (!mxwm_verificar_dependencias_foros()) return;
    
    $group = groups_get_group($group_id);
    if (!$group) return;
    
    // Obtener forum_id
    $forum_id = groups_get_groupmeta($group_id, 'forum_id', true);
    if (!$forum_id) return;
    
    // Mapeo privacidad
    $status_map = array(
        'public'  => 'publish',
        'private' => 'private',
        'hidden'  => 'private'
    );
    
    $nuevo_status = isset($status_map[$group->status]) ? $status_map[$group->status] : 'publish';
    
    // Actualizar foro
    wp_update_post(array(
        'ID' => $forum_id,
        'post_status' => $nuevo_status
    ));
    
    error_log("MXWM: Privacidad sincronizada - Grupo {$group_id} → Foro {$forum_id} ({$nuevo_status})");
}

// ============================================================================
// CONTROL DE INTERFAZ - OCULTAR CHECKBOX FORO PARA NIVEL 3
// ============================================================================

function mxwm_ocultar_checkbox_foro_nivel_3() {
    if (!is_page('crear-proyecto-frontend') && !is_singular('proyecto')) return;
    
    $user_level = mxwm_get_user_pmp_level(get_current_user_id());
    
    if (!$user_level || intval($user_level) < 4) {
        ?>
        <style>
            .acf-field[data-name="activar_foro"],
            .acf-field-checkbox[data-name="activar_foro"] {
                display: none !important;
            }
        </style>
        <?php
    }
}
add_action('wp_head', 'mxwm_ocultar_checkbox_foro_nivel_3');
add_action('admin_head', 'mxwm_ocultar_checkbox_foro_nivel_3');

/**
 * VALIDACIÓN SERVER-SIDE PARA PREVENIR ACTIVACIÓN MANUAL DE FOROS
 */
function mxwm_validar_foro_por_nivel($post_id) {
    if (get_post_type($post_id) !== 'proyecto') return;
    
    $author_level = mxwm_get_user_pmp_level(get_post_field('post_author', $post_id));
    
    // Si es nivel 3 o menor, forzar desactivación de foro
    if (!$author_level || intval($author_level) < 4) {
        update_field('activar_foro', false, $post_id);
        
        // También desactivar foro en grupo si existe
        $grupo_id = get_post_meta($post_id, '_mxwm_grupo_id', true);
        if ($grupo_id) {
            groups_update_groupmeta($grupo_id, 'forum_id', '');
            
            // Desactivar foro en tabla principal
            global $wpdb;
            $wpdb->update(
                $wpdb->prefix . 'bp_groups',
                array('enable_forum' => 0),
                array('id' => $grupo_id),
                array('%d'),
                array('%d')
            );
        }
    }
}
add_action('acf/save_post', 'mxwm_validar_foro_por_nivel', 20);

// ============================================================================
// ACTIVACIÓN RETROACTIVA PARA UPGRADES
// ============================================================================

/**
 * Cuando usuario sube a nivel 4, permitir activar foros en proyectos existentes
 */
add_action('pmpro_after_change_membership_level', 'mxwm_manejar_upgrade_nivel', 10, 2);
function mxwm_manejar_upgrade_nivel($level_id, $user_id) {
    $nuevo_nivel = strval($level_id);
    
    // Solo nos interesa upgrade a nivel 4+
    if ($nuevo_nivel < '4') return;
    
    // Buscar proyectos del usuario que tengan grupos pero no foros
    $proyectos_usuario = get_posts(array(
        'post_type' => 'proyecto',
        'author' => $user_id,
        'post_status' => 'publish',
        'numberposts' => -1,
        'meta_query' => array(
            array(
                'key' => '_mxwm_grupo_id',
                'compare' => 'EXISTS'
            )
        )
    ));
    
    foreach ($proyectos_usuario as $proyecto) {
        $grupo_id = get_post_meta($proyecto->ID, '_mxwm_grupo_id', true);
        $foro_id = groups_get_groupmeta($grupo_id, 'forum_id', true);
        
        // Si tiene grupo pero no foro, ofrecer activación
        if ($grupo_id && empty($foro_id)) {
            // Podemos marcar un flag o enviar notificación
            update_post_meta($proyecto->ID, '_mxwm_puede_activar_foro', true);
        }
    }
    
    error_log("MXWM: Usuario {$user_id} upgrade a nivel {$nuevo_nivel} - puede activar foros en proyectos existentes");
}

// ============================================================================
// NOTIFICACIONES EMAIL - APROBACIÓN/RECHAZO DE PROYECTOS
// ============================================================================

/**
 * Enviar email cuando proyecto cambia de estado
 */
function mxwm_notificar_cambio_estado_proyecto($new_status, $old_status, $post) {
    // Solo para proyectos
    if ($post->post_type !== 'proyecto') {
        return;
    }
    
    // Solo enviar email si cambió de pending a publish o trash
    if ($old_status === 'pending' && ($new_status === 'publish' || $new_status === 'trash')) {
        
        $author_id = $post->post_author;
        $author_email = get_the_author_meta('user_email', $author_id);
        $author_name = get_the_author_meta('display_name', $author_id);
        $titulo_proyecto = $post->post_title;
        $user_pmp_level = mxwm_get_user_pmp_level($author_id);
        
        // PROYECTO APROBADO
        if ($new_status === 'publish') {
            $subject = ' Tu proyecto "' . $titulo_proyecto . '" ha sido aprobado';
            
            $message = "Hola " . $author_name . ",\n\n";
            $message .= "¡Excelentes noticias! Tu proyecto ha sido aprobado y ya está visible públicamente en MXWM.\n\n";
            $message .= " Ver tu proyecto:\n";
            $message .= get_permalink($post->ID) . "\n\n";
            
            // Enlaces adicionales según nivel
            if ($user_pmp_level >= 3) {
                $grupo_id = get_post_meta($post->ID, '_mxwm_grupo_id', true);
                if ($grupo_id && function_exists('groups_get_group')) {
                    $grupo = groups_get_group($grupo_id);
                    $grupo_url = bp_get_group_permalink($grupo) . 'admin/edit-details/';
                    $message .= " Configura tu Grupo Privado:\n";
                    $message .= $grupo_url . "\n\n";
                }
            }
            
            // SECCIÓN FORO (Nivel 4+)
            if ($user_pmp_level >= 4) {
                $activar_foro = get_field('activar_foro', $post->ID);
                if ($activar_foro && $grupo_id) {
                    $foro_id = groups_get_groupmeta($grupo_id, 'forum_id', true);
                    
                    if ($foro_id && function_exists('bp_get_group_permalink')) {
                        $grupo = groups_get_group($grupo_id);
                        $foro_url = bp_get_group_permalink($grupo) . 'forum/';
                        
                        $message .= " TU FORO DE GRUPO:\n";
                        $message .= "Tu grupo tiene un foro ";
                        $message .= ($grupo->status === 'private') ? "privado" : "público";
                        $message .= " listo para discusiones.\n";
                        $message .= "→ Accede aquí: " . $foro_url . "\n\n";
                    }
                }
            }
            
            $message .= "¡Gracias por ser parte de MXWM! \n\n";
            $message .= "Equipo MXWM";
            
        } 
        // PROYECTO RECHAZADO
        else if ($new_status === 'trash') {
            $subject = ' Tu proyecto "' . $titulo_proyecto . '" necesita ajustes';
            
            $message = "Hola " . $author_name . ",\n\n";
            $message .= "Tu proyecto no cumple con nuestras políticas actuales y ha sido rechazado.\n\n";
            $message .= " Razones comunes de rechazo:\n";
            $message .= "• Contenido inapropiado o spam\n";
            $message .= "• Información incompleta o poco clara\n";
            $message .= "• No alineado con el propósito de MXWM\n\n";
            $message .= " Puedes editar y reenviar tu proyecto:\n";
            $message .= home_url('/mis-proyectos/') . "\n\n";
            $message .= " Revisa nuestras políticas:\n";
            $message .= home_url('/terminos-y-condiciones/') . "\n\n";
            $message .= "Si tienes dudas, responde a este email.\n\n";
            $message .= "Equipo MXWM";
        }
        
        // Enviar email
        $headers = array(
            'Content-Type: text/plain; charset=UTF-8',
            'From: MXWM <noreply@beta.mxwithme.com>'
        );
        
        wp_mail($author_email, $subject, $message, $headers);
        
        error_log("MXWM: Email enviado a {$author_email} - Proyecto {$post->ID} cambió de {$old_status} a {$new_status}");
    }
}
add_action('transition_post_status', 'mxwm_notificar_cambio_estado_proyecto', 10, 3);

// ============================================================================
// LIMPIEZA DE GRUPOS/FOROS AL ELIMINAR PROYECTO
// ============================================================================

/**
 * Limpiar grupos y foros cuando se elimina un proyecto
 */
function mxwm_limpiar_recursos_al_eliminar_proyecto($post_id) {
    if (get_post_type($post_id) !== 'proyecto') return;
    
    $grupo_id = get_post_meta($post_id, '_mxwm_grupo_id', true);
    
    if ($grupo_id && function_exists('groups_get_group')) {
        // Obtener foro asociado
        $foro_id = groups_get_groupmeta($grupo_id, 'forum_id', true);
        
        // Eliminar foro si existe
        if ($foro_id) {
            wp_delete_post($foro_id, true);
            error_log("MXWM: Foro {$foro_id} eliminado con proyecto {$post_id}");
        }
        
        // Eliminar grupo
        groups_delete_group($grupo_id);
        error_log("MXWM: Grupo {$grupo_id} eliminado con proyecto {$post_id}");
    }
}
add_action('before_delete_post', 'mxwm_limpiar_recursos_al_eliminar_proyecto');

// ============================================================================
// PROCESAR ACCIONES DE LOS BOTONES - VERSIÓN COMPLETAMENTE CORREGIDA
// ============================================================================

function mxwm_procesar_acciones_gestion_proyecto() {
    if (!is_user_logged_in() || !isset($_POST['accion']) || !isset($_POST['proyecto_id'])) {
        return;
    }
    
    $accion = sanitize_text_field($_POST['accion']);
    $proyecto_id = intval($_POST['proyecto_id']);
    
    // Verificar nonce
    if (!isset($_POST['mxwm_nonce']) || !wp_verify_nonce($_POST['mxwm_nonce'], 'mxwm_' . $accion . '_' . $proyecto_id)) {
        wp_die('Error de seguridad. Por favor, intenta de nuevo.');
    }
    
    // Verificar que el proyecto existe
    $proyecto = get_post($proyecto_id);
    if (!$proyecto || $proyecto->post_type !== 'proyecto') {
        wp_die('Proyecto no encontrado.');
    }
    
    // Verificar permisos: autor del proyecto O administrador
    $current_user_id = get_current_user_id();
    $es_autor = ($proyecto->post_author == $current_user_id);
    $es_admin = current_user_can('manage_options');
    
    if (!$es_autor && !$es_admin) {
        wp_die('No tienes permisos para realizar esta acción.');
    }
    
    // Procesar acción
    switch ($accion) {
        case 'pausar_proyecto':
            // Cambiar a draft (pausado)
            wp_update_post(array(
                'ID' => $proyecto_id,
                'post_status' => 'draft'
            ));
            
            // Redirigir a la página "Mis Proyectos" en lugar del proyecto individual
            wp_redirect(add_query_arg('mxwm_msg', 'proyecto_pausado', home_url('/mis-proyectos/')));
            exit;
            
        case 'activar_proyecto':
            // Cambiar a publish (activado)
            wp_update_post(array(
                'ID' => $proyecto_id,
                'post_status' => 'publish'
            ));
            
            // Redirigir al proyecto ahora que está publicado
            $redirect_url = get_permalink($proyecto_id);
            wp_redirect(add_query_arg('mxwm_msg', 'proyecto_activado', $redirect_url));
            exit;
            
        case 'eliminar_proyecto':
            // Mover a la papelera
            wp_trash_post($proyecto_id);
            
            wp_redirect(add_query_arg('mxwm_msg', 'proyecto_eliminado', home_url('/mis-proyectos/')));
            exit;
    }
}
add_action('init', 'mxwm_procesar_acciones_gestion_proyecto');

// ============================================================================
// MOSTRAR MENSAJES DE CONFIRMACIÓN
// ============================================================================

function mxwm_mostrar_mensajes_gestion() {
    if (!isset($_GET['mxwm_msg'])) {
        return;
    }
    
    $mensajes = array(
        'proyecto_pausado' => array('success', '✅ Proyecto pausado correctamente.'),
        'proyecto_activado' => array('success', '✅ Proyecto activado correctamente.'),
        'proyecto_eliminado' => array('info', '🗑️ Proyecto eliminado correctamente.')
    );
    
    $msg = $_GET['mxwm_msg'];
    
    if (isset($mensajes[$msg])) {
        list($tipo, $texto) = $mensajes[$msg];
        echo '<div class="notice notice-' . $tipo . ' is-dismissible" style="margin: 20px 0; padding: 15px; border-radius: 5px; border-left: 4px solid;">';
        echo '<p>' . $texto . '</p>';
        echo '</div>';
    }
}
add_action('wp_head', 'mxwm_mostrar_mensajes_gestion');

// ============================================================================
// HOOK DE ACTIVACIÓN - VERIFICACIÓN FINAL
// ============================================================================

/**
 * Verificación completa al activar el tema
 */
function mxwm_verificacion_activacion() {
    // Verificar dependencias críticas
    mxwm_verificar_dependencias_foros();
    
    // Flush rewrite rules para el post type
    flush_rewrite_rules();
    
    error_log('MXWM SYSTEM: Tema activado - verificación completada');
}
add_action('after_switch_theme', 'mxwm_verificacion_activacion');

// ============================================================================
// MANEJO DE ERRORES Y LOGGING MEJORADO
// ============================================================================

/**
 * Log personalizado para debugging
 */
function mxwm_log($message, $level = 'info') {
    $timestamp = current_time('mysql');
    $log_message = "[{$timestamp}] [{$level}] {$message}\n";
    
    // Escribir en archivo de log de WordPress
    error_log($log_message);
    
    // También escribir en archivo personalizado si es error crítico
    if ($level === 'error') {
        $upload_dir = wp_upload_dir();
        $log_file = $upload_dir['basedir'] . '/mxwm-debug.log';
        file_put_contents($log_file, $log_message, FILE_APPEND | LOCK_EX);
    }
}

// ============================================================================
// SISTEMA DE MIGRACIÓN SEGURA
// ============================================================================

/**
 * Migrar datos de versión anterior si es necesario
 */
function mxwm_migrar_datos_version_anterior() {
    $version_actual = '2.2';
    $version_guardada = get_option('mxwm_version', '1.0');
    
    if (version_compare($version_guardada, $version_actual, '<')) {
        // Migrar forum_id de array a entero si es necesario
        if (version_compare($version_guardada, '2.0', '<')) {
            mxwm_migrar_forum_ids();
        }
        
        update_option('mxwm_version', $version_actual);
        error_log("MXWM: Migración completada de {$version_guardada} a {$version_actual}");
    }
}

function mxwm_migrar_forum_ids() {
    // Buscar todos los grupos que puedan tener forum_id como array
    global $wpdb;
    $grupos = $wpdb->get_results(
        "SELECT group_id, meta_value FROM {$wpdb->prefix}bp_groups_groupmeta WHERE meta_key = 'forum_id'"
    );
    
    foreach ($grupos as $grupo) {
        $forum_id = maybe_unserialize($grupo->meta_value);
        
        // Si es array, convertirlo a entero
        if (is_array($forum_id) && !empty($forum_id)) {
            $nuevo_forum_id = $forum_id[0];
            groups_update_groupmeta($grupo->group_id, 'forum_id', $nuevo_forum_id);
            error_log("MXWM MIGRACIÓN: Grupo {$grupo->group_id} forum_id migrado de array a entero: {$nuevo_forum_id}");
        }
    }
}

// Ejecutar migración al cargar
add_action('init', 'mxwm_migrar_datos_version_anterior');

// ============================================================================
// SINCRONIZAR ESTADO PROYECTO-GRUPO
// ============================================================================

function mxwm_sincronizar_estado_proyecto_grupo($post_id, $post, $update) {
    if ($post->post_type !== 'proyecto') {
        return;
    }
    
    $grupo_id = get_post_meta($post_id, '_mxwm_grupo_id', true);
    
    if (!$grupo_id || !function_exists('groups_get_group')) {
        return;
    }
    
    $grupo = groups_get_group($grupo_id);
    
    // Si proyecto está en pausa (draft), ocultar grupo
    if ($post->post_status === 'draft') {
        // Cambiar grupo a oculto
        groups_edit_base_group_details(array(
            'group_id' => $grupo_id,
            'status' => 'hidden'
        ));
        error_log("MXWM: Grupo {$grupo_id} ocultado por proyecto pausado {$post_id}");
    }
    
    // Si proyecto está publicado, hacer grupo público
    if ($post->post_status === 'publish') {
        // Solo hacer público si antes estaba oculto por pausa
        if ($grupo->status === 'hidden') {
            groups_edit_base_group_details(array(
                'group_id' => $grupo_id,
                'status' => 'public'
            ));
            error_log("MXWM: Grupo {$grupo_id} hecho público por proyecto activado {$post_id}");
        }
    }
    
    // Si proyecto se elimina, eliminar grupo
    if ($post->post_status === 'trash') {
        if (function_exists('groups_delete_group')) {
            groups_delete_group($grupo_id);
            error_log("MXWM: Grupo {$grupo_id} eliminado con proyecto {$post_id}");
        }
    }
}
add_action('save_post', 'mxwm_sincronizar_estado_proyecto_grupo', 30, 3);

// ============================================================================
// SOLUCIÓN CORREGIDA: ELIMINAR PESTAÑA FORO PARA TODOS LOS USUARIOS EN GRUPOS DE NIVEL 3
// ============================================================================

// Hook temprano para eliminar la pestaña basado en el CREADOR del grupo
function mxwm_eliminar_pestana_foro_grupos_nivel_3_early() {
    if (!function_exists('bp_is_group') || !bp_is_group()) {
        return;
    }
    
    $group_id = bp_get_current_group_id();
    $group = groups_get_group($group_id);
    
    // Obtener nivel del CREADOR del grupo
    $creator_level = mxwm_get_user_pmp_level($group->creator_id);
    
    // SOLO para grupos creados por nivel 3
    if ($creator_level === '3') {
        // Método 1: Remover mediante BP Core
        bp_core_remove_subnav_item(bp_get_current_group_slug(), 'forum');
        
        // Método 2: Remover directamente del array global de BuddyPress
        global $bp;
        if (isset($bp->groups->current_group->nav['forum'])) {
            unset($bp->groups->current_group->nav['forum']);
        }
    }
}
add_action('bp_setup_nav', 'mxwm_eliminar_pestana_foro_grupos_nivel_3_early', 1);

// Hook adicional para asegurar la eliminación
function mxwm_eliminar_pestana_foro_grupos_nivel_3_late() {
    if (!function_exists('bp_is_group') || !bp_is_group()) {
        return;
    }
    
    $group_id = bp_get_current_group_id();
    $group = groups_get_group($group_id);
    $creator_level = mxwm_get_user_pmp_level($group->creator_id);
    
    // SOLO para grupos creados por nivel 3
    if ($creator_level === '3') {
        ?>
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            // Eliminar pestaña de foro del DOM para TODOS los usuarios
            $('li#forums-personal-li, li[data-bp-scope="forums"], a[href*="forum"]').parent().remove();
            $('.bp-navs.group-nav a[href*="forum"]').parent().remove();
            $('.buddypress_object_nav a[href*="forum"]').parent().remove();
            $('nav:contains("Forum"), nav:contains("Foro")').find('a[href*="forum"]').parent().remove();
        });
        </script>
        <style>
        /* Ocultar mediante CSS como respaldo para TODOS */
        li#forums-personal-li,
        .bp-navs.group-nav li a[href*="forum"],
        .buddypress_object_nav li a[href*="forum"],
        .item-list-tabs li a[href*="forum"] {
            display: none !important;
        }
        </style>
        <?php
    }
}
add_action('wp_footer', 'mxwm_eliminar_pestana_foro_grupos_nivel_3_late');

// ============================================================================
// VERSIÓN HÍBRIDA CORREGIDA - OCTUBRE 2025
// Combina: Estructura de colaboración + Correcciones técnicas Claude
// ============================================================================

// ============================================================================
// SINCRONIZACIÓN VISIBILIDAD PROYECTO-GRUPO (ISSUES 1 y 6)
// ============================================================================

add_action('acf/save_post', 'mxwm_sincronizar_visibilidad_proyecto', 20);
function mxwm_sincronizar_visibilidad_proyecto($post_id) {
    // Verificar si es un proyecto
    if (get_post_type($post_id) !== 'proyecto') return;

    // Obtener datos clave
    $user_id = get_post_field('post_author', $post_id);
    $author_level = mxwm_get_user_pmp_level($user_id);
    $grupo_id = get_post_meta($post_id, '_mxwm_grupo_id', true);

    // Obtener valores ACF
    $activar_grupo = get_field('activar_grupo', $post_id);
    $activar_foro = get_field('activar_foro', $post_id);
    $estado_grupo = get_field('estado_grupo', $post_id);
    $estado_foro = get_field('estado_foro', $post_id);
    
    // Default a público si no hay estado
    if (empty($estado_grupo)) {
        $estado_grupo = 'Público 🌎';
    }

    // ========================================================================
    // VALIDACIÓN DE PERMISOS (ISSUE 7)
    // ========================================================================
    
    // Extraer solo el valor sin emoji
    $estado_limpio = strtolower(trim(explode(' ', $estado_grupo)[0]));
    
    // Nivel 3 o menor: NO puede usar "Oculto"
    if (intval($author_level) < 4 && $estado_limpio === 'oculto') {
        $estado_grupo = 'Privado 🔒';
        update_field('estado_grupo', 'Privado 🔒', $post_id);
        $estado_limpio = 'privado';
    }
    
    // Nivel 2 o menor: Solo puede usar "Público"
    if (intval($author_level) < 3 && in_array($estado_limpio, ['privado', 'oculto'])) {
        $estado_grupo = 'Público 🌎';
        update_field('estado_grupo', 'Público 🌎', $post_id);
        $estado_limpio = 'public';
    }
    
    // Si grupo no activado, forzar público
    if (!$activar_grupo) {
        $estado_grupo = 'Público 🌎';
        update_field('estado_grupo', 'Público 🌎', $post_id);
        $estado_limpio = 'public';
    }

    // ========================================================================
    // SINCRONIZACIÓN CON BUDDYPRESS (ISSUE 1 y 6)
    // ========================================================================
    
    if ($grupo_id && function_exists('groups_get_group')) {
        // Mapeo correcto de estados
        $bp_status_map = [
            'público' => 'public',
            'public' => 'public',
            'privado' => 'private',
            'private' => 'private',
            'oculto' => 'hidden',
            'hidden' => 'hidden'
        ];
        
        $bp_group_status = $bp_status_map[$estado_limpio] ?? 'public';
        
        // Determinar si activar foro
        $enable_forum = 0;
        if ($activar_grupo && $activar_foro && intval($author_level) >= 4) {
            $enable_forum = 1;
        }
        
        // Nivel 3 con estado privado/oculto: sin foro
        if (intval($author_level) === 3 && in_array($bp_group_status, ['private', 'hidden'])) {
            $enable_forum = 0;
        }
        
        // ACTUALIZACIÓN DIRECTA SIN PENDING (ISSUE 6)
        groups_edit_base_group_details(array(
            'group_id' => $grupo_id,
            'status' => $bp_group_status
        ));
        
        // Actualizar enable_forum en la tabla principal
        global $wpdb;
        $wpdb->update(
            $wpdb->prefix . 'bp_groups',
            array('enable_forum' => $enable_forum),
            array('id' => $grupo_id),
            array('%d'),
            array('%d')
        );
        
        // Si tiene foro, sincronizar su privacidad
        if ($enable_forum) {
            $foro_id = groups_get_groupmeta($grupo_id, 'forum_id', true);
            if ($foro_id) {
                $foro_status_map = [
                    'public' => 'publish',
                    'private' => 'private',
                    'hidden' => 'private'
                ];
                
                $foro_status = $foro_status_map[$bp_group_status] ?? 'publish';
                
                wp_update_post(array(
                    'ID' => $foro_id,
                    'post_status' => $foro_status
                ));
            }
        }
        
        error_log("MXWM: Grupo {$grupo_id} actualizado a estado '{$bp_group_status}' sin pending");
    }
}

// ============================================================================
// FILTRO ARCHIVE - OCULTAR PROYECTOS PRIVADOS (ISSUE 8)
// ============================================================================

add_action('pre_get_posts', 'mxwm_filter_proyectos_archive');
function mxwm_filter_proyectos_archive($query) {
    // Solo en archive de proyectos, query principal, no admin
    if (is_admin() || !$query->is_main_query() || !$query->is_post_type_archive('proyecto')) {
        return;
    }

    $current_user_id = get_current_user_id();
    
    // Admins ven todo
    if (current_user_can('edit_others_posts')) {
        return;
    }

    // Construir meta_query para filtrar por visibilidad
    $meta_query = array(
        'relation' => 'OR',
        // Proyectos públicos
        array(
            'key' => 'estado_grupo',
            'value' => 'Público 🌎',
            'compare' => '='
        ),
        // Proyectos sin estado (legacy, asumimos públicos)
        array(
            'key' => 'estado_grupo',
            'compare' => 'NOT EXISTS'
        )
    );
    
    // Si está logueado, también sus propios proyectos
    if ($current_user_id) {
        // Agregar filtro por autor usando parámetro de WP_Query
        $author_in = $query->get('author__in');
        if (!$author_in) {
            $author_in = array();
        }
        $author_in[] = $current_user_id;
        $query->set('author__in', $author_in);
    }

    $query->set('meta_query', $meta_query);
}

// =========================================================================
// PERSONALIZACIÓN DEL FOOTER - MXwithME
// =========================================================================

// Reemplazar completamente el texto del footer
function custom_buddyx_footer_text() {
    $ano_actual = date('Y');
    
    $output = 'Copyright © ' . $ano_actual . ' MXwithME | ';
    $output .= '<a href="' . esc_url(home_url('/politica-antispam/')) . '" target="_blank" rel="noopener" style="color: inherit; text-decoration: none;">Política Antispam</a> | ';
    $output .= '<a href="' . esc_url(home_url('/aviso-de-privacidad/')) . '" target="_blank" rel="noopener" style="color: inherit; text-decoration: none;">Aviso de Privacidad</a> | ';
	$output .= '<a href="' . esc_url(home_url('/terminos-y-condiciones/')) . '" target="_blank" rel="noopener" style="color: inherit; text-decoration: none;">Términos y Condiciones</a> | ';
    
    return $output;
}
add_filter( 'buddyx_footer_copyright_text', 'custom_buddyx_footer_text' );


// ============================================================================
// FIN DEL ARCHIVO FUNCTIONS.PHP
// ============================================================================
?>