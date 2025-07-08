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
            'hero-saas-dark' => array(
                'title'        => __( 'Hero SaaS Dark', 'ai-auto-style' ),
                'pattern_slug' => 'aias/hero-saas-dark', // block pattern slug.
                'placeholders' => array( 'headline', 'subheadline', 'cta_text' ),
            ),
            'features-4-grid' => array(
                'title'        => __( 'Features Grid 4', 'ai-auto-style' ),
                'pattern_slug' => 'aias/features-4-grid',
                'placeholders' => array( 'feature_1', 'feature_2', 'feature_3', 'feature_4' ),
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