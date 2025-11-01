<?php
defined('ABSPATH') || exit;

add_filter('bp_user_can_view_group', 'mxwm_bloquear_contenido_grupos_privados', 10, 2);
function mxwm_bloquear_contenido_grupos_privados($can_view, $group_id) {
    if ( $can_view ) return $can_view;
    if ( ! function_exists('groups_get_group') ) return $can_view;

    $group = groups_get_group($group_id);
    if ( empty($group) ) return false;

    if ( ! in_array($group->status, array('private','hidden')) ) return true;

    $user_id = get_current_user_id();
    if ( ! $user_id ) {
        MXWM_Helpers::log("Acceso denegado: usuario no autenticado a grupo {$group_id}", 'warn');
        return false;
    }

    if ( ! function_exists('groups_is_user_member') ) return false;
    if ( groups_is_user_member($user_id, $group_id) ) {
        MXWM_Helpers::log("Acceso permitido: usuario {$user_id} miembro del grupo {$group_id}", 'debug');
        return true;
    }

    MXWM_Helpers::log("Acceso denegado: usuario {$user_id} NO es miembro del grupo {$group_id}", 'warn');
    return false;
}

add_action('template_redirect', 'mxwm_bloquear_acceso_foros_privados', 5);
function mxwm_bloquear_acceso_foros_privados() {
    if ( ! function_exists('bbp_is_single_forum') || ! bbp_is_single_forum() ) return;

    $forum_id = bbp_get_forum_id();
    if ( ! $forum_id ) return;

    $group_ids = get_post_meta($forum_id, '_bbp_group_ids', true);
    if ( empty($group_ids) || ! is_array($group_ids) ) return;

    $group_id = intval($group_ids[0]);
    if ( ! $group_id ) return;

    if ( ! function_exists('groups_get_group') ) return;
    $group = groups_get_group($group_id);
    if ( ! in_array($group->status, array('private','hidden')) ) return;

    $user_id = get_current_user_id();
    if ( ! $user_id || ! groups_is_user_member($user_id, $group_id) ) {
        if ( function_exists('bp_core_add_message') ) {
            bp_core_add_message('No tienes permiso para acceder a este foro. Debes ser miembro del grupo.', 'error');
        }
        wp_safe_redirect(home_url());
        exit;
    }
}
