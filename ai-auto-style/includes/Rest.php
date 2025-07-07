<?php
/**
 * REST API registration for AI Auto Style plugin.
 *
 * @package AI_Auto_Style
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AIAS_REST {

    const NAMESPACE = 'ai-auto-style/v1';

    /**
     * Hook into rest_api_init.
     */
    public static function init() {
        add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
    }

    /**
     * Register REST routes.
     */
    public static function register_routes() {
        register_rest_route( self::NAMESPACE, '/generate', array(
            'methods'             => 'POST',
            'callback'            => array( __CLASS__, 'generate_callback' ),
            'permission_callback' => array( __CLASS__, 'permissions_check' ),
            'args'                => array(
                'prompt' => array(
                    'required' => true,
                    'type'     => 'string',
                ),
            ),
        ) );
    }

    /**
     * Permissions check (edit_posts capability & nonce).
     *
     * @param WP_REST_Request $request Request.
     * @return bool|WP_Error
     */
    public static function permissions_check( $request ) {
        if ( ! current_user_can( 'edit_posts' ) ) {
            return new WP_Error( 'rest_forbidden', __( 'You do not have permissions.', 'ai-auto-style' ), array( 'status' => 403 ) );
        }

        // Optional: Allow cross-site nonces.
        // If aias_nonce header provided, verify.
        $nonce = $request->get_header( 'X-WP-Nonce' );
        if ( $nonce && ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
            return new WP_Error( 'rest_invalid_nonce', __( 'Invalid nonce.', 'ai-auto-style' ), array( 'status' => 403 ) );
        }

        return true;
    }

    /**
     * Callback to generate styles via OpenAI.
     *
     * @param WP_REST_Request $request Request.
     * @return WP_REST_Response
     */
    public static function generate_callback( WP_REST_Request $request ) {
        $prompt = sanitize_text_field( $request->get_param( 'prompt' ) );

        if ( empty( $prompt ) ) {
            return new WP_Error( 'aias_empty_prompt', __( 'Prompt is required.', 'ai-auto-style' ), array( 'status' => 400 ) );
        }

        $result = AIAS_OpenAI::generate( $prompt );
        if ( is_wp_error( $result ) ) {
            return $result;
        }

        return rest_ensure_response( array(
            'success' => true,
            'data'    => $result,
        ) );
    }
}

// Initialize the REST routes.
AIAS_REST::init();