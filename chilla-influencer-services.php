<?php
/**
 * Plugin Name: Influencer Services for WooCommerce
 * Description: Adds custom roles & service fields in Woocommerce products & Creates front-end page for adding & editing services.
 * Version: 1.0.0
 * Author: chinchillabrains
 * Requires at least: 5.0
 * Author URI: https://chinchillabrains.com
 * Text Domain: chilla-influencer-services
 * Domain Path: /languages/
 * WC tested up to: 4.1
 */


if ( ! defined( 'ABSPATH' ) ) {
    exit;
}


if ( ! class_exists( 'Chin_Influencer_Services' ) ) {

    class Chin_Influencer_Services {

        // Instance of this class.
        protected static $instance = null;

        public function __construct() {
            if ( ! class_exists( 'WooCommerce' ) ) {
                return;
            }


            // Load translation files
            add_action( 'init', array( $this, 'add_translation_files' ) );

            // Admin page
            add_action('admin_menu', array( $this, 'setup_menu' ));


            // Add settings link to plugins page
            add_filter( 'plugin_action_links_'.plugin_basename(__FILE__), array( $this, 'add_settings_link' ) );

            // Register plugin settings fields
            // register_setting( 'chis_settings', 'chis_email_message', array('sanitize_callback' => array( 'Chin_Influencer_Services', 'chis_sanitize_code' ) ) );

            $this->add_custom_user_roles();
            
            add_shortcode( 'chilla_add_service_form', array( $this, 'add_service_form' ) );

        }


        public static function chis_sanitize_code( $input ) {        
            $sanitized = wp_kses_post( $input );
            if ( isset( $sanitized ) ) {
                return $sanitized;
            }
            return '';
        }

        public function add_translation_files () {
            load_plugin_textdomain( 'chilla-influencer-services', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
        }

        public function setup_menu() {
            add_management_page(
                __( 'Influencer Services', 'chilla-influencer-services' ),
                __( 'Influencer Services', 'chilla-influencer-services' ),
                'manage_options',
                'chis_settings_page',
                array( $this, 'admin_panel_page' )
            );
        }

        public function admin_panel_page(){
            require_once( __DIR__ . '/chilla-influencer-services.admin.php' );
        }

        public function add_settings_link( $links ) {
            $links[] = '<a href="' . admin_url( 'tools.php?page=chis_settings_page' ) . '">' . __('Settings') . '</a>';
            return $links;
        }

        public function add_service_form () {
            $user = wp_get_current_user();
            if ( ! in_array( 'administrator', (array) $user->roles ) && ! in_array( 'influencer', (array) $user->roles ) ) {
                return '';
            }
            ob_start();
            acf_form_head();
            get_header(); ?>

                <div>
                    <?php 
                    acf_form( array(
                        'post_id'       => 'new_post',
                        'new_post'      => array(
                            'post_type'     => 'product',
                            'post_status'   => 'pending'
                        ),
                        'post_title'    => true,
                        'submit_value'  => __( 'Submit for Review' ),
                        // 'updated_message' => __( 'Service submitted', 'acf' ),
                        // 'return'     => '%post_url%'
                    )); 
                    ?>
                </div>

            <?php get_sidebar();
            get_footer();
            return ob_get_clean();
        }

        public function add_custom_user_roles () {
            add_role( 'influencer', 'Influencer' );
            add_role( 'advertiser', 'Advertiser' );
        }

        // Return an instance of this class.
        public static function get_instance () {
            if ( ! class_exists('ACF') ) {
                return;
            }
            // If the single instance hasn't been set, set it now.
            if ( self::$instance == null ) {
                self::$instance = new self;
            }

            return self::$instance;
        }

    }

    add_action( 'plugins_loaded', array( 'Chin_Influencer_Services', 'get_instance' ), 0 );

}