<?php
header('Content-Type: text/html; charset=UTF-8');
/* Template Name: Mis Proyectos */
get_header();
?>

<style>
.proyecto-actions form {
    display: inline-block;
    vertical-align: top;
}

@media (max-width: 768px) {
    .proyecto-actions .btn,
    .proyecto-actions form {
        margin-top: 15px !important;
    }
}
</style>

<?php

// Mostrar mensajes de gestión
if (isset($_GET['mxwm_msg'])) {
    $mensajes = array(
        'proyecto_pausado' => array('success', '✅ Proyecto pausado correctamente.'),
        'proyecto_activado' => array('success', '✅ Proyecto activado correctamente.'),
        'proyecto_eliminado' => array('info', '🗑️ Proyecto eliminado correctamente.')
    );
    
    $msg = $_GET['mxwm_msg'];
    if (isset($mensajes[$msg])) {
        list($tipo, $texto) = $mensajes[$msg];
        echo '<div class="notice notice-' . $tipo . '" style="margin: 20px auto; max-width: 900px;">';
        echo '<p>' . $texto . '</p>';
        echo '</div>';
    }
}

if (isset($_GET['updated']) && $_GET['updated'] === 'pending'): ?>
    <div style="background: #fff3cd; border: 1px solid #ffc107; color: #856404; padding: 1rem; margin: 1rem auto; max-width: 900px; border-radius: 8px; text-align: center;">
        <strong>✅ Proyecto actualizado correctamente</strong><br>
        Está en revisión y aparecerá públicamente una vez aprobado.
    </div>
<?php endif;

if ( ! is_user_logged_in() ) {
    echo '<p>Debes iniciar sesión para ver tus proyectos.</p>';
    wp_login_form();
    get_footer();
    exit;
}

$current_user_id = get_current_user_id();

// MOSTRAR ESTADÍSTICAS
echo do_shortcode('[mxwm_estadisticas]');

$args = array(
    'post_type'      => 'proyecto',
    'post_status'    => array('publish','pending','draft'),
    'author'         => $current_user_id,
    'posts_per_page' => -1,
    'orderby'        => 'date',
    'order'          => 'DESC'
);

$loop = new WP_Query($args);

if ( $loop->have_posts() ) : ?>

    <div class="mx-mis-proyectos" style="max-width:900px; margin:40px auto;">
        <h1 style="text-align:center; margin-bottom:20px;">Mis Proyectos</h1>

        <div class="proyectos-grid">
        <?php while ( $loop->have_posts() ) : $loop->the_post(); 
            $estado = get_post_status();
            $estado_label = '';
            $proyecto_id = get_the_ID();

            switch($estado) {
                case 'pending':
                    $estado_label = 'Pendiente de revisión';
                    $estado_color = '#ffc107';
                    break;
                case 'publish':
                    $estado_label = 'Publicado';
                    $estado_color = '#28a745';
                    break;
                case 'draft':
                    $estado_label = 'Pausado';
                    $estado_color = '#6c757d';
                    break;
            }
        ?>

        <div class="proyecto-card" data-estado="<?php echo $estado; ?>" style="border:1px solid #ddd; border-radius:8px; padding:20px; margin-bottom:20px; position:relative;">
            <h3 style="margin-top: 0;"><?php the_title(); ?></h3>
            
            <?php 
            // CORRECCIÓN: Mostrar imagen destacada o imagen ACF
            if (has_post_thumbnail()): ?>
                <div class="proyecto-imagen" style="margin-bottom:10px;">
                    <?php the_post_thumbnail('medium', array('style' => 'width:350px; height:200px; object-fit:cover; border-radius:8px;')); ?>
                </div>
            <?php else: ?>
                <?php 
                // Fallback a imagen ACF si no hay featured image
                $imagen_principal = get_field('imagen_principal', $proyecto_id);
                if ($imagen_principal): 
                    $imagen_url = is_array($imagen_principal) ? $imagen_principal['url'] : $imagen_principal;
                ?>
                    <div class="proyecto-imagen" style="margin-bottom:10px;">
                        <img src="<?php echo esc_url($imagen_url); ?>" alt="<?php the_title(); ?>" style="width:350px; height:200px; object-fit:cover; border-radius:8px;">
                    </div>
                <?php else: ?>
                    <div style="background:#f8f9fa; width:350px; height:200px; display:flex; align-items:center; justify-content:center; border-radius:8px; margin-bottom:10px;">
                        <span style="color:#6c757d;">🖼️ Sin imagen</span>
                    </div>
                <?php endif; ?>
            <?php endif; ?>

            <p><?php echo get_field('proposito_breve') ?: wp_trim_words(get_the_content(), 20); ?></p>

            <div class="proyecto-estado" style="background:<?php echo $estado_color; ?>; color:#fff; padding:4px 10px; border-radius:4px; font-weight:bold; display: inline-block;">
                <?php echo $estado_label; ?>
            </div>

            <div class="proyecto-actions" style="margin-top: 15px;">
                <a href="<?php the_permalink(); ?>" class="btn btn-primary" style="display:inline-block; padding:6px 12px; background:#0073aa; color:#fff; text-decoration:none; border-radius:4px;">👁️ Ver detalles</a>
                
                <a href="<?php echo home_url('/editar-proyecto-frontend/?proyecto_id=' . $proyecto_id); ?>" class="btn btn-secondary" style="display:inline-block; padding:6px 12px; background:#6c757d; color:#fff; text-decoration:none; border-radius:4px;">✏️ Editar</a>
                
                <?php if ($estado === 'publish') : ?>
                    <form method="post" style="display: inline;">
                        <?php wp_nonce_field('mxwm_pausar_proyecto_' . $proyecto_id, 'mxwm_nonce'); ?>
                        <input type="hidden" name="accion" value="pausar_proyecto">
                        <input type="hidden" name="proyecto_id" value="<?php echo $proyecto_id; ?>">
                        <button type="submit" class="btn btn-warning" style="padding:6px 12px; background:#ffc107; color:#000; border:none; border-radius:4px; cursor:pointer;">⏸️ Pausar</button>
                    </form>
                <?php elseif ($estado === 'draft') : ?>
                    <form method="post" style="display: inline;">
                        <?php wp_nonce_field('mxwm_activar_proyecto_' . $proyecto_id, 'mxwm_nonce'); ?>
                        <input type="hidden" name="accion" value="activar_proyecto">
                        <input type="hidden" name="proyecto_id" value="<?php echo $proyecto_id; ?>">
                        <button type="submit" class="btn btn-success" style="padding:6px 12px; background:#28a745; color:#fff; border:none; border-radius:4px; cursor:pointer;">▶️ Activar</button>
                    </form>
                <?php endif; ?>
                
                <form method="post" style="display: inline;" onsubmit="return confirm('¿Estás seguro de que quieres eliminar este proyecto? Esta acción no se puede deshacer.');">
                    <?php wp_nonce_field('mxwm_eliminar_proyecto_' . $proyecto_id, 'mxwm_nonce'); ?>
                    <input type="hidden" name="accion" value="eliminar_proyecto">
                    <input type="hidden" name="proyecto_id" value="<?php echo $proyecto_id; ?>">
                    <button type="submit" class="btn btn-danger" style="padding:6px 12px; background:#dc3545; color:#fff; border:none; border-radius:4px; cursor:pointer;">🗑️ Eliminar</button>
                </form>
            </div>
        </div>

        <?php endwhile; wp_reset_postdata(); ?>
        </div>
    </div>

<?php else : ?>
    <div style="text-align:center; padding: 3rem;">
        <p style="font-size: 1.2rem; margin-bottom: 2rem;">No tienes proyectos creados aún.</p>
        <a href="<?php echo home_url('/crear-proyecto-frontend/'); ?>" class="btn btn-primary" style="background: #0073aa; color: white; padding: 1rem 2rem; text-decoration: none; border-radius: 5px; font-size: 1.1rem;">
            🚀 Crear mi primer proyecto
        </a>
    </div>
<?php endif;

get_footer();
?>