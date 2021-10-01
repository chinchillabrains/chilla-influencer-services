<?php

// require_once( __DIR__ . '/includes/chinchillabrains/Tools.class.php' );
// use chinchillabrains\Tools as Chin_Tools;

add_action( 'admin_head', function () {
    if ( isset( $_GET['run-custom-code'] ) ) {

        // Chin_Tools::test();

        // \chinchillabrains\Tools::set_product_terms();


        // $product_id = 1461;
        // $values = array(
        //     'pa_area' => array(
        //       'Αθήνα (Β.Προάστια)',
        //       'Αθήνα (Ν.Προάστια)',
        //     ),
        //     'pa_basic-audience-gender' => array(
        //       'Άνδρες',
        //     ),
        //     'pa_basic-audience-age' => array(
        //       '55-64',
        //     ),
        //     'pa_insdustry' => array(
        //       'Accessories',
        //     ),
        //     'pa_avgengagerate' => array(
        //       '2,5-5%',
        //     ),
        //     'pa_facelikes' => array(
        //       '116k-130k',
        //     ),
        //   );


        // as_enqueue_async_action( 'chilla_update_product_attributes', array(
        //     array(
        //         'product_id' => 1478,
        //         'attributes' => $values
        //     )
        // ) );
        
        // \chinchillabrains\Tools::set_product_attribute_terms( $product_id, $values );
        
        // var_dump( get_post_meta( 1443, '_product_attributes', true ) );
        
        
        // $key = 'basic-audience-gender';
        
        // $product_attributes = [];
        // foreach ( $values as $key => $val ) {
        //     foreach ( $val as $value ) {
        //         $term_taxonomy_ids = wp_set_object_terms( $product_id, $value, 'pa_'.$key, true );
        //     }
        //     $product_attributes['pa_'.$key] = array(
        //         'name' => 'pa_'.$key,
        //         'value' => $val,
        //         'is_visible' => 1,
        //         'is_variation' => 0,
        //         'is_taxonomy' => 1
        //     );
        // }

        // //Add as post meta
        // update_post_meta( $product_id, '_product_attributes', $product_attributes );

    }
} );
