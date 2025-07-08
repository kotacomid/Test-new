<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class GAI_OpenAI {
    protected static function key() {
        if ( defined( 'GAI_OPENAI_KEY' ) && GAI_OPENAI_KEY ) {
            return GAI_OPENAI_KEY;
        }
        return getenv( 'OPENAI_API_KEY' );
    }

    public static function generate( $prompt, $options = array() ) {
        $key = self::key();
        if ( ! $key ) {
            return new WP_Error( 'gai_no_key', 'OpenAI API key not set.' );
        }

        $defaults = array(
            'model'       => 'gpt-3.5-turbo-0125',
            'temperature' => 0.4,
            'messages'    => array(
                array( 'role' => 'user', 'content' => $prompt ),
            ),
        );
        $args = wp_parse_args( $options, $defaults );

        $body = array(
            'model' => $args['model'],
            'temperature' => $args['temperature'],
            'messages' => $args['messages'],
            'response_format' => array( 'type' => 'json_object' ),
        );

        $response = wp_remote_post( 'https://api.openai.com/v1/chat/completions', array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $key,
                'Content-Type'  => 'application/json',
            ),
            'body'    => wp_json_encode( $body ),
            'timeout' => 30,
        ) );

        if ( is_wp_error( $response ) ) {
            return $response;
        }
        $code = wp_remote_retrieve_response_code( $response );
        if ( 200 !== $code ) {
            return new WP_Error( 'gai_openai_error', 'OpenAI error: ' . $code );
        }
        $data = json_decode( wp_remote_retrieve_body( $response ), true );
        $content = $data['choices'][0]['message']['content'] ?? '';
        $json = json_decode( $content, true );
        return $json ? $json : new WP_Error( 'gai_bad_json', 'Invalid JSON from AI.' );
    }
}