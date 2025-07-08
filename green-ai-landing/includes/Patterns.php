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

    public static function register_patterns() {
        foreach ( self::sections_list() as $slug => $def ) {
            register_block_pattern( $def['pattern_slug'], array(
                'title'       => $def['title'],
                'content'     => self::sample_content( $slug ),
                'categories'  => array( 'text' ),
            ) );
        }
    }

    private static function sample_content( $slug ) {
        switch ( $slug ) {
            case 'hero':
                return '<!-- wp:group {"align":"full","style":{"spacing":{"padding":{"top":"80px","bottom":"80px"}}}} --><div class="wp-block-group alignfull" style="padding-top:80px;padding-bottom:80px"><!-- wp:heading {"textAlign":"center","level":1} --><h1 class="has-text-align-center">{{headline}}</h1><!-- /wp:heading --><!-- wp:paragraph {"align":"center"} --><p class="has-text-align-center">{{subheadline}}</p><!-- /wp:paragraph --><!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} --><div class="wp-block-buttons"><!-- wp:button {"className":"is-style-fill"} --><div class="wp-block-button is-style-fill"><a class="wp-block-button__link">{{cta_text}}</a></div><!-- /wp:button --></div><!-- /wp:buttons --></div><!-- /wp:group -->';
            case 'features':
                return '<!-- wp:columns --><div class="wp-block-columns"><!-- wp:column --><div class="wp-block-column"><!-- wp:paragraph --><p>{{feature_1}}</p><!-- /wp:paragraph --></div><!-- /wp:column --><!-- wp:column --><div class="wp-block-column"><p>{{feature_2}}</p></div><!-- /wp:column --><!-- wp:column --><div class="wp-block-column"><p>{{feature_3}}</p></div><!-- /wp:column --><!-- wp:column --><div class="wp-block-column"><p>{{feature_4}}</p></div><!-- /wp:column --></div><!-- /wp:columns -->';
            case 'info':
                return '<!-- wp:heading --><h2>{{info_title}}</h2><!-- /wp:heading --><!-- wp:paragraph --><p>{{info_body}}</p><!-- /wp:paragraph -->';
            case 'pricing':
                return '<!-- wp:group --><div class="wp-block-group"><h3>{{price_title}}</h3><p>{{price_desc}}</p><p><strong>{{price_price}}</strong></p></div><!-- /wp:group -->';
            case 'cta':
                return '<!-- wp:cover {"align":"full"} --><div class="wp-block-cover alignfull"><span aria-hidden="true" class="wp-block-cover__background"></span><div class="wp-block-cover__inner-container"><h2>{{cta_headline}}</h2><p>{{cta_text}}</p></div></div><!-- /wp:cover -->';
            case 'faq':
                return '<!-- wp:group --><div class="wp-block-group"><details><summary>{{faq_q1}}</summary><p>{{faq_a1}}</p></details><details><summary>{{faq_q2}}</summary><p>{{faq_a2}}</p></details></div><!-- /wp:group -->';
            default:
                return '';
        }
    }
}

add_action( 'init', ['GAIPatterns','register_patterns'] );