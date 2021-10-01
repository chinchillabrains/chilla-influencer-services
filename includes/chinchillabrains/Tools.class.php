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
        
        $product_attributes = array();
        foreach ( $combined_attributes as $tax => $terms ) {
            $product_attributes[ $tax ] = array(
                'name' => $tax,
                'value' => $terms,
                'is_visible' => 1,
                'is_variation' => 0,
                'is_taxonomy' => 1
            );
        }

        $update_result = update_post_meta( $product_id, '_product_attributes', $product_attributes );
        
        
    }
    public function log ( $data ) {
        ob_start();
        var_dump( $data );
        $data_str = ob_get_clean();
        $log_file = fopen( __DIR__ . '/log.txt', 'a+' );
        fwrite( $log_file, $data_str . PHP_EOL );
        fclose( $log_file );
    }
}