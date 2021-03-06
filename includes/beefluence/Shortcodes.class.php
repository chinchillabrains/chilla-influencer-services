<?php
namespace beefluence;

class Shortcodes {

    protected static $instance = null;
    
    function __construct () {
        add_shortcode( 'service_influencer_engagement', array( $this, 'service_influencer_engagement' ) );
        add_shortcode( 'service_followers', array( $this, 'service_followers' ) );
        add_shortcode( 'influencer_field', array( $this, 'influencer_fields' ) );
        add_shortcode( 'influencer_pricelist', array( $this, 'influencer_pricelist' ) );
    }

    public function influencer_pricelist () {
        $ret_html = '';
        $author_id = get_the_author_id();
        $pricelist = get_field( 'price_list', 'user_' . $author_id );
        $labels = [
            'post_prices'   => 'Post a Brand',
            'story_prices'  => 'Story/Video a Brand',
            'meet_prices'   => 'Meet the Brand',

            'instagram_price'   => 'Instagram',
            'facebook_price'    => 'Facebook',
            'twitter_price'     => 'Twitter',
            'youtube_price'     => 'Youtube',
            'tiktok_price'      => 'TikTok',
        ];
        $prices_html = '<div id="beefluence-pricelist"><h4>Ενδεικτικός Τιμοκατάλογος</h4>';
        $total_values = 0;
        foreach ( $pricelist as $key => $prices ) {
            $cat_html = "<table class=\"beefluence-pricelist__table\"><thead><th class=\"beefluence-pricelist__tableHead\" colspan=\"2\">{$labels[ $key ]}</th></div>";
            $cat_values = 0;
            foreach ( $prices as $platform => $value ) {
                if ( empty( $value ) ) {
                    continue;
                }
                $platform_html = "<tr><td class=\"beefluence-pricelist__platform\">{$labels[ $platform ]}</td><td class=\"beefluence-pricelist__value\">{$value}&euro;</td></tr>";
                $cat_html .= $platform_html;
                $cat_values += (float) $value;
                $total_values += (float) $value;
            }
            $cat_html .= "</table>";
            if ( $cat_values > 0 ) {
                $prices_html .= $cat_html;
            }
        }
        $prices_html .= '</div>';
        if ( $total_values > 0 ) {
            $ret_html = $prices_html;
        }
        return $ret_html;
    }

    public function influencer_fields ( $args ) {
        if ( ! isset( $args['field'] ) ) {
            return;
        }

        if ( isset( $args['field'] ) ) {
            $field_slug = trim( $args['field'] );
        }
        if ( isset( $args['title'] ) ) {
            $field_title = trim( $args['title'] );
        } else {
            $field_title = '';
        }
        $author_id = get_the_author_id();
        $field_data = get_field( $field_slug, 'user_' . $author_id );
        $field_data_display = $this->get_display_data( $field_data );
        if ( empty( $field_data_display ) ) {
            return '';
        }
        $ret_html .= "<div class=\"beef-service-featured-stats\"><span class=\"beef-service-featured-stats__stat beef-service-featured-stats__stat--top\">{$field_data_display}</span><span class=\"beef-service-featured-stats__stat beef-service-featured-stats__stat--bot\">{$field_title}</span></div>";

        return $ret_html;
    }

    public function get_display_data ( $data ) {
        $ret_str = '';
        if ( empty( $data ) ) {
            return $ret_str;
        }
        if ( is_array( $data ) ) {
            $count = 0;
            foreach ( $data as $item ) {
                $ret_str .= $count > 0 ? PHP_EOL : '';
                $ret_str .= $this->display_item_data( $item );
                $count++;
            }
        } else {
            $ret_str .= $this->display_item_data( $data );
        }
        return $ret_str;
    }

    public function display_item_data ( $item ) {
        $type = gettype( $item );
        if ( 'string' === $type || 'integer' === $type || 'double' === $type ) {
            return $item;
        }
        if ( 'object' === $type && 'WP_Term' === get_class( $item ) ) {
            return $item->name;
        }
    }

    public function service_followers () {
        global $post;
        $post_id = $post->ID;
        $ret_html = '';
        $follower_fields = array(
            'facebook_likes'        => 'Facebook Likes',
            'instagram_followers'   => 'Instagram Followers',
            'tiktok_followers'      => 'TikTok Followers',
            'twitter_followers'     => 'Twitter Followers',
            'youtube_subscribers'   => 'Youtube Subscribers',
        );
        foreach ( $follower_fields as $field => $label ) {
            $followers = get_field( $field, $post_id );
            if ( ! empty( $followers ) ) {
                $ret_html .= "<div class=\"beef-service-featured-stats\"><span class=\"beef-service-featured-stats__stat beef-service-featured-stats__stat--top\">{$followers->name}</span><span class=\"beef-service-featured-stats__stat beef-service-featured-stats__stat--bot\">{$label}</span></div>";
                break;
            }
        }
        return $ret_html;
    }

    public function service_influencer_engagement ( $args ) {
        global $post;
        $ret_html = '';
        $title = 'Engagement Rate';
        if ( isset( $args['title'] ) ) {
            $title = $args['title'];
        }
        
        $user_id = $post->post_author;
        $eng_rate = get_field( 'mean_engagement_rate_txt', 'user_' . $user_id );
        if ( ! empty( $eng_rate ) ) {
            $ret_html .= "<div class=\"beef-service-featured-stats\"><span class=\"beef-service-featured-stats__stat beef-service-featured-stats__stat--top\">{$eng_rate}</span><span class=\"beef-service-featured-stats__stat beef-service-featured-stats__stat--bot\">{$title}</span></div>";
        }
        return $ret_html;
    }

    public static function init () {
        // If the single instance hasn't been set, set it now.
        if ( self::$instance == null ) {
            self::$instance = new self;
        }

        return self::$instance;
    }

}