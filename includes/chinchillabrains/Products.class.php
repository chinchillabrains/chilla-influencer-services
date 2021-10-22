<?php


class Chilla_Products {

    public $parent_id = 0;

    public function __construct ( $parent_id ) {
        $this->parent_id = isset( $parent_id ) ? $parent_id : 0;
    }

    public function update_variable_product ( $product_data = [] ) {
        $post_status = isset( $product_data['post_status'] ) ? $product_data['post_status'] : 'pending';
        if ( empty( $this->parent_id ) ) {
            $variable_obj = new WC_Product_Variable();
            $variable_obj->set_sku( $product_data['sku'] );
            $product_attributes = $this->new_product_attributes();
            $variable_obj->set_attributes( $product_attributes );
            $variable_obj->set_status( $post_status );
        } else {
            $variable_obj = new WC_Product_Variable( $this->parent_id );
            if ( isset( $product_data['post_status'] ) ) {
                $variable_obj->set_status( $product_data['post_status'] );
            }
        }
        if ( isset( $product_data['title'] ) ) {
            $variable_obj->set_name( $product_data['title'] );
        }


        $ret = $variable_obj->save();
        if ( ! empty( $ret ) ) {
            $this->parent_id = $ret;
        }
        return $ret;
    }

    public function new_product_attributes () {

        $product_attributes = [];
        $attrs_data = [
            [
                'id' => 13,
                'slug' => 'pa_servicecategory',
                'attributes' => [
                    'Meet the Brand',
                    'Post a Brand',
                    'Story / Video a Brand',
                ]
            ],
            [
                'id' => 14,
                'slug' => 'pa_serviceplatform',
                'attributes' => [
                    'Facebook',
                    'Instagram',
                    'TikTok',
                    'Twitter',
                    'Youtube',
                ]
            ],
        ];

        foreach ( $attrs_data as $attr ) {
            //Create the attribute object
            $attribute = new WC_Product_Attribute();
            //pa_size tax id
            $attribute->set_id( $attr['id'] );
            //pa_size slug
            $attribute->set_name( $attr['slug'] );
    
            //Set terms Names
            $attribute->set_options( $attr['attributes'] );
            $attribute->set_position( 0 );
            //If enabled
            $attribute->set_visible( 1 );
            //If we are going to use attribute in order to generate variations
            $attribute->set_variation( 1 );
            
            $product_attributes[] = $attribute;
        }

        return $product_attributes;
        
    }

    public function update_variation ( $product_data = [] ) {
        if ( ! isset( $product_data['id'] ) ) {
            $variation_obj = new WC_Product_Variation();
            $variation_obj->set_parent_id( $this->parent_id );
        } else {
            $variation_obj = new WC_Product_Variation( $product_data['id'] );
        }
        $variation_obj->set_regular_price( $product_data['price'] );

        // tax and term slug
        $variation_obj->set_attributes(
            array(
                'pa_servicecategory' => $product_data['category'],
                'pa_serviceplatform' => $product_data['platform'],
            )
        );
        $ret = $variation_obj->save();
        return $ret;
    }

}