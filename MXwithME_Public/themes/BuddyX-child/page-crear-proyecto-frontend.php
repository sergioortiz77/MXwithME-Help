<?php
/**
 * Template Name: Crear Proyecto Frontend - MENSAJE CORREGIDO
 * MXWM-FIX-MENSAJE-ANTES-FORMULARIO-OCT2025-v1.8
 * SISTEMA-PRIVACIDAD-3-ESTADOS-v2.0
 * ORDEN-CORREGIDO-FEEDBACK-v2.1
 * 
 * CAMBIOS v2.1:
 * - Orden corregido: Galería → Video → Grupo → Foro
 * - Icono público actualizado: 🔓 → 🌎
 * - Mantiene toda la funcionalidad existente
 */


get_header();

// Verificar si el usuario está logueado
if (!is_user_logged_in()) {
    echo '<div class="container"><p>Debes iniciar sesión para crear un proyecto.</p></div>';
    get_footer();
    return;
}

// Obtener nivel PMP del usuario actual
$current_user_id = get_current_user_id();
$user_pmp_level = function_exists('mxwm_get_user_pmp_level') ? mxwm_get_user_pmp_level($current_user_id) : false;

// Auto-asignar nivel 1 si no tiene nivel PMP
if (!$user_pmp_level) {
    update_user_meta($current_user_id, 'pmp_level', '1');
    $user_pmp_level = '1';
}

// Convertir a número para comparaciones
$user_pmp_level = intval($user_pmp_level);

// Verificar que tenga nivel PMP válido
if (!$user_pmp_level || !in_array($user_pmp_level, [1, 2, 3, 4, 5])) {
    echo '<div class="container">';
    echo '<div style="background: #f8d7da; color: #721c24; padding: 2rem; border-radius: 10px; text-align: center; margin: 2rem 0;">';
    echo '<h3>Bienvenido a MXWM</h3>';
    echo '<p>Para crear proyectos necesitas ser miembro de nuestra comunidad PMP.</p>';
    echo '<p><strong>¡La membresía básica es GRATUITA!</strong></p>';
    echo '<a href="#upgrade" style="background: #28a745; color: white; padding: 1rem 2rem; text-decoration: none; border-radius: 25px; font-weight: bold;">Activar Membresía Gratuita</a>';
    echo '</div>';
    echo '</div>';
    get_footer();
    return;
}

// Verificar límite de proyectos ANTES de mostrar formulario
if (!function_exists('mxwm_can_user_create_project') || !mxwm_can_user_create_project($current_user_id)) {
    $config = mxwm_get_pmp_levels_config();
    $max_projects = $config[$user_pmp_level]['max_projects'];
    
    // Contar proyectos actuales
    $current_count = count(get_posts(array(
        'post_type' => 'proyecto',
        'author' => $current_user_id,
        'post_status' => array('publish', 'pending', 'draft'),
        'numberposts' => -1
    )));
    
    // Calcular siguiente nivel para upgrade
    $next_level = min($user_pmp_level + 1, 5);
    
    echo '<div class="container" style="max-width: 800px; margin: 2rem auto; padding: 0 1rem;">';
    echo '<div style="background: #fff3cd; border: 2px solid #ffc107; color: #856404; padding: 2rem; border-radius: 10px; text-align: center;">';
    echo '<div style="font-size: 3rem; margin-bottom: 1rem;">⚠️</div>';
    echo '<h2 style="color: #856404; margin: 0 0 1rem;">Límite de Proyectos Alcanzado</h2>';
    echo '<p style="font-size: 1.1rem; margin-bottom: 1rem;">Has alcanzado tu límite de <strong>' . $max_projects . ' proyecto(s)</strong> para tu nivel actual.</p>';
    echo '<p style="margin-bottom: 1.5rem;">Actualmente tienes <strong>' . $current_count . ' proyecto(s)</strong> creado(s).</p>';
    
    if ($user_pmp_level < 5) {
        echo '<div style="background: white; padding: 1.5rem; border-radius: 8px; margin: 1.5rem 0;">';
        echo '<p style="color: #333; margin-bottom: 1rem;">Upgrade al <strong>Nivel ' . $next_level . '</strong> para crear más proyectos y desbloquear más funciones.</p>';
        echo '<a href="' . home_url('/pago-de-membresia/?pmpro_level=' . $next_level) . '" style="background: #28a745; color: white; padding: 1rem 2rem; text-decoration: none; border-radius: 25px; font-weight: bold; font-size: 1.1rem; display: inline-block;">Hacer Upgrade Ahora</a>';
        echo '</div>';
    } else {
        echo '<p style="margin-top: 1rem;">Ya tienes el nivel máximo. Contacta con soporte para opciones empresariales.</p>';
    }
    
    echo '<div style="margin-top: 1.5rem;">';
    echo '<a href="' . home_url('/mis-proyectos/') . '" style="color: #007bff; text-decoration: none;">← Ver mis proyectos existentes</a>';
    echo ' | ';
    echo '<a href="' . home_url('/proyectos/') . '" style="color: #007bff; text-decoration: none;">Explorar otros proyectos</a>';
    echo '</div>';
    echo '</div>';
    echo '</div>';
    get_footer();
    return;
}

// INICIALIZAR VARIABLES DE MENSAJE (VACÍAS POR DEFECTO)
$mensaje = '';
$tipo_mensaje = '';

// Procesar envío del formulario
if (isset($_POST['crear_proyecto']) && wp_verify_nonce($_POST['proyecto_nonce'], 'crear_proyecto_frontend')) {
    
    $titulo = sanitize_text_field($_POST['titulo_proyecto']);
    
    if (!empty($titulo)) {
        
        $proyecto_data = array(
            'post_title'   => $titulo,
            'post_content' => '',
            'post_status'  => 'pending',
            'post_type'    => 'proyecto',
            'post_author'  => $current_user_id
        );
        
        $proyecto_id = wp_insert_post($proyecto_data);
        
        if ($proyecto_id && !is_wp_error($proyecto_id)) {
            
// Guardar todos los campos ACF (incluyendo descripción)
$field_groups = acf_get_field_groups(array('post_type' => 'proyecto'));
if ($field_groups) {
    foreach ($field_groups as $group) {
        $fields = acf_get_fields($group['ID']);
        if ($fields) {
            foreach ($fields as $field) {
                $field_name = $field['name'];
                
                if (isset($_POST[$field_name])) {
                    $value = $_POST[$field_name];
                    
                    switch ($field['type']) {
                        case 'text':
                        case 'select':
                            $value = sanitize_text_field($value);
                            break;
                        case 'textarea':
                            $value = sanitize_textarea_field($value);
                            break;
                        case 'checkbox':
                            $value = ($value === '1' || $value === 'true') ? 1 : 0;
                            break;
                        case 'image':
                            continue 2;
                    }
                    
                    update_field($field_name, $value, $proyecto_id);
                }
            }
        }
    }
}
          // === LIBRERÍAS NECESARIAS PARA SUBIDAS ===
if ( ! function_exists('wp_handle_upload') ) {
    require_once ABSPATH . 'wp-admin/includes/file.php';
}
if ( ! function_exists('wp_generate_attachment_metadata') ) {
    require_once ABSPATH . 'wp-admin/includes/image.php';
}
if ( ! function_exists('media_handle_upload') ) {
    require_once ABSPATH . 'wp-admin/includes/media.php';
}
// ===========================================


            // Procesar imágenes de galería (solo nivel 2+)
            $image_fields = ['galeria_foto_1', 'galeria_foto_2', 'galeria_foto_3', 'galeria_foto_4', 'galeria_foto_5'];
            
            if ($user_pmp_level >= 2) {
                foreach ($image_fields as $image_field) {
                    if (isset($_FILES[$image_field]) && !empty($_FILES[$image_field]['name'])) {
                        $upload = wp_handle_upload($_FILES[$image_field], array('test_form' => false));
 
                        if (!isset($upload['error'])) {
                            $attachment = array(
                                'post_mime_type' => $upload['type'],
                                'post_title' => sanitize_file_name( wp_basename( $upload['file'] ) ),
                                'post_content' => '',
                                'post_status' => 'inherit'
                            );
                            
                            $attachment_id = wp_insert_attachment($attachment, $upload['file'], $proyecto_id);
                            
                            if ($attachment_id) {
                                require_once(ABSPATH . 'wp-admin/includes/image.php');
                                $attachment_data = wp_generate_attachment_metadata($attachment_id, $upload['file']);
                                wp_update_attachment_metadata($attachment_id, $attachment_data);
                                
                                update_field($image_field, $attachment_id, $proyecto_id);
                            }
                        }
                    }
                }
            }
            
            // Procesar imagen principal (todos los niveles)
            if (isset($_FILES['imagen_principal']) && !empty($_FILES['imagen_principal']['name'])) {
                $upload = wp_handle_upload($_FILES['imagen_principal'], array('test_form' => false));
                
                if (!isset($upload['error'])) {
                    $attachment = array(
                        'post_mime_type' => $upload['type'],
                        'post_title' => sanitize_file_name( wp_basename( $upload['file'] ) ),
                        'post_content' => '',
                        'post_status' => 'inherit'
                    );
                    
                    $attachment_id = wp_insert_attachment($attachment, $upload['file'], $proyecto_id);
                    
                    if ($attachment_id) {
                        require_once(ABSPATH . 'wp-admin/includes/image.php');
                        $attachment_data = wp_generate_attachment_metadata($attachment_id, $upload['file']);
                        wp_update_attachment_metadata($attachment_id, $attachment_data);
                        
                        update_field('imagen_principal', $attachment_id, $proyecto_id);
                    }
                }
            }
            
            // ============ BLOQUE DE REDIRECCIÓN A GRUPO (AHORA DENTRO DEL IF) ============
            $activar_grupo = isset($_POST['activar_grupo']) ? 1 : 0;

            if ($activar_grupo && $user_pmp_level >= 3) {
                // Forzar creación inmediata del grupo (no esperar a save_post)
                do_action('save_post', $proyecto_id, get_post($proyecto_id), false);
                
                // Obtener ID del grupo recién creado
                $grupo_id = get_post_meta($proyecto_id, '_mxwm_grupo_id', true);
                
                if ($grupo_id && function_exists('bp_get_group_permalink')) {
                    // Obtener permalink del grupo
                    $grupo = groups_get_group($grupo_id);
                    $grupo_url = bp_get_group_permalink($grupo) . 'admin/edit-details/';
                    
                    // Mensaje de éxito con instrucciones
                    $mensaje = '¡Proyecto y Grupo creados exitosamente! Ahora configura tu grupo...';
                    $tipo_mensaje = 'success';
                    
                    // Redirigir a edición del grupo después de 2 segundos
                    echo '<script>
                        setTimeout(function() {
                            window.location.href = "' . esc_url($grupo_url) . '";
                        }, 2000);
                    </script>';
                } else {
                    // Si falla crear grupo, mensaje normal
                    $mensaje = '¡Proyecto enviado exitosamente! Está en revisión y aparecerá públicamente una vez aprobado.';
                    $tipo_mensaje = 'success';
                }
            } else {
                // Mensaje normal si no activó grupo
                $mensaje = '¡Proyecto enviado exitosamente! Está en revisión y aparecerá públicamente una vez aprobado.';
                $tipo_mensaje = 'success';
            }
            // ============ FIN DEL BLOQUE DE REDIRECCIÓN ============
            
            $_POST = array();
            
        } else {
            $mensaje = 'Error al crear el proyecto. Inténtalo nuevamente.';
            $tipo_mensaje = 'error';
        }
        
    } else {
        $mensaje = 'Por favor completa el título del proyecto.';
        $tipo_mensaje = 'error';
    }
}

// Obtener campos ACF
$acf_fields = array();
$field_groups = acf_get_field_groups(array('post_type' => 'proyecto'));
if ($field_groups) {
    foreach ($field_groups as $group) {
        $fields = acf_get_fields($group['ID']);
        if ($fields) {
            foreach ($fields as $field) {
                $acf_fields[] = $field;
            }
        }
    }
}
?>

<div class="container" style="max-width: 800px; margin: 2rem auto; padding: 0 1rem;">
    
    <h1 style="text-align: center; color: #333; margin-bottom: 1rem;">Crear Nuevo Proyecto</h1>
    <p style="text-align: center; color: #666; margin-bottom: 2rem; font-style: italic;">
        Comparte tu visión y conecta con personas que resuenen con tu propósito ✨
    </p>
    
    <?php if (!empty($mensaje)): ?>
        <div class="mensaje-formulario <?php echo $tipo_mensaje; ?>" style="
            padding: 1rem;
            margin-bottom: 2rem;
            border-radius: 5px;
            text-align: center;
            <?php echo $tipo_mensaje == 'success' ? 'background: #d4edda; color: #155724; border: 1px solid #c3e6cb;' : 'background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb;'; ?>
        ">
            <?php echo $mensaje; ?>
        </div>
    <?php endif; ?>
    
    <form method="post" enctype="multipart/form-data" style="background: #fff; padding: 2rem; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
        
        <?php wp_nonce_field('crear_proyecto_frontend', 'proyecto_nonce'); ?>
        
        <!-- Título (obligatorio) -->
        <div class="campo-formulario" style="margin-bottom: 1.5rem;">
            <label for="titulo_proyecto" style="display: block; margin-bottom: 0.5rem; font-weight: bold; color: #333;">
                Título del Proyecto *
            </label>
            <input type="text" 
                   id="titulo_proyecto" 
                   name="titulo_proyecto" 
                   required 
                   maxlength="200"
                   value="<?php echo isset($_POST['titulo_proyecto']) ? esc_attr($_POST['titulo_proyecto']) : ''; ?>"
                   style="width: 100%; padding: 0.875rem 1rem; border: 1px solid #ddd; border-radius: 5px; font-size: 1rem; line-height: 1.2;">
        </div>

        <!-- Renderizar campos ACF -->
        <?php if (!empty($acf_fields)): ?>
            <?php foreach ($acf_fields as $field): ?>
                
                <?php
                // Saltar campos especiales que se renderizan manualmente
                $special_fields = ['activar_grupo', 'activar_foro', 'video', 'galeria_foto_1', 'galeria_foto_2', 'galeria_foto_3', 'galeria_foto_4','galeria_foto_5', 'estado_grupo', 'estado_foro'];
                if (in_array($field['name'], $special_fields)) {
                    continue;
                }
                ?>
                
                <div class="campo-formulario" style="margin-bottom: 1.5rem;" data-field="<?php echo $field['name']; ?>">
                    <label for="<?php echo $field['name']; ?>" style="display: block; margin-bottom: 0.5rem; font-weight: bold; color: #333;">
                        <?php echo $field['label']; ?><?php if (isset($field['required']) && $field['required']): ?> *<?php endif; ?>
                    </label>
                    
                    <?php if ($field['type'] === 'text'): ?>
                        <input type="text" 
                               id="<?php echo $field['name']; ?>" 
                               name="<?php echo $field['name']; ?>"
                               value="<?php echo isset($_POST[$field['name']]) ? esc_attr($_POST[$field['name']]) : ''; ?>"
                               placeholder="<?php echo isset($field['placeholder']) ? esc_attr($field['placeholder']) : ''; ?>"
                               style="width: 100%; padding: 0.875rem 1rem; border: 1px solid #ddd; border-radius: 5px; font-size: 1rem; line-height: 1.2;"
                               onchange="handleFieldChange()">
                    
                    <?php elseif ($field['type'] === 'select'): ?>
                        <select id="<?php echo $field['name']; ?>" 
                                name="<?php echo $field['name']; ?>"
                                style="width: 100%; padding: 0.875rem 1rem; border: 1px solid #ddd; border-radius: 5px; font-size: 1rem; line-height: 1.4; background: white; appearance: none; background-image: url('data:image/svg+xml;charset=US-ASCII,<svg xmlns=\"http://www.w3.org/2000/svg\" width=\"12\" height=\"6\" viewBox=\"0 0 12 6\"><path fill=\"%23333\" d=\"M6 6L0 0h12z\"/></svg>'); background-repeat: no-repeat; background-position: right 1rem center; background-size: 12px; padding-right: 3rem; vertical-align: top; display: flex; align-items: center;"
                                onchange="handleFieldChange()">
                            <option value="">Selecciona una opción</option>
                            <?php if (isset($field['choices']) && is_array($field['choices'])): ?>
                                <?php foreach ($field['choices'] as $value => $label): ?>
                                    <option value="<?php echo esc_attr($value); ?>" <?php selected(isset($_POST[$field['name']]) ? $_POST[$field['name']] : '', $value); ?>>
                                        <?php echo esc_html($label); ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    
                    <?php elseif ($field['type'] === 'textarea'): ?>
                        <textarea id="<?php echo $field['name']; ?>" 
                                  name="<?php echo $field['name']; ?>" 
                                  rows="4"
                                  placeholder="<?php echo isset($field['placeholder']) ? esc_attr($field['placeholder']) : ''; ?>"
                                  style="width: 100%; padding: 0.875rem 1rem; border: 1px solid #ddd; border-radius: 5px; font-size: 1rem; resize: vertical; line-height: 1.4;"
                                  onchange="handleFieldChange()"><?php echo isset($_POST[$field['name']]) ? esc_textarea($_POST[$field['name']]) : ''; ?></textarea>
                    
                    <?php elseif ($field['type'] === 'image' && $field['name'] === 'imagen_principal'): ?>
                        <input type="file" 
                               id="<?php echo $field['name']; ?>" 
                               name="<?php echo $field['name']; ?>"
                               accept="image/*"
                               style="width: 100%; padding: 0.875rem 1rem; border: 1px solid #ddd; border-radius: 5px; font-size: 1rem; line-height: 1.2; background: white;">
                    
                    <?php endif; ?>
                    
                    <?php if (isset($field['instructions']) && !empty($field['instructions'])): ?>
                        <small style="color: #666; display: block; margin-top: 0.5rem;">
                            <?php echo $field['instructions']; ?>
                        </small>
                    <?php endif; ?>
                </div>
                
            <?php endforeach; ?>
        <?php endif; ?>

        <!-- SECCIÓN GALERÍA -->
        <?php if ($user_pmp_level >= 2): ?>
            <?php 
            $gallery_images = ['galeria_foto_1', 'galeria_foto_2', 'galeria_foto_3', 'galeria_foto_4', 'galeria_foto_5'];
            foreach ($gallery_images as $index => $image_field): 
            ?>
            <div class="campo-formulario" style="margin-bottom: 1.5rem;">
                <label for="<?php echo $image_field; ?>" style="display: block; margin-bottom: 0.5rem; font-weight: bold; color: #333;">
                    Imagen Galería <?php echo ($index + 1); ?>
                </label>
                <div style="border: 2px solid #28a745; background: #f8fff9; padding: 0.5rem; border-radius: 5px;">
                    <input type="file" 
                           id="<?php echo $image_field; ?>" 
                           name="<?php echo $image_field; ?>"
                           accept="image/*"
                           style="width: 100%; padding: 0.875rem 1rem; border: 1px solid #ddd; border-radius: 5px; font-size: 1rem; line-height: 1.2; background: white;">
                    <small style="color: #28a745; display: block; margin-top: 0.5rem; font-weight: 500;">
                        ✓ Disponible en tu plan PMP
                    </small>
                </div>
            </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="campo-formulario" style="margin-bottom: 1.5rem;">
                <label style="display: block; margin-bottom: 0.5rem; font-weight: bold; color: #333;">
                    Galería de Imágenes
                </label>
                <div style="border: 2px dashed #F2B84B; background: #fffcf0; padding: 1rem; border-radius: 8px;">
                    <div style="display: flex; align-items: center; gap: 1rem;">
                        <div style="background: #F2B84B; color: #333; width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; flex-shrink: 0;">
                            📸
                        </div>
                        <div style="flex: 1;">
                            <h4 style="margin: 0 0 0.3rem; color: #333; font-size: 1rem;">Galería Premium</h4>
                            <p style="margin: 0; color: #666; font-size: 0.85rem;">Upgrade a PMP Pro para agregar hasta 4 imágenes adicionales</p>
                        </div>
                        <a href="<?php echo home_url('/pago-de-membresia/?pmpro_level=2'); ?>" style="background: #F2B84B; color: #333; padding: 0.5rem 1rem; border-radius: 6px; text-decoration: none; font-size: 0.85rem; font-weight: bold; white-space: nowrap;">
                            Upgrade
                        </a>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- SECCIÓN VIDEO -->
        <div class="campo-formulario" style="margin-bottom: 1.5rem;">
            <label style="display: block; margin-bottom: 0.5rem; font-weight: bold; color: #333;">
                Video del Proyecto
            </label>
            
            <?php if ($user_pmp_level >= 3): ?>
                <input type="url" 
                       id="video" 
                       name="video"
                       value="<?php echo isset($_POST['video']) ? esc_attr($_POST['video']) : ''; ?>"
                       placeholder="https://www.youtube.com/watch?v=... o https://vimeo.com/..."
                       style="width: 100%; padding: 0.875rem 1rem; border: 1px solid #ddd; border-radius: 5px; font-size: 1rem; line-height: 1.2;">
                <small style="color: #666; display: block; margin-top: 0.5rem;">
                    Pega la URL completa de YouTube o Vimeo
                </small>
            <?php else: ?>
                <div style="border: 2px dashed #3854F2; background: #f0f4ff; padding: 1rem; border-radius: 8px;">
                    <div style="display: flex; align-items: center; gap: 1rem;">
                        <div style="background: #3854F2; color: white; width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; flex-shrink: 0;">
                            🎬
                        </div>
                        <div style="flex: 1;">
                            <h4 style="margin: 0 0 0.3rem; color: #333; font-size: 1rem;">Video Premium</h4>
                            <p style="margin: 0; color: #666; font-size: 0.85rem;">Upgrade a nivel 3+ para agregar videos</p>
                        </div>
                        <a href="<?php echo home_url('/pago-de-membresia/?pmpro_level=3'); ?>" style="background: #3854F2; color: white; padding: 0.5rem 1rem; border-radius: 6px; text-decoration: none; font-size: 0.85rem; font-weight: bold; white-space: nowrap;">
                            Upgrade
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- SECCIÓN PRIVACIDAD DEL GRUPO -->
        <div class="campo-formulario" style="margin-bottom: 1.5rem;">
            <label style="display: block; margin-bottom: 0.5rem; font-weight: bold; color: #333;">
                Grupo Privado
            </label>
            
            <?php if ($user_pmp_level >= 3): ?>
                <div style="border: 2px solid #7569BF; background: #f8f9ff; padding: 1rem; border-radius: 8px;">
                    <label style="display: flex; align-items: center; gap: 0.75rem; cursor: pointer; margin-bottom: 1rem;">
                        <input type="checkbox" 
                               id="activar_grupo" 
                               name="activar_grupo" 
                               value="1"
                               <?php checked(isset($_POST['activar_grupo']) ? $_POST['activar_grupo'] : '', '1'); ?>
                               style="width: 20px; height: 20px; cursor: pointer;"
                               onchange="togglePrivacidadGrupo()">
                        <div style="flex: 1;">
                            <span style="font-weight: 600; color: #333; font-size: 1rem;">Activar Grupo</span>
                            <p style="margin: 0.25rem 0 0; color: #666; font-size: 0.85rem;">
                                Crea un espacio para que los miembros de tu proyecto colaboren
                            </p>
                        </div>
                    </label>
                    
                    <!-- SELECTOR DE PRIVACIDAD DEL GRUPO -->
                    <div id="selector-privacidad-grupo" style="<?php echo (isset($_POST['activar_grupo']) && $_POST['activar_grupo']) ? 'display: block;' : 'display: none;'; ?> background: white; padding: 1rem; border-radius: 6px; border: 1px solid #e0e0e0;">
                        <h4 style="margin: 0 0 1rem; color: #333; font-size: 1rem;">Configurar Privacidad del Grupo</h4>
                        
                        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 10px; margin-bottom: 1rem;">
                            <label style="display: flex; flex-direction: column; align-items: center; padding: 1rem; border: 2px solid #28a745; border-radius: 8px; background: #f8fff9; cursor: pointer; text-align: center;">
                                <input type="radio" name="estado_grupo" value="publico" <?php checked(isset($_POST['estado_grupo']) ? $_POST['estado_grupo'] : 'publico', 'publico'); ?> style="margin-bottom: 0.5rem;">
                                <div style="font-size: 1.5rem; margin-bottom: 0.5rem;">🌎</div>
                                <div style="font-weight: 600; color: #28a745;">Público</div>
                                <small style="color: #666; font-size: 0.75rem;">Visible para todos</small>
                            </label>
                            
                            <label style="display: flex; flex-direction: column; align-items: center; padding: 1rem; border: 2px solid #ffc107; border-radius: 8px; background: #fffcf0; cursor: pointer; text-align: center;">
                                <input type="radio" name="estado_grupo" value="privado" <?php checked(isset($_POST['estado_grupo']) ? $_POST['estado_grupo'] : '', 'privado'); ?> style="margin-bottom: 0.5rem;">
                                <div style="font-size: 1.5rem; margin-bottom: 0.5rem;">🔒</div>
                                <div style="font-weight: 600; color: #856404;">Privado</div>
                                <small style="color: #666; font-size: 0.75rem;">Solo miembros</small>
                            </label>
                            
                            <label style="display: flex; flex-direction: column; align-items: center; padding: 1rem; border: 2px solid #6f42c1; border-radius: 8px; background: #f8f9ff; cursor: pointer; text-align: center;">
                                <input type="radio" name="estado_grupo" value="oculto" <?php checked(isset($_POST['estado_grupo']) ? $_POST['estado_grupo'] : '', 'oculto'); ?> style="margin-bottom: 0.5rem;">
                                <div style="font-size: 1.5rem; margin-bottom: 0.5rem;">🕵️</div>
                                <div style="font-weight: 600; color: #6f42c1;">Oculto</div>
                                <small style="color: #666; font-size: 0.75rem;">Solo por invitación</small>
                            </label>
                        </div>
                        
                        <div style="background: #fff3cd; padding: 0.75rem; border-radius: 5px; border-left: 4px solid #ffc107;">
                            <small style="color: #856404; display: block;">
                                ⚠️ <strong>Importante:</strong> Una vez que el grupo sea privado u oculto, NO podrá volver a ser público.
                            </small>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div style="border: 2px dashed #7569BF; background: #f8f9ff; padding: 1rem; border-radius: 8px;">
                    <div style="display: flex; align-items: center; gap: 1rem;">
                        <div style="background: #7569BF; color: white; width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; flex-shrink: 0;">
                            👥
                        </div>
                        <div style="flex: 1;">
                            <h4 style="margin: 0 0 0.3rem; color: #333; font-size: 1rem;">Grupo Premium</h4>
                            <p style="margin: 0; color: #666; font-size: 0.85rem;">Upgrade a nivel 3+ para crear grupos con control de privacidad</p>
                        </div>
                        <a href="<?php echo home_url('/pago-de-membresia/?pmpro_level=3'); ?>" style="background: #7569BF; color: white; padding: 0.5rem 1rem; border-radius: 6px; text-decoration: none; font-size: 0.85rem; font-weight: bold; white-space: nowrap;">
                            Upgrade
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- SECCIÓN PRIVACIDAD DEL FORO -->
        <div class="campo-formulario" style="margin-bottom: 1.5rem;">
            <label style="display: block; margin-bottom: 0.5rem; font-weight: bold; color: #333;">
                Foro Especializado
            </label>
            
            <?php if ($user_pmp_level >= 4): ?>
                <div style="border: 2px solid #F2B84B; background: #fffcf0; padding: 1rem; border-radius: 8px;">
                    <label style="display: flex; align-items: center; gap: 0.75rem; cursor: pointer; margin-bottom: 1rem;">
                        <input type="checkbox" 
                               id="activar_foro" 
                               name="activar_foro" 
                               value="1"
                               <?php checked(isset($_POST['activar_foro']) ? $_POST['activar_foro'] : '', '1'); ?>
                               style="width: 20px; height: 20px; cursor: pointer;"
                               onchange="togglePrivacidadForo()">
                        <div style="flex: 1;">
                            <span style="font-weight: 600; color: #333; font-size: 1rem;">Activar Foro</span>
                            <p style="margin: 0.25rem 0 0; color: #666; font-size: 0.85rem;">
                                Crea un foro donde la comunidad puede discutir sobre tu proyecto
                            </p>
                        </div>
                    </label>
                    
                    <!-- SELECTOR DE PRIVACIDAD DEL FORO -->
                    <div id="selector-privacidad-foro" style="<?php echo (isset($_POST['activar_foro']) && $_POST['activar_foro']) ? 'display: block;' : 'display: none;'; ?> background: white; padding: 1rem; border-radius: 6px; border: 1px solid #e0e0e0;">
                        <h4 style="margin: 0 0 1rem; color: #333; font-size: 1rem;">Configurar Privacidad del Foro</h4>
                        
                        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 10px;">
                            <label style="display: flex; flex-direction: column; align-items: center; padding: 1rem; border: 2px solid #28a745; border-radius: 8px; background: #f8fff9; cursor: pointer; text-align: center;">
                                <input type="radio" name="estado_foro" value="publico" <?php checked(isset($_POST['estado_foro']) ? $_POST['estado_foro'] : 'publico', 'publico'); ?> style="margin-bottom: 0.5rem;">
                                <div style="font-size: 1.5rem; margin-bottom: 0.5rem;">🌎</div>
                                <div style="font-weight: 600; color: #28a745;">Público</div>
                                <small style="color: #666; font-size: 0.75rem;">Todos pueden ver</small>
                            </label>
                            
                            <label style="display: flex; flex-direction: column; align-items: center; padding: 1rem; border: 2px solid #ffc107; border-radius: 8px; background: #fffcf0; cursor: pointer; text-align: center;">
                                <input type="radio" name="estado_foro" value="privado" <?php checked(isset($_POST['estado_foro']) ? $_POST['estado_foro'] : '', 'privado'); ?> style="margin-bottom: 0.5rem;">
                                <div style="font-size: 1.5rem; margin-bottom: 0.5rem;">🔒</div>
                                <div style="font-weight: 600; color: #856404;">Privado</div>
                                <small style="color: #666; font-size: 0.75rem;">Solo miembros</small>
                            </label>
                            
                            <label style="display: flex; flex-direction: column; align-items: center; padding: 1rem; border: 2px solid #6f42c1; border-radius: 8px; background: #f8f9ff; cursor: pointer; text-align: center;">
                                <input type="radio" name="estado_foro" value="oculto" <?php checked(isset($_POST['estado_foro']) ? $_POST['estado_foro'] : '', 'oculto'); ?> style="margin-bottom: 0.5rem;">
                                <div style="font-size: 1.5rem; margin-bottom: 0.5rem;">🕵️</div>
                                <div style="font-weight: 600; color: #6f42c1;">Oculto</div>
                                <small style="color: #666; font-size: 0.75rem;">Solo por invitación</small>
                            </label>
                        </div>
                        
                        <div style="background: #e7f3ff; padding: 0.75rem; border-radius: 5px; border-left: 4px solid #007bff; margin-top: 1rem;">
                            <small style="color: #004085; display: block;">
                                💡 <strong>Flexible:</strong> Puedes cambiar la privacidad del foro en cualquier momento.
                            </small>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div style="border: 2px dashed #F2B84B; background: #fffcf0; padding: 1rem; border-radius: 8px;">
                    <div style="display: flex; align-items: center; gap: 1rem;">
                        <div style="background: #F2B84B; color: #333; width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; flex-shrink: 0;">
                            💬
                        </div>
                        <div style="flex: 1;">
                            <h4 style="margin: 0 0 0.3rem; color: #333; font-size: 1rem;">Foro Elite</h4>
                            <p style="margin: 0; color: #666; font-size: 0.85rem;">Upgrade a nivel 4+ para crear foros con control de privacidad</p>
                        </div>
                        <a href="<?php echo home_url('/pago-de-membresia/?pmpro_level=4'); ?>" style="background: #F2B84B; color: #333; padding: 0.5rem 1rem; border-radius: 6px; text-decoration: none; font-size: 0.85rem; font-weight: bold; white-space: nowrap;">
                            Upgrade
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <div style="text-align: center; margin-top: 2rem;">
            <button type="submit" 
                    name="crear_proyecto"
                    style="background: #28a745; color: white; padding: 1rem 2rem; border: none; border-radius: 5px; font-size: 1.1rem; cursor: pointer; transition: background 0.3s;"
                    onmouseover="this.style.background='#218838'"
                    onmouseout="this.style.background='#28a745'">
                ✨ Crear Mi Proyecto
            </button>
        </div>
        
    </form>
    
    <div style="text-align: center; margin-top: 2rem;">
        <a href="<?php echo home_url('/proyectos/'); ?>" style="color: #007bff; text-decoration: none;">
            ← Volver a ver todos los proyectos
        </a>
    </div>

    <!-- INFORMACIÓN DE CARACTERÍSTICAS POR NIVEL -->
    <div class="nivel-info" style="margin-top: 30px; padding: 20px; background: #f8f9fa; border-radius: 8px;">
        <h3 style="text-align: center; margin-bottom: 20px;">📊 Características según tu nivel</h3>
        
        <?php
        $user_level = $user_pmp_level;
        $config = mxwm_get_pmp_levels_config();
        $nivel_actual = $config[$user_level] ?? array();
        ?>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; text-align: center;">
            <div style="padding: 15px; background: white; border-radius: 8px;">
                <div style="font-size: 1.5em; font-weight: bold; color: #0073aa;"><?php echo $nivel_actual['max_projects'] ?? 0; ?></div>
                <div style="font-size: 0.9em; color: #666;">Proyectos</div>
            </div>
            
            <div style="padding: 15px; background: white; border-radius: 8px;">
                <div style="font-size: 1.5em;"><?php echo ($nivel_actual['groups'] ?? false) ? '✅' : '❌'; ?></div>
                <div style="font-size: 0.9em; color: #666;">Grupos</div>
            </div>
            
            <div style="padding: 15px; background: white; border-radius: 8px;">
                <div style="font-size: 1.5em;"><?php echo ($nivel_actual['forums'] ?? false) ? '✅' : '❌'; ?></div>
                <div style="font-size: 0.9em; color: #666;">Foros</div>
            </div>
            
            <div style="padding: 15px; background: white; border-radius: 8px;">
                <div style="font-size: 1.5em;"><?php echo ($nivel_actual['gallery'] ?? false) ? '✅' : '❌'; ?></div>
                <div style="font-size: 0.9em; color: #666;">Galería</div>
            </div>
            
            <div style="padding: 15px; background: white; border-radius: 8px;">
                <div style="font-size: 1.5em;"><?php echo ($nivel_actual['video'] ?? false) ? '✅' : '❌'; ?></div>
                <div style="font-size: 0.9em; color: #666;">Video</div>
            </div>
        </div>
        
        <?php if ($user_level < 5) : ?>
            <div style="text-align: center; margin-top: 20px;">
                <a href="<?php echo home_url('/membership-levels/'); ?>" class="btn btn-primary" style="background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; font-weight: bold;">
                    🚀 Mejorar mi nivel
                </a>
            </div>
        <?php endif; ?>
    </div>
    
</div>

<style>
.campo-formulario input:focus,
.campo-formulario textarea:focus,
.campo-formulario select:focus {
    outline: none;
    border-color: #28a745;
    box-shadow: 0 0 5px rgba(40, 167, 69, 0.3);
}

.mensaje-formulario.success {
    animation: slideIn 0.5s ease-in-out;
}

.mensaje-formulario.error {
    animation: slideIn 0.5s ease-in-out;
}

@keyframes slideIn {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}

.campo-formulario input,
.campo-formulario select {
    min-height: 50px;
    box-sizing: border-box;
    display: flex;
    align-items: center;
    vertical-align: middle;
}

.campo-formulario select {
    padding-top: 0.875rem !important;
    padding-bottom: 0.875rem !important;
    line-height: 1.2 !important;
}

.campo-formulario input[type="text"] {
    padding: 0.875rem 1rem;
    line-height: 1.2;
}

.campo-formulario textarea {
    min-height: 120px;
    box-sizing: border-box;
    padding: 0.875rem 1rem;
    line-height: 1.4;
}

.campo-formulario input[type="file"] {
    padding: 0.875rem 1rem;
    line-height: 1.2;
}

.campo-condicional {
    display: none;
    transition: all 0.3s ease;
}

.campo-condicional.show {
    display: block;
    animation: slideIn 0.3s ease-in-out;
}

/* Estilos para selectores de privacidad */
#selector-privacidad-grupo,
#selector-privacidad-foro {
    transition: all 0.3s ease-in-out;
}

input[type="radio"] {
    accent-color: #28a745;
}

@media (max-width: 768px) {
    .container {
        margin: 1rem auto !important;
        padding: 0 0.5rem !important;
    }
    
    form {
        padding: 1rem !important;
    }
    
    .campo-formulario .grid-3 {
        grid-template-columns: 1fr !important;
    }
}
</style>

<script>
function handleFieldChange() {
    const etapaField = document.getElementById('etapa_del_proyecto');
    if (!etapaField) return;
    
    const etapaValue = etapaField.value;
    
    const conditionalFields = {
        'inspiracion_y_analisis': 'Idea',
        'estructuracion': 'Ya iniciado', 
        'fortalecimiento': 'Avanzado',
        'difusion': 'Consolidado'
    };
    
    Object.keys(conditionalFields).forEach(fieldName => {
        const field = document.querySelector('[data-field="' + fieldName + '"]');
        if (field) {
            field.style.display = 'none';
            const input = field.querySelector('textarea');
            if (input) input.value = '';
        }
    });
    
    Object.keys(conditionalFields).forEach(fieldName => {
        if (conditionalFields[fieldName] === etapaValue) {
            const field = document.querySelector('[data-field="' + fieldName + '"]');
            if (field) {
                field.style.display = 'block';
                field.style.animation = 'slideIn 0.3s ease-in-out';
            }
        }
    });
}

function togglePrivacidadGrupo() {
    const activarGrupo = document.getElementById('activar_grupo');
    const selectorPrivacidad = document.getElementById('selector-privacidad-grupo');
    
    if (activarGrupo.checked) {
        selectorPrivacidad.style.display = 'block';
        selectorPrivacidad.style.animation = 'slideIn 0.3s ease-in-out';
    } else {
        selectorPrivacidad.style.display = 'none';
    }
}

function togglePrivacidadForo() {
    const activarForo = document.getElementById('activar_foro');
    const selectorPrivacidad = document.getElementById('selector-privacidad-foro');
    
    if (activarForo.checked) {
        selectorPrivacidad.style.display = 'block';
        selectorPrivacidad.style.animation = 'slideIn 0.3s ease-in-out';
    } else {
        selectorPrivacidad.style.display = 'none';
    }
}

// Validación para privacidad irreversible del grupo
document.addEventListener('DOMContentLoaded', function() {
    const grupoPrivadoRadios = document.querySelectorAll('input[name="estado_grupo"][value="privado"], input[name="estado_grupo"][value="oculto"]');
    
    grupoPrivadoRadios.forEach(radio => {
        radio.addEventListener('change', function() {
            if (this.checked) {
                const tipo = this.value === 'privado' ? 'PRIVADO 🔒' : 'OCULTO 🕵️';
                if (!confirm(`⚠️ ATENCIÓN: Hacer el grupo ${tipo} es IRREVERSIBLE.\n\nUna vez creado, NO podrá volver a público.\n¿Continuar?`)) {
                    // Revertir al botón público
                    document.querySelector('input[name="estado_grupo"][value="publico"]').checked = true;
                }
            }
        });
    });
    
    // Inicializar campos condicionales
    const conditionalFields = ['inspiracion_y_analisis', 'estructuracion', 'fortalecimiento', 'difusion'];
    
    conditionalFields.forEach(fieldName => {
        const field = document.querySelector('[data-field="' + fieldName + '"]');
        if (field) field.style.display = 'none';
    });
    
    const etapaField = document.getElementById('etapa_del_proyecto');
    if (etapaField) {
        etapaField.addEventListener('change', handleFieldChange);
        handleFieldChange();
    }
    
    // Inicializar selectores de privacidad
    togglePrivacidadGrupo();
    togglePrivacidadForo();
});
</script>

<?php get_footer(); ?>
