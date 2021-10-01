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

require_once( __DIR__ . '/testing.php' );
require_once( __DIR__ . '/includes/beefluence/Service_Price_Calc.class.php' );
use beefluence\Service_Price_Calc as Beef_Price;
require_once( __DIR__ . '/includes/chinchillabrains/Tools.class.php' );
use chinchillabrains\Tools as Chin_Tools;

if ( ! class_exists( 'Chin_Influencer_Services' ) ) {

    class Chin_Influencer_Services {

        // Instance of this class.
        protected static $instance = null;

        public $dashboard_sections = array();

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

            $this->init_dashboard_sections();
            
            add_shortcode( 'chilla_add_service_form', array( $this, 'add_service_form' ) );

            add_shortcode( 'chilla_login_form', array( $this, 'login_form' ) );

            add_shortcode( 'chilla_dashboard_nav', array( $this, 'show_dashboard_nav' ) );
            add_shortcode( 'chilla_dashboard', array( $this, 'get_user_dashboard' ) );
            

            add_action('acf/save_post', array( $this, 'service_add_values_on_save_post' ), 200, 1);

            add_filter( 'oa_social_login_filter_new_user_role', array( $this, 'oa_social_login_set_new_user_role' ) );

            add_action( 'wp_enqueue_scripts', array( $this, 'add_scripts_and_styles' ) );

            add_action( 'template_redirect', array( $this, 'redirect_to_dashboard' ) );

            add_action( 'wp_head', array( $this, 'js_redirects' ), 1 );


        }

        public function js_redirects () {
            $request_uri = $_SERVER['REQUEST_URI'];
            if ( strpos( $request_uri, 'influencer-dashboard' ) !== false && $_REQUEST['_acf_screen'] == 'acf_form' ) {
                echo '<script>window.location.replace("https://beefluence.gr/influencer-dashboard");</script>';
            } elseif ( strpos( $request_uri, 'brand-dashboard' ) !== false && $_REQUEST['_acf_screen'] == 'acf_form' ) {
                echo '<script>window.location.replace("https://beefluence.gr/brand-dashboard");</script>';
            }
        }
        
        public function add_scripts_and_styles () {
            wp_enqueue_style( 'influencer-services-styles', plugin_dir_url( __FILE__ ) . 'assets/css/chilla-style.css' );

            wp_enqueue_script( 'beefluence-dashboard-js', plugin_dir_url( __FILE__ ) . 'assets/js/scripts.js', array('jquery') );
        }

        public function login_form () {

            if ( is_user_logged_in() ) {
                return $this->get_user_dashboard();
            }
            $register_link = "<a href=\"?action=register\">Εγγραφή</a>";
            if ( isset( $_GET['action'] ) && 'register' === $_GET['action'] ) {
                $register_link = "<a href=\".\">Σύνδεση</a>";
            }
            echo '<div id="custom-login-page" class="custom-login-page">';
            require ABSPATH . 'wp-login.php';
            echo "<div class=\"custom-login-page__links\">{$register_link} | <a href=\"/my-account/lost-password/\">Χάσατε το συνθηματικό σας;</a></div>";
            echo '</div>';
        }

        public function log ( $data ) {
            ob_start();
            var_dump( $data );
            $data_str = ob_get_clean();
            $log_file = fopen( __DIR__ . '/log.txt', 'a+' );
            fwrite( $log_file, $data_str . PHP_EOL );
            fclose( $log_file );
        }

        public function redirect_to_dashboard () {

            // $this->log('srv::');
            // $this->log($_SERVER);

            // $this->log('req::');
            // $this->log($_REQUEST);

            // $this->log('post::');
            // $this->log($_POST);

            
            if ( is_user_logged_in() ) {
                global $wp;
                $current_page = $wp->request;
                if ( $current_page == 'brand-login' ) {
                    wp_redirect( home_url( '/brand-dashboard' ) );
                    exit;
                } elseif ( $current_page == 'influencer-login' ) {
                    wp_redirect( home_url( '/influencer-dashboard' ) );
                    exit;
                }
            }
        }

        public function service_add_values_on_save_post ( $post_id ) {
            $product = wc_get_product( $post_id );
            if ( empty( $product ) ) {
                return;
            }
            $request_uri = $_SERVER['REQUEST_URI'];
            
            if ( strpos( $request_uri, 'influencer-dashboard' ) === false ) {
                $this->log( 'acf/save_post reuquest: ' . $request_uri );
                return;    
            }



            $product_fields = array();
            $fields_to_get = array(
                'service_category',
                'service_subcategory_meet',
                'service_subcategory_post',
                'service_subcategory_story',
                'description',
                'area',
                'audience_gender',
                'audience_age',
                'industry',
                'avgengagerate',
                'featured_image',
                'facebook_likes',
                'instagram_followers',
                'tiktok_followers',
                'twitter_followers',
                'youtube_subscribers',
                'gallery',
            );
            foreach ( $fields_to_get as $field_name ) {
                $product_fields[ $field_name ] = get_field( $field_name, $post_id );
            }

            // Do not add Category. Only subcategory
            $subcategories = array(
                'meet', 
                'post', 
                'story',
            );
            foreach ( $subcategories as $subcat ) {
                if ( empty( $product_fields["service_subcategory_{$subcat}"] ) ) {
                    continue;
                }
                wp_set_object_terms( $post_id, (int) $product_fields["service_subcategory_{$subcat}"]['value'], 'product_cat', true ); // Subcategory
            }

            $taxonomies = array(
                'area',
                'audience_gender',
                'audience_age',
                'industry',
                'avgengagerate',
                'facebook_likes',
                'instagram_followers',
                'tiktok_followers',
                'twitter_followers',
                'youtube_subscribers',
            );
            $service_followers = '';
            $service_engagement = $product_fields['avgengagerate']->name;
            $service_category = $product_fields['service_category']['label'];
            $attributes_arr = [];
            foreach ( $taxonomies as $term ) {
                if ( empty( $product_fields[ $term ] ) ) {
                    continue;
                }
                $att_values = [];
                if ( is_array( $product_fields[ $term ] ) ) {
                    foreach ( $product_fields[ $term ] as $term_obj ) {
                        $att_values[] = $term_obj->name;
                        $attribute_tax = $term_obj->taxonomy;
                    }
                } else {
                    $term_data = $product_fields[ $term ];
                    $att_values[] = $term_data->name;

                    $attribute_tax = $term_data->taxonomy;
                    if ( in_array( $term, array( 'facebook_likes', 'instagram_followers', 'tiktok_followers', 'twitter_followers', 'youtube_subscribers' ), true ) ) {
                        $service_followers = $term_data->name;
                    }
                }
                $attributes_arr[ $attribute_tax ] = $att_values;
            }


            $price = $this->get_product_price( 
                array(
                    'followers'     => $service_followers,
                    'engagement'    => $service_engagement,
                    'category'      => $service_category,
                ) 
            );
            if ( ! empty( $price ) ) {
                $product->set_regular_price( $price );   
            }
            
            $product->set_description( $product_fields['description'] );
            $product->set_image_id( $product_fields['featured_image'] );
            $product->save();
            Chin_Tools::set_product_attribute_terms( $post_id, $attributes_arr );

        }

        private function get_product_price ( $options ) {
            if ( !isset( $options['followers'] ) || !isset( $options['engagement'] ) || !isset( $options['category'] ) ) {
                return false;
            }
            $followers = $options['followers'];
            $engagement = $options['engagement'];
            $category = $options['category'];
            
            $price_calc = new Beef_Price( $category, $followers, $engagement );
            $this->log('price_calc');
            $this->log($price_calc);
            $price = $price_calc->get_price();
            $this->log('price');
            $this->log($price);

            return $price;
        }

        public function get_user_dashboard () {
            $error_msg = '<p>Δυστυχώς δεν έχετε πρόσβαση σε αυτή τη σελίδα. Σε περίπτωση που πιστεύετε ότι έχει γίνει κάποιο λάθος επικοινωνήστε με το διαχειριστή της ιστοσελίδας. <a href="/">Επιστροφή στην Αρχική</a></p>';
            if ( ! is_user_logged_in() ) {
                return $error_msg;    
            }
            global $wp;
            $current_page = $wp->request;
            $user = wp_get_current_user();
            $roles = ( array ) $user->roles;
            if ( in_array( 'influencer', $roles ) && $current_page == 'influencer-dashboard' ) {
                return $this->show_user_dashboard( 'influencer' );
            } elseif ( in_array( 'advertiser', $roles ) && $current_page == 'brand-dashboard' ) {
                return $this->show_user_dashboard( 'advertiser' );
            }
            return $error_msg;
        }

        public function show_user_dashboard ( $role ) {
            if ( ! in_array( $role, array( 'influencer', 'advertiser' ) ) ) {
                return '';
            }
            $current_user = wp_get_current_user();
            $user_id = $current_user->ID;
            $ret_html = '<div id="beefluence-dashboard-' . $role . '" class="custom-dashboard-page user-role-' . $role . '">';
            $count = 0;
            foreach ( $this->dashboard_sections as $section => $label ) {
                if ( method_exists( $this, "dashboard_section_{$section}" ) ) {
                    $ret_html .= call_user_func( 
                        array( $this, "dashboard_section_{$section}" ),
                        array( 
                            'user_role' => $role,
                            'user_id'   => $user_id,
                            'section'   => $section,
                            'label'     => $label,
                            'order'     => $count,
                         ),
                    );
                    $count++;
                }
            }
            $ret_html .= '';
            return $ret_html;
        }

        public function dashboard_section_accountdetails ( $args ) {
            $influcencer_fields_group = 'group_61424b86874f4';
            $advertiser_fields_group = 'group_6145f473359c5';
            $user_fields_group = ( $args['user_role'] == 'influencer' ? $influcencer_fields_group : $advertiser_fields_group );
            $options = array(
                'post_id' => 'user_' . $args['user_id'],
                'field_groups' => array( $user_fields_group ),
                'form' => true, 
                'return' => '', 
                // 'html_before_fields' => '',
                // 'html_after_fields' => '',
                'submit_value' => 'Αποθήκευση'
            );
            $ret_html = '<div id="dashboard-accountdetails" class="dashboard-section dashboard-section__accountdetails">';
                $ret_html .= "<h3>{$args['label']}</h3>";
                ob_start();
                acf_form_head();
                acf_form( $options );
                $ret_html .= ob_get_clean();
            $ret_html .= '</div>';
            return $ret_html;
        }

        public function dashboard_section_newservice ( $args ) {
            $ret_html = '<div id="dashboard-newservice" class="dashboard-section dashboard-section__newservice">';
                $ret_html .= "<h3>{$args['label']}</h3>";
                $ret_html .= $this->add_service_form();
            $ret_html .= '</div>';
            return $ret_html;
        }

        public function dashboard_section_myservices ( $args ) {
            // Show service list with status for each & option to deactivate temporarily OR delete service (OR edit?)
            $ret_html = '<div id="dashboard-myservices" class="dashboard-section dashboard-section__myservices">';
                $ret_html .= "<h3>{$args['label']}</h3>";
                $services = $this->get_influencer_services( $args['user_id'] );
                // $ret_html .= print_r( $services, true );
            $ret_html .= '</div>';
            return $ret_html;
        }

        public function get_influencer_services ( $user_id ) {
            // Return service all details 
                // ID
                // Name
                // Status
                // Price
                // Image
            $ret_arr = array();
            global $wpdb;
            $query = "SELECT ID FROM {$wpdb->posts} WHERE post_type='product' AND post_author='{$user_id}'";
            $services_ids = $wpdb->get_results( $query, 'ARRAY_A' );
            if ( empty( $services_ids ) ) {
                return array();
            }
            foreach ( $services_ids as $item ) {
                $id = $item['ID'];
                $product = wc_get_product( $id );
                $product_status = $product->get_status();
                $stock_status = $product->get_stock_status();
                $product_details = array(
                    'id'                => $id,
                    'title'             => $product->get_title(),
                    'product_status'    => $product_status,
                    'stock_status'      => $stock_status,
                    'price'             => $product->get_price(),
                    'image'             => $product->get_image(),
                );
                array_push( $ret_arr, $product_details );
            }
            return $ret_arr;
        }

        public function dashboard_section_orderedservices ( $args ) {
            $ret_html = '<div id="dashboard-orderedservices" class="dashboard-section dashboard-section__orderedservices">';
                $ret_html .= "<h3>{$args['label']}</h3>";
            $ret_html .= '</div>';
            return $ret_html;
        }


        public function init_dashboard_sections () {
            $user = wp_get_current_user();
            $roles = ( array ) $user->roles;
            if ( ! in_array( 'influencer', (array) $roles ) && ! in_array( 'advertiser', (array) $roles ) ) {
                return '';
            }
            $sections = array();
            if ( in_array( 'influencer', $roles ) ) {
                $sections[ 'myservices' ]       = 'Οι υπηρεσίες μου';
                $sections[ 'newservice' ]       = 'Νέα υπηρεσία';
                $sections[ 'accountdetails' ]   = 'Στοιχεία λογαριασμού';
                $sections[ 'logout' ]           = 'Αποσύνδεση';
            } elseif ( in_array( 'advertiser', $roles ) ) {
                $sections[ 'orderedservices' ]  = 'Ιστορικό Υπηρεσιών';
                $sections[ 'accountdetails' ]   = 'Στοιχεία λογαριασμού';
                $sections[ 'logout' ]           = 'Αποσύνδεση';
            } 
            foreach ( $sections as $section => $label ) {
                $this->dashboard_sections[ $section ] = $label;
            }
        }


        public function show_dashboard_nav () {
            $ret_html = '<div id="beefluence-dashboard-nav" class="beefluence-dashboard-nav">';
                $ret_html .= '<ul>';
                foreach ( $this->dashboard_sections as $section => $label ) {
                    $ret_html .= "<li class=\"beefluence-dashboard-nav__section beefluence-dashboard-nav__{$section}\">";
                        if ( 'logout' == $section ) {
                            $ret_html .= wp_loginout( '/', false );
                        } else {
                            $ret_html .= "<a href=\"#dashboard-{$section}\" data-target=\"{$section}\">{$label}</a>";   
                        }
                    $ret_html .= '</li>';
                }
                $ret_html .= '</ul>';
            $ret_html .= '</div>';
            return $ret_html;
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
            
            ?>

                <div>
                    <?php 
                    acf_form( array(
                        'post_id'       => 'new_post',
                        'field_groups' => array( 'group_613914e753a83' ),
                        'new_post'      => array(
                            'post_type'     => 'product',
                            'post_status'   => 'pending'
                        ),
                        'post_title'    => true,
                        'submit_value'  => __( 'Καταχώρηση Υπηρεσίας' ),
                        // 'updated_message' => __( 'Service submitted', 'acf' ),
                        // 'return'     => '%post_url%'
                        // 'return'     => 'https://beefluence.gr/influencer-dashboard/',
                    )); 
                    ?>
                </div>

            <?php
            return ob_get_clean();
        }

        public function add_custom_user_roles () {
            add_role( 'influencer', 'Influencer', array( 'upload_files' ) );
            add_role( 'advertiser', 'Advertiser' );
        }

        public function oa_social_login_set_new_user_role ( $user_role ) {
            $current_url = oa_social_login_get_current_url();
            $user_role = 'advertiser';

            if ( strpos ( $current_url, 'influencer-login' ) !== false ) {
                return 'influencer';
            }

            return $user_role;
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