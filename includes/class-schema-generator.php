<?php
/**
 * Schema Markup Generator for Enhanced SEO
 * Automatically generates structured data for AI content
 */

if (!defined('ABSPATH')) {
    exit;
}

class KotacomAI_Schema_Generator {
    
    private $schema_types = array(
        'article' => 'Article',
        'how_to' => 'HowTo',
        'faq' => 'FAQPage',
        'review' => 'Review',
        'product' => 'Product',
        'organization' => 'Organization',
        'person' => 'Person',
        'course' => 'Course',
        'recipe' => 'Recipe',
        'video' => 'VideoObject',
        'event' => 'Event'
    );
    
    public function __construct() {
        $this->init();
    }
    
    private function init() {
        // Hook into content generation process safely
        if (function_exists('add_action')) {
            add_action('wp_head', array($this, 'add_schema_markup'));
            add_action('kotacom_ai_after_content_generation', array($this, 'generate_post_schema'), 10, 4);
            add_filter('kotacom_ai_generated_content', array($this, 'add_inline_schema'), 20, 3);
        }
    }
    
    /**
     * Add schema markup to head
     */
    public function add_schema_markup() {
        if (is_singular() && get_post_meta(get_the_ID(), 'kotacom_ai_generated', true)) {
            $schema_data = get_post_meta(get_the_ID(), 'kotacom_ai_schema_markup', true);
            if ($schema_data) {
                echo "\n" . $schema_data . "\n";
            }
        }
    }
    
    /**
     * Generate schema markup for a post
     */
    public function generate_post_schema($post_id, $keyword, $content, $post_settings) {
        $post = get_post($post_id);
        if (!$post) return;
        
        // Determine schema type from content analysis
        $schema_type = $this->detect_content_type($content, $keyword);
        
        // Generate appropriate schema
        $schema = $this->generate_schema_by_type($schema_type, $post, $content, $keyword);
        
        if ($schema) {
            $schema_json = '<script type="application/ld+json">' . json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . '</script>';
            update_post_meta($post_id, 'kotacom_ai_schema_markup', $schema_json);
            update_post_meta($post_id, 'kotacom_ai_schema_type', $schema_type);
        }
    }
    
    /**
     * Detect content type for schema generation
     */
    private function detect_content_type($content, $keyword) {
        $content_lower = strtolower($content);
        
        // Check for How-To content
        if (preg_match('/step\s*\d+|how\s+to|tutorial|guide/i', $content) && 
            preg_match('/##\s*step|###\s*step|\d+\.\s/i', $content)) {
            return 'how_to';
        }
        
        // Check for FAQ content
        if (preg_match('/frequently\s+asked|questions?\s+and\s+answers?|faq/i', $content) &&
            preg_match('/###\s*(?:what|how|why|when|where|who)/i', $content)) {
            return 'faq';
        }
        
        // Check for Review content
        if (preg_match('/review|rating|pros?\s+and\s+cons?|verdict|recommendation/i', $content) &&
            preg_match('/\d+(?:\/\d+|\s*out\s+of\s*\d+|\s*stars?)/i', $content)) {
            return 'review';
        }
        
        // Check for Course/Tutorial content
        if (preg_match('/course|tutorial\s+series|module\s*\d+|lesson|learning/i', $content) &&
            preg_match('/##\s*module|###\s*lesson|prerequisites/i', $content)) {
            return 'course';
        }
        
        // Check for Recipe content
        if (preg_match('/ingredients?|instructions?|cooking|recipe|preparation/i', $content) &&
            preg_match('/##\s*ingredients|###\s*instructions/i', $content)) {
            return 'recipe';
        }
        
        // Default to Article
        return 'article';
    }
    
    /**
     * Generate schema markup by type
     */
    private function generate_schema_by_type($type, $post, $content, $keyword) {
        switch ($type) {
            case 'how_to':
                return $this->generate_howto_schema($post, $content, $keyword);
            case 'faq':
                return $this->generate_faq_schema($post, $content, $keyword);
            case 'review':
                return $this->generate_review_schema($post, $content, $keyword);
            case 'course':
                return $this->generate_course_schema($post, $content, $keyword);
            case 'recipe':
                return $this->generate_recipe_schema($post, $content, $keyword);
            case 'article':
            default:
                return $this->generate_article_schema($post, $content, $keyword);
        }
    }
    
    /**
     * Generate Article schema
     */
    private function generate_article_schema($post, $content, $keyword) {
        $schema = array(
            '@context' => 'https://schema.org',
            '@type' => 'Article',
            'headline' => $post->post_title,
            'description' => $this->get_post_description($post),
            'datePublished' => get_the_date('c', $post),
            'dateModified' => get_the_modified_date('c', $post),
            'author' => $this->get_author_schema($post),
            'publisher' => $this->get_publisher_schema(),
            'mainEntityOfPage' => array(
                '@type' => 'WebPage',
                '@id' => get_permalink($post)
            ),
            'keywords' => $this->extract_keywords($content, $keyword),
            'articleSection' => $this->get_article_section($content),
            'wordCount' => str_word_count(wp_strip_all_tags($content))
        );
        
        // Add image if available
        $image_schema = $this->get_image_schema($post);
        if ($image_schema) {
            $schema['image'] = $image_schema;
        }
        
        // Add breadcrumb if applicable
        $breadcrumb_schema = $this->get_breadcrumb_schema($post);
        if ($breadcrumb_schema) {
            $schema['breadcrumb'] = $breadcrumb_schema;
        }
        
        return $schema;
    }
    
    /**
     * Generate HowTo schema
     */
    private function generate_howto_schema($post, $content, $keyword) {
        $steps = $this->extract_howto_steps($content);
        
        $schema = array(
            '@context' => 'https://schema.org',
            '@type' => 'HowTo',
            'name' => $post->post_title,
            'description' => $this->get_post_description($post),
            'totalTime' => $this->estimate_total_time($content),
            'supply' => $this->extract_supplies($content),
            'tool' => $this->extract_tools($content),
            'step' => $steps,
            'author' => $this->get_author_schema($post),
            'datePublished' => get_the_date('c', $post),
            'dateModified' => get_the_modified_date('c', $post)
        );
        
        // Add image if available
        $image_schema = $this->get_image_schema($post);
        if ($image_schema) {
            $schema['image'] = $image_schema;
        }
        
        return $schema;
    }
    
    /**
     * Generate FAQ schema
     */
    private function generate_faq_schema($post, $content, $keyword) {
        $faq_items = $this->extract_faq_items($content);
        
        if (empty($faq_items)) {
            return $this->generate_article_schema($post, $content, $keyword);
        }
        
        $schema = array(
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => $faq_items,
            'name' => $post->post_title,
            'description' => $this->get_post_description($post),
            'author' => $this->get_author_schema($post),
            'datePublished' => get_the_date('c', $post),
            'dateModified' => get_the_modified_date('c', $post)
        );
        
        return $schema;
    }
    
    /**
     * Generate Review schema
     */
    private function generate_review_schema($post, $content, $keyword) {
        $rating = $this->extract_rating($content);
        $reviewed_item = $this->extract_reviewed_item($content, $keyword);
        
        $schema = array(
            '@context' => 'https://schema.org',
            '@type' => 'Review',
            'name' => $post->post_title,
            'reviewBody' => wp_strip_all_tags($content),
            'author' => $this->get_author_schema($post),
            'datePublished' => get_the_date('c', $post),
            'itemReviewed' => $reviewed_item,
            'publisher' => $this->get_publisher_schema()
        );
        
        if ($rating) {
            $schema['reviewRating'] = array(
                '@type' => 'Rating',
                'ratingValue' => $rating['value'],
                'bestRating' => $rating['best'],
                'worstRating' => $rating['worst']
            );
        }
        
        return $schema;
    }
    
    /**
     * Generate Course schema
     */
    private function generate_course_schema($post, $content, $keyword) {
        $modules = $this->extract_course_modules($content);
        
        $schema = array(
            '@context' => 'https://schema.org',
            '@type' => 'Course',
            'name' => $post->post_title,
            'description' => $this->get_post_description($post),
            'provider' => $this->get_publisher_schema(),
            'author' => $this->get_author_schema($post),
            'datePublished' => get_the_date('c', $post),
            'educationalLevel' => $this->determine_educational_level($content),
            'about' => $keyword,
            'teaches' => $this->extract_learning_outcomes($content)
        );
        
        if (!empty($modules)) {
            $schema['syllabusSections'] = $modules;
        }
        
        return $schema;
    }
    
    /**
     * Generate Recipe schema
     */
    private function generate_recipe_schema($post, $content, $keyword) {
        $ingredients = $this->extract_ingredients($content);
        $instructions = $this->extract_recipe_instructions($content);
        
        $schema = array(
            '@context' => 'https://schema.org',
            '@type' => 'Recipe',
            'name' => $post->post_title,
            'description' => $this->get_post_description($post),
            'author' => $this->get_author_schema($post),
            'datePublished' => get_the_date('c', $post),
            'recipeIngredient' => $ingredients,
            'recipeInstructions' => $instructions,
            'prepTime' => $this->extract_prep_time($content),
            'cookTime' => $this->extract_cook_time($content),
            'totalTime' => $this->extract_total_time($content)
        );
        
        // Add nutrition info if found
        $nutrition = $this->extract_nutrition_info($content);
        if ($nutrition) {
            $schema['nutrition'] = $nutrition;
        }
        
        return $schema;
    }
    
    /**
     * Extract HowTo steps from content
     */
    private function extract_howto_steps($content) {
        $steps = array();
        
        // Look for numbered steps or step headers
        preg_match_all('/(?:###+\s*step\s*\d+[:\s]*(.+?)\n(.*?)(?=###+\s*step|\z))/is', $content, $matches, PREG_SET_ORDER);
        
        if (!empty($matches)) {
            foreach ($matches as $i => $match) {
                $step_name = trim($match[1]);
                $step_text = trim(wp_strip_all_tags($match[2]));
                
                $steps[] = array(
                    '@type' => 'HowToStep',
                    'position' => $i + 1,
                    'name' => $step_name,
                    'text' => $step_text
                );
            }
        } else {
            // Fallback: look for numbered lists
            preg_match_all('/\d+\.\s+(.+?)(?=\d+\.\s+|\z)/s', $content, $matches);
            if (!empty($matches[1])) {
                foreach ($matches[1] as $i => $step_text) {
                    $step_text = trim(wp_strip_all_tags($step_text));
                    if (!empty($step_text)) {
                        $steps[] = array(
                            '@type' => 'HowToStep',
                            'position' => $i + 1,
                            'text' => $step_text
                        );
                    }
                }
            }
        }
        
        return $steps;
    }
    
    /**
     * Extract FAQ items from content
     */
    private function extract_faq_items($content) {
        $faq_items = array();
        
        // Look for Q&A patterns
        preg_match_all('/###\s*(.+?)\n(.*?)(?=###|\z)/s', $content, $matches, PREG_SET_ORDER);
        
        foreach ($matches as $match) {
            $question = trim($match[1]);
            $answer = trim(wp_strip_all_tags($match[2]));
            
            // Check if it looks like a question
            if (preg_match('/\?$|^(?:what|how|why|when|where|who|which|can|do|does|is|are|will|would|should)/i', $question)) {
                $faq_items[] = array(
                    '@type' => 'Question',
                    'name' => $question,
                    'acceptedAnswer' => array(
                        '@type' => 'Answer',
                        'text' => $answer
                    )
                );
            }
        }
        
        return $faq_items;
    }
    
    /**
     * Extract rating from content
     */
    private function extract_rating($content) {
        // Look for rating patterns
        if (preg_match('/(\d+(?:\.\d+)?)\s*(?:\/|\s*out\s+of\s*|\s*stars?\s*out\s+of\s*)(\d+)/i', $content, $matches)) {
            return array(
                'value' => floatval($matches[1]),
                'best' => intval($matches[2]),
                'worst' => 1
            );
        }
        
        // Look for star ratings
        if (preg_match('/(\d+(?:\.\d+)?)\s*(?:stars?|\*+)/i', $content, $matches)) {
            return array(
                'value' => floatval($matches[1]),
                'best' => 5,
                'worst' => 1
            );
        }
        
        return null;
    }
    
    /**
     * Extract reviewed item
     */
    private function extract_reviewed_item($content, $keyword) {
        return array(
            '@type' => 'Thing',
            'name' => $keyword
        );
    }
    
    /**
     * Get author schema
     */
    private function get_author_schema($post) {
        $author_id = $post->post_author;
        $author_name = get_the_author_meta('display_name', $author_id);
        $author_url = get_author_posts_url($author_id);
        $author_bio = get_the_author_meta('description', $author_id);
        
        $author_schema = array(
            '@type' => 'Person',
            'name' => $author_name,
            'url' => $author_url
        );
        
        if ($author_bio) {
            $author_schema['description'] = $author_bio;
        }
        
        return $author_schema;
    }
    
    /**
     * Get publisher schema
     */
    private function get_publisher_schema() {
        $site_name = get_bloginfo('name');
        $site_url = home_url();
        $site_description = get_bloginfo('description');
        
        $publisher_schema = array(
            '@type' => 'Organization',
            'name' => $site_name,
            'url' => $site_url
        );
        
        if ($site_description) {
            $publisher_schema['description'] = $site_description;
        }
        
        // Add logo if available
        $custom_logo_id = get_theme_mod('custom_logo');
        if ($custom_logo_id) {
            $logo = wp_get_attachment_image_src($custom_logo_id, 'full');
            if ($logo) {
                $publisher_schema['logo'] = array(
                    '@type' => 'ImageObject',
                    'url' => $logo[0],
                    'width' => $logo[1],
                    'height' => $logo[2]
                );
            }
        }
        
        return $publisher_schema;
    }
    
    /**
     * Get image schema
     */
    private function get_image_schema($post) {
        if (has_post_thumbnail($post->ID)) {
            $image = wp_get_attachment_image_src(get_post_thumbnail_id($post->ID), 'full');
            if ($image) {
                return array(
                    '@type' => 'ImageObject',
                    'url' => $image[0],
                    'width' => $image[1],
                    'height' => $image[2]
                );
            }
        }
        
        return null;
    }
    
    /**
     * Get post description
     */
    private function get_post_description($post) {
        $excerpt = get_the_excerpt($post);
        if ($excerpt) {
            return $excerpt;
        }
        
        // Fallback to first paragraph
        $content = wp_strip_all_tags($post->post_content);
        $first_paragraph = explode("\n\n", $content)[0];
        return wp_trim_words($first_paragraph, 25);
    }
    
    /**
     * Extract keywords from content
     */
    private function extract_keywords($content, $primary_keyword) {
        $keywords = array($primary_keyword);
        
        // Extract other important terms
        $text = wp_strip_all_tags($content);
        $words = str_word_count($text, 1);
        $word_freq = array_count_values(array_map('strtolower', $words));
        
        // Filter out common words
        $common_words = array('the', 'a', 'an', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for', 'of', 'with', 'by');
        $word_freq = array_diff_key($word_freq, array_flip($common_words));
        
        // Get top keywords
        arsort($word_freq);
        $top_keywords = array_slice(array_keys($word_freq), 0, 10);
        
        $keywords = array_merge($keywords, $top_keywords);
        return array_unique($keywords);
    }
    
    /**
     * Get article section based on content
     */
    private function get_article_section($content) {
        // Try to determine section from headings
        if (preg_match('/<h[2-6][^>]*>([^<]+)<\/h[2-6]>/i', $content, $matches)) {
            return trim($matches[1]);
        }
        
        return 'General';
    }
    
    /**
     * Get breadcrumb schema
     */
    private function get_breadcrumb_schema($post) {
        $breadcrumbs = array();
        
        // Add home
        $breadcrumbs[] = array(
            '@type' => 'ListItem',
            'position' => 1,
            'name' => 'Home',
            'item' => home_url()
        );
        
        // Add categories
        $categories = get_the_category($post->ID);
        if (!empty($categories)) {
            $position = 2;
            foreach ($categories as $category) {
                $breadcrumbs[] = array(
                    '@type' => 'ListItem',
                    'position' => $position,
                    'name' => $category->name,
                    'item' => get_category_link($category->term_id)
                );
                $position++;
            }
        }
        
        // Add current post
        $breadcrumbs[] = array(
            '@type' => 'ListItem',
            'position' => count($breadcrumbs) + 1,
            'name' => $post->post_title,
            'item' => get_permalink($post)
        );
        
        return array(
            '@type' => 'BreadcrumbList',
            'itemListElement' => $breadcrumbs
        );
    }
    
    /**
     * Estimate total time for HowTo
     */
    private function estimate_total_time($content) {
        $word_count = str_word_count(wp_strip_all_tags($content));
        $reading_time = ceil($word_count / 200); // 200 words per minute
        
        // Add implementation time based on complexity
        $steps = substr_count($content, 'step');
        $implementation_time = $steps * 5; // 5 minutes per step
        
        $total_minutes = $reading_time + $implementation_time;
        
        return 'PT' . $total_minutes . 'M';
    }
    
    /**
     * Extract supplies from content
     */
    private function extract_supplies($content) {
        $supplies = array();
        
        // Look for supply lists
        if (preg_match('/(?:supplies?|materials?|requirements?)[\s\S]*?<ul>([\s\S]*?)<\/ul>/i', $content, $matches)) {
            preg_match_all('/<li[^>]*>(.*?)<\/li>/i', $matches[1], $items);
            foreach ($items[1] as $item) {
                $supplies[] = array(
                    '@type' => 'HowToSupply',
                    'name' => trim(wp_strip_all_tags($item))
                );
            }
        }
        
        return $supplies;
    }
    
    /**
     * Extract tools from content
     */
    private function extract_tools($content) {
        $tools = array();
        
        // Look for tool lists
        if (preg_match('/(?:tools?|equipment)[\s\S]*?<ul>([\s\S]*?)<\/ul>/i', $content, $matches)) {
            preg_match_all('/<li[^>]*>(.*?)<\/li>/i', $matches[1], $items);
            foreach ($items[1] as $item) {
                $tools[] = array(
                    '@type' => 'HowToTool',
                    'name' => trim(wp_strip_all_tags($item))
                );
            }
        }
        
        return $tools;
    }
    
    /**
     * Add inline schema to content
     */
    public function add_inline_schema($content, $keyword, $parameters) {
        // Add microdata to specific elements
        $content = $this->add_microdata_to_headings($content);
        $content = $this->add_microdata_to_lists($content);
        
        return $content;
    }
    
    /**
     * Add microdata to headings
     */
    private function add_microdata_to_headings($content) {
        // Add itemProp to headings for better structure
        $content = preg_replace('/<h([2-6])([^>]*)>/i', '<h$1$2 itemprop="headline">', $content);
        
        return $content;
    }
    
    /**
     * Add microdata to lists
     */
    private function add_microdata_to_lists($content) {
        // Add itemProp to lists that appear to be instructions
        if (preg_match('/step|instruction|procedure/i', $content)) {
            $content = preg_replace('/<ol([^>]*)>/i', '<ol$1 itemscope itemtype="https://schema.org/ItemList">', $content);
            $content = preg_replace('/<li([^>]*)>/i', '<li$1 itemprop="itemListElement">', $content);
        }
        
        return $content;
    }
    
    /**
     * Extract course modules
     */
    private function extract_course_modules($content) {
        $modules = array();
        
        preg_match_all('/##\s*module\s*\d+[:\s]*(.+?)\n(.*?)(?=##\s*module|\z)/is', $content, $matches, PREG_SET_ORDER);
        
        foreach ($matches as $i => $match) {
            $module_name = trim($match[1]);
            $module_description = trim(wp_strip_all_tags($match[2]));
            
            $modules[] = array(
                '@type' => 'Syllabus',
                'name' => $module_name,
                'description' => wp_trim_words($module_description, 30)
            );
        }
        
        return $modules;
    }
    
    /**
     * Determine educational level
     */
    private function determine_educational_level($content) {
        if (preg_match('/beginner|basic|introduction|getting\s+started/i', $content)) {
            return 'Beginner';
        } elseif (preg_match('/advanced|expert|professional|master/i', $content)) {
            return 'Advanced';
        } else {
            return 'Intermediate';
        }
    }
    
    /**
     * Extract learning outcomes
     */
    private function extract_learning_outcomes($content) {
        $outcomes = array();
        
        // Look for "what you'll learn" sections
        if (preg_match('/(?:what.*?learn|learning\s+outcomes?)[\s\S]*?<ul>([\s\S]*?)<\/ul>/i', $content, $matches)) {
            preg_match_all('/<li[^>]*>(.*?)<\/li>/i', $matches[1], $items);
            foreach ($items[1] as $item) {
                $outcomes[] = trim(wp_strip_all_tags($item));
            }
        }
        
        return $outcomes;
    }
    
    /**
     * Extract ingredients from recipe content
     */
    private function extract_ingredients($content) {
        $ingredients = array();
        
        if (preg_match('/ingredients?[\s\S]*?<ul>([\s\S]*?)<\/ul>/i', $content, $matches)) {
            preg_match_all('/<li[^>]*>(.*?)<\/li>/i', $matches[1], $items);
            foreach ($items[1] as $item) {
                $ingredients[] = trim(wp_strip_all_tags($item));
            }
        }
        
        return $ingredients;
    }
    
    /**
     * Extract recipe instructions
     */
    private function extract_recipe_instructions($content) {
        $instructions = array();
        
        if (preg_match('/instructions?[\s\S]*?<ol>([\s\S]*?)<\/ol>/i', $content, $matches)) {
            preg_match_all('/<li[^>]*>(.*?)<\/li>/i', $matches[1], $items);
            foreach ($items[1] as $i => $item) {
                $instructions[] = array(
                    '@type' => 'HowToStep',
                    'position' => $i + 1,
                    'text' => trim(wp_strip_all_tags($item))
                );
            }
        }
        
        return $instructions;
    }
    
    /**
     * Extract preparation time
     */
    private function extract_prep_time($content) {
        if (preg_match('/prep(?:aration)?\s+time[:\s]*(\d+)\s*(?:min|minutes?)/i', $content, $matches)) {
            return 'PT' . $matches[1] . 'M';
        }
        
        return null;
    }
    
    /**
     * Extract cooking time
     */
    private function extract_cook_time($content) {
        if (preg_match('/cook(?:ing)?\s+time[:\s]*(\d+)\s*(?:min|minutes?)/i', $content, $matches)) {
            return 'PT' . $matches[1] . 'M';
        }
        
        return null;
    }
    
    /**
     * Extract total time
     */
    private function extract_total_time($content) {
        if (preg_match('/total\s+time[:\s]*(\d+)\s*(?:min|minutes?)/i', $content, $matches)) {
            return 'PT' . $matches[1] . 'M';
        }
        
        return null;
    }
    
    /**
     * Extract nutrition information
     */
    private function extract_nutrition_info($content) {
        $nutrition = array();
        
        if (preg_match('/calories[:\s]*(\d+)/i', $content, $matches)) {
            $nutrition['@type'] = 'NutritionInformation';
            $nutrition['calories'] = $matches[1];
        }
        
        return !empty($nutrition) ? $nutrition : null;
    }
}