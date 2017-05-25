<?php

defined( 'ABSPATH' ) || exit;

class MUP_Add_Headers {

    const DEFAULTS = [
        'add_etag_header' => true,
        'generate_weak_etag' => false,
        'add_last_modified_header' => true,
        'add_expires_header' => true,
        'add_backwards_cache_control' => true,
        'cache_max_age_seconds' => 0,
        'remove_pre_existing_headers' => false,
    ];

    public function __construct() {
        add_action( 'template_redirect', array( $this, 'add_headers' ) );
    }

    public function send_headers_for_object( $defaults ) {
        $post = get_queried_object();

        if ( ! is_object( $post) || ! isset( $post->post_type ) ) {
            return;
        }

        // should check for post types

        if ( post_password_required() ) {
            return;
        }

        $post_mtime = $post->post_modified_gmt;
        $post_mtime_unix = strtotime( $post_mtime );

        $mtime = $post_mtime_unix;

        $this->send_headers( $post, $mtime, $defaults );
    }

    public function send_headers_for_archive( $defaults ) {
        global $posts;
        
        if ( empty($posts) ) {
            return;
        }
        $post = $posts[0];

        if ( ! is_object($post) || ! isset($post->post_type) ) {
            return;
        }

        $post_mtime = $post->post_modified_gmt;
        $mtime = strtotime( $post_mtime );

        $this->send_headers( $post, $mtime, $defaults );
    }

    public function send_headers( $post, $mtime, $defaults ) {
        $headers = [];
        $supported_headers = [
            'ETag',
            'Last-Modified',
            'Expires',
            'Cache-Control',
            'Pragma'
        ];

        if ( true === $defaults['add_etag_header'] ) {
            $headers['ETag'] = $this->get_etag_header( $post, $mtime, $defaults );
        }

        if ( true === $defaults['add_last_modified_header'] ) {
            $headers['Last-Modified'] = $this->get_last_modified_header( $post, $mtime, $defaults );
        }

        if ( true === $defaults['add_expires_header'] ) {
            $headers['Expires'] = $this->get_expires_header( $post, $mtime, $defaults );
        }

        if ( true === $defaults['add_backwards_cache_control'] ) {
            $headers['Expires'] = $this->get_pragma_header( $post, $mtime, $defaults );
        }

        $headers = apply_filters( 'mup_add_headers_send', $headers );

        if ( headers_sent() ) {
            // should error?!
            return;
        }

        if ( true === $defaults['remove_pre_existing_headers'] ) {
            // should do something ;)
        }

        foreach( $headers as $key => $value ) {
            header( sprintf('%s: %s', $key, $value) );
        }

    }

    public function get_last_modified_header( $post, $mtime, $defaults ) {
        return str_replace( '+0000', 'GMT', gmdate('r', $mtime) );
    }

    public function get_expires_header( $post, $mtime, $defaults ) {
        return str_replace( '+0000', 'GMT', gmdate('r', time() + $options['cache_max_age_seconds'] ) );
    }

    public function get_pragma_header( $post, $mtime, $defaults ) {
        if ( intval($options['cache_max_age_seconds']) > 0 ) {
            return 'public';
        };
        return 'no-cache';
    }

    public function get_etag_header( $post, $mtime, $defaults ) {
        global $wp;

        $to_hash = array( $mtime, $post->post_date_gmt, $post->guid, $post->ID, serialize( $wp->query_vars ) );
        $etag = sha1( serialize( $to_hash ) );

        if ( true === $default['generate_weak_etag'] ) {
            return sprintf( 'W/"%s"', $etag );
        }

        return sprintf( '"%s"', $etag );
    }

    public function add_headers() {
        global $wp_query;

        if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
            return;
        } elseif( defined('XMLRPC_REQUEST') && XMLRPC_REQUEST ) {
            return;
        } elseif( defined('REST_REQUEST') && REST_REQUEST ) {
            return;
        } elseif ( is_admin() ) {
            return;
        }

        $defaults = apply_filters( 'mup_add_headers_defaults', self::DEFAULTS );

        if ( $wp_query->is_feed() || $wp_query->is_archive() || $wp_query->is_search() || $wp_query->is_home() ) {
            $this->addh_set_headers_for_archive( $defaults );
        } elseif ( $wp_query->is_singular() ) {
            $this->addh_set_headers_for_object( $defaults );
        }

        return;
    }

}

$mup_add_headers = new MUP_Add_Headers();