<?php
/**
 * Archive Template: Proyectos MXwithME - VERSIÓN CORREGIDA Y LIMPIA
 * Fixes: Paginación 404 + Filtros Responsive + Limpieza de parámetros
 */

get_header();

// LIMPIAR PARÁMETROS VACÍOS Y REDIRIGIR SI ES NECESARIO
if (isset($_GET['tipo_de_proyecto']) || isset($_GET['ubicacion'])) {
    $clean_params = array();
    $needs_redirect = false;
    
    // Limpiar tipo_de_proyecto
    if (isset($_GET['tipo_de_proyecto'])) {
        if (!empty($_GET['tipo_de_proyecto'])) {
            $clean_params['tipo_de_proyecto'] = sanitize_text_field($_GET['tipo_de_proyecto']);
        } else {
            $needs_redirect = true;
        }
    }
    
    // Limpiar ubicacion
    if (isset($_GET['ubicacion'])) {
        if (!empty($_GET['ubicacion'])) {
            $clean_params['ubicacion'] = sanitize_text_field($_GET['ubicacion']);
        } else {
            $needs_redirect = true;
        }
    }
    
    // Si hay parámetros vacíos o la cantidad no coincide, redirigir
    if ($needs_redirect || empty($clean_params)) {
        if (empty($clean_params)) {
            // Ambos vacíos, ir a la página base
            wp_redirect(get_post_type_archive_link('proyecto'), 301);
            exit;
        } else {
            // Redirigir con solo los parámetros que tienen valor
            wp_redirect(add_query_arg($clean_params, get_post_type_archive_link('proyecto')), 301);
            exit;
        }
    }
}

$user_logged_in = is_user_logged_in();
?>

<!-- CSS COMPLETO Y CORREGIDO -->
<style>
body {
    overflow-x: hidden !important;
}

.mxwithme-proyectos-archive {
    max-width: 1200px !important;
    margin: 0 auto !important;
    padding: 2rem 1rem !important;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif !important;
    min-height: calc(100vh - 200px) !important;
}

.projects-grid-new {
    display: grid !important;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)) !important;
    gap: 1.5rem !important;
    margin: 2rem 0 !important;
}

.project-card-new {
    background: white !important;
    border: 1px solid #e5e7eb !important;
    border-radius: 12px !important;
    overflow: hidden !important;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1) !important;
    transition: transform 0.2s, box-shadow 0.2s !important;
}

.project-card-new:hover {
    transform: translateY(-2px) !important;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15) !important;
}

.project-image-new {
    width: 100% !important;
    height: 160px !important;
    background: linear-gradient(135deg, #fef3c7, #fed7aa) !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    position: relative !important;
    overflow: hidden !important;
}

.project-image-new img {
    width: 100% !important;
    height: 100% !important;
    object-fit: cover !important;
    object-position: center !important;
    display: block !important;
}

.project-content-new {
    padding: 1rem !important;
}

.project-title-new {
    font-size: 1.1rem !important;
    font-weight: 600 !important;
    color: #1f2937 !important;
    margin: 0 0 0.5rem 0 !important;
    line-height: 1.4 !important;
}

.project-title-new a {
    color: inherit !important;
    text-decoration: none !important;
}

.project-title-new a:hover {
    color: #d97706 !important;
}

.project-meta-new {
    display: flex !important;
    align-items: center !important;
    gap: 0.75rem !important;
    margin-bottom: 0.75rem !important;
    font-size: 0.75rem !important;
    color: #374151 !important;
}

.status-dot {
    width: 8px !important;
    height: 8px !important;
    border-radius: 50% !important;
    margin-right: 4px !important;
}

.status-activo { background-color: #10b981 !important; }
.status-desarrollo { background-color: #f59e0b !important; }
.status-idea { background-color: #6b7280 !important; }
.status-consolidado { background-color: #3b82f6 !important; }

.project-desc-new {
    font-size: 0.875rem !important;
    color: #374151 !important;
    line-height: 1.4 !important;
    margin-bottom: 1rem !important;
    display: -webkit-box !important;
    -webkit-line-clamp: 2 !important;
    -webkit-box-orient: vertical !important;
    overflow: hidden !important;
}

.project-footer-new {
    display: flex !important;
    justify-content: space-between !important;
    align-items: center !important;
    padding-top: 0.75rem !important;
    border-top: 1px solid #e5e7eb !important;
}

.author-info-new {
    display: flex !important;
    align-items: center !important;
    gap: 0.5rem !important;
}

.author-info-new img {
    width: 24px !important;
    height: 24px !important;
    border-radius: 50% !important;
}

.author-name-new {
    font-size: 0.75rem !important;
    color: #6b7280 !important;
}

.btn-ver-new {
    background: #d97706 !important;
    color: white !important;
    padding: 0.5rem 1rem !important;
    border-radius: 6px !important;
    text-decoration: none !important;
    font-size: 0.875rem !important;
    font-weight: 500 !important;
    transition: background 0.2s !important;
    border: none !important;
    cursor: pointer !important;
    display: inline-block !important;
}

.btn-ver-new:hover {
    background: #b45309 !important;
    color: white !important;
}

.btn-login-fixed {
    background: #6b7280 !important;
    color: white !important;
    padding: 0.5rem 1rem !important;
    border-radius: 6px !important;
    text-decoration: none !important;
    font-size: 0.875rem !important;
    font-weight: 500 !important;
    border: none !important;
    cursor: pointer !important;
    display: inline-block !important;
    transition: background 0.2s !important;
}

.btn-login-fixed:hover {
    background: #4b5563 !important;
    color: white !important;
    text-decoration: none !important;
}

.header-new {
    text-align: center !important;
    margin-bottom: 3rem !important;
}

.header-new h1 {
    font-size: 2.5rem !important;
    font-weight: bold !important;
    color: #1f2937 !important;
    margin: 0 0 1rem 0 !important;
}

.header-new p {
    font-size: 1.125rem !important;
    color: #6b7280 !important;
    max-width: 600px !important;
    margin: 0 auto 2rem auto !important;
    line-height: 1.6 !important;
}

/* ========================================
   FILTROS RESPONSIVE
   ======================================== */

.filtros-container {
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    padding: 1.5rem;
    margin-bottom: 2rem;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.filtros-responsive {
    display: grid;
    grid-template-columns: 1fr 1fr auto;
    gap: 1rem;
    align-items: end;
}

.filter-group {
    display: flex;
    flex-direction: column;
}

.filter-label {
    display: block;
    font-size: 0.875rem;
    font-weight: 500;
    color: #374151;
    margin-bottom: 0.5rem;
}

.select-custom {
    width: 100% !important;
    height: 50px !important;
    padding: 0 45px 0 15px !important;
    border: 2px solid #d1d5db !important;
    border-radius: 6px !important;
    font-size: 14px !important;
    background: white !important;
    color: #374151 !important;
    appearance: none !important;
    background-image: url('data:image/svg+xml;charset=US-ASCII,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="gray"><path d="M7 10l5 5 5-5z"/></svg>') !important;
    background-repeat: no-repeat !important;
    background-position: right 15px center !important;
    background-size: 16px !important;
    line-height: 50px !important;
    vertical-align: middle !important;
    display: flex !important;
    align-items: center !important;
    box-sizing: border-box !important;
    overflow: hidden !important;
    text-overflow: ellipsis !important;
    white-space: nowrap !important;
}

.select-custom:focus {
    outline: none !important;
    border-color: #F2B84B !important;
    box-shadow: 0 0 0 3px rgba(242, 184, 75, 0.2) !important;
}

.select-custom option {
    padding: 8px 15px !important;
    line-height: 1.4 !important;
    color: #374151 !important;
    background: white !important;
}

.button-group {
    display: flex;
    gap: 0.5rem;
    align-items: flex-end;
}

.btn-filtrar {
    background: #d97706;
    color: white;
    padding: 0.75rem 1.5rem;
    border: none;
    border-radius: 8px;
    font-size: 0.875rem;
    font-weight: 500;
    cursor: pointer;
    white-space: nowrap;
    transition: background 0.2s;
}

.btn-filtrar:hover {
    background: #b45309;
}

.btn-limpiar {
    background: #6b7280;
    color: white;
    padding: 0.75rem 1rem;
    border-radius: 8px;
    font-size: 0.875rem;
    text-decoration: none;
    display: flex;
    align-items: center;
    white-space: nowrap;
    transition: background 0.2s;
}

.btn-limpiar:hover {
    background: #4b5563;
}

/* ========================================
   PAGINACIÓN
   ======================================== */

.pagination-wrapper {
    text-align: center;
    margin-top: 3rem;
}

.page-numbers {
    display: inline-flex;
    gap: 0.5rem;
    align-items: center;
    flex-wrap: wrap;
    justify-content: center;
}

.page-numbers a {
    background: white !important;
    color: #374151 !important;
    padding: 0.5rem 1rem !important;
    border: 1px solid #e5e7eb !important;
    border-radius: 6px !important;
    text-decoration: none !important;
    transition: all 0.2s !important;
}

.page-numbers a:hover {
    background: #F2B84B !important;
    color: white !important;
    border-color: #F2B84B !important;
}

.page-numbers .current {
    background: #F2B84B !important;
    color: white !important;
    padding: 0.5rem 1rem !important;
    border: 1px solid #F2B84B !important;
    border-radius: 6px !important;
    font-weight: 600 !important;
}

/* ========================================
   RESPONSIVE BREAKPOINTS
   ======================================== */

/* Tablets (768px - 1024px) */
@media (max-width: 1024px) {
    .filtros-responsive {
        grid-template-columns: 1fr 1fr;
        gap: 1rem;
    }
    
    .button-group {
        grid-column: 1 / -1;
        justify-content: center;
    }
}

/* Móviles (hasta 768px) */
@media (max-width: 768px) {
    .mxwithme-proyectos-archive {
        padding: 1.5rem 1rem !important;
    }
    
    .header-new h1 {
        font-size: 1.75rem !important;
    }
    
    .header-new p {
        font-size: 1rem !important;
    }
    
    .filtros-container {
        padding: 1rem;
    }
    
    .filtros-responsive {
        grid-template-columns: 1fr;
        gap: 1rem;
    }
    
    .select-custom {
        font-size: 16px !important;
        height: 48px !important;
        line-height: 48px !important;
    }
    
    .button-group {
        grid-column: 1;
        flex-direction: column;
        width: 100%;
    }
    
    .btn-filtrar,
    .btn-limpiar {
        width: 100%;
        justify-content: center;
        padding: 0.875rem 1rem;
    }
    
    .projects-grid-new {
        grid-template-columns: 1fr !important;
        gap: 1rem !important;
    }
    
    .page-numbers {
        gap: 0.25rem;
    }
    
    .page-numbers a,
    .page-numbers .current {
        padding: 0.4rem 0.75rem !important;
        font-size: 0.875rem;
    }
}

/* Móviles pequeños (hasta 480px) */
@media (max-width: 480px) {
    .header-new h1 {
        font-size: 1.5rem !important;
    }
    
    .filtros-container {
        padding: 0.75rem;
    }
    
    .filter-label {
        font-size: 0.8rem;
    }
}
</style>

<script>
// Prevenir caché del navegador en los selectores
document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    const tipoSelect = document.querySelector('select[name="tipo_de_proyecto"]');
    const ubicacionSelect = document.querySelector('select[name="ubicacion"]');
    const form = document.querySelector('.filtros-responsive');
    
    if (tipoSelect && ubicacionSelect) {
        // Si no hay parámetros en la URL, resetear los selectores
        if (!urlParams.has('tipo_de_proyecto') && !urlParams.has('ubicacion')) {
            tipoSelect.value = '';
            ubicacionSelect.value = '';
        }
        
        // Sincronizar selectores con la URL actual
        if (urlParams.has('tipo_de_proyecto')) {
            tipoSelect.value = urlParams.get('tipo_de_proyecto');
        }
        if (urlParams.has('ubicacion')) {
            ubicacionSelect.value = urlParams.get('ubicacion');
        }
    }
    
    // IMPORTANTE: Al cambiar filtros, resetear a página 1
    if (form) {
        form.addEventListener('submit', function(e) {
            // Eliminar cualquier referencia a paginación en la URL
            const currentUrl = new URL(window.location.href);
            currentUrl.searchParams.delete('paged');
            
            // Limpiar el path de /page/X/
            let cleanPath = currentUrl.pathname.replace(/\/page\/\d+\/?/, '');
            
            // Construir nueva URL limpia
            const baseUrl = window.location.origin + cleanPath;
            form.action = baseUrl;
        });
    }
});
</script>

<div class="mxwithme-proyectos-archive">

    <!-- Header -->
    <div class="header-new">
        <h1>Proyectos con Propósito</h1>
        <p>
            Conecta con iniciativas auténticas que están transformando México. 
            Encuentra tu match perfecto basado en valores compartidos y propósito común.
        </p>
    </div>

    <!-- FILTROS RESPONSIVE -->
    <div class="filtros-container">
        <form method="GET" class="filtros-responsive">
            
            <div class="filter-group">
                <label class="filter-label">Tipo de Proyecto</label>
                <select name="tipo_de_proyecto" class="select-custom">
                    <option value="">Todos los tipos</option>
                    <option value="Bienestar" <?php selected($_GET['tipo_de_proyecto'] ?? '', 'Bienestar'); ?>>Bienestar</option>
                    <option value="Cultura" <?php selected($_GET['tipo_de_proyecto'] ?? '', 'Cultura'); ?>>Cultura</option>
                    <option value="Naturaleza" <?php selected($_GET['tipo_de_proyecto'] ?? '', 'Naturaleza'); ?>>Naturaleza</option>
                    <option value="Sustentabilidad" <?php selected($_GET['tipo_de_proyecto'] ?? '', 'Sustentabilidad'); ?>>Sustentabilidad</option>
                    <option value="Espiritualidad" <?php selected($_GET['tipo_de_proyecto'] ?? '', 'Espiritualidad'); ?>>Espiritualidad</option>
                    <option value="Emprendimiento / Negocio" <?php selected($_GET['tipo_de_proyecto'] ?? '', 'Emprendimiento / Negocio'); ?>>Emprendimiento</option>
                    <option value="Comunidad / Voluntariado" <?php selected($_GET['tipo_de_proyecto'] ?? '', 'Comunidad / Voluntariado'); ?>>Comunidad</option>
                </select>
            </div>

            <div class="filter-group">
                <label class="filter-label">Ubicación</label>
                <select name="ubicacion" class="select-custom">
                    <option value="">Todas las ubicaciones</option>
                    <option value="En línea (online)" <?php selected($_GET['ubicacion'] ?? '', 'En línea (online)'); ?>>En línea (online)</option>
                    <option value="Aguascalientes" <?php selected($_GET['ubicacion'] ?? '', 'Aguascalientes'); ?>>Aguascalientes</option>
                    <option value="Baja California" <?php selected($_GET['ubicacion'] ?? '', 'Baja California'); ?>>Baja California</option>
                    <option value="Baja California Sur" <?php selected($_GET['ubicacion'] ?? '', 'Baja California Sur'); ?>>Baja California Sur</option>
                    <option value="Campeche" <?php selected($_GET['ubicacion'] ?? '', 'Campeche'); ?>>Campeche</option>
                    <option value="Chiapas" <?php selected($_GET['ubicacion'] ?? '', 'Chiapas'); ?>>Chiapas</option>
                    <option value="Chihuahua" <?php selected($_GET['ubicacion'] ?? '', 'Chihuahua'); ?>>Chihuahua</option>
                    <option value="Ciudad de México" <?php selected($_GET['ubicacion'] ?? '', 'Ciudad de México'); ?>>Ciudad de México</option>
                    <option value="Coahuila" <?php selected($_GET['ubicacion'] ?? '', 'Coahuila'); ?>>Coahuila</option>
                    <option value="Colima" <?php selected($_GET['ubicacion'] ?? '', 'Colima'); ?>>Colima</option>
                    <option value="Durango" <?php selected($_GET['ubicacion'] ?? '', 'Durango'); ?>>Durango</option>
                    <option value="Estado de México" <?php selected($_GET['ubicacion'] ?? '', 'Estado de México'); ?>>Estado de México</option>
                    <option value="Guanajuato" <?php selected($_GET['ubicacion'] ?? '', 'Guanajuato'); ?>>Guanajuato</option>
                    <option value="Guerrero" <?php selected($_GET['ubicacion'] ?? '', 'Guerrero'); ?>>Guerrero</option>
                    <option value="Hidalgo" <?php selected($_GET['ubicacion'] ?? '', 'Hidalgo'); ?>>Hidalgo</option>
                    <option value="Jalisco" <?php selected($_GET['ubicacion'] ?? '', 'Jalisco'); ?>>Jalisco</option>
                    <option value="Michoacán" <?php selected($_GET['ubicacion'] ?? '', 'Michoacán'); ?>>Michoacán</option>
                    <option value="Morelos" <?php selected($_GET['ubicacion'] ?? '', 'Morelos'); ?>>Morelos</option>
                    <option value="Nayarit" <?php selected($_GET['ubicacion'] ?? '', 'Nayarit'); ?>>Nayarit</option>
                    <option value="Nuevo León" <?php selected($_GET['ubicacion'] ?? '', 'Nuevo León'); ?>>Nuevo León</option>
                    <option value="Oaxaca" <?php selected($_GET['ubicacion'] ?? '', 'Oaxaca'); ?>>Oaxaca</option>
                    <option value="Puebla" <?php selected($_GET['ubicacion'] ?? '', 'Puebla'); ?>>Puebla</option>
                    <option value="Querétaro" <?php selected($_GET['ubicacion'] ?? '', 'Querétaro'); ?>>Querétaro</option>
                    <option value="Quintana Roo" <?php selected($_GET['ubicacion'] ?? '', 'Quintana Roo'); ?>>Quintana Roo</option>
                    <option value="San Luis Potosí" <?php selected($_GET['ubicacion'] ?? '', 'San Luis Potosí'); ?>>San Luis Potosí</option>
                    <option value="Sinaloa" <?php selected($_GET['ubicacion'] ?? '', 'Sinaloa'); ?>>Sinaloa</option>
                    <option value="Sonora" <?php selected($_GET['ubicacion'] ?? '', 'Sonora'); ?>>Sonora</option>
                    <option value="Tabasco" <?php selected($_GET['ubicacion'] ?? '', 'Tabasco'); ?>>Tabasco</option>
                    <option value="Tamaulipas" <?php selected($_GET['ubicacion'] ?? '', 'Tamaulipas'); ?>>Tamaulipas</option>
                    <option value="Tlaxcala" <?php selected($_GET['ubicacion'] ?? '', 'Tlaxcala'); ?>>Tlaxcala</option>
                    <option value="Veracruz" <?php selected($_GET['ubicacion'] ?? '', 'Veracruz'); ?>>Veracruz</option>
                    <option value="Yucatán" <?php selected($_GET['ubicacion'] ?? '', 'Yucatán'); ?>>Yucatán</option>
                    <option value="Zacatecas" <?php selected($_GET['ubicacion'] ?? '', 'Zacatecas'); ?>>Zacatecas</option>
                </select>
            </div>

            <div class="button-group">
                <button type="submit" class="btn-filtrar">Filtrar</button>
                <?php if (!empty($_GET['tipo_de_proyecto']) || !empty($_GET['ubicacion'])): ?>
                    <a href="<?php echo get_post_type_archive_link('proyecto'); ?>" class="btn-limpiar">Limpiar</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- QUERY CORREGIDO PARA PAGINACIÓN -->
    <?php 
    // Construcción del meta_query para filtros
    $meta_query = array('relation' => 'AND');

    if(!empty($_GET['tipo_de_proyecto'])) {
        $meta_query[] = array(
            'key' => 'tipo_de_proyecto',
            'value' => sanitize_text_field($_GET['tipo_de_proyecto']),
            'compare' => '='
        );
    }

    if(!empty($_GET['ubicacion'])) {
        $meta_query[] = array(
            'key' => 'ubicacion',
            'value' => sanitize_text_field($_GET['ubicacion']),
            'compare' => '='
        );
    }

    // Obtener página actual - detecta tanto /page/2/ como ?paged=2
    $paged = (get_query_var('paged')) ? get_query_var('paged') : 1;
    if (!$paged && isset($_GET['paged'])) {
        $paged = intval($_GET['paged']);
    }
    
    // Args del query
    $args = array(
        'post_type' => 'proyecto',
        'posts_per_page' => 6,
        'paged' => $paged,
        'post_status' => 'publish'
    );
    
    // Agregar meta_query solo si hay filtros
    if (count($meta_query) > 1) {
        $args['meta_query'] = $meta_query;
    }
    
    // Crear query personalizado
    $proyectos_query = new WP_Query($args);

    if ( $proyectos_query->have_posts() ) : ?>
        
        <div class="projects-grid-new">
            <?php while ( $proyectos_query->have_posts() ) : $proyectos_query->the_post(); ?>
                <article class="project-card-new">
                    
                    <!-- Imagen -->
                    <div class="project-image-new">
                        <?php if ($user_logged_in): ?>
                            <a href="<?php the_permalink(); ?>" style="display: block; width: 100%; height: 100%;">
                        <?php endif; ?>
                        
                        <?php if( get_field('imagen_principal') ): ?>
                            <?php 
                            $imagen = get_field('imagen_principal');
                            $imagen_url = is_array($imagen) ? $imagen['url'] : $imagen;
                            ?>
                            <img src="<?php echo esc_url($imagen_url); ?>" alt="<?php the_title(); ?>" />
                        <?php elseif( has_post_thumbnail() ): ?>
                            <?php the_post_thumbnail('medium'); ?>
                        <?php else: ?>
                            <div style="text-align: center; color: #d97706;">
                                <svg width="40" height="40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                </svg>
                                <p style="font-size: 12px; margin: 8px 0 0 0;">Imagen pendiente</p>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($user_logged_in): ?>
                            </a>
                        <?php endif; ?>
                    </div>

                    <!-- Contenido -->
                    <div class="project-content-new">
                        
                        <!-- Título -->
                        <h3 class="project-title-new">
                            <?php if ($user_logged_in): ?>
                                <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                            <?php else: ?>
                                <?php the_title(); ?>
                            <?php endif; ?>
                        </h3>

                        <!-- Metadatos -->
                        <div class="project-meta-new">
                            <?php if( get_field('etapa_del_proyecto') ): ?>
                                <?php 
                                $etapa = get_field('etapa_del_proyecto');
                                $status_class = 'status-' . ($etapa == 'en_desarrollo' ? 'desarrollo' : $etapa);
                                ?>
                                <div style="display: flex; align-items: center;">
                                    <span class="status-dot <?php echo $status_class; ?>"></span>
                                    <span><?php echo ucfirst(str_replace('_', ' ', $etapa)); ?></span>
                                </div>
                            <?php endif; ?>

                            <?php if( get_field('ubicacion') ): ?>
                                <div style="display: flex; align-items: center;">
                                    <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="margin-right: 4px;">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                    </svg>
                                    <span><?php echo wp_trim_words(get_field('ubicacion'), 2, ''); ?></span>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Descripción -->
                        <?php if( get_field('proposito_en_breve') ): ?>
                            <p class="project-desc-new">
                                <?php echo wp_trim_words(get_field('proposito_en_breve'), 15, '...'); ?>
                            </p>
                        <?php endif; ?>

                        <!-- Footer -->
                        <div class="project-footer-new">
                            <div class="author-info-new">
                                <?php echo get_avatar(get_the_author_meta('ID'), 24); ?>
                                <span class="author-name-new"><?php the_author(); ?></span>
                            </div>

                            <?php if ($user_logged_in): ?>
                                <a href="<?php the_permalink(); ?>" class="btn-ver-new">Conectar</a>
                            <?php else: ?>
                                <a href="<?php echo wp_login_url(get_permalink()); ?>" class="btn-login-fixed">Iniciar Sesión</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </article>
            <?php endwhile; ?>
        </div>

        <!-- PAGINACIÓN CORREGIDA -->
        <div class="pagination-wrapper">
            <?php
            // Construir los argumentos de filtro
            $filter_args = array();
            if (!empty($_GET['tipo_de_proyecto'])) {
                $filter_args['tipo_de_proyecto'] = sanitize_text_field($_GET['tipo_de_proyecto']);
            }
            if (!empty($_GET['ubicacion'])) {
                $filter_args['ubicacion'] = sanitize_text_field($_GET['ubicacion']);
            }
            
            // URL base
            $base_url = get_post_type_archive_link('proyecto');
            
            if (!empty($filter_args)) {
                // CON FILTROS: Usar solo query string (?tipo_de_proyecto=X&paged=2)
                echo paginate_links(array(
                    'base' => add_query_arg($filter_args, $base_url) . '%_%',
                    'format' => '&paged=%#%',
                    'current' => max(1, $paged),
                    'total' => $proyectos_query->max_num_pages,
                    'prev_text' => '← Anterior',
                    'next_text' => 'Siguiente →',
                    'type' => 'plain',
                    'add_args' => false,
                ));
            } else {
                // SIN FILTROS: Usar pretty permalinks (/page/2/)
                echo paginate_links(array(
                    'base' => trailingslashit($base_url) . 'page/%#%/',
                    'format' => '',
                    'current' => max(1, $paged),
                    'total' => $proyectos_query->max_num_pages,
                    'prev_text' => '← Anterior',
                    'next_text' => 'Siguiente →',
                    'type' => 'plain',
                ));
            }
            ?>
        </div>

    <?php 
    else : 
    ?>
        <div style="text-align: center; padding: 4rem 1rem; background: #f9fafb; border-radius: 12px;">
            <h3 style="color: #6b7280; margin: 0 0 1rem 0;">No hay proyectos disponibles</h3>
            <p style="color: #9ca3af;">Pronto habrá increíbles proyectos de la comunidad.</p>
        </div>
    <?php 
    endif; 
    
    // IMPORTANTE: Resetear post data
    wp_reset_postdata();
    ?>

</div>

<?php get_footer(); ?>