<?php
/**
 * Defines available GreenShift section patterns and helpers.
 *
 * @package Green_AI_Landing
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class GAIPatterns {

    /**
     * Return associative array of section definitions.
     *
     * @return array
     */
    public static function sections_list() {
        return array(
            'hero'     => array(
                'title'        => __( 'Hero Section', 'green-ai-landing' ),
                'pattern_slug' => 'gail/hero',    // Ensure pattern is registered elsewhere (sample below)
                'placeholders' => array( 'headline', 'subheadline', 'cta_text' ),
            ),
            'features' => array(
                'title'        => __( 'Features Section', 'green-ai-landing' ),
                'pattern_slug' => 'gail/features',
                'placeholders' => array( 'feature_1', 'feature_2', 'feature_3', 'feature_4' ),
            ),
            'info' => array(
                'title'        => __( 'Info Section', 'green-ai-landing' ),
                'pattern_slug' => 'gail/info',
                'placeholders' => array( 'info_title', 'info_body' ),
            ),
            'pricing' => array(
                'title'        => __( 'Pricing Section', 'green-ai-landing' ),
                'pattern_slug' => 'gail/pricing',
                'placeholders' => array( 'price_title', 'price_price', 'price_desc' ),
            ),
            'cta' => array(
                'title'        => __( 'Call To Action Section', 'green-ai-landing' ),
                'pattern_slug' => 'gail/cta',
                'placeholders' => array( 'cta_headline', 'cta_text' ),
            ),
            'faq' => array(
                'title'        => __( 'FAQ Section', 'green-ai-landing' ),
                'pattern_slug' => 'gail/faq',
                'placeholders' => array( 'faq_q1', 'faq_a1', 'faq_q2', 'faq_a2' ),
            ),
        );
    }

    /**
     * Get pattern raw HTML.
     *
     * @param string $slug Section key.
     * @return string|false
     */
    public static function get_pattern_html( $slug ) {
        $list = self::sections_list();
        if ( ! isset( $list[ $slug ] ) ) {
            return false;
        }
        $pattern_slug = $list[ $slug ]['pattern_slug'];
        $registry     = WP_Block_Patterns_Registry::get_instance();
        if ( ! $registry->is_registered( $pattern_slug ) ) {
            return false;
        }
        $pattern = $registry->get_registered( $pattern_slug );
        return $pattern ? $pattern['content'] : false;
    }

    /**
     * Replace placeholders in HTML.
     *
     * @param string $html Pattern HTML.
     * @param array  $data key=>value.
     * @return string
     */
    public static function fill_placeholders( $html, $data ) {
        foreach ( $data as $key => $value ) {
            $html = str_replace( '{{' . $key . '}}', esc_html( $value ), $html );
        }
        return $html;
    }
}