<?php
defined( 'ABSPATH' ) || exit;

class MUP_Cache_Control {

    const cache_control_defaults = [
        'front_page'   => [
            'max-age'  => 300,           //                5 min
            's-maxage' => 150            //                2 min 30 sec
        ],
        'singles'      => [
            'max-age'  => 600,           //               10 min
            's-maxage' => 60,            //                1 min
            'mmulti'   => 1              // enabled
        ],
        'pages'        => [
            'max-age'  => 1200,          //               20 min
            's-maxage' => 300            //                5 min
        ],
        'home'         => [
            'max-age'  => 180,           //                3 min
            's-maxage' => 45,            //                      45 sec
            'paged'    => 5              //                       5 sec
        ],
        'categories'   => [
            'max-age'  => 900,           //               15 min
            's-maxage' => 300,           //                5 min
            'paged'    => 8              //                       8 sec
        ],
        'tags'         => [
            'max-age'  => 900,           //               15 min
            's-maxage' => 300,           //                5 min            //                       8 sec
        ],
        'authors'      => [
            'max-age'  => 1800,          //               30 min
            's-maxage' => 600,           //               10 min
            'paged'    => 10             //                      10 sec
        ],
        'dates'        =>  [
            'max-age'  => 10800,         //      3 hours
            's-maxage' =>  2700          //               45 min
        ],
        'feeds'        => [
            'max-age'  => 5400,          //       1 hours 30 min
            's-maxage' => 600            //               10 min
        ],
        'attachment'   => [
            'max-age'  => 10800,         //       3 hours
            's-maxage' =>  2700          //               45 min
        ],
        'search'       => [
            'max-age'  => 1800,          //               30 min
            's-maxage' => 600            //               10 min
        ],
        'notfound'     => [
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
        $directives = null;

        if ( ! $this->should_be_cached() ) {
            $directives = get_cache_control_directive();
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

        if ( empty( $default ) || ! array_key_exists( $default, self::cache_control_defaults ) ) {
            return 'no-cache, no-store, must-revalidate';
        }
        
        $default = array_intersect_key( self::cache_control_defaults[ $default ], $spec );
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