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
require_once( __DIR__ . '/includes/beefluence/Shortcodes.class.php' );
use beefluence\Shortcodes as Beef_Short;
require_once( __DIR__ . '/includes/chinchillabrains/Tools.class.php' );
use chinchillabrains\Tools as Chin_Tools;
require_once( __DIR__ . '/includes/chinchillabrains/Products.class.php' );

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

            add_filter( 'login_headerurl', function () {
                return home_url();
            } );
            
            add_shortcode( 'chilla_add_service_form', array( $this, 'add_service_form' ) );

            add_shortcode( 'chilla_login_form', array( $this, 'login_form' ) );

            add_shortcode( 'chilla_dashboard_nav', array( $this, 'show_dashboard_nav' ) );
            add_shortcode( 'chilla_dashboard', array( $this, 'get_user_dashboard' ) );
            

            // add_action('acf/save_post', array( $this, 'service_add_values_on_save_post' ), 200, 1);

            add_action('acf/save_post', array( $this, 'user_details_save' ), 201, 1);

            add_filter( 'oa_social_login_filter_new_user_role', array( $this, 'oa_social_login_set_new_user_role' ) );

            add_action( 'beef_update_influencer_product', array( $this, 'update_influencer_product' ), 10, 1 );
            add_action( 'beef_update_product_variations', array( $this, 'update_product_variations' ), 10, 1 );

            add_action( 'wp_enqueue_scripts', array( $this, 'add_scripts_and_styles' ) );
            add_action( 'login_enqueue_scripts', array( $this, 'add_scripts_and_styles' ) );

            add_action( 'template_redirect', array( $this, 'redirect_to_dashboard' ) );

            add_action( 'wp_head', array( $this, 'js_redirects' ), 1 );

            add_action( 'wp_head', array( $this, 'dashboard_change_stock_status' ) );

            add_action( 'register_form', array( $this, 'register_form_influencer' ) );

            add_action( 'register_new_user', array( $this, 'register_influencer' ), 1, 1 );

            // add_filter( 'login_url', function ( $url ) {
            //     return home_url();
            // } );

            add_filter( 'login_redirect', function () {
                $redirect_url = home_url();
                $referer = $_SERVER['HTTP_REFERER'];
                if ( strpos( $referer, 'influencer' ) !== false ) {
                    $redirect_url .= '/influencer-dashboard';
                } elseif ( strpos( $referer, 'brand' ) !== false ) {
                    $redirect_url .= '/brand-dashboard';
                }
                return $redirect_url;
            } );

            $this->limit_acf_inputs();

            Beef_Short::init();

            add_filter( 'gettext', array( $this, 'replace_search_placeholder' ), 20, 1);

            add_action( 'wp_head', array( $this, 'css_blur_influencer_names' ), 1 );

            // Hide outofstock variations
            add_filter( 'woocommerce_variation_is_active', function ( $active, $variation ) {
                if ( ! $variation->is_in_stock() ) {
                    return false;
                }
                return $active;
            }, 10, 2 );

            add_action( 'admin_head', function () {
                if ( isset( $_GET['temp'] ) ) {
                    // $influencer_services = new Chilla_Products(  );
                    // $influencer_services->update_variable_product( ['title' => 'TestTitle', 'sku' => 'sku_tzzzdfssfdxest', 'post_status' => 'publish'] );
                    // $influencer_services->update_variation( ['price' => 17, 'category' => 'postbrand', 'platform' => 'twitter'] );
                    // $influencer_services->update_variation( ['price' => 17, 'category' => 'meetbrand', 'platform' => 'facebook'] );

                    // do_action( 'beef_update_product_variations', 1825 );
                }
            } );

        }

        public function update_influencer_product ( $options ) {
            $action = $options['action'];
            $user_id = $options['user_id'];
            $sku = $options['sku'];
            $field_names = [
                'product_title',
                'description',
                'featured_image',
            ];
            $product_data = [];
            foreach ( $field_names as $field_name ) {
                $product_data[$field_name] = get_field( $field_name, 'user_' . $user_id );
                
            }
            $product_options = [
                'author_id' => $user_id,
                'sku' => $sku,
                'title' => $product_data['product_title'],

            ];
            $product_id = isset( $options['product_id'] ) ? $options['product_id'] : 0;
            $influencer_services = new Chilla_Products( $product_id );
            // Create/Update Parent product
            $product_id = $influencer_services->update_variable_product( $product_options );

            $this->service_add_values_on_save_post( $product_id, 'user_' . $user_id );

            do_action( 'beef_update_product_variations', $product_id );

            $this->notification_newservice_admin( $product_id );
            
        }

        public function update_product_variations ( $product_id = 0 ) {
            if ( empty( $product_id ) ) {
                return;
            }
            $influencer_services = new Chilla_Products( $product_id );

            $service_categories = [
                'meetbrand' => 'Meet the Brand',
                'postbrand' => 'Post a Brand',
                'storybrand' => 'Story / Video a Brand',
            ];

            $author_id = get_post_field( 'post_author', $product_id );

            $facebook_likes         = get_field( 'facebook_likes', 'user_' . $author_id );
            $instagram_followers    = get_field( 'instagram_followers', 'user_' . $author_id );
            $tiktok_followers       = get_field( 'tiktok_followers', 'user_' . $author_id );
            $twitter_followers      = get_field( 'twitter_followers', 'user_' . $author_id );
            $youtube_subscribers    = get_field( 'youtube_subscribers', 'user_' . $author_id );
            $platforms = [
                'facebook'      => $facebook_likes->name,
                'instagram'     => $instagram_followers->name,
                'tiktok'        => $tiktok_followers->name,
                'twitter'       => $twitter_followers->name,
                'youtube'       => $youtube_subscribers->name,
            ];

            $eng_rate    = get_field( 'avgengagerate', 'user_' . $author_id );

            $variations_added = get_post_meta( $product_id, 'beef_variations_added', true );

            if ( empty( $variations_added ) ) {
                foreach ( $service_categories as $category_slug => $category_label ) {
                    foreach ( $platforms as $platform => $platform_followers ) {
                        $price_options = [
                            'category'      => $category_label,
                            'followers'     => $platform_followers,
                            'engagement'    => $eng_rate->name,
                        ];
                        $service_price = $this->get_product_price( $price_options );
                        $influencer_services->update_variation( ['price' => $service_price, 'category' => $category_slug, 'platform' => $platform] );
                    }   
                }
                update_post_meta( $product_id, 'beef_variations_added', true );
            } else {
                $variable_obj = new WC_Product_Variable( $product_id );
                $variations = $variable_obj->get_available_variations();
                foreach ( $variations as $variation ) {
                    $var_id = $variation['variation_id'];
                    $platform = $variation['attributes']['attribute_pa_serviceplatform'];
                    $platform_followers = $platforms[ $platform ];
                    $category_slug = $variation['attributes']['attribute_pa_servicecategory'];
                    $category_label = $service_categories[ $category_slug ];
                    $price_options = [
                        'category'      => $category_label,
                        'followers'     => $platform_followers,
                        'engagement'    => $eng_rate->name,
                    ];
                    $service_price = $this->get_product_price( $price_options );
                    $influencer_services->update_variation( ['id' => $var_id, 'price' => $service_price] );
                }
            }

        }

        public function replace_search_placeholder ( $translated ) {
            $translated = str_ireplace( 'Αναζήτηση προϊόντων&hellip;', 'Ψάχνω Influencer για&hellip;', $translated );
            return $translated;
        }

        public function css_blur_influencer_names () {
            echo '<style>body:not(.logged-in) h4.elementor-author-box__name {
                filter: blur(5px);
                user-select: none;
            }</style>';
        }

        public function register_influencer ( $user_id ) {
            if ( isset( $_POST['beefluence-register'] ) && $_POST['beefluence-register'] == 'influencer' ) {
                $user = new WP_User( $user_id );
                $user->set_role( 'influencer' );
                $this->notification_newinfluencer_admin( $user->user_login, $user->user_email );
            }
        }

        public function register_form_influencer () {
            $register_influencer = $this->is_register_influencer_page();
            if ( $register_influencer ) {
                echo '<input type="hidden" name="beefluence-register" value="influencer" />';
            }
        }

        public function is_register_influencer_page () {
            global $wp;
            $current_page = $wp->request;
            $referer = $_SERVER['HTTP_REFERER'];
            $influencer_reg_referer = false;
            $influencer_reg_page = false;
            $reg_page_after_error = false;
            if ( 'influencer-login' == $current_page && isset( $_GET['action'] ) && 'register' == $_GET['action'] ) {
                $influencer_reg_page = true;
            }
            if ( strpos( $referer, 'influencer-login/?action=register' ) !== false ) {
                $influencer_reg_referer = true;
            }
            if ( isset( $_POST['beefluence-register'] ) && $_POST['beefluence-register'] == 'influencer' ) {
                $reg_page_after_error = true;
            }
            return $influencer_reg_page || $influencer_reg_referer || $reg_page_after_error;
        }

        public function limit_acf_inputs () {
            $fields = [
                'area' => 3,
                'audience_age' => 2,
                'audience_gender' => 2,
                'industry' => 5,
    
                'social_platform' => 5,
                'audience_age_group' => 2,
            ];

            foreach ( $fields as $field => $limit ) {
                add_filter('acf/validate_value/name=' . $field, function ( $valid, $value ) use ( $limit ) {
                    if (count($value) > $limit) {
                        $valid = 'Επιλέξτε μέχρι ' . $limit;
                    }
                    return $valid;
                }, 20, 2);
            }
        }

        public function js_redirects () {
            $request_uri = $_SERVER['REQUEST_URI'];
            if ( strpos( $request_uri, 'influencer-dashboard' ) !== false && $_REQUEST['_acf_screen'] == 'acf_form' ) {
                echo '<script>setTimeout(function(){
                    window.location.replace("https://beefluence.gr/influencer-dashboard");
                },500);</script>';
            } elseif ( strpos( $request_uri, 'brand-dashboard' ) !== false && $_REQUEST['_acf_screen'] == 'acf_form' ) {
                echo '<script>setTimeout(function(){
                    window.location.replace("https://beefluence.gr/brand-dashboard");
                },500);</script>';
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
            $register_form = false;
            $register_link = "<a href=\"?action=register\">Εγγραφή</a>";
            if ( isset( $_GET['action'] ) && 'register' === $_GET['action'] ) {
                $register_form = true;
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
            global $wp;
            $current_page = $wp->request;
            if ( is_user_logged_in() ) {
                if ( $current_page == 'brand-login' ) {
                    wp_redirect( home_url( '/brand-dashboard' ) );
                    exit;
                } elseif ( $current_page == 'influencer-login' ) {
                    wp_redirect( home_url( '/influencer-dashboard' ) );
                    exit;
                }
            } elseif ( $current_page == 'brand-login' || $current_page == 'influencer-login' ) {
                $this->set_test_cookie();
            }
        }

        private function set_test_cookie () {
            // Set a cookie now to see if they are supported by the browser.
            $secure = ( 'https' === parse_url( wp_login_url(), PHP_URL_SCHEME ) );
            setcookie( TEST_COOKIE, 'WP Cookie check', 0, COOKIEPATH, COOKIE_DOMAIN, $secure );

            if ( SITECOOKIEPATH !== COOKIEPATH ) {
                setcookie( TEST_COOKIE, 'WP Cookie check', 0, SITECOOKIEPATH, COOKIE_DOMAIN, $secure );
            }
        }

        public function service_add_values_on_save_post ( $post_id, $influencer_id = '' ) {
            $product = wc_get_product( $post_id );
            if ( empty( $product ) || empty( $influencer_id ) ) {
                return;
            }
            // $request_uri = $_SERVER['REQUEST_URI'];
            
            // if ( strpos( $request_uri, 'influencer-dashboard' ) === false ) {
            //     $this->log( 'acf/save_post request: ' . $request_uri );
            //     return;    
            // }



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
                $product_fields[ $field_name ] = get_field( $field_name, $influencer_id );
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
                    // if ( in_array( $term, array( 'facebook_likes', 'instagram_followers', 'tiktok_followers', 'twitter_followers', 'youtube_subscribers' ), true ) ) {
                    //     $service_followers = $term_data->name;
                    // }
                }
                $attributes_arr[ $attribute_tax ] = $att_values;
            }



            if ( ! empty( $product_fields['gallery'] ) ) {
                $img_ids = [];
                foreach ( $product_fields['gallery'] as $img ) {
                    $img_ids[] = $img['gallery_img'];
                }
                $product->set_gallery_image_ids( $img_ids );
            }


            

            // $price = $this->get_product_price( 
            //     array(
            //         'followers'     => $service_followers,
            //         'engagement'    => $service_engagement,
            //         'category'      => $service_category,
            //     ) 
            // );
            // if ( ! empty( $price ) ) {
            //     $product->set_regular_price( $price );   
            // }
            $product->set_description( $product_fields['description'] );
            $product->set_short_description( $product_fields['description'] );
            $product->set_image_id( $product_fields['featured_image'] );
            $product->save();
            Chin_Tools::set_product_attribute_terms( $post_id, $attributes_arr );

        }

        protected function notification_newservice_admin ( $product_id ) {
            // $to_mail = get_option( 'admin_email' ) . ',ilias.p@wecommerce.gr,beefluence@gmail.com';
            $to_mail = get_option( 'admin_email' ) . ',ilias.p@wecommerce.gr';
            $subject = 'Beefluence | Νέα υπηρεσία προς έγκριση';
            $message = "Προστέθηκε μια νέα υπηρεσία προς έγκριση. Κωδικός υπηρεσίας: #{$product_id}";
            wp_mail( $to_mail, $subject, $message );
        }

        protected function notification_newinfluencer_admin ( $user_name, $user_email ) {
            $to_mail = get_option( 'admin_email' ) . ',ilias.p@wecommerce.gr,beefluence@gmail.com';
            $subject = 'Beefluence | Νέα εγγραφή Influencer';
            $message = "Νέος Influencer γράφτηκε στον ιστότοπο. Username: {$user_name} E-mail: {$user_email}";
            wp_mail( $to_mail, $subject, $message );
        }

        private function get_product_price ( $options ) {
            if ( !isset( $options['followers'] ) || !isset( $options['engagement'] ) || !isset( $options['category'] ) ) {
                return false;
            }
            $followers = $options['followers'];
            $engagement = $options['engagement'];
            $category = $options['category'];
            $price_calc = new Beef_Price( $category, $followers, $engagement );
            $price = $price_calc->get_price();

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

        public function user_details_save ( $post_id ) {
            if ( false === strpos( $post_id, 'user_' ) ) {
                return;
            }
            $current_user = wp_get_current_user();
            $current_user_id = $current_user->ID;
            $user_id = (int) str_replace( 'user_', '', $post_id );
            if ( $user_id != $current_user_id ) {
                return;
            }
            $user_roles = $current_user->roles;
            if ( in_array( 'advertiser', $user_roles ) ) {
                $this->save_advertiser_details( $current_user );
            } elseif ( in_array( 'influencer', $user_roles ) ) {
                $this->save_influencer_details( $current_user );
            }
            return;
        }

        protected function save_advertiser_details ( $user ) {
            $user_id = $user->ID;
            $fields_to_get = array(
                'company_name',
                'manager_name',
                'manager_surname',
                'email',
                'website',
                'company_phone',
                'mobile_phone',
                'afm',
                'irs',
                'company_activity',
            );
            $user_fields = [];
            foreach ( $fields_to_get as $field_name ) {
                $user_fields[ $field_name ] = get_field( $field_name, 'user_' . $user_id );
            }
            $user_data = array(
                'billing_company'       => $user_fields['company_name'],
                'first_name'            => $user_fields['manager_name'],
                'last_name'             => $user_fields['manager_surname'],
                'user_email'            => $user_fields['email'],
                'url'                   => $user_fields['website'],
                'billing_first_name'    => $user_fields['manager_name'],
                'billing_last_name'     => $user_fields['manager_surname'],
                'billing_email'         => $user_fields['email'],
                'billing_phone'         => $user_fields['mobile_phone'],
                'company_phone'         => $user_fields['company_phone'],
                'billing_vat'           => $user_fields['afm'],
                'billing_irs'           => $user_fields['irs'],
                'billing_store'         => $user_fields['company_activity']

            );
            foreach ( $user_data as $key => $val ) {
                update_user_meta( $user_id, $key, $val );
            }

        }

        protected function save_influencer_details ( $user ) {
            // Check if variable product exists for current user & create/update it
            $user_id = $user->ID;
            $product_sku = 'influencer_' . $user_id;
            $product_id = wc_get_product_id_by_sku( $product_sku );
            $args = [
                'user_id' => $user_id,
                'sku' => $product_sku,
            ];
            $pic_media_id = get_field( 'profile_picture', 'user_' . $user_id );
            if ( class_exists( 'Simple_Local_Avatars' ) ) {
                $avatar = new Simple_Local_Avatars();
                $avatar->set_avatar_rest( array( 'media_id' => $pic_media_id ), $user );
            }
            if ( empty( $product_id ) ) {
                $args['action'] = 'create';
                do_action( 'beef_update_influencer_product', $args );
                return;
            }
            $args['action'] = 'update';
            $args['product_id'] = $product_id;
            do_action( 'beef_update_influencer_product', $args );
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
                if ( empty( $services ) ) {
                    $ret_html .= '<p>Δεν υπάρχουν υπηρεσίες.</p>';
                } else {
                    $ret_html .= "<ul class=\"dashboard-services-list\">";
                        foreach ( $services as $service ) {
                            $orders = Chin_Tools::get_orders( $service['id'], 'product' );
                            $stock_checked = ( 'green' === $service['stock_status_color'] ? 'checked' : '' );
                            $service_active = ( 'green' === $service['stock_status_color'] ? 'active' : 'inactive' );
                            $service_url = ( 'green' === $service['product_status_color'] ? $service['url'] : '#' );
                            $ret_html .= "<li class=\"dashboard-services-list__serviceWrapper\">";
                                $ret_html .= "<div class=\"dashboard-services-list__service\" data-id=\"{$service['id']}\" data-status=\"{$service_active}\">";
                                    $ret_html .= "<div class=\"dashboard-services-list__serviceCol\">{$service['image']}</div>";
                                    $ret_html .= "<div class=\"dashboard-services-list__serviceCol\">";
                                        $ret_html .= "<h4 class=\"dashboard-services-list__serviceTitle\"><a href=\"{$service_url}\">{$service['title']} - #{$service['id']}</a></h4>";
                                        $ret_html .= "<span class=\"dashboard-services-list__servicePrice\">Τιμή: {$service['price']}</span>";
                                        if ( 'green' == $service['product_status_color'] ) {
                                            $ret_html .= "<div class=\"dashboard-services-list__serviceStock\">";
                                                $ret_html .= "<span class=\"dashboard-services-list__serviceStockstatus dashboard-services-list__service--{$service['stock_status_color']}\">{$service['stock_status']}</span>";
                                                $ret_html .= "<label class=\"chilla-toggle-switch dashboard-services-list__serviceStockswitch\"><input type=\"checkbox\" {$stock_checked}><span class=\"chilla-toggle-slider round\"></span></label>";
                                            $ret_html .= "</div>";
                                        }
                                    $ret_html .= "</div>";
                                    $ret_html .= "<div class=\"dashboard-services-list__serviceCol\">";
                                        $ret_html .= "<span class=\"dashboard-services-list__serviceProductstatus dashboard-services-list__service--{$service['product_status_color']}\">{$service['product_status']}</span>";
                                    $ret_html .= "</div>";
                                $ret_html .= "</div>";
                                if ( ! empty( $orders ) ) {
                                    $ret_html .= "<div class=\"dashboard-services-list__serviceOrders\">";
                                        $ret_html .= "<p class=\"dashboard-services-list__ordersToggle\">Παραγγελίες &darr;</p>";
                                        $ret_html .= "<div class=\"dashboard-services-list__orders\">";
                                        foreach ( $orders as $order_id ) {

                                            $order_obj = wc_get_order( $order_id );
                                            $date_obj = $order_obj->get_date_created();
                                            $date_added = $date_obj->date_i18n( 'd/m/Y' );
                                            $name = $order_obj->get_formatted_billing_full_name();
                                            $total_price = $order_obj->get_formatted_order_total();
                                            $ret_html .= "<div class=\"dashboard-services-list__order\">";
                                                $ret_html .= "<p>{$name}</p>";
                                                $ret_html .= "<div><span>Τιμή: {$total_price} - Ημερομηνία: {$date_added}</span></div>";
                                            $ret_html .= "</div>";
                                        }
                                        $ret_html .= "</div>";
                                    $ret_html .= "</div>";
                                }
                            $ret_html .= "</li>";
                        }
                    $ret_html .= "</ul>";
                }
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
            $query = "SELECT ID FROM {$wpdb->posts} WHERE post_type='product' AND post_author='{$user_id}' ORDER BY post_modified DESC";
            $services_ids = $wpdb->get_results( $query, 'ARRAY_A' );
            if ( empty( $services_ids ) ) {
                return array();
            }
            foreach ( $services_ids as $item ) {
                $id = $item['ID'];
                $product = wc_get_product( $id );
                $price = (float) wc_get_price_to_display( $product );
                $price = ( $price == 0 ? 'Επικοινωνήστε μαζί μας' : $price.'&euro;' );
                $stock_status = $product->get_stock_status();
                $product_status = $product->get_status();

                if ( $stock_status == 'instock' ) {
                    $stock_status = 'Ενεργή';
                    $stock_status_color = 'green';
                } else {
                    $stock_status = 'Ανενεργή';
                    $stock_status_color = 'red';
                }
                if ( $product_status === 'pending' ) {
                    $product_status = 'Αναμένεται έγκριση';
                    $product_status_color = 'orange';
                } elseif ( $product_status === 'publish' ) {
                    $product_status = 'Εγκρίθηκε';
                    $product_status_color = 'green';
                } else {
                    $product_status = 'Απορρίφθηκε';
                    $product_status_color = 'red';
                }

                $product_details = array(
                    'id'                    => $id,
                    'url'                   => $product->get_permalink(),
                    'title'                 => $product->get_title(),
                    'stock_status'          => $stock_status,
                    'stock_status_color'    => $stock_status_color,
                    'product_status'        => $product_status,
                    'product_status_color'  => $product_status_color,
                    'price'                 => $price,
                    'image'                 => $product->get_image(),
                );
                array_push( $ret_arr, $product_details );
            }
            return $ret_arr;
        }

        public function dashboard_change_stock_status () {
            if ( isset( $_GET['beefluence-service-activate'] ) ) {
                $status_set = 'instock';
                $prod_id = (int) $_GET['beefluence-service-activate'];
            } elseif ( isset( $_GET['beefluence-service-deactivate'] ) ) {
                $status_set = 'outofstock';
                $prod_id = (int) $_GET['beefluence-service-deactivate'];
            } else {
                return;
            }
            $product = wc_get_product( $prod_id );
            if ( empty( $product ) ) {
                return;
            }
            $service_author_id = (int) get_post_field( 'post_author', $prod_id ); // String
            $user = wp_get_current_user();
            $current_user_id = (int) $user->ID;
            if ( $service_author_id === $current_user_id ) {
                $product->set_stock_status( $status_set );
                $product->save();
            }

        }

        public function dashboard_section_orderedservices ( $args ) {
            $user_id = get_current_user_id();
            $orders_ids = Chin_Tools::get_orders( $user_id, 'customer' );
            
            $ret_html = '<div id="dashboard-orderedservices" class="dashboard-section dashboard-section__orderedservices">';
                $ret_html .= "<h3>{$args['label']}</h3>";
                $ret_html .= get_product_search_form( false );
                if ( empty( $orders_ids ) ) {
                    $ret_html .= '<p>Δεν υπάρχουν υπηρεσίες.</p>';
                } else {
                    $ret_html .= "<ul class=\"ordered-services\">";
                    foreach ( $orders_ids as $id ) {
                        $order = wc_get_order( $id );
                        $date_obj = $order->get_date_created();
                        $date_added = $date_obj->date_i18n( 'd/m/Y' );
                        $order_items = $order->get_items();
                        $ret_html .= "<li class=\"ordered-services__order\">";
                            $ret_html .= "<ul class=\"ordered-services__services\">";
                                foreach ( $order_items as $item ) {
                                    $product_obj = $item->get_product();
                                    if ( ! empty( $product_obj ) ) {
                                        $url = $product_obj->get_permalink();
                                        $thumbnail = $product_obj->get_image();
                                        $name = $product_obj->get_title();
                                        $price = (float) $item->get_subtotal();
                                        $price_tax = (float) $item->get_subtotal_tax();
                                        $total_price = $price + $price_tax;
                                        $total_price = ( $total_price == 0 ? 'Επικοινωνήστε μαζί μας' : $total_price.'&euro;' );
                                        $ret_html .= "<li class=\"ordered-services__service\">
                                            <div class=\"ordered-services__img\">{$thumbnail}</div>
                                            <div class=\"ordered-services__txtwrapper\">
                                            <a href=\"{$url}\"><h4 class=\"ordered-services__name\">{$name} - #{$product_obj->get_id()}</h4></a>
                                            <p class=\"ordered-services__details\">
                                                <span class=\"ordered-services__price\">Τιμή: {$total_price}</span>
                                                <span class=\"ordered-services__date\">Ημερομηνία: {$date_added}</span>
                                            </p>
                                            </div>
                                        </li>";
                                    }
                                    
                                    
                                }
                            $ret_html .= "</ul>";
                        $ret_html .= "</li>";

                    }
                    $ret_html .= "</ul>";
                }
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
            if ( in_array( 'advertiser', $roles ) ) {
                $sections[ 'orderedservices' ]  = 'Ιστορικό Υπηρεσιών';
                $sections[ 'accountdetails' ]   = 'Στοιχεία λογαριασμού';
                $sections[ 'logout' ]           = 'Αποσύνδεση';
            } elseif ( in_array( 'influencer', $roles ) ) {
                $sections[ 'myservices' ]       = 'Οι υπηρεσίες μου';
                $sections[ 'newservice' ]       = 'Νέα υπηρεσία';
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
                        'html_after_fields' => '<p>Το κόστος της υπηρεσίας υπολογίζεται αυτόματα από το beefluence.gr</p>',
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