<?php
defined('ABSPATH') || exit;

add_action('wp_enqueue_scripts', 'mxwm_enqueue_privacy_ui', 20);
add_action('admin_enqueue_scripts', 'mxwm_enqueue_privacy_ui', 20);
function mxwm_enqueue_privacy_ui() {
    $dir = plugin_dir_url(__FILE__) . 'assets/';
    wp_register_script('mxwm-privacy-ui', $dir . 'js/mxwm-privacy-ui.js', array('jquery'), '1.0', true);

    $group_id = 0;
    if ( function_exists('bp_get_current_group_id') ) {
        $group_id = bp_get_current_group_id();
    }

    wp_localize_script('mxwm-privacy-ui', 'mxwm_privacidad', array(
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce'   => wp_create_nonce('mxwm_privacidad_nonce'),
        'group_id'=> intval($group_id),
    ));

    wp_enqueue_script('mxwm-privacy-ui');
}
