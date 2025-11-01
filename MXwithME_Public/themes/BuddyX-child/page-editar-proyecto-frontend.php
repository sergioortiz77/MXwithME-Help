<?php
/**
 * Template Name: Editar Proyecto Frontend - RECONSTRUIDO COMPLETO
 * MXWM-EDITAR-RECONSTRUIDO-OCT2025-v7.0
 * BASADO EN: page-crear-proyecto-frontend.php
 */

get_header();

// Verificar si el usuario está logueado
if (!is_user_logged_in()) {
    echo '<div class="container"><p>Debes iniciar sesión para editar proyectos.</p></div>';
    get_footer();
    return;
}

$current_user_id = get_current_user_id();

// Obtener proyecto ID
$proyecto_id = isset($_GET['proyecto_id']) ? intval($_GET['proyecto_id']) : (isset($_GET['proyecto']) ? intval($_GET['proyecto']) : 0);

// Verificar que existe
if (!$proyecto_id || get_post_type($proyecto_id) !== 'proyecto') {
    echo '<div class="container"><p>Proyecto no encontrado.</p></div>';
    get_footer();
    return;
}

// Verificar que es el autor
$proyecto_author = get_post_field('post_author', $proyecto_id);
if ($proyecto_author != $current_user_id && !current_user_can('administrator')) {
    echo '<div class="container"><p>No tienes permisos para editar este proyecto.</p></div>';
    get_footer();
    return;
}

// Obtener nivel PMP del usuario actual
$user_pmp_level = function_exists('mxwm_get_user_pmp_level') ? mxwm_get_user_pmp_level($current_user_id) : false;
$user_pmp_level = intval($user_pmp_level);

// INICIALIZAR VARIABLES DE MENSAJE (VACÍAS POR DEFECTO)
$mensaje = '';
$tipo_mensaje = '';

// Obtener datos actuales del proyecto
$proyecto_actual = get_post($proyecto_id);
$titulo_actual = $proyecto_actual->post_title;
// Obtener descripción actual desde ACF
$descripcion_actual = get_field('descripcion_proyecto', $proyecto_id);

// Verificar si ya tiene grupo/foro creados
$tiene_grupo = get_post_meta($proyecto_id, '_mxwm_grupo_id', true);
$tiene_foro = get_post_meta($proyecto_id, '_mxwm_foro_id', true);

// Procesar envío del formulario
if (isset($_POST['editar_proyecto']) && wp_verify_nonce($_POST['proyecto_nonce'], 'editar_proyecto_frontend')) {
    
    $titulo = sanitize_text_field($_POST['titulo_proyecto']);
    
    if (!empty($titulo)) {
        
        $proyecto_data = array(
            'ID' => $proyecto_id,
            'post_title'   => $titulo,
            'post_status'  => 'pending' // Siempre a pending después de editar
        );
        
        
        $resultado = wp_update_post($proyecto_data);
        
        if ($resultado && !is_wp_error($resultado)) {
            
            // Guardar todos los campos ACF
            $field_groups = acf_get_field_groups(array('post_type' => 'proyecto'));
            if ($field_groups) {
                foreach ($field_groups as $group) {
                    $fields = acf_get_fields($group['ID']);
                    if ($fields) {
                        foreach ($fields as $field) {
                            $field_name = $field['name'];
                            
                            // Saltar campos especiales que se procesan después
                            if (in_array($field_name, ['galeria_foto_1', 'galeria_foto_2', 'galeria_foto_3', 'galeria_foto_4', 'galeria_foto_5', 'imagen_principal'])) {
                                continue;
                            }
                            
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
            
            // PROCESAR NUEVA IMAGEN PRINCIPAL
            if (isset($_FILES['imagen_principal']) && !empty($_FILES['imagen_principal']['name'])) {
                $upload = wp_handle_upload($_FILES['imagen_principal'], array('test_form' => false));
                
                if (!isset($upload['error'])) {
                    // Eliminar imagen anterior si existe
$imagen_anterior_id = get_field('imagen_principal', $proyecto_id);
                    if ($imagen_anterior_id) {
                        wp_delete_attachment($imagen_anterior_id, true);
                    }
                    
                    $attachment = array(
                        'post_mime_type' => $upload['type'],
                        'post_title' => sanitize_file_name( wp_basename( $upload['file'] ) ),
                        'post_content' => '',
                        'post_status' => 'inherit'
                    );
                    
                    $attachment_id = wp_insert_attachment($attachment, $upload['file'], $proyecto_id);
                    
                    if ($attachment_id) {
                        $attachment_data = wp_generate_attachment_metadata($attachment_id, $upload['file']);
                        wp_update_attachment_metadata($attachment_id, $attachment_data);
                        
                        update_field('imagen_principal', $attachment_id, $proyecto_id);
                    }
                }
            }
            
            // PROCESAR GALERÍA (solo nivel 2+)
            if ($user_pmp_level >= 2) {
                $image_fields = ['galeria_foto_1', 'galeria_foto_2', 'galeria_foto_3', 'galeria_foto_4', 'galeria_foto_5'];
                
                foreach ($image_fields as $image_field) {
                    // Procesar eliminación
                    if (isset($_POST['eliminar_' . $image_field]) && $_POST['eliminar_' . $image_field] === '1') {
                        $imagen_actual_id = get_field($image_field, $proyecto_id);
                        if ($imagen_actual_id) {
                            wp_delete_attachment($imagen_actual_id, true);
                            update_field($image_field, '', $proyecto_id);
                        }
                    }

                    // Procesar nueva imagen
                    if (isset($_FILES[$image_field]) && !empty($_FILES[$image_field]['name'])) {
                        $upload = wp_handle_upload($_FILES[$image_field], array('test_form' => false));
                        
                        if (!isset($upload['error'])) {
                            // Eliminar imagen anterior si existe
                            $imagen_anterior_id = get_field($image_field, $proyecto_id);
                            if ($imagen_anterior_id) {
                                wp_delete_attachment($imagen_anterior_id, true);
                            }
                            
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
            
            // Mensaje de éxito
            $mensaje = '¡Proyecto actualizado exitosamente! Está en revisión y aparecerá públicamente una vez aprobado.';
            $tipo_mensaje = 'success';
            
            
        } else {
            $mensaje = 'Error al actualizar el proyecto. Inténtalo nuevamente.';
            $tipo_mensaje = 'error';
        }
        
    } else {
        $mensaje = 'Por favor completa el título del proyecto.';
        $tipo_mensaje = 'error';
    }
}

// Obtener campos ACF actuales para el formulario
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

// Obtener imágenes actuales
$imagen_principal_actual = get_field('imagen_principal', $proyecto_id);
$galeria_actual = [];
if ($user_pmp_level >= 2) {
    $galeria_fields = ['galeria_foto_1', 'galeria_foto_2', 'galeria_foto_3', 'galeria_foto_4', 'galeria_foto_5'];
    foreach ($galeria_fields as $field) {
        // Usar get_post_meta en lugar de get_field
        $galeria_actual[$field] = get_post_meta($proyecto_id, $field, true);
    }
}
?>

<div class="container" style="max-width: 800px; margin: 2rem auto; padding: 0 1rem;">
    
    <h1 style="text-align: center; color: #333; margin-bottom: 1rem;">Editar Proyecto: <?php echo esc_html($titulo_actual); ?></h1>
    <p style="text-align: center; color: #666; margin-bottom: 2rem; font-style: italic;">
        Actualiza la información de tu proyecto y continúa conectando con personas que resuenen con tu propósito ✨
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
        
        <?php wp_nonce_field('editar_proyecto_frontend', 'proyecto_nonce'); ?>
        
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
                   value="<?php echo esc_attr($titulo_actual); ?>"
                   style="width: 100%; padding: 0.875rem 1rem; border: 1px solid #ddd; border-radius: 5px; font-size: 1rem; line-height: 1.2;">
        </div>

        <!-- IMAGEN PRINCIPAL -->
<!-- SECCIÓN IMAGEN PRINCIPAL -->
<div class="campo-formulario" style="margin-bottom: 1.5rem;">
    <label style="display: block; margin-bottom: 0.5rem; font-weight: bold; color: #333;">
        Imagen Principal del Proyecto
    </label>
    
    <?php if ($imagen_principal_actual): ?>
        <div style="margin-bottom: 1rem; padding: 1rem; border: 2px solid #e0e0e0; border-radius: 8px; background: #f9f9f9;">
            <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1rem;">
                <div style="flex-shrink: 0;">
                    <?php
                    $imagen_url = wp_get_attachment_image_url($imagen_principal_actual, 'medium');
                    if ($imagen_url):
                    ?>
                    <img src="<?php echo esc_url($imagen_url); ?>" 
                         alt="Imagen actual del proyecto" 
                         style="width: 150px; height: 150px; object-fit: cover; border-radius: 5px;">
                    <?php endif; ?>
                </div>
                <div style="flex: 1;">
                    <p style="margin: 0 0 0.5rem; font-weight: 500;">Ya tienes una imagen principal en tu proyecto</p>
                    <p style="margin: 0; font-size: 0.9rem; color: #28a745;">
                        📸  Sube una nueva para reemplazar la actual
                    </p>
                </div>
            </div>
        </div>
    <?php endif; ?>
    
    <input type="file" 
           id="imagen_principal" 
           name="imagen_principal" 
           accept="image/*" 
           style="width: 100%; padding: 0.875rem 1rem; border: 1px solid #ddd; border-radius: 5px; font-size: 1rem; line-height: 1.2; background: white;">
    
    <small style="color: #666; display: block; margin-top: 0.5rem;">
        <?php echo $imagen_principal_actual ? 'La nueva imagen reemplazará a la actual' : 'Sube la imagen más representativa de tu proyecto'; ?>
    </small>
</div>
        <!-- Renderizar campos ACF -->
        <?php if (!empty($acf_fields)): ?>
            <?php foreach ($acf_fields as $field): ?>
                
                <?php
                // Saltar campos especiales que se renderizan manualmente
                $special_fields = ['activar_grupo', 'activar_foro', 'video', 'galeria_foto_1', 'galeria_foto_2', 'galeria_foto_3', 'galeria_foto_4', 'galeria_foto_3', 'estado_grupo', 'estado_foro', 'imagen_principal' ];
                if (in_array($field['name'], $special_fields)) {
                    continue;
                }
                ?>
                
                <div class="campo-formulario" style="margin-bottom: 1.5rem;" data-field="<?php echo $field['name']; ?>">
                    <label for="<?php echo $field['name']; ?>" style="display: block; margin-bottom: 0.5rem; font-weight: bold; color: #333;">
                        <?php echo $field['label']; ?><?php if (isset($field['required']) && $field['required']): ?> *<?php endif; ?>
                    </label>
                    
                    <?php 
                    $valor_actual = get_field($field['name'], $proyecto_id);
                    ?>
                    
                    <?php if ($field['type'] === 'text'): ?>
                        <input type="text" 
                               id="<?php echo $field['name']; ?>" 
                               name="<?php echo $field['name']; ?>"
                               value="<?php echo esc_attr($valor_actual); ?>"
                               placeholder="<?php echo isset($field['placeholder']) ? esc_attr($field['placeholder']) : ''; ?>"
                               style="width: 100%; padding: 0.875rem 1rem; border: 1px solid #ddd; border-radius: 5px; font-size: 1rem; line-height: 1.2;">
                    
                    <?php elseif ($field['type'] === 'select'): ?>
                        <select id="<?php echo $field['name']; ?>" 
                                name="<?php echo $field['name']; ?>"
                                style="width: 100%; padding: 0.875rem 1rem; border: 1px solid #ddd; border-radius: 5px; font-size: 1rem; line-height: 1.4; background: white; appearance: none; background-image: url('data:image/svg+xml;charset=US-ASCII,<svg xmlns=\"http://www.w3.org/2000/svg\" width=\"12\" height=\"6\" viewBox=\"0 0 12 6\"><path fill=\"%23333\" d=\"M6 6L0 0h12z\"/></svg>'); background-repeat: no-repeat; background-position: right 1rem center; background-size: 12px; padding-right: 3rem; vertical-align: top; display: flex; align-items: center;">
                            <option value="">Selecciona una opción</option>
                            <?php if (isset($field['choices']) && is_array($field['choices'])): ?>
                                <?php foreach ($field['choices'] as $value => $label): ?>
                                    <option value="<?php echo esc_attr($value); ?>" <?php selected($valor_actual, $value); ?>>
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
                                  style="width: 100%; padding: 0.875rem 1rem; border: 1px solid #ddd; border-radius: 5px; font-size: 1rem; resize: vertical; line-height: 1.4;"><?php echo esc_textarea($valor_actual); ?></textarea>
                    
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
    <div class="campo-formulario" style="margin-bottom: 1.5rem;">
        <label style="display: block; margin-bottom: 0.5rem; font-weight: bold; color: #333;">
            Galería de Imágenes
        </label>
        
        <?php 
        $gallery_images = [
            'galeria_foto_1' => 'Imagen Galería 1',
            'galeria_foto_2' => 'Imagen Galería 2', 
            'galeria_foto_3' => 'Imagen Galería 3',
            'galeria_foto_4' => 'Imagen Galería 4',
            'galeria_foto_5' => 'Imagen Galería 5'
        ];
        
        foreach ($gallery_images as $image_field => $image_label): 
            $imagen_actual = $galeria_actual[$image_field] ?? '';
        ?>
        <div class="galeria-item" style="margin-bottom: 1rem; padding: 1rem; border: 1px solid #e0e0e0; border-radius: 8px; background: #f8f9fa;">
            
            <?php if ($imagen_actual): ?>
                <?php 
                $imagen_url = wp_get_attachment_image_url($imagen_actual, 'medium');
                if ($imagen_url): 
                ?>
                    <div style="position: relative; display: inline-block; margin-bottom: 1rem;">
                        <img src="<?php echo esc_url($imagen_url); ?>" 
                             alt="<?php echo esc_attr($image_label); ?>" 
                             style="width: 120px; height: 120px; object-fit: cover; border-radius: 5px;">
                        <button type="button" 
                                class="eliminar-imagen-btn" 
                                data-field="<?php echo $image_field; ?>"
                                style="position: absolute; top: -8px; right: -8px; width: 24px; height: 24px; background: #dc3545; color: white; border: none; border-radius: 50%; cursor: pointer; font-size: 14px; font-weight: bold; display: flex; align-items: center; justify-content: center; line-height: 1;">
                            ×
                        </button>
                    </div>
                    <input type="hidden" name="eliminar_<?php echo $image_field; ?>" value="0" id="hidden_eliminar_<?php echo $image_field; ?>">
                    <p style="margin: 0.5rem 0 0; font-weight: 500; color: #333;"><?php echo $image_label; ?></p>
                <?php endif; ?>
            <?php else: ?>
                <input type="file" 
                       id="<?php echo $image_field; ?>" 
                       name="<?php echo $image_field; ?>"
                       accept="image/*"
                       style="width: 100%; padding: 0.875rem 1rem; border: 1px solid #ddd; border-radius: 5px; font-size: 1rem; line-height: 1.2; background: white;">
                <small style="color: #28a745; display: block; margin-top: 0.5rem; font-weight: 500;">
                    ✓ Disponible en tu plan PMP
                </small>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
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
                    <p style="margin: 0; color: #666; font-size: 0.85rem;">Upgrade a PMP Pro para agregar hasta 5 imágenes adicionales</p>
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
<?php 
// Obtener el valor CRUDO del campo video (sin procesar oEmbed)
$video_actual = get_field('video', $proyecto_id, false); // El tercer parámetro 'false' evita el formateo
?>
                <input type="url" 
                       id="video" 
                       name="video"
                       value="<?php echo esc_attr($video_actual); ?>"
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
                <?php 
                $activar_grupo_actual = get_field('activar_grupo', $proyecto_id);
                $estado_grupo_actual = get_field('estado_grupo', $proyecto_id);
                ?>
                <div style="border: 2px solid #7569BF; background: #f8f9ff; padding: 1rem; border-radius: 8px;">
                    <label style="display: flex; align-items: center; gap: 0.75rem; cursor: pointer; margin-bottom: 1rem;">
                        <input type="checkbox" 
                               id="activar_grupo" 
                               name="activar_grupo" 
                               value="1"
                               <?php checked($activar_grupo_actual, 1); ?>
                               <?php echo $tiene_grupo ? 'disabled' : ''; ?>
                               style="width: 20px; height: 20px; cursor: pointer;"
                               onchange="togglePrivacidadGrupo()">
                        <div style="flex: 1;">
                            <span style="font-weight: 600; color: #333; font-size: 1rem;">
                                <?php echo $tiene_grupo ? '✅ Grupo Activado' : 'Activar Grupo'; ?>
                            </span>
                            <p style="margin: 0.25rem 0 0; color: #666; font-size: 0.85rem;">
                                <?php echo $tiene_grupo ? 'Tu grupo ya está creado y activo' : 'Crea un espacio para que los miembros de tu proyecto colaboren'; ?>
                            </p>
                            <?php if ($tiene_grupo): ?>
                                <p style="margin: 0.25rem 0 0; color: #28a745; font-size: 0.8rem;">
                                    ℹ️ El grupo no se puede desactivar una vez creado
                                </p>
                            <?php endif; ?>
                        </div>
                    </label>
                    
                    <!-- SELECTOR DE PRIVACIDAD DEL GRUPO -->
                    <div id="selector-privacidad-grupo" style="<?php echo ($activar_grupo_actual || $tiene_grupo) ? 'display: block;' : 'display: none;'; ?> background: white; padding: 1rem; border-radius: 6px; border: 1px solid #e0e0e0;">
                        <h4 style="margin: 0 0 1rem; color: #333; font-size: 1rem;">Configurar Privacidad del Grupo</h4>
                        
                        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 10px; margin-bottom: 1rem;">
                            <label style="display: flex; flex-direction: column; align-items: center; padding: 1rem; border: 2px solid #28a745; border-radius: 8px; background: #f8fff9; cursor: pointer; text-align: center;">
                                <input type="radio" name="estado_grupo" value="publico" <?php checked($estado_grupo_actual, 'publico'); ?> style="margin-bottom: 0.5rem;">
                                <div style="font-size: 1.5rem; margin-bottom: 0.5rem;">🌎</div>
                                <div style="font-weight: 600; color: #28a745;">Público</div>
                                <small style="color: #666; font-size: 0.75rem;">Visible para todos</small>
                            </label>
                            
                            <label style="display: flex; flex-direction: column; align-items: center; padding: 1rem; border: 2px solid #ffc107; border-radius: 8px; background: #fffcf0; cursor: pointer; text-align: center;">
                                <input type="radio" name="estado_grupo" value="privado" <?php checked($estado_grupo_actual, 'privado'); ?> style="margin-bottom: 0.5rem;">
                                <div style="font-size: 1.5rem; margin-bottom: 0.5rem;">🔒</div>
                                <div style="font-weight: 600; color: #856404;">Privado</div>
                                <small style="color: #666; font-size: 0.75rem;">Solo miembros</small>
                            </label>
                            
                            <label style="display: flex; flex-direction: column; align-items: center; padding: 1rem; border: 2px solid #6f42c1; border-radius: 8px; background: #f8f9ff; cursor: pointer; text-align: center;">
                                <input type="radio" name="estado_grupo" value="oculto" <?php checked($estado_grupo_actual, 'oculto'); ?> style="margin-bottom: 0.5rem;">
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
                <?php 
                $activar_foro_actual = get_field('activar_foro', $proyecto_id);
                $estado_foro_actual = get_field('estado_foro', $proyecto_id);
                ?>
                <div style="border: 2px solid #F2B84B; background: #fffcf0; padding: 1rem; border-radius: 8px;">
                    <label style="display: flex; align-items: center; gap: 0.75rem; cursor: pointer; margin-bottom: 1rem;">
                        <input type="checkbox" 
                               id="activar_foro" 
                               name="activar_foro" 
                               value="1"
                               <?php checked($activar_foro_actual, 1); ?>
                               <?php echo $tiene_foro ? 'disabled' : ''; ?>
                               style="width: 20px; height: 20px; cursor: pointer;"
                               onchange="togglePrivacidadForo()">
                        <div style="flex: 1;">
                            <span style="font-weight: 600; color: #333; font-size: 1rem;">
                                <?php echo $tiene_foro ? '✅ Foro Activado' : 'Activar Foro'; ?>
                            </span>
                            <p style="margin: 0.25rem 0 0; color: #666; font-size: 0.85rem;">
                                <?php echo $tiene_foro ? 'Tu foro ya está creado y activo' : 'Crea un foro donde la comunidad puede discutir sobre tu proyecto'; ?>
                            </p>
                            <?php if ($tiene_foro): ?>
                                <p style="margin: 0.25rem 0 0; color: #28a745; font-size: 0.8rem;">
                                    ℹ️ El foro no se puede desactivar una vez creado
                                </p>
                            <?php endif; ?>
                        </div>
                    </label>
                    
                    <!-- SELECTOR DE PRIVACIDAD DEL FORO -->
                    <div id="selector-privacidad-foro" style="<?php echo ($activar_foro_actual || $tiene_foro) ? 'display: block;' : 'display: none;'; ?> background: white; padding: 1rem; border-radius: 6px; border: 1px solid #e0e0e0;">
                        <h4 style="margin: 0 0 1rem; color: #333; font-size: 1rem;">Configurar Privacidad del Foro</h4>
                        
                        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 10px;">
                            <label style="display: flex; flex-direction: column; align-items: center; padding: 1rem; border: 2px solid #28a745; border-radius: 8px; background: #f8fff9; cursor: pointer; text-align: center;">
                                <input type="radio" name="estado_foro" value="publico" <?php checked($estado_foro_actual, 'publico'); ?> style="margin-bottom: 0.5rem;">
                                <div style="font-size: 1.5rem; margin-bottom: 0.5rem;">🌎</div>
                                <div style="font-weight: 600; color: #28a745;">Público</div>
                                <small style="color: #666; font-size: 0.75rem;">Todos pueden ver</small>
                            </label>
                            
                            <label style="display: flex; flex-direction: column; align-items: center; padding: 1rem; border: 2px solid #ffc107; border-radius: 8px; background: #fffcf0; cursor: pointer; text-align: center;">
                                <input type="radio" name="estado_foro" value="privado" <?php checked($estado_foro_actual, 'privado'); ?> style="margin-bottom: 0.5rem;">
                                <div style="font-size: 1.5rem; margin-bottom: 0.5rem;">🔒</div>
                                <div style="font-weight: 600; color: #856404;">Privado</div>
                                <small style="color: #666; font-size: 0.75rem;">Solo miembros</small>
                            </label>
                            
                            <label style="display: flex; flex-direction: column; align-items: center; padding: 1rem; border: 2px solid #6f42c1; border-radius: 8px; background: #f8f9ff; cursor: pointer; text-align: center;">
                                <input type="radio" name="estado_foro" value="oculto" <?php checked($estado_foro_actual, 'oculto'); ?> style="margin-bottom: 0.5rem;">
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
                    name="editar_proyecto"
                    style="background: #28a745; color: white; padding: 1rem 2rem; border: none; border-radius: 5px; font-size: 1.1rem; cursor: pointer; transition: background 0.3s;"
                    onmouseover="this.style.background='#218838'"
                    onmouseout="this.style.background='#28a745'">
                💾 Guardar Cambios
            </button>
        </div>
        
    </form>
    
    <div style="text-align: center; margin-top: 2rem;">
        <a href="<?php echo home_url('/mis-proyectos/'); ?>" style="color: #007bff; text-decoration: none;">
            ← Volver a mis proyectos
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
                <a href="<?php echo home_url('/registrarse/'); ?>" class="btn btn-primary" style="background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; font-weight: bold;">
                    🚀 Mejorar mi nivel
                </a>
            </div>
        <?php endif; ?>
    </div>
    
</div>

<!-- MODAL EDUCATIVO DE PRIVACIDAD -->
<div id="modal-privacidad-grupo" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.75); z-index:99999; align-items:center; justify-content:center;">
    <div style="background:#fff; padding:2.5rem; border-radius:12px; max-width:520px; margin:2rem; box-shadow: 0 10px 40px rgba(0,0,0,0.3);">
        <div style="text-align:center; margin-bottom:1.5rem;">
            <span style="font-size:3rem;">⚠️</span>
        </div>
        <h2 style="color:#dc3545; margin:0 0 1rem; text-align:center; font-size:1.5rem;">Decisión Importante sobre Privacidad</h2>
        
        <div style="background:#fff3cd; border-left:4px solid #ffc107; padding:1rem; margin-bottom:1.5rem; border-radius:4px;">
            <p style="margin:0; font-size:1rem; color:#856404;">
                <strong>💡 Recomendación:</strong> Los grupos privados u ocultos son ideales para proyectos sensibles donde la privacidad de los miembros es prioritaria.
            </p>
        </div>
        
        <p style="font-size:1.05rem; line-height:1.7; color:#333; margin-bottom:1rem;">
            Estás a punto de hacer tu grupo <strong style="color:#dc3545;">PRIVADO</strong> o <strong style="color:#6c757d;">OCULTO</strong>.
        </p>
        
        <p style="font-size:0.95rem; color:#666; line-height:1.6; margin-bottom:1.5rem;">
            Si bien puedes cambiar entre Privado ↔ Oculto, te recomendamos considerar cuidadosamente esta decisión. Los grupos públicos permiten mayor visibilidad y crecimiento de tu comunidad.
        </p>
        
        <div style="background:#f8f9fa; padding:1rem; border-radius:6px; margin-bottom:1.5rem;">
            <p style="margin:0 0 0.5rem; font-weight:600; color:#333; font-size:0.9rem;">¿Cuál es la diferencia?</p>
            <ul style="margin:0.5rem 0 0; padding-left:1.5rem; font-size:0.9rem; color:#666; line-height:1.6;">
                <li><strong>Público 🌎:</strong> Visible para todos, cualquiera puede unirse</li>
                <li><strong>Privado 🔒:</strong> Visible pero requiere solicitud de acceso</li>
                <li><strong>Oculto 🕵️:</strong> Solo visible para miembros invitados</li>
            </ul>
        </div>
        
        <div style="display:flex; gap:1rem; margin-top:2rem;">
            <button id="btn-cancelar-privacidad" class="button" style="flex:1; background:#6c757d; color:#fff; border:none; padding:14px 20px; border-radius:6px; cursor:pointer; font-size:1rem; font-weight:600; transition:background 0.2s;">
                Volver a Público
            </button>
            <button id="btn-confirmar-privacidad" class="button button-primary" style="flex:1; background:#7569BF; color:#fff; border:none; padding:14px 20px; border-radius:6px; cursor:pointer; font-size:1rem; font-weight:600; transition:background 0.2s;">
                Confirmar Cambio
            </button>
        </div>
        
        <p style="text-align:center; margin:1rem 0 0; font-size:0.8rem; color:#999;">
            Podrás cambiar entre Privado y Oculto en cualquier momento
        </p>
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

/* Responsive */
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
    
    /* Para las miniaturas de imágenes en móvil */
    .campo-formulario img {
        width: 100px !important;
        height: 100px !important;
    }
}

/* Estilos para imágenes eliminadas */
.imagen-eliminada {
    opacity: 0.5;
    filter: grayscale(100%);
}
</style>

<script>
// Funciones básicas para mostrar/ocultar selectores de privacidad
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
    
    // Inicializar selectores de privacidad
    togglePrivacidadGrupo();
    togglePrivacidadForo();
    
    // Manejar eliminación de imágenes - mostrar campo de subida cuando se marca eliminar
    const checkboxesEliminar = document.querySelectorAll('input[type="checkbox"][name^="eliminar_"]');
    checkboxesEliminar.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            if (this.checked) {
                // Encontrar el campo de archivo correspondiente
                const fieldName = this.name.replace('eliminar_', '');
                const fileInput = document.querySelector(`input[name="${fieldName}"]`);
                if (fileInput) {
                    fileInput.style.borderColor = '#28a745';
                    fileInput.style.boxShadow = '0 0 5px rgba(40, 167, 69, 0.3)';
                }
            }
        });
    });
});

// Cargar el sistema de privacidad MXWM si está disponible
document.addEventListener('DOMContentLoaded', function() {
    // Verificar si el script de privacidad está cargado
    if (typeof mxwmPrivacyUI !== 'undefined') {
        console.log('MXWM: Sistema de privacidad cargado');
    } else {
        console.log('MXWM: Cargando sistema de privacidad...');
        // Aquí podrías cargar dinámicamente el script mxwm-privacy-ui.js si es necesario
    }
});
</script>

<?php 
// Intentar cargar el script de privacidad MXWM si existe
$privacy_script_path = WP_PLUGIN_DIR . '/mxwm-privacy-core/mxwm-privacy-ui.js';
if (file_exists($privacy_script_path)) {
    echo '<script src="' . plugins_url('mxwm-privacy-core/mxwm-privacy-ui.js') . '"></script>';
} else {
    error_log('MXWM: No se encontró el script de privacidad en ' . $privacy_script_path);
}
?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('JavaScript cargado - Galería'); // Para debug
    
    // Manejar eliminación de imágenes
    document.querySelectorAll('.eliminar-imagen-btn').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            console.log('Botón clickeado:', this.getAttribute('data-field')); // Para debug
            
            const fieldName = this.getAttribute('data-field');
            const galeriaItem = this.closest('.galeria-item');
            
            // Mostrar confirmación
            if (confirm('¿Estás seguro de que quieres eliminar esta imagen?')) {
                console.log('Eliminando:', fieldName); // Para debug
                
                // Marcar para eliminación en el formulario
                const hiddenInput = document.getElementById('hidden_eliminar_' + fieldName);
                if (hiddenInput) {
                    hiddenInput.value = '1';
                    console.log('Hidden input actualizado:', hiddenInput.value); // Para debug
                }
                
                // Crear nuevo input file
                const fileInput = document.createElement('input');
                fileInput.type = 'file';
                fileInput.name = fieldName;
                fileInput.id = fieldName;
                fileInput.accept = 'image/*';
                fileInput.style.cssText = 'width: 100%; padding: 0.875rem 1rem; border: 1px solid #ddd; border-radius: 5px; font-size: 1rem; line-height: 1.2; background: white;';
                
                const smallText = document.createElement('small');
                smallText.style.cssText = 'color: #28a745; display: block; margin-top: 0.5rem; font-weight: 500;';
                smallText.textContent = '✓ Sube una nueva imagen';
                
                // Limpiar el contenedor y agregar nuevos elementos
                galeriaItem.innerHTML = '';
                galeriaItem.appendChild(fileInput);
                galeriaItem.appendChild(smallText);
                
                console.log('Interfaz actualizada'); // Para debug
            }
        });
    });
});
</script>

<?php get_footer(); ?>
