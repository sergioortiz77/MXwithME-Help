<?php
/**
 * Plugin Name: MXWM BuddyPress Privacy - v1.2
 * Description: Sincroniza estado ACF de privacidad de grupos con BuddyPress, bloqueo de acceso a foros y protección irreversible. Modular y seguro.
 * Version: 1.2
 * Author: MXWM Team
 */

defined('ABSPATH') || exit;

if ( ! class_exists('MXWM_Privacy') ) :

class MXWM_Privacy {

    const VERSION = '1.2';
    private static $instance = null;
    private $plugin_dir;
    private $plugin_url;

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->plugin_dir = plugin_dir_path(__FILE__);
        $this->plugin_url = plugin_dir_url(__FILE__);

        add_action('bp_include', array($this, 'init'));
        add_action('wp_loaded', array($this, 'maybe_init_fallback'));
    }

    public function maybe_init_fallback() {
        if ( ! did_action('bp_include') && function_exists('bp_is_active') ) {
            $this->init();
        }
    }

    public function init() {
        if ( ! function_exists('bp_is_active') || ! bp_is_active('groups') ) {
            error_log('MXWM: BuddyPress Groups no activo - MXWM Privacy no inicializado');
            return;
        }

        require_once __DIR__ . '/mxwm-helpers.php';
        require_once __DIR__ . '/mxwm-capabilities.php';
        require_once __DIR__ . '/mxwm-transitions.php';
        require_once __DIR__ . '/mxwm-security.php';
        require_once __DIR__ . '/mxwm-ajax.php';
        require_once __DIR__ . '/mxwm-ui.php';

        MXWM_Helpers::log('MXWM Privacy v' . self::VERSION . ' inicializado');

        register_activation_hook(__FILE__, array('MXWM_Capabilities', 'activate'));
    }

}

MXWM_Privacy::instance();

endif;
