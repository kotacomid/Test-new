<?php
/**
 * Generates a draft page containing selected GreenShift sections filled with AI content.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class GAI_Generator {

    /**
     * Build a page.
     *
     * @param string $description Business description.
     * @param array  $section_slugs Selected section keys (hero, features...).
     * @return int|WP_Error Post ID on success.
     */
    public static function generate_page( $description, $section_slugs = array() ) {
        $sections = GAIPatterns::sections_list();
        if ( empty( $section_slugs ) ) {
            $section_slugs = array_keys( $sections );
        }

        // Collect placeholders.
        $placeholders = array();
        foreach ( $section_slugs as $s ) {
            if ( isset( $sections[ $s ] ) ) {
                $placeholders = array_merge( $placeholders, $sections[ $s ]['placeholders'] );
            }
        }
        $placeholders = array_unique( $placeholders );
        if ( empty( $placeholders ) ) {
            return new WP_Error( 'gai_no_placeholders', 'No placeholders found for sections.' );
        }

        // Build prompt.
        $prompt  = 'Anda adalah copywriter profesional. Deskripsi bisnis/tujuan halaman: "' . $description . '".' . "\n";
        $prompt .= 'Buat JSON ONLY dengan kunci persis berikut: ' . implode( ',', $placeholders ) . '. Jangan tambahkan kunci lain.';

        $ai_data = GAI_OpenAI::generate( $prompt );
        if ( is_wp_error( $ai_data ) ) {
            return $ai_data;
        }

        // Ensure all placeholders present.
        foreach ( $placeholders as $ph ) {
            if ( ! isset( $ai_data[ $ph ] ) ) {
                $ai_data[ $ph ] = '';
            }
        }

        // Assemble HTML from selected sections.
        $html = '';
        foreach ( $section_slugs as $slug ) {
            if ( ! isset( $sections[ $slug ] ) ) {
                continue;
            }
            $raw_html = GAIPatterns::get_pattern_html( $slug );
            if ( ! $raw_html ) {
                continue;
            }
            $html .= GAIPatterns::fill_placeholders( $raw_html, $ai_data );
        }

        if ( ! $html ) {
            return new WP_Error( 'gai_no_html', 'Failed to build HTML.' );
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

        update_post_meta( $post_id, '_gai_ai_json', wp_json_encode( $ai_data ) );
        update_post_meta( $post_id, '_gai_ai_desc', $description );

        return $post_id;
    }
}