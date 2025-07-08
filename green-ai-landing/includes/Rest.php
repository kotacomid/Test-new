<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class GAI_Rest {
    public static function init() {
        add_action( 'rest_api_init', [ __CLASS__, 'register' ] );
    }
    public static function register() {
        register_rest_route( 'gai/v1', '/generate', [
            'methods'  => 'POST',
            'permission_callback' => function () { return current_user_can( 'edit_posts' ); },
            'callback' => [ __CLASS__, 'generate' ],
            'args' => [
                'description' => [ 'required'=>true,'type'=>'string' ],
                'sections'    => [ 'required'=>false,'type'=>'array' ],
            ],
        ] );
    }
    public static function generate( WP_REST_Request $req ) {
        $desc = sanitize_text_field( $req->get_param( 'description' ) );
        $sections = $req->get_param( 'sections' );
        $sections = array_map( 'sanitize_key', (array) $sections );
        $post_id = GAI_Generator::generate_page( $desc, $sections );
        if ( is_wp_error( $post_id ) ) return $post_id;
        return [ 'success'=>true, 'post_id'=>$post_id, 'edit_link'=> get_edit_post_link( $post_id, '' ) ];
    }
}
GAI_Rest::init();