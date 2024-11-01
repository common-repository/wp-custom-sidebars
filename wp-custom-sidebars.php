<?php
/*
 * Plugin Name: WP Custom Sidebars
 * Plugin URI: http://mnmlthms.com/plugins/wp-custom-sidebars
 * Description: Create unlimited sidebars for pages/posts easily without writing a single line of code
 * Author: mnmlthms
 * Version: 1.0.2
 * Author URI: http://mnmlthms.com/
 * Text domain: wp-custom-sidebars
 */
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

define( 'WP_CUSTOM_SIDEBARS_VERSION', '1.0.2' );
define( 'WP_CUSTOM_SIDEBARS_OPTION', 'wp_custom_sidebars' );

if( !class_exists( 'WP_Custom_Sidebars' ) ):

    /**
     * class WP_Custom_Sidebars
     */
    final class WP_Custom_Sidebars {
        /**
         * Constructor
         *
         * @return    void
         *
         * @access    public
         * @since     1.0
         */
        public function __construct() {

            add_action( 'plugins_loaded', array( $this, 'plugins_loaded' ) );
            add_action( 'after_setup_theme', array( $this, 'hooks' ) );
        }

        /**
         * Include admin files
         *
         * These functions are included on admin pages only.
         *
         * @return    void
         *
         * @access    private
         * @since     1.0
         */
        private function admin_includes() {
          
            /* exit early if we're not on an admin page */
            if ( ! is_admin() )
                return false;

        }
        /**
         * Fire on plugins_loaded
         *
         * @return    void
         *
         * @access    public
         * @since     1.0
         */
        public function plugins_loaded(){

            load_plugin_textdomain( 'wp-custom-sidebars', false, self::get_dirname() . '/langs/' ); 
        }

        /**
         * Execute the Hooks
         *
         * @return    void
         *
         * @access    public
         * @since     1.0
         */
        public function hooks() {

            add_action( 'wp_enqueue_scripts', array( $this, 'wp_enqueue_scripts' ), 16 );

        }

        /**
         * JS and CSS
         *
         * @return    void
         *
         * @access    public
         * @since     1.0
         */
        public function wp_enqueue_scripts(){
            $suffix = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? '' : '.min';
            
        }
        /**
         * Helpers
         *
         * @return    void
         *
         * @access    public
         * @since     1.0
         */
        static function get_url() {
            return plugin_dir_url( __FILE__ );
        }

        static function get_dir() {
            return plugin_dir_path( __FILE__ );
        }

        static function plugin_basename() {
            return plugin_basename( __FILE__ );
        }
        
        static function get_dirname( $path = '' ) {
            return dirname( plugin_basename( __FILE__ ) );
        }

    }

    require_once( 'inc/public/options.php' );

    if( is_admin() ){
        require_once( 'inc/admin/settings.php' );
        require_once( 'inc/admin/metabox.php' );
        require_once( 'inc/admin/taxonomy.php' );
    }

    require_once( 'inc/public/main.php' );

endif;

// Kickstart it
$GLOBALS['wp_custom_sidebars'] = new WP_Custom_Sidebars;