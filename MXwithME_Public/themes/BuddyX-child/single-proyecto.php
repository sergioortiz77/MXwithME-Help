<?php
/**
 * Single Proyecto Template - Estructura PHP Corregida
 * MXWM-PMP-FRONTEND-FIX-DIC2024-FINAL-PHP-STRUCTURE
 * FIXES: Imagen responsive, Padding móvil, GLightbox, Plyr videoplayer
 */

get_header(); ?>

<div class="container mxwm-proyecto-container" style="max-width: 800px; margin: 0 auto; padding: 2rem 1rem;">

<?php if (have_posts()) : ?>
    <?php while (have_posts()) : the_post(); ?>
<?php 
$author_id = get_the_author_meta('ID');
$author_pmp_level = mxwm_get_user_pmp_level($author_id);
?>

    <article id="proyecto-<?php the_ID(); ?>" <?php post_class('proyecto-single'); ?>>
        
        <!-- Breadcrumb -->
        <nav class="breadcrumb" style="margin-bottom: 2rem; font-size: 0.9rem; color: #666;">
            <a href="<?php echo home_url(); ?>" style="color: #007bff; text-decoration: none;">Inicio</a>
            <span style="margin: 0 0.5rem;">→</span>
            <a href="<?php echo get_post_type_archive_link('proyecto'); ?>" style="color: #007bff; text-decoration: none;">Proyectos</a>
            <span style="margin: 0 0.5rem;">→</span>
            <span><?php the_title(); ?></span>
        </nav>
<!-- BOTONES DE GESTIÓN DEL PROYECTO -->
<div class="proyecto-gestion" style="margin: 20px 0;">
    <?php 
    // Solo mostrar si el usuario actual es el autor o admin
    if (get_current_user_id() == $author_id || current_user_can('manage_options')) {
        echo do_shortcode('[mxwm_botones_gestion]');
    }
    ?>
</div>
        <!-- Header del Proyecto -->
        <header class="proyecto-header" style="margin-bottom: 2rem;">
            <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 2rem; flex-wrap: wrap;">
                <div style="flex: 1; min-width: 300px;">
                    <h1 style="font-size: 2.5rem; margin: 0 0 1rem; color: #333; line-height: 1.2;"><?php the_title(); ?></h1>
                    
                    <!-- Meta información -->
                    <div class="proyecto-meta" style="display: flex; flex-wrap: wrap; gap: 1rem; margin-bottom: 1rem; font-size: 0.9rem;">
                        <span style="background: #f0f0f0; padding: 0.3rem 0.8rem; border-radius: 15px;">
                            <strong>Autor:</strong> <?php echo get_the_author(); ?>
                        </span>
                        <span style="background: #f0f0f0; padding: 0.3rem 0.8rem; border-radius: 15px;">
                            <strong>Fecha:</strong> <?php echo get_the_date(); ?>
                        </span>
                        <?php if (get_field('ubicacion_proyecto')): ?>
                        <span style="background: #f0f0f0; padding: 0.3rem 0.8rem; border-radius: 15px;">
                            <strong>Ubicación:</strong> <?php the_field('ubicacion_proyecto'); ?>
                        </span>
                        <?php endif; ?>
                    </div>

                    <!-- Estados y categoría con paleta MXWM -->
                    <div class="proyecto-status" style="display: flex; flex-wrap: wrap; gap: 0.5rem; margin-bottom: 1rem;">
                        <?php 
                        $estado = get_field('estado_proyecto') ?: 'abierto';
                        $categoria = get_field('categoria_proyecto');
                        $presupuesto = get_field('presupuesto_proyecto');
                        
                        // Paleta MXWM: Verde, Amarillo Mostaza, Azul + matices
                        $estado_colors = array(
                            'abierto' => '#28a745',        // Verde MXWM
                            'en_progreso' => '#007bff',    // Azul MXWM  
                            'pausado' => '#d4a536',        // Amarillo Mostaza MXWM
                            'completado' => '#17a2b8',     // Azul claro
                            'cancelado' => '#6c757d',      // Gris neutro
                            'cerrado' => '#495057'         // Gris oscuro
                        );
                        $estado_color = isset($estado_colors[$estado]) ? $estado_colors[$estado] : '#28a745';
                        ?>
                        
                        <span class="estado-badge" style="background: <?php echo $estado_color; ?>; color: white; padding: 0.5rem 1rem; border-radius: 20px; font-weight: bold; text-transform: uppercase; font-size: 0.8rem; box-shadow: 0 2px 8px rgba(0,0,0,0.15);">
                            <?php echo str_replace('_', ' ', ucwords($estado)); ?>
                        </span>
                        
                        <?php if ($categoria): ?>
                        <span style="background: #007bff; color: white; padding: 0.5rem 1rem; border-radius: 20px; font-weight: bold; font-size: 0.8rem; box-shadow: 0 2px 8px rgba(0, 123, 255, 0.3);">
                            <?php echo ucfirst($categoria); ?>
                        </span>
                        <?php endif; ?>
                        
                        <?php if ($presupuesto): ?>
                        <span style="background: #d4a536; color: white; padding: 0.5rem 1rem; border-radius: 20px; font-weight: bold; font-size: 0.8rem; box-shadow: 0 2px 8px rgba(212, 165, 54, 0.3);">
                            <?php echo $presupuesto; ?>
                        </span>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Botones de acción -->
                <div class="proyecto-actions" style="display: flex; flex-direction: column; gap: 0.5rem; min-width: 200px;">
                    <?php if (function_exists('bp_is_active')): ?>
                    <?php 
                    $author_id = get_the_author_meta('ID');
                    $current_user_id = get_current_user_id();
                    ?>
                    
                    <?php if ($current_user_id && $current_user_id != $author_id): ?>
                        <a href="<?php echo bp_core_get_user_domain($author_id) . 'activity/'; ?>" 
                           class="btn-conectar"
                           style="background: #28a745; color: white; padding: 1rem 1.5rem; text-align: center; text-decoration: none; border-radius: 8px; font-weight: bold; transition: all 0.3s; box-shadow: 0 2px 8px rgba(40, 167, 69, 0.3);"
                           onmouseover="this.style.background='#218838'; this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 12px rgba(40, 167, 69, 0.4)'"
                           onmouseout="this.style.background='#28a745'; this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 8px rgba(40, 167, 69, 0.3)'">
                            💬 Conectar con <?php echo get_the_author_meta('first_name') ?: get_the_author(); ?>
                        </a>
                        
                        <a href="<?php echo wp_nonce_url(bp_loggedin_user_domain() . bp_get_messages_slug() . '/compose/?r=' . bp_core_get_username($author_id), 'messages_compose_screen'); ?>"
                           style="background: #007bff; color: white; padding: 0.8rem 1.5rem; text-align: center; text-decoration: none; border-radius: 8px; font-weight: bold; transition: all 0.3s; box-shadow: 0 2px 8px rgba(0, 123, 255, 0.3);"
                           onmouseover="this.style.background='#0056b3'; this.style.transform='translateY(-2px)'"
                           onmouseout="this.style.background='#007bff'; this.style.transform='translateY(0)'">
                            ✉️ Enviar Mensaje
                        </a>
                    <?php elseif ($current_user_id == $author_id): ?>
                        <!-- Solo el autor ve el botón de editar -->
                        <a href="<?php echo home_url('/editar-proyecto-frontend/?proyecto_id=' . get_the_ID()); ?>"
                           style="background: #d4a536; color: white; padding: 1rem 1.5rem; text-align: center; text-decoration: none; border-radius: 8px; font-weight: bold; transition: all 0.3s; box-shadow: 0 2px 8px rgba(212, 165, 54, 0.3);"
                           onmouseover="this.style.background='#b8941f'; this.style.transform='translateY(-2px)'"
                           onmouseout="this.style.background='#d4a536'; this.style.transform='translateY(0)'">
                            ✏️ Editar Mi Proyecto
                        </a>
                        <p style="text-align: center; color: #666; font-style: italic; margin-top: 1rem;">
                            Este es tu proyecto
                        </p>
                    <?php else: ?>
                        <p style="text-align: center; color: #666; font-style: italic;">
                            Inicia sesión para conectar con el autor
                        </p>
                    <?php endif; ?>
                    
                    <?php endif; ?>
                </div>
            </div>
        </header>

<!-- Imagen Principal ACF (Editable por el usuario) - CON GLIGHTBOX -->
<?php 
$imagen_principal = get_field('imagen_principal');
if ($imagen_principal): 
    $imagen_url = is_array($imagen_principal) ? $imagen_principal['url'] : $imagen_principal;
?>
<div class="proyecto-imagen-principal" style="margin-bottom: 2rem; text-align: center;">
    <a href="<?php echo esc_url($imagen_url); ?>" class="glightbox" data-gallery="proyecto-imagenes">
        <img src="<?php echo esc_url($imagen_url); ?>" 
             alt="<?php the_title(); ?>" 
             style="max-width: 100%; width: 100%; height: auto; aspect-ratio: 16/9; object-fit: cover; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); display: block; margin: 0 auto; cursor: pointer; transition: transform 0.3s ease;"
             onmouseover="this.style.transform='scale(1.02)'"
             onmouseout="this.style.transform='scale(1)'">
    </a>
</div>
<?php endif; ?>

        <!-- Contenido Principal -->
        <div class="proyecto-content" style="background: #fff; padding: 2rem; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 2rem;">
    <?php

// RENDERIZADO AUTOMÁTICO DE TODOS LOS CAMPOS ACF
$field_groups = acf_get_field_groups(array('post_type' => 'proyecto'));

if ($field_groups && function_exists('get_field')) {
    foreach ($field_groups as $group) {
        $fields = acf_get_fields($group['ID']);
        
        if ($fields) {
            echo '<div class="campos-acf-container" style="margin-top: 2rem;">';
            
            foreach ($fields as $field) {
                $field_name = $field['name'];
// Excluir campos de activación de grupo/foro (son solo para lógica interna)
$excluded_fields = ['activar_grupo', 'activar_foro', 'video'];
if (in_array($field_name, $excluded_fields)) {
    continue;
}
                $field_value = get_field($field_name, get_the_ID());
                
                // Solo mostrar campos que tengan valor
				$campos_excluir = array ('estado_grupo','estado_foro','imagen_principal');
                if (!empty($field_value) && !in_array($field_name, $campos_excluir)) {
                    
                    echo '<div class="campo-acf-item">';
                    
                    // Título del campo
                    echo '<h3 style="color: #333; margin: 0 0 1rem 0; font-size: 1.2rem; font-weight: 600;">' . esc_html($field['label']) . '</h3>';
                    
                    // Valor del campo según su tipo
                    echo '<div class="campo-valor" style="color: #555; line-height: 1.6;">';
                    
                    switch ($field['type']) {
                        case 'text':
                        case 'email':
                        case 'url':
                            echo '<p style="margin: 0; font-size: 1rem;">' . esc_html($field_value) . '</p>';
                            break;
                            
                        case 'textarea':
                            // Limpiar etiquetas HTML si las hay
                            $clean_text = strip_tags($field_value);
                            echo '<div style="white-space: pre-line; font-size: 1rem;">' . esc_html($clean_text) . '</div>';
                            break;
                            
                        case 'select':
                        case 'radio':
case 'select':
case 'radio':
    // Diferentes colores según el tipo de campo
case 'select':
    case 'radio':
        echo '<p style="margin: 0; font-size: 1rem; color: #374151;">' . esc_html($field_value) . '</p>';
        break;
                            
                        case 'image':
                            if (is_array($field_value)) {
                                $image_url = $field_value['url'];
                                $image_alt = $field_value['alt'] ?: $field['label'];
                            } else {
                                $image_url = wp_get_attachment_image_src($field_value, 'medium')[0];
                                $image_full_url = wp_get_attachment_image_src($field_value, 'full')[0];
                                $image_alt = $field['label'];
                            }
                            
                            if ($image_url) {
                                echo '<div style="text-align: center; margin: 1rem 0;">';
                                
                                // Verificar si es una de las 5 imágenes de galería
                                $galeria_fields = ['galeria_foto_1', 'galeria_foto_2', 'galeria_foto_3', 'galeria_foto_4', 'galeria_foto_5'];
                                if (in_array($field_name, $galeria_fields)) {
                                    // Con lightbox para imágenes de galería
                                    $full_url = $image_full_url ?: $image_url;
                                    echo '<a href="' . esc_url($full_url) . '" class="glightbox" data-gallery="proyecto-imagenes">';
                                    echo '<img src="' . esc_url($image_url) . '" alt="' . esc_attr($image_alt) . '" style="max-width: 100%; height: auto; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); cursor: pointer; transition: transform 0.3s ease;" onmouseover="this.style.transform=\'scale(1.02)\'" onmouseout="this.style.transform=\'scale(1)\'">';
                                    echo '</a>';
                                } else {
                                    // Sin lightbox para otras imágenes
                                    echo '<img src="' . esc_url($image_url) . '" alt="' . esc_attr($image_alt) . '" style="max-width: 100%; height: auto; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">';
                                }
                                
                                echo '</div>';
                            }
                            break;
                            
                        case 'gallery':
                            if (is_array($field_value)) {
                                echo '<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 1rem; margin-top: 1rem;">';
                                foreach ($field_value as $image) {
                                    $img_url = is_array($image) ? $image['sizes']['medium'] : wp_get_attachment_image_src($image, 'medium')[0];
                                    if ($img_url) {
                                        echo '<img src="' . esc_url($img_url) . '" style="width: 100%; height: 150px; object-fit: cover; border-radius: 8px;">';
                                    }
                                }
                                echo '</div>';
                            }
                            break;
                            
                        case 'wysiwyg':
                            // Limpiar HTML innecesario pero mantener formato básico
                            $clean_content = wp_kses($field_value, array(
                                'p' => array(),
                                'br' => array(),
                                'strong' => array(),
                                'em' => array(),
                                'ul' => array(),
                                'ol' => array(),
                                'li' => array()
                            ));
                            echo '<div style="font-size: 1rem;">' . $clean_content . '</div>';
                            break;
                            
                        case 'number':
                            echo '<p style="margin: 0; font-size: 1.1rem; font-weight: 500; color: #28a745;">' . esc_html($field_value) . '</p>';
                            break;
                            
                        case 'date_picker':
                            $formatted_date = date('j F Y', strtotime($field_value));
                            echo '<p style="margin: 0; font-size: 1rem;">' . esc_html($formatted_date) . '</p>';
                            break;
                            
                        default:
                            // Para cualquier tipo de campo no contemplado
                            if (is_string($field_value)) {
                                echo '<p style="margin: 0; font-size: 1rem;">' . esc_html($field_value) . '</p>';
                            } elseif (is_array($field_value)) {
                                echo '<ul style="margin: 0; padding-left: 1.5rem;">';
                                foreach ($field_value as $item) {
                                    echo '<li style="margin-bottom: 0.5rem;">' . esc_html(is_string($item) ? $item : print_r($item, true)) . '</li>';
                                }
                                echo '</ul>';
                            }
                            break;
                    }
                    
                    echo '</div>'; // .campo-valor
                    echo '</div>'; // .campo-acf-item
                }
            }
            
            echo '</div>'; // .campos-acf-container
        }
    }
}
?>
        </div>

        <!-- Galería de Imágenes Premium - CON GLIGHTBOX -->
        <?php 
        $galeria = get_field('galeria_proyecto');
        $user_pmp_level = function_exists('mxwm_get_user_pmp_level') ? mxwm_get_user_pmp_level() : false;
        
        if ($galeria && is_array($galeria) && count($galeria) > 0): 
            if ($user_pmp_level && $user_pmp_level >= 2): 
        ?>
        <div class="proyecto-galeria" style="background: #fff; padding: 2rem; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 2rem;">
            <h2 style="color: #333; margin-bottom: 1rem;">📸 Galería del Proyecto</h2>
            
            <div class="galeria-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                <?php foreach ($galeria as $imagen): ?>
                    <?php 
                    $img_id = is_array($imagen) ? $imagen['ID'] : $imagen;
                    $img_url = wp_get_attachment_image_src($img_id, 'medium');
                    $img_full = wp_get_attachment_image_src($img_id, 'full');
                    ?>
                    <?php if ($img_url): ?>
                    <div class="galeria-item" style="position: relative; overflow: hidden; border-radius: 8px; aspect-ratio: 1;">
                        <a href="<?php echo $img_full[0]; ?>" class="glightbox" data-gallery="proyecto-imagenes">
                            <img src="<?php echo $img_url[0]; ?>" 
                                 alt="Imagen del proyecto" 
                                 style="width: 100%; height: 100%; object-fit: cover; transition: transform 0.3s ease; cursor: pointer;"
                                 onmouseover="this.style.transform='scale(1.05)'"
                                 onmouseout="this.style.transform='scale(1)'">
                        </a>
                    </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>
        <?php else: ?>
        <!-- Mensaje premium para usuarios sin acceso a galería -->
        <div class="proyecto-galeria-premium" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 2rem; border-radius: 10px; margin-bottom: 2rem; text-align: center;">
            <div style="font-size: 3rem; margin-bottom: 1rem;">🔒</div>
            <h3 style="color: white; margin-bottom: 1rem;">Galería Premium</h3>
            <p style="opacity: 0.9; margin-bottom: 1.5rem;">Este proyecto incluye una galería de imágenes exclusiva para miembros PMP Pro+</p>
            <a href="#upgrade" style="background: rgba(255,255,255,0.2); color: white; padding: 1rem 2rem; border-radius: 25px; text-decoration: none; font-weight: bold; backdrop-filter: blur(10px);">Upgrade para Ver Galería →</a>
        </div>
        <?php endif; ?>
        <?php endif; ?>

<!-- Video del Proyecto (solo nivel 3+) - CON PLYR VIDEOPLAYER -->
        <?php 
		$video = get_field('video');
		if ($video && in_array($author_pmp_level, ['3', '4', '5'])):
        ?>
        <div class="proyecto-video" style="background: #fff; padding: 2rem; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 2rem;">
            <!-- <h2 style="color: #333; margin-bottom: 1rem;">🎥 Video del Proyecto</h2> -->
            <div class="plyr-video-wrapper">
                <?php 
                // Detectar tipo de video (YouTube o Vimeo)
                $video_id = '';
                $video_provider = '';
                
                if (strpos($video, 'youtube.com') !== false || strpos($video, 'youtu.be') !== false) {
                    // Extraer ID de YouTube (soporta múltiples formatos)
                    if (preg_match('/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/i', $video, $match)) {
                        $video_id = $match[1];
                        $video_provider = 'youtube';
                    }
                } elseif (strpos($video, 'vimeo.com') !== false) {
                    // Extraer ID de Vimeo
                    if (preg_match('/vimeo\.com\/(\d+)/i', $video, $match)) {
                        $video_id = $match[1];
                        $video_provider = 'vimeo';
                    }
                }
                
                if ($video_id && $video_provider):
                ?>
                    <div id="player-<?php echo get_the_ID(); ?>" data-plyr-provider="<?php echo $video_provider; ?>" data-plyr-embed-id="<?php echo $video_id; ?>"></div>
                <?php else: ?>
                    <p style="text-align: center; color: #666;">No se pudo cargar el video. Por favor verifica la URL.</p>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
                        
<!-- Grupos y Foros (nivel 3+ y 4) - MENSAJES DINÁMICOS CORREGIDOS -->
<?php 
$activar_grupo = get_field('activar_grupo');
$activar_foro = get_field('activar_foro');

// Obtener IDs de grupo y foro asociados
$grupo_id = get_post_meta(get_the_ID(), '_mxwm_grupo_id', true);
$foro_id = get_post_meta(get_the_ID(), '_mxwm_foro_id', true);

// Obtener estado de privacidad (IMPORTANTE: usar guión bajo)
$estado_grupo = get_field('estado_grupo');
$estado_foro = get_field('estado_foro');

// Mostrar solo si están activados Y existen
if (($activar_grupo && $grupo_id) || ($activar_foro && $foro_id)): 
?>
<div style="background: #f8f9fa; border-left: 4px solid #3854F2; padding: 1.5rem; border-radius: 8px; margin-bottom: 2rem;">
    
    <?php if ($activar_grupo && $grupo_id && function_exists('groups_get_group')): ?>
        <?php 
        $grupo = groups_get_group($grupo_id);
        if ($grupo && in_array($author_pmp_level, ['3', '4', '5'])) {
            $grupo_url = bp_get_group_permalink($grupo);
            
            // LÓGICA DE MENSAJES SEGÚN PRIVACIDAD
            // Oculto = NO mostrar nada
            // Privado = Mensaje con "clave de acceso"
            // Público = Mensaje normal
            
            if ($estado_grupo === 'oculto') {
                // NO MOSTRAR NADA para grupos ocultos
            } elseif ($estado_grupo === 'privado') {
                // Mensaje especial para privados
                ?>
                <div style="margin-bottom: <?php echo ($activar_foro && $foro_id) ? '1rem' : '0'; ?>;">
                    <span style="color: #555; font-size: 1rem;">👥 Este proyecto tiene un </span>
                    <a href="<?php echo esc_url($grupo_url); ?>" style="color: #7569BF; font-weight: bold; text-decoration: none;">Grupo Privado</a>
                    <span style="color: #555;"> para colaborar, si tienes clave de acceso </span>
                    <a href="<?php echo esc_url($grupo_url); ?>" style="color: #7569BF; font-weight: bold; text-decoration: none;">accede aquí</a>
                    <span style="color: #555;">.</span>
                </div>
                <?php
            } else {
                // Mensaje normal para públicos
                ?>
                <div style="margin-bottom: <?php echo ($activar_foro && $foro_id) ? '1rem' : '0'; ?>;">
                    <span style="color: #555; font-size: 1rem;">👥 Este proyecto tiene un </span>
                    <a href="<?php echo esc_url($grupo_url); ?>" style="color: #7569BF; font-weight: bold; text-decoration: none;">Grupo Público</a>
                    <span style="color: #555;"> para colaborar.</span>
                </div>
                <?php
            }
        }
        ?>
    <?php endif; ?>
    
    <?php if ($activar_foro && $foro_id && in_array($author_pmp_level, ['4', '5'])): ?>
        <?php
        // Obtener información del foro
        $foro_slug = get_post_field('post_name', $foro_id);
        $foro_url = home_url('/forums/forum/' . $foro_slug);
        
        // Lógica similar para foros si fuera necesario
        // Por ahora, mensaje estándar
        ?>
        <div>
            <span style="color: #555; font-size: 1rem;">💬 Este proyecto tiene un </span>
            <a href="<?php echo esc_url($foro_url); ?>" style="color: #F2B84B; font-weight: bold; text-decoration: none;">Foro Especializado</a>
            <span style="color: #555;"> para interactuar.</span>
        </div>
    <?php endif; ?>
    
</div>
<?php endif; ?>

        <!-- Información Adicional -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 2rem; margin-bottom: 2rem;">
            
            <!-- Detalles del Proyecto -->
            <div class="proyecto-detalles" style="background: #f8f9fa; padding: 1.5rem; border-radius: 10px;">
                <h3 style="color: #333; margin-bottom: 1rem;">Detalles</h3>
                <ul style="list-style: none; padding: 0; margin: 0;">
                    <li style="margin-bottom: 0.5rem; display: flex; justify-content: space-between;">
                        <strong>Estado:</strong>
                        <span style="color: <?php echo $estado_color; ?>; font-weight: bold;">
                            <?php echo str_replace('_', ' ', ucwords($estado)); ?>
                        </span>
                    </li>
                    <?php if ($categoria): ?>
                    <li style="margin-bottom: 0.5rem; display: flex; justify-content: space-between;">
                        <strong>Categoría:</strong>
                        <span><?php echo ucfirst($categoria); ?></span>
                    </li>
                    <?php endif; ?>
                    <?php if ($presupuesto): ?>
                    <li style="margin-bottom: 0.5rem; display: flex; justify-content: space-between;">
                        <strong>Presupuesto:</strong>
                        <span><?php echo $presupuesto; ?></span>
                    </li>
                    <?php endif; ?>
                    <li style="margin-bottom: 0.5rem; display: flex; justify-content: space-between;">
                        <strong>Publicado:</strong>
                        <span><?php echo get_the_date('j M Y'); ?></span>
                    </li>
                </ul>
            </div>

            <!-- Información del Autor -->
            <div class="autor-info" style="background: #f8f9fa; padding: 1.5rem; border-radius: 10px;">
                <h3 style="color: #333; margin-bottom: 1rem;">Sobre el Autor</h3>
                <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1rem;">
                    <?php echo get_avatar($author_id, 60, '', '', array('style' => 'border-radius: 50%;')); ?>
                    <div>
                        <h4 style="margin: 0; color: #333;"><?php echo get_the_author_meta('display_name'); ?></h4>
                        <?php if (function_exists('bp_is_active')): ?>
                        <a href="<?php echo bp_core_get_user_domain($author_id); ?>" 
                           style="color: #007bff; text-decoration: none; font-size: 0.9rem;">
                            Ver perfil completo →
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php if (get_the_author_meta('description')): ?>
                <p style="margin: 0; color: #666; font-size: 0.9rem;">
                    <?php echo wp_trim_words(get_the_author_meta('description'), 20); ?>
                </p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Navegación Anterior/Siguiente -->
        <nav class="proyecto-navigation" style="display: flex; justify-content: space-between; align-items: center; margin: 2rem 0; padding: 1rem; background: #f8f9fa; border-radius: 10px;">
            <?php
            $prev_post = get_previous_post();
            $next_post = get_next_post();
            ?>
            
            <div class="nav-previous">
                <?php if ($prev_post): ?>
                <a href="<?php echo get_permalink($prev_post->ID); ?>" 
                   style="color: #007bff; text-decoration: none; display: flex; align-items: center; gap: 0.5rem;">
                    <span style="font-size: 1.5rem;">←</span>
                    <div>
                        <div style="font-size: 0.8rem; color: #666;">Anterior</div>
                    </div>
                </a>
                <?php endif; ?>
            </div>

            <div class="nav-center">
                <a href="<?php echo get_post_type_archive_link('proyecto'); ?>" 
                   style="background: #007bff; color: white; padding: 0.5rem 1rem; border-radius: 5px; text-decoration: none; font-size: 0.9rem;">
                    Ver todos los proyectos
                </a>
            </div>

            <div class="nav-next">
                <?php if ($next_post): ?>
                <a href="<?php echo get_permalink($next_post->ID); ?>" 
                   style="color: #007bff; text-decoration: none; display: flex; align-items: center; gap: 0.5rem; text-align: right;">
                    <div>
                        <div style="font-size: 0.8rem; color: #666;">Siguiente</div>
                    </div>
                    <span style="font-size: 1.5rem;">→</span>
                </a>
                <?php endif; ?>
            </div>
        </nav>

    </article>

<!-- Comentarios (solo nivel 1 y 2) -->
    <?php 
    $author_id = get_the_author_meta('ID');
  $author_pmp_level = mxwm_get_user_pmp_level($author_id);
    
    // Solo mostrar comentarios si el autor es nivel 1 o 2
    if (in_array($author_pmp_level, ['1', '2']) && (comments_open() || get_comments_number())): 
    ?>
    <div class="proyecto-comments" style="background: #fff; padding: 2rem; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-top: 2rem;">
        <h3 style="margin-bottom: 1.5rem; color: #333;">💬 Comentarios</h3>
        <?php comments_template(); ?>
    </div>
    <?php endif; ?>
    <?php endwhile; ?>

<?php else: ?>

    <div style="text-align: center; padding: 3rem; background: #f8f9fa; border-radius: 10px;">
        <h2>Proyecto no encontrado</h2>
        <p>Lo sentimos, no pudimos encontrar el proyecto que buscas.</p>
        <a href="<?php echo get_post_type_archive_link('proyecto'); ?>" 
           style="background: #007bff; color: white; padding: 1rem 2rem; text-decoration: none; border-radius: 5px; display: inline-block; margin-top: 1rem;">
            Ver todos los proyectos
        </a>
    </div>

<?php endif; ?>

</div>

<!-- GLightbox CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/glightbox@3.2.0/dist/css/glightbox.min.css" />

<!-- Plyr CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/plyr@3.7.8/dist/plyr.css" />

<style>
/* Reducir márgenes laterales en móvil para más espacio de contenido */
@media (max-width: 768px) {
    .mxwm-proyecto-container {
        padding-left: 0.5rem !important;
        padding-right: 0.5rem !important;
    }
    
    .proyecto-content {
        padding: 1rem 0.5rem !important;
    }
    
    .proyecto-video {
        padding: 1rem 0.5rem !important;
    }
}

/* Estilos base de campos ACF */
.proyecto-content .campo-acf-item {
    margin-bottom: 2rem;
    padding: 1.5rem;
    background: #f8f9fa;
    border-radius: 10px;
    border-left: 4px solid #F2B84B;
}

/* Padding mínimo en móvil para maximizar espacio de contenido */
@media (max-width: 768px) {
    .proyecto-content .campos-acf-container .campo-acf-item {
        padding: 0.75rem !important;
    }
    
    .proyecto-video {
        padding: 1rem !important;
    }
}
    
    .proyecto-header > div {
        flex-direction: column;
    }
    
    .proyecto-actions {
        width: 100%;
        min-width: auto;
    }
    
    .proyecto-navigation {
        flex-direction: column;
        gap: 1rem;
        text-align: center;
    }
    
    .nav-previous, .nav-next {
        width: 100%;
    }
    
    .galeria-grid {
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)) !important;
    }
}

@media (max-width: 480px) {
    .proyecto-meta, .proyecto-status {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .galeria-grid {
        grid-template-columns: 1fr 1fr !important;
    }
}

/* GLightbox Customization */
.glightbox-clean .gslide-description {
    background: rgba(0, 0, 0, 0.8);
}

.glightbox-clean .gdesc-inner {
    color: #fff;
}

/* Plyr Video Player Customization */
.plyr-video-wrapper {
    position: relative;
    width: 100%;
    aspect-ratio: 16 / 9;
    border-radius: 8px;
    overflow: hidden;
    background: #000;
}

.plyr {
    border-radius: 8px;
    overflow: hidden;
    width: 100%;
    height: 100%;
}

.plyr__video-embed {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
}

/* Ocultar logo de YouTube/Vimeo */
.plyr__video-embed iframe {
    pointer-events: none;
}

.plyr--playing .plyr__video-embed iframe {
    pointer-events: auto;
}

/* Estilos personalizados de Plyr */
.plyr--full-ui input[type=range] {
    color: #007bff;
}

.plyr__control--overlaid {
    background: rgba(0, 123, 255, 0.8);
}

.plyr__control--overlaid:hover {
    background: #007bff;
}

.plyr__menu__container .plyr__control[role=menuitemradio][aria-checked=true]::before {
    background: #007bff;
}

/* Cursor pointer para imágenes con lightbox */
.glightbox img {
    cursor: pointer;
}
</style>

<!-- GLightbox JavaScript -->
<script src="https://cdn.jsdelivr.net/npm/glightbox@3.2.0/dist/js/glightbox.min.js"></script>

<!-- Plyr JavaScript -->
<script src="https://cdn.jsdelivr.net/npm/plyr@3.7.8/dist/plyr.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    
    // ========================================
    // Inicializar GLightbox para las imágenes
    // ========================================
    const lightbox = GLightbox({
        touchNavigation: true,
        loop: true,
        autoplayVideos: false,
        closeButton: true,
        closeOnOutsideClick: true,
        openEffect: 'zoom',
        closeEffect: 'fade',
        slideEffect: 'slide',
        moreLength: 0,
        skin: 'clean',
        videosWidth: '90%',
        descPosition: 'bottom'
    });
    
    console.log('GLightbox inicializado correctamente');
    
    // ========================================
    // Inicializar Plyr para el video
    // ========================================
    const playerElement = document.querySelector('[id^="player-"]');
    
    if (playerElement) {
        console.log('Elemento de video encontrado:', playerElement);
        console.log('Provider:', playerElement.dataset.plyrProvider);
        console.log('Video ID:', playerElement.dataset.plyrEmbedId);
        
        const player = new Plyr(playerElement, {
            controls: [
                'play-large',
                'play',
                'progress',
                'current-time',
                'duration',
                'mute',
                'volume',
                'settings',
                'fullscreen'
            ],
            settings: ['quality', 'speed'],
            youtube: {
                noCookie: true,
                rel: 0,
                showinfo: 0,
                iv_load_policy: 3,
                modestbranding: 1
            },
            vimeo: {
                byline: false,
                portrait: false,
                title: false,
                speed: true,
                transparent: false
            },
            ratio: '16:9',
            clickToPlay: true,
            hideControls: true,
            resetOnEnd: false,
            keyboard: { 
                focused: true, 
                global: false 
            },
            loadSprite: true,
            iconUrl: 'https://cdn.jsdelivr.net/npm/plyr@3.7.8/dist/plyr.svg'
        });

        player.on('ready', function() {
            console.log('Plyr video listo');
            console.log('Plyr instance:', player);
        });

        player.on('error', function(event) {
            console.error('Error en Plyr:', event);
        });
    } else {
        console.log('No se encontró elemento de video en esta página');
    }
});
</script>

<?php get_footer(); ?>