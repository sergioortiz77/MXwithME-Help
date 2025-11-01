<?php
/**
 * Template Name: Home Page MXwithME - Para uso con Divi Builder
 * Página limpia sin configuración WP Admin
 */

get_header();
?>

<!-- Página completamente editable con Divi Builder -->
<div class="mxwithme-home-divi">
    <?php if (have_posts()) : while (have_posts()) : the_post(); ?>
        <div class="page-content">
            <?php the_content(); ?>
        </div>
    <?php endwhile; endif; ?>
</div>

<!-- CSS básico para asegurar compatibilidad con Divi -->
<style>
.mxwithme-home-divi {
    width: 100%;
}

.mxwithme-home-divi .page-content {
    width: 100%;
}

/* Asegurar que Divi funcione correctamente */
body.divi_builder_stats_front .et_pb_section,
body.divi_builder_stats_front .et_pb_row {
    position: relative;
}
</style>

<?php get_footer(); ?>