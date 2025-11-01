<?php
defined('ABSPATH') || exit;

add_action('wp_ajax_mxwm_confirmar_privacidad_grupo', 'mxwm_ajax_confirmar_privacidad_grupo');
function mxwm_ajax_confirmar_privacidad_grupo() {
    if ( ! check_ajax_referer('mxwm_privacidad_nonce', 'security', false) ) {
        wp_send_json_error(array('message' => 'Nonce inválido'), 403);
    }

    $group_id = isset($_POST['group_id']) ? intval($_POST['group_id']) : 0;
    if ( ! $group_id ) {
        wp_send_json_error(array('message' => 'ID grupo inválido'), 400);
    }

    if ( ! function_exists('groups_update_groupmeta') ) {
        wp_send_json_error(array('message' => 'Funciones de BuddyPress no disponibles'), 500);
    }

    groups_update_groupmeta($group_id, 'mxwm_irreversible', 1);
    groups_update_groupmeta($group_id, 'mxwm_fecha_irreversible', current_time('mysql'));
    MXWM_Helpers::log("AJAX: Grupo {$group_id} marcado irreversible", 'info');

    wp_send_json_success(array('message' => 'ok'));
}
