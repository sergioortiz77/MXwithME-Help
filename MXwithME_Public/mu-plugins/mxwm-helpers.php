<?php
defined('ABSPATH') || exit;

class MXWM_Helpers {
    public static function log($msg, $level = 'info') {
        if ( defined('WP_DEBUG') && WP_DEBUG ) {
            error_log("MXWM {$level}: " . $msg);
        }
    }

    public static function sanitize_privacy_value($val) {
        if ( empty($val) ) return 'public';
        $v = strtolower(trim($val));
        $v = preg_replace('/[^a-zA-ZáéíóúÁÉÍÓÚñÑ]/u', '', $v);
        $map = array(
            'publico' => 'public',
            'public'  => 'public',
            'público' => 'public',
            'privado' => 'private',
            'private' => 'private',
            'oculto'  => 'hidden',
            'hidden'  => 'hidden'
        );
        return isset($map[$v]) ? $map[$v] : 'public';
    }
}
