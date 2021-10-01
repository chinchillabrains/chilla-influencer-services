<?php
namespace beefluence;

class Service_Price_Calc {
    private $category, $followers, $engagement;
    private $category_code, $followers_tier, $engagement_tier;
    private $total_price;

    
    function __construct ( $category, $followers, $engagement ) {
        $this->category     = $category;
        $this->followers    = $followers;
        $this->engagement   = $engagement;

        $this->total_price = false;
        
        $success = $this->build_tiers();

        if ( $success ) {
            $this->calculate_price();
        }
    }

    private function build_tiers () {
        $done1 = $this->build_category_code();
        $done2 = $this->build_followers_tier();
        $done3 = $this->build_engagement_tier();
        if ( $done1 && $done2 && $done3 ) {
            return true;
        }
        return false;
    }

    private function build_category_code () {
        $available_categories = [
            'Post a Brand' => 'post',
            'Story / Video a Brand' => 'story',
            'Meet the Brand' => 'meet'
        ];
        if ( isset( $available_categories[ $this->category ] ) ) {
            $this->category_code = $available_categories[ $this->category ];
            return true;
        }
        return false;
    }

    private function build_followers_tier () {
        $available_followers = [
            '3k-5k'     => 0,
            '6k-9k'     => 1,
            '10k-25k'   => 2,
            '26k-40k'   => 3,
            '41k-55k'   => 4,
            '56k-70k'   => 5,
            '71k-85k'   => 6,
            '86k-100k'  => 7,
            '101k-115k' => 8,
            '116k-130k' => 9,
            '131k-145k' => 10,
            '146k-160k' => 11,
            '161k-175k' => 12,
            '176k-190k' => 13,
            '191k-205k' => 14,
            '206k-220k' => 15
        ];
        if ( isset( $available_followers[ $this->followers ] ) ) {
            $this->followers_tier = $available_followers[ $this->followers ];
            return true;
        }
        return false;
    }

    private function build_engagement_tier () {
        $available_engagement = [
            '0,5-2%'    => 0,
            '2,5-5%'    => 1,
            '5,5-10%'   => 2
        ];
        if ( isset( $available_engagement[ $this->engagement ] ) ) {
            $this->engagement_tier = $available_engagement[ $this->engagement ];
            return true;
        }
        return false;
    }

    private function calculate_price () {
        $base_category_cost = [
            'post'  => 20,
            'story' => 15,
            'meet'  => 40
        ];
        $category_followers_modifier_list = [
            'post' => [
                0,
                15,
                25,
                20
            ],
            'story' => [
                0,
                15,
                20
            ],
            'meet' => [
                0,
                15,
                30,
                25,
                20,
                25,
                20,
                20,
                25,
                20
            ]
        ];
        // Adds 5 euro for each engagement tier
        $cost_per_tier = 5;
        $engagement_tier_modifier = $cost_per_tier * $this->engagement_tier;

        $price = $base_category_cost[ $this->category_code ];
        $price += $engagement_tier_modifier;

        $followers_added_cost = $category_followers_modifier_list[ $this->category_code ];
        for ( $i = 0; $i <= $this->followers_tier; $i++ ) {
            $cost_index = $i;
            if ( $cost_index > array_key_last( $followers_added_cost ) ) {
                $cost_index = array_key_last( $followers_added_cost );
            }
            $price += $followers_added_cost[ $cost_index ];
        }
        $this->total_price = $price;
    }

    public function get_price () {
        return $this->total_price;
    }
}