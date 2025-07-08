<?php
/**
 * Template helper for AI Content Filler.
 *
 * @package AI_Auto_Style
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AIAS_Templates {

    /**
     * Return list of available template definitions.
     * Each template uses block pattern slug that must be registered by theme/plugin.
     * Placeholders wrapped in {{token}} within pattern content.
     *
     * @return array
     */
    public static function get_templates() {
        return array(
            'hero' => array(
                'title'        => __( 'Hero Section', 'ai-auto-style' ),
                'pattern_slug' => 'aias/hero',
                'placeholders' => array( 'headline', 'subheadline', 'cta_text' ),
            ),
            'features' => array(
                'title'        => __( 'Features Section', 'ai-auto-style' ),
                'pattern_slug' => 'aias/features',
                'placeholders' => array( 'feature_1', 'feature_2', 'feature_3', 'feature_4' ),
            ),
            'info' => array(
                'title'        => __( 'Info Section', 'ai-auto-style' ),
                'pattern_slug' => 'aias/info',
                'placeholders' => array( 'info_title', 'info_body' ),
            ),
            'cta' => array(
                'title'        => __( 'Call to Action Section', 'ai-auto-style' ),
                'pattern_slug' => 'aias/cta',
                'placeholders' => array( 'cta_headline', 'cta_text' ),
            ),
            'pricing' => array(
                'title'        => __( 'Pricing Section', 'ai-auto-style' ),
                'pattern_slug' => 'aias/pricing',
                'placeholders' => array( 'price_title', 'price_price', 'price_desc' ),
            ),
            'faq' => array(
                'title'        => __( 'FAQ Section', 'ai-auto-style' ),
                'pattern_slug' => 'aias/faq',
                'placeholders' => array( 'faq_q1', 'faq_a1', 'faq_q2', 'faq_a2' ),
            ),
        );
    }

    /**
     * Retrieve template content (raw HTML of pattern).
     *
     * @param string $slug Template key from get_templates().
     * @return string|false HTML block markup.
     */
    public static function get_template_content( $slug ) {
        $templates = self::get_templates();
        if ( ! isset( $templates[ $slug ] ) ) {
            return false;
        }
        $pattern_slug = $templates[ $slug ]['pattern_slug'];
        $registry     = WP_Block_Patterns_Registry::get_instance();
        if ( ! $registry->is_registered( $pattern_slug ) ) {
            return false;
        }
        $pattern = $registry->get_registered( $pattern_slug );
        return $pattern ? $pattern['content'] : false;
    }

    /**
     * Fill placeholders in HTML with provided data.
     *
     * @param string $html Raw HTML containing placeholders {{token}}.
     * @param array  $data Associative array token => replacement.
     * @return string
     */
    public static function fill_placeholders( $html, $data ) {
        foreach ( $data as $key => $value ) {
            $html = str_replace( '{{' . $key . '}}', esc_html( $value ), $html );
        }
        return $html;
    }
}