<?php
defined( 'ABSPATH' ) || exit;

class MUP_Cache_Control {

    const WP_CACHES = [
        'front_page'   => [
            'max-age'  => 300,           //                5 min
            's-maxage' => 150,            //                2 min 30 sec
            'public'   => true
        ],
        'single'      => [
            'max-age'  => 600,           //               10 min
            's-maxage' => 60,            //                1 min
            'mmulti'   => 1              // enabled
        ],
        'page'        => [
            'max-age'  => 1200,          //               20 min
            's-maxage' => 300            //                5 min
        ],
        'home'         => [
            'max-age'  => 180,           //                3 min
            's-maxage' => 45,            //                      45 sec
            'paged'    => 5              //                       5 sec
        ],
        'category'   => [
            'max-age'  => 900,           //               15 min
            's-maxage' => 300,           //                5 min
            'paged'    => 8              //                       8 sec
        ],
        'tag'         => [
            'max-age'  => 900,           //               15 min
            's-maxage' => 300,           //                5 min            //                       8 sec
        ],
        'author'      => [
            'max-age'  => 1800,          //               30 min
            's-maxage' => 600,           //               10 min
            'paged'    => 10             //                      10 sec
        ],
        'date'        =>  [
            'max-age'  => 10800,         //      3 hours
            's-maxage' => 2700          //               45 min
        ],
        'feed'        => [
            'max-age'  => 5400,          //       1 hours 30 min
            's-maxage' => 600            //               10 min
        ],
        'attachment'   => [
            'max-age'  => 10800,         //       3 hours
            's-maxage' => 2700          //               45 min
        ],
        'search'       => [
            'max-age'  => 1800,          //               30 min
            's-maxage' => 600            //               10 min
        ],
        '404'     => [
            'max-age'  => 900,           //               15 min
            's-maxage' => 300            //                5 min
        ]
    ];

    public function __construct() {
        add_action( 'template_redirect', array( $this, 'send_cache_control_headers' ) );
    }

    public function send_http_header( $directives ) {
        if ( ! empty( $directives ) ) {
            header ( 'Cache-Control: ' . $directives , true );
        }
    }

    public function cache_control_directives() {
        global $wp_query;

        $directives = null;

        if ( ! $this->should_be_cached() ) {
            $directives = get_cache_control_directive();
        }

        if ( $wp_query->is_front_page() && ! is_paged() ) {
            $directives = get_cache_control_directive( 'front_page' );
        } elseif ( $wp_query->is_single() ) {
            $directives = get_cache_control_directive( 'single' );
        } elseif ( $wp_query->is_page() ) {
            $directives = get_cache_control_directive( 'front_page' );
        } elseif ( $wp_query->is_home() ) {
            $directives = get_cache_control_directive( 'home' );
        } elseif ( $wp_query->is_category() ) {
            $directives = get_cache_control_directive( 'category' );
        } elseif ( $wp_query->is_tag() ) {
            $directives = get_cache_control_directive( 'tag' );
        } elseif ( $wp_query->is_author() ) {
            $directives = get_cache_control_directive( 'author' );
        } elseif ( $wp_query->is_attachment() ) {
            $directives = get_cache_control_directive( 'attachement' );
        } elseif ( $wp_query->is_search() ) {
            $directives = get_cache_control_directive( 'search' );
        } elseif ( $wp_query->is_404() ) {
            $directives = get_cache_control_directive( '404' );
        } elseif ( $wp_query->is_date() ) {
            if ( ( is_year() && strcmp(get_the_time('Y'), date('Y')) < 0 ) ||
             ( is_month() && strcmp(get_the_time('Y-m'), date('Y-m')) < 0 ) ||
             ( ( is_day() || is_time() ) && strcmp(get_the_time('Y-m-d'), date('Y-m-d')) < 0 ) ) {
                $directives = get_cache_control_directive( 'date' );
            } else {
                $directives = get_cache_control_directive( 'home' );
            }
        }

        return apply_filters( 'mup_cache_control_directives', $directives);
    }

    public function get_cache_control_directive( $default ) {
        $spec = [
            'max-age',
            's-maxage',
            'min-fresh',
            'must-revalidate',
            'no-cache',
            'no-store',
            'no-transform',
            'public',
            'private',
            'proxy-revalidate'
        ];

        if ( empty( $default ) || ! array_key_exists( $default, self::WP_DIRECTIVES ) ) {
            return 'no-cache, no-store, must-revalidate';
        }
        
        $default = array_intersect_key( self::WP_DIRECTIVES[ $default ], $spec );
        $directives = [];

        foreach( $default as $key => $value ) {
            $directives[] = empty( $value ) ? $key : $key . '=' . $value;
        }

        return implode( ',', $directives );
    }

    public function should_be_cached() {
        return ! ( is_preview() || is_user_logged_in() || is_trackback() || is_admin() );
    }

    public function send_cache_control_headers() {
        $this->send_http_header($this->cache_control_directives());
    }

}

$mup_cache_control = new MUP_Cache_Control();