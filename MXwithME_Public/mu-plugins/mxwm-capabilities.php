<?php
defined('ABSPATH') || exit;

class MXWM_Capabilities {

    public static function activate() {
        $role = get_role('administrator');
        if ( $role ) {
            $role->add_cap('override_group_privacy_lock');
            $role->add_cap('manage_mxwm_privacy');
        }
        MXWM_Helpers::log('Capacidades registradas', 'debug');
    }
}
