<?php
/**
 * OpenAI API wrapper for AI Auto Style plugin.
 *
 * @package AI_Auto_Style
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AIAS_OpenAI {

    /**
     * Retrieve the configured API key.
     *
     * Looks for constant AIAS_OPENAI_API_KEY or env OPENAI_API_KEY.
     *
     * @return string|false
     */
    protected static function get_api_key() {
        if ( defined( 'AIAS_OPENAI_API_KEY' ) && AIAS_OPENAI_API_KEY ) {
            return AIAS_OPENAI_API_KEY;
        }

        $env_key = getenv( 'OPENAI_API_KEY' );
        return $env_key ? $env_key : false;
    }

    /**
     * Call OpenAI chat completion endpoint.
     *
     * @param string $prompt The user prompt.
     * @param array  $options Optional overrides: model, temperature, etc.
     *
     * @return array|WP_Error Parsed JSON response or WP_Error on failure.
     */
    public static function generate( $prompt, $options = array() ) {
        $api_key = self::get_api_key();
        if ( ! $api_key ) {
            return new WP_Error( 'aias_missing_key', __( 'OpenAI API key is not configured.', 'ai-auto-style' ), array( 'status' => 500 ) );
        }

        $defaults = array(
            'model'       => 'gpt-3.5-turbo-0125',
            'temperature' => 0.7,
            'messages'    => array(
                array(
                    'role'    => 'system',
                    'content' => 'You are a helpful assistant that generates JSON structure for Gutenberg + GreenShift layout styling.',
                ),
                array(
                    'role'    => 'user',
                    'content' => $prompt,
                ),
            ),
        );

        $args = wp_parse_args( $options, $defaults );

        $body = array(
            'model'       => $args['model'],
            'temperature' => $args['temperature'],
            'messages'    => $args['messages'],
            'response_format' => array( 'type' => 'json_object' ),
        );

        $request_args = array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type'  => 'application/json',
            ),
            'body'    => wp_json_encode( $body ),
            'timeout' => 30,
        );

        $response = wp_remote_post( 'https://api.openai.com/v1/chat/completions', $request_args );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $code = wp_remote_retrieve_response_code( $response );
        if ( 200 !== $code ) {
            return new WP_Error( 'aias_openai_error', __( 'OpenAI API error.', 'ai-auto-style' ), array( 'status' => $code, 'details' => wp_remote_retrieve_body( $response ) ) );
        }

        $data = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( ! $data || empty( $data['choices'][0]['message']['content'] ) ) {
            return new WP_Error( 'aias_bad_response', __( 'Invalid response from OpenAI.', 'ai-auto-style' ), array( 'status' => 500 ) );
        }

        // Attempt to decode JSON from content; if fails, return raw.
        $json_content = json_decode( trim( $data['choices'][0]['message']['content'] ), true );
        return $json_content ? $json_content : $data['choices'][0]['message']['content'];
    }
}