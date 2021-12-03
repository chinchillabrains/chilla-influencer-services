<?php
namespace beefluence;

class Service_Price_Calc {
    private $followers;
    private $followers_tier;
    private $influencer_fee;
    private $influencer_fee_percentage;
    private $total_price;

    
    function __construct ( $followers, $influencer_fee, $influencer_fee_percentage = 0.15 ) {
        $this->followers = (int) $followers;

        $this->influencer_fee = (float) $influencer_fee;
        
        $this->influencer_fee_percentage = (float) $influencer_fee_percentage;

        $this->total_price = false;
        
        $this->build_tiers();
        
        $this->calculate_price();
    }

    private function build_tiers () {
        $this->build_followers_tier();
        return true;
    }

    private function build_followers_tier () {
        $available_followers = [
            [
                'floor'     => 0,
                'ceil'      => 24999,
                'charge'    => 7.99,
            ],
            [
                'floor'     => 25000,
                'ceil'      => 39999,
                'charge'    => 9.99,
            ],
            [
                'floor'     => 40000,
                'ceil'      => 59999,
                'charge'    => 11.99,
            ],
            [
                'floor'     => 60000,
                'ceil'      => 79999,
                'charge'    => 13.99,
            ],
            [
                'floor'     => 80000,
                'ceil'      => 99999,
                'charge'    => 15.99,
            ],
            [
                'floor'     => 100000,
                'ceil'      => 119999,
                'charge'    => 17.99,
            ],
            [
                'floor'     => 120000,
                'ceil'      => 139999,
                'charge'    => 19.99,
            ],
            [
                'floor'     => 140000,
                'ceil'      => 159999,
                'charge'    => 21.99,
            ],
            [
                'floor'     => 160000,
                'ceil'      => 179999,
                'charge'    => 23.99,
            ],
            [
                'floor'     => 180000,
                'ceil'      => 199999,
                'charge'    => 25.99,
            ],
            [
                'floor'     => 200000,
                'ceil'      => 204999,
                'charge'    => 27.99,
            ],
        ];
        
        $this->followers_tier = $available_followers;    
        return true;
    }

    private function calculate_price () {
        
        foreach ( $this->followers_tier as $tier ) {
            if ( $tier['floor'] <= $this->followers && $this->followers <= $tier['ceil'] ) {
                $price = $tier['charge'] + ($this->influencer_fee_percentage * $this->influencer_fee);
                break;
            }
        }
        if (  205000 <= $this->followers ) {
            $price = 0;
        }

        $this->total_price = $price;
    }

    public function get_price () {
        return $this->total_price;
    }
}