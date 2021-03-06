<?php
namespace chinchillabrains;

class Tools {

    public static function set_product_attribute_terms ( $product_id, $input_attributes, $append = false ) {
        $current_attributes = get_post_meta( $product_id, '_product_attributes', true );
        if ( empty( $current_attributes ) ) {
            $current_att_keys = array();    
        } else {
            $current_att_keys = array_keys( $current_attributes );
        }
        $input_att_keys = array_keys( $input_attributes );

        $common_att_keys = array_intersect( $current_att_keys, $input_att_keys );
        $current_only_keys = array_diff( $current_att_keys, $input_att_keys );
        $input_only_att_keys = array_diff( $input_att_keys, $current_att_keys );

        $combined_attributes = array();
        foreach ( $common_att_keys as $tax ) {
            if ( $append ) {
                $combined_attributes[ $tax ] = array_unique( array_merge( $current_attributes[ $tax ]['value'], $input_attributes[ $tax ] ) );
            } else {
                $combined_attributes[ $tax ] = $input_attributes[ $tax ];
            }
        }
        foreach ( $current_only_keys as $tax ) {
            $combined_attributes[ $tax ] = $current_attributes[ $tax ]['value'];
        }
        foreach ( $input_only_att_keys as $tax ) {
            $combined_attributes[ $tax ] = $input_attributes[ $tax ];
        }

        foreach ( $input_attributes as $tax => $terms ) {
            $term_count = 0;
            foreach ( $terms as $term_value ) {
                if ( ! $append && $term_count === 0 ) {
                    $term_append = false;
                } else {
                    $term_append = true;
                }
                wp_set_object_terms( $product_id, $term_value, $tax, $term_append );
                $term_count++;
            }
        }
        $variation_attrs = [
            'pa_servicecategory',
            'pa_serviceplatform'
        ];
        $product_attributes = array();
        foreach ( $combined_attributes as $tax => $terms ) {
            $is_variation = in_array( $tax, $variation_attrs ) ? 1 : 0;
            $non_tax_attrs = [
                'pa_facebooklikesnum'
            ];
            $is_taxonomy = in_array( $tax, $non_tax_attrs ) ? 0 : 1;
            $product_attributes[ $tax ] = array(
                'name' => $tax,
                'value' => $terms,
                'is_visible' => 1,
                'is_variation' => $is_variation,
                'is_taxonomy' => $is_taxonomy
            );
        }

        $update_result = update_post_meta( $product_id, '_product_attributes', $product_attributes );
        
        
    }

    public static function update_product_followers_filters ( $post_id, $followers ) {

        $taxonomies_to_set = [];
        if ( isset( $followers['facebook_likes_num'] ) ) {
            $taxonomies_to_set['facebook_likes'] = (string) $followers['facebook_likes_num'];
        }
        if ( isset( $followers['instagram_followers_num'] ) ) {
            $taxonomies_to_set['instagram_followers'] = (string) $followers['instagram_followers_num'];
        }
        if ( isset( $followers['tiktok_followers_num'] ) ) {
            $taxonomies_to_set['tiktok_followers'] = (string) $followers['tiktok_followers_num'];
        }
        if ( isset( $followers['twitter_followers_num'] ) ) {
            $taxonomies_to_set['twitter_followers'] = (string) $followers['twitter_followers_num'];
        }
        if ( isset( $followers['youtube_subscribers_num'] ) ) {
            $taxonomies_to_set['youtube_subscribers'] = (string) $followers['youtube_subscribers_num'];
        }

        foreach ( $taxonomies_to_set as $tax => $value ) {
            if ( ! term_exists( $value, $tax ) ) {
                wp_insert_term( $value, $tax );
            } else {
                wp_set_post_terms( $post_id, $value, $tax, false );
            }
        }
    }

    public static function get_orders ( $object_id, $search_by ) {
        global $wpdb;
        if ( $search_by == 'product' ) {
            $orders_query = "SELECT order_items.order_id AS ID
            FROM {$wpdb->prefix}woocommerce_order_items as order_items
            LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta as order_item_meta ON order_items.order_item_id = order_item_meta.order_item_id
            LEFT JOIN {$wpdb->posts} AS posts ON order_items.order_id = posts.ID
            WHERE posts.post_type = 'shop_order'
            AND order_items.order_item_type = 'line_item'
            AND order_item_meta.meta_key = '_product_id'
            AND order_item_meta.meta_value = '{$object_id}'";
        } elseif ( $search_by == 'customer' ) {
            $orders_query = "SELECT ID FROM {$wpdb->posts} WHERE post_type='shop_order' AND ID IN (SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key='_customer_user' AND meta_value='{$object_id}') ORDER BY post_modified_gmt DESC";
        } else {
            return false;
        }
        $result = $wpdb->get_results( $orders_query, 'ARRAY_A' );
        $ret_arr = [];
        if ( empty( $result ) ) {
            return false;
        }
        foreach ( $result as $order ) {
            $ret_arr[] = (int) $order['ID'];
        }
        return $ret_arr;
    }

    protected static function log ( $data ) {
        ob_start();
        var_dump( $data );
        $data_str = ob_get_clean();
        $log_file = fopen( __DIR__ . '/log.txt', 'a+' );
        fwrite( $log_file, $data_str . PHP_EOL );
        fclose( $log_file );
    }
}