<?php
/**
 * Generator handles creating new page with filled sections.
 *
 * @package AI_Auto_Style
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AIAS_Generator {

    /**
     * Generate page.
     *
     * @param string $description Business description.
     * @param array  $sections    Section slugs (default all).
     * @return int|WP_Error Post ID or error.
     */
    public static function generate_page( $description, $sections = array() ) {
        $templates = AIAS_Templates::get_templates();

        if ( empty( $sections ) ) {
            $sections = array_keys( $templates );
        }

        // Gather placeholders.
        $placeholders = array();
        foreach ( $sections as $slug ) {
            if ( isset( $templates[ $slug ] ) ) {
                $placeholders = array_merge( $placeholders, $templates[ $slug ]['placeholders'] );
            }
        }
        $placeholders = array_unique( $placeholders );

        // Build prompt.
        $prompt  = 'Anda adalah copywriter profesional. Bisnis / tujuan halaman: "' . $description . ""\n";
        $prompt .= 'Buat teks landing page dalam Bahasa Indonesia. Kembalikan JSON dengan KUNCI persis berikut (tanpa tambahan): ' . implode( ',', $placeholders ) . '. Jangan kembalikan apa pun selain JSON.';

        $ai_data = AIAS_OpenAI::generate( $prompt, array( 'temperature' => 0.4 ) );
        if ( is_wp_error( $ai_data ) ) {
            return $ai_data;
        }

        // Ensure all placeholders exist in result.
        foreach ( $placeholders as $ph ) {
            if ( ! isset( $ai_data[ $ph ] ) ) {
                $ai_data[ $ph ] = '';
            }
        }

        // Build HTML content.
        $html = '';
        foreach ( $sections as $slug ) {
            if ( ! isset( $templates[ $slug ] ) ) {
                continue;
            }
            $pattern_html = AIAS_Templates::get_template_content( $slug );
            if ( ! $pattern_html ) {
                continue;
            }
            $html .= AIAS_Templates::fill_placeholders( $pattern_html, $ai_data );
        }

        if ( ! $html ) {
            return new WP_Error( 'aias_no_html', __( 'Failed to build page content.', 'ai-auto-style' ) );
        }

        $post_arr = array(
            'post_title'   => wp_trim_words( $description, 6, '...' ),
            'post_content' => $html,
            'post_status'  => 'draft',
            'post_type'    => 'page',
        );

        $post_id = wp_insert_post( $post_arr, true );
        if ( is_wp_error( $post_id ) ) {
            return $post_id;
        }

        // store original json for future editing if needed.
        update_post_meta( $post_id, '_aias_ai_json', wp_json_encode( $ai_data ) );
        update_post_meta( $post_id, '_aias_ai_desc', $description );

        return $post_id;
    }
}