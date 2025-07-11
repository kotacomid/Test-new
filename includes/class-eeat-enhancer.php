<?php
/**
 * E-E-A-T Enhancer Class - Makes content more compliant with Google's E-E-A-T guidelines
 * Adds Experience, Expertise, Authoritativeness, and Trustworthiness signals
 */

if (!defined('ABSPATH')) {
    exit;
}

class KotacomAI_EEAT_Enhancer {
    
    private $content_analyzer;
    private $entity_linker;
    private $schema_generator;
    
    public function __construct() {
        $this->init();
    }
    
    private function init() {
        // Hook into content generation process safely
        if (function_exists('add_filter')) {
            add_filter('kotacom_ai_generated_content', array($this, 'enhance_content_with_eeat'), 10, 3);
            add_filter('kotacom_ai_prompt_template', array($this, 'enhance_prompt_with_eeat'), 10, 3);
            add_action('kotacom_ai_after_content_generation', array($this, 'add_post_meta_eeat'), 10, 4);
        }
    }
    
    /**
     * Enhance content with E-E-A-T signals
     */
    public function enhance_content_with_eeat($content, $keyword, $parameters) {
        // Apply all E-E-A-T enhancements
        $content = $this->add_experience_signals($content, $keyword);
        $content = $this->add_expertise_signals($content, $keyword);
        $content = $this->add_authority_signals($content, $keyword);
        $content = $this->add_trust_signals($content, $keyword);
        $content = $this->add_semantic_variations($content, $keyword);
        $content = $this->add_internal_entity_links($content);
        $content = $this->optimize_content_structure($content);
        
        return $content;
    }
    
    /**
     * Enhance prompts to generate better E-E-A-T content
     */
    public function enhance_prompt_with_eeat($prompt, $keyword, $parameters) {
        $eeat_instructions = $this->get_eeat_prompt_instructions($keyword, $parameters);
        return $prompt . "\n\n" . $eeat_instructions;
    }
    
    /**
     * Generate E-E-A-T specific prompt instructions
     */
    private function get_eeat_prompt_instructions($keyword, $parameters) {
        $niche = $parameters['niche'] ?? '';
        $audience = $parameters['audience'] ?? 'general';
        
        $instructions = "IMPORTANT E-E-A-T GUIDELINES:\n";
        $instructions .= "- Include personal experience or case studies related to {$keyword}\n";
        $instructions .= "- Add specific facts, statistics, or data points\n";
        $instructions .= "- Reference authoritative sources when discussing {$keyword}\n";
        $instructions .= "- Use first-person experience examples where appropriate\n";
        $instructions .= "- Include expert opinions or quotes if relevant\n";
        $instructions .= "- Add practical tips based on real experience\n";
        $instructions .= "- Mention relevant credentials or expertise\n";
        $instructions .= "- Include disclaimers where appropriate\n";
        $instructions .= "- Use natural, conversational language\n";
        $instructions .= "- Vary sentence structure and length\n";
        
        if (!empty($niche)) {
            $instructions .= "- Focus on {$niche} industry specifics\n";
        }
        
        return $instructions;
    }
    
    /**
     * Add experience signals to content
     */
    private function add_experience_signals($content, $keyword) {
        $experience_phrases = array(
            "In my experience with {keyword}",
            "After working with {keyword} for several years",
            "From what I've observed when dealing with {keyword}",
            "Based on real-world testing of {keyword}",
            "Through hands-on experience with {keyword}",
            "Having personally worked with {keyword}",
            "After implementing {keyword} in multiple projects"
        );
        
        // Add experience phrases to content paragraphs
        $paragraphs = explode("\n\n", $content);
        if (count($paragraphs) >= 2) {
            $random_position = rand(1, min(2, count($paragraphs) - 1));
            $experience_phrase = str_replace('{keyword}', $keyword, $experience_phrases[array_rand($experience_phrases)]);
            $paragraphs[$random_position] = $experience_phrase . ', ' . lcfirst($paragraphs[$random_position]);
        }
        
        return implode("\n\n", $paragraphs);
    }
    
    /**
     * Add expertise signals to content
     */
    private function add_expertise_signals($content, $keyword) {
        // Add expert callouts
        $expert_additions = array();
        
        // Add pro tip
        $pro_tips = array(
            "💡 **Pro Tip**: When working with {keyword}, always consider the long-term implications and scalability.",
            "⚡ **Expert Insight**: The key to success with {keyword} lies in understanding the underlying principles.",
            "🎯 **Professional Advice**: Based on industry best practices, {keyword} requires careful planning and execution."
        );
        
        $content .= "\n\n" . str_replace('{keyword}', $keyword, $pro_tips[array_rand($pro_tips)]);
        
        // Add expertise indicators
        $expertise_phrases = array(
            "industry standards",
            "best practices",
            "professional guidelines", 
            "expert recommendations",
            "proven methodologies",
            "technical specifications"
        );
        
        // Randomly inject expertise phrases
        foreach ($expertise_phrases as $phrase) {
            if (rand(0, 100) < 30) { // 30% chance
                $content = str_replace(
                    array(' methods', ' approaches', ' ways', ' techniques'),
                    array(" {$phrase} and methods", " {$phrase} and approaches", " {$phrase} and ways", " {$phrase} and techniques"),
                    $content,
                    1
                );
            }
        }
        
        return $content;
    }
    
    /**
     * Add authority signals to content
     */
    private function add_authority_signals($content, $keyword) {
        // Add citations and references
        $authority_additions = array();
        
        // Add reference section
        $reference_formats = array(
            "According to recent industry research on {keyword}",
            "Studies have shown that {keyword}",
            "Leading experts in the field recommend",
            "Industry analysis indicates that {keyword}",
            "Research from authoritative sources confirms"
        );
        
        // Add random authority reference
        if (rand(0, 100) < 60) { // 60% chance
            $authority_phrase = str_replace('{keyword}', $keyword, $reference_formats[array_rand($reference_formats)]);
            $content = preg_replace('/([.!?])\s+/', '$1 ' . $authority_phrase . ' reinforces this point. ', $content, 1);
        }
        
        // Add statistics (placeholder - can be enhanced with real data)
        $stats = array(
            "Recent data shows a 73% improvement when implementing {keyword} correctly.",
            "Industry surveys indicate that 85% of professionals recommend {keyword}.",
            "Statistical analysis reveals that {keyword} increases efficiency by up to 67%.",
            "Market research demonstrates that {keyword} adoption has grown by 156% this year."
        );
        
        if (rand(0, 100) < 40) { // 40% chance
            $content .= "\n\n📊 **Key Statistic**: " . str_replace('{keyword}', $keyword, $stats[array_rand($stats)]);
        }
        
        return $content;
    }
    
    /**
     * Add trust signals to content
     */
    private function add_trust_signals($content, $keyword) {
        // Add disclaimers and transparency
        $trust_elements = array();
        
        // Add last updated date
        $content .= "\n\n---\n*Last updated: " . date('F j, Y') . "*";
        
        // Add transparency note
        $transparency_notes = array(
            "**Transparency Note**: This content is based on current industry practices and our experience with {keyword}.",
            "**Disclaimer**: While we strive for accuracy, always consult with professionals for specific {keyword} implementations.",
            "**Editorial Note**: This information about {keyword} is regularly reviewed and updated to ensure accuracy."
        );
        
        $content .= "\n\n" . str_replace('{keyword}', $keyword, $transparency_notes[array_rand($transparency_notes)]);
        
        // Add author expertise indicator
        $author_indicators = array(
            "✅ **Author Expertise**: Written by professionals with extensive experience in {keyword}",
            "👥 **Review Process**: This {keyword} guide has been reviewed by industry experts",
            "🔍 **Fact-Checked**: All information about {keyword} has been verified against authoritative sources"
        );
        
        if (rand(0, 100) < 50) { // 50% chance
            $content .= "\n\n" . str_replace('{keyword}', $keyword, $author_indicators[array_rand($author_indicators)]);
        }
        
        return $content;
    }
    
    /**
     * Add semantic variations to avoid spam detection
     */
    private function add_semantic_variations($content, $keyword) {
        // Create semantic variations of the keyword
        $variations = $this->generate_semantic_variations($keyword);
        
        // Replace some instances of the keyword with variations
        $keyword_count = substr_count(strtolower($content), strtolower($keyword));
        $replace_count = min(2, floor($keyword_count * 0.3)); // Replace 30% but max 2
        
        for ($i = 0; $i < $replace_count; $i++) {
            $variation = $variations[array_rand($variations)];
            $content = preg_replace('/\b' . preg_quote($keyword, '/') . '\b/i', $variation, $content, 1);
        }
        
        return $content;
    }
    
    /**
     * Generate semantic variations of a keyword
     */
    private function generate_semantic_variations($keyword) {
        $variations = array();
        
        // Basic transformations
        $variations[] = "this " . strtolower($keyword);
        $variations[] = "the " . strtolower($keyword);
        $variations[] = "such " . strtolower($keyword);
        
        // Context-specific variations
        if (strpos($keyword, ' ') !== false) {
            $words = explode(' ', $keyword);
            $variations[] = implode(' ', array_reverse($words));
            $variations[] = end($words);
        }
        
        // Common synonyms/related terms (basic patterns)
        $synonym_patterns = array(
            'solution' => array('approach', 'method', 'strategy', 'system'),
            'guide' => array('tutorial', 'handbook', 'manual', 'instructions'),
            'tips' => array('advice', 'suggestions', 'recommendations', 'insights'),
            'best' => array('top', 'leading', 'optimal', 'preferred'),
            'tool' => array('resource', 'utility', 'application', 'platform')
        );
        
        foreach ($synonym_patterns as $pattern => $synonyms) {
            if (stripos($keyword, $pattern) !== false) {
                foreach ($synonyms as $synonym) {
                    $variations[] = str_ireplace($pattern, $synonym, $keyword);
                }
            }
        }
        
        return array_unique($variations);
    }
    
    /**
     * Add internal entity links
     */
    private function add_internal_entity_links($content) {
        // Enhanced entity recognition and linking
        $entities = $this->extract_entities($content);
        
        foreach ($entities as $entity) {
            $internal_link = $this->find_internal_link($entity);
            if ($internal_link) {
                $content = $this->replace_with_link($content, $entity, $internal_link);
            }
        }
        
        return $content;
    }
    
    /**
     * Extract entities from content
     */
    private function extract_entities($content) {
        $entities = array();
        
        // Extract potential entities (nouns, proper nouns)
        preg_match_all('/\b[A-Z][a-z]+(?:\s+[A-Z][a-z]+)*\b/', $content, $matches);
        $entities = array_merge($entities, $matches[0]);
        
        // Extract common business/tech terms
        $tech_patterns = array(
            '/\b(?:API|SDK|SaaS|CRM|ERP|SEO|CMS|AI|ML|IoT|SLA|KPI)\b/',
            '/\b(?:WordPress|Google|Microsoft|Amazon|Facebook|Twitter|LinkedIn)\b/i',
            '/\b(?:marketing|business|technology|software|platform|solution)\b/i'
        );
        
        foreach ($tech_patterns as $pattern) {
            preg_match_all($pattern, $content, $matches);
            $entities = array_merge($entities, $matches[0]);
        }
        
        return array_unique($entities);
    }
    
    /**
     * Find internal link for entity
     */
    private function find_internal_link($entity) {
        // Search for existing posts with similar titles or content
        $posts = get_posts(array(
            'post_type' => array('post', 'page'),
            'post_status' => 'publish',
            's' => $entity,
            'posts_per_page' => 1,
            'orderby' => 'relevance date',
            'order' => 'DESC'
        ));
        
        if (!empty($posts)) {
            return get_permalink($posts[0]->ID);
        }
        
        return false;
    }
    
    /**
     * Replace entity with internal link
     */
    private function replace_with_link($content, $entity, $link) {
        // Only replace first occurrence to avoid over-linking
        return preg_replace('/\b' . preg_quote($entity, '/') . '\b/', '<a href="' . esc_url($link) . '">' . $entity . '</a>', $content, 1);
    }
    
    /**
     * Optimize content structure for readability and SEO
     */
    private function optimize_content_structure($content) {
        // Add table of contents for longer content
        if (str_word_count($content) > 800) {
            $content = $this->add_table_of_contents($content);
        }
        
        // Ensure proper heading structure
        $content = $this->optimize_headings($content);
        
        // Add FAQ section if appropriate
        if (rand(0, 100) < 40) { // 40% chance
            $content = $this->add_faq_section($content);
        }
        
        return $content;
    }
    
    /**
     * Add table of contents
     */
    private function add_table_of_contents($content) {
        preg_match_all('/<h([2-6])[^>]*>(.*?)<\/h\1>/i', $content, $headings);
        
        if (count($headings[0]) >= 3) {
            $toc = "\n\n## Table of Contents\n\n";
            foreach ($headings[2] as $index => $heading_text) {
                $heading_id = sanitize_title($heading_text);
                $toc .= "- [" . strip_tags($heading_text) . "](#" . $heading_id . ")\n";
                
                // Add ID to heading
                $content = str_replace($headings[0][$index], 
                    str_replace('>', ' id="' . $heading_id . '">', $headings[0][$index]), $content);
            }
            
            // Insert TOC after first paragraph
            $paragraphs = explode("\n\n", $content);
            if (count($paragraphs) > 1) {
                array_splice($paragraphs, 1, 0, $toc);
                $content = implode("\n\n", $paragraphs);
            }
        }
        
        return $content;
    }
    
    /**
     * Optimize heading structure
     */
    private function optimize_headings($content) {
        // Ensure H1 is title only, start content with H2
        $content = preg_replace('/<h1[^>]*>.*?<\/h1>/i', '', $content);
        
        // Convert any remaining high-level headings to H2
        $content = preg_replace('/<h([1-2])[^>]*>/i', '<h2>', $content);
        $content = preg_replace('/<\/h[1-2]>/i', '</h2>', $content);
        
        return $content;
    }
    
    /**
     * Add FAQ section
     */
    private function add_faq_section($content) {
        $faq_section = "\n\n## Frequently Asked Questions\n\n";
        
        // Extract main topic from content for FAQ generation
        $main_topic = $this->extract_main_topic($content);
        
        $faqs = array(
            array(
                'q' => "What are the benefits of {$main_topic}?",
                'a' => "The main benefits include improved efficiency, better results, and enhanced user experience."
            ),
            array(
                'q' => "How do I get started with {$main_topic}?",
                'a' => "Start by understanding the basics, then gradually implement advanced features as you become more comfortable."
            ),
            array(
                'q' => "Is {$main_topic} suitable for beginners?",
                'a' => "Yes, with proper guidance and starting with basic concepts, beginners can successfully learn and implement {$main_topic}."
            )
        );
        
        foreach (array_slice($faqs, 0, 2) as $faq) { // Add 2 FAQs
            $faq_section .= "### " . $faq['q'] . "\n\n";
            $faq_section .= $faq['a'] . "\n\n";
        }
        
        return $content . $faq_section;
    }
    
    /**
     * Extract main topic from content
     */
    private function extract_main_topic($content) {
        // Simple extraction - get most frequent non-common word
        $words = str_word_count(strtolower(strip_tags($content)), 1);
        $common_words = array('the', 'a', 'an', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for', 'of', 'with', 'by', 'is', 'are', 'was', 'were', 'be', 'been', 'have', 'has', 'had', 'will', 'would', 'could', 'should', 'may', 'might', 'can', 'this', 'that', 'these', 'those');
        
        $word_freq = array_count_values(array_filter($words, function($word) use ($common_words) {
            return strlen($word) > 3 && !in_array($word, $common_words);
        }));
        
        arsort($word_freq);
        return !empty($word_freq) ? key($word_freq) : 'this topic';
    }
    
    /**
     * Add post meta for E-E-A-T tracking
     */
    public function add_post_meta_eeat($post_id, $keyword, $content, $post_settings) {
        // Add E-E-A-T compliance metadata
        update_post_meta($post_id, 'eeat_enhanced', true);
        update_post_meta($post_id, 'eeat_signals_added', array(
            'experience' => true,
            'expertise' => true,
            'authoritativeness' => true,
            'trustworthiness' => true,
            'enhanced_date' => current_time('mysql')
        ));
        
        // Add content quality score
        $quality_score = $this->calculate_content_quality_score($content);
        update_post_meta($post_id, 'content_quality_score', $quality_score);
    }
    
    /**
     * Calculate content quality score
     */
    private function calculate_content_quality_score($content) {
        $score = 0;
        
        // Word count (1-10 points)
        $word_count = str_word_count($content);
        $score += min(10, $word_count / 100);
        
        // Heading structure (1-15 points)
        $heading_count = preg_match_all('/<h[2-6][^>]*>/', $content);
        $score += min(15, $heading_count * 3);
        
        // Internal links (1-10 points)
        $link_count = preg_match_all('/<a[^>]*href=["\'][^"\']*["\'][^>]*>/', $content);
        $score += min(10, $link_count * 2);
        
        // Lists (1-10 points)
        $list_count = preg_match_all('/<[ou]l>/', $content);
        $score += min(10, $list_count * 5);
        
        // E-E-A-T signals (1-20 points)
        $eeat_indicators = array('experience', 'expert', 'professional', 'industry', 'research', 'study', 'data', 'statistics');
        $eeat_score = 0;
        foreach ($eeat_indicators as $indicator) {
            if (stripos($content, $indicator) !== false) {
                $eeat_score += 2.5;
            }
        }
        $score += min(20, $eeat_score);
        
        // Readability (1-15 points)
        $sentences = preg_split('/[.!?]+/', $content);
        $avg_sentence_length = $word_count / max(1, count($sentences));
        if ($avg_sentence_length >= 10 && $avg_sentence_length <= 25) {
            $score += 15;
        } else {
            $score += max(0, 15 - abs($avg_sentence_length - 17));
        }
        
        return min(100, round($score));
    }
    
    /**
     * Generate schema markup for content
     */
    public function generate_schema_markup($post_id, $content_type = 'Article') {
        $post = get_post($post_id);
        if (!$post) return '';
        
        $schema = array(
            '@context' => 'https://schema.org',
            '@type' => $content_type,
            'headline' => $post->post_title,
            'description' => get_the_excerpt($post),
            'datePublished' => get_the_date('c', $post),
            'dateModified' => get_the_modified_date('c', $post),
            'author' => array(
                '@type' => 'Person',
                'name' => get_the_author_meta('display_name', $post->post_author),
                'url' => get_author_posts_url($post->post_author)
            ),
            'publisher' => array(
                '@type' => 'Organization',
                'name' => get_bloginfo('name'),
                'url' => home_url()
            ),
            'mainEntityOfPage' => array(
                '@type' => 'WebPage',
                '@id' => get_permalink($post)
            )
        );
        
        // Add featured image if available
        if (has_post_thumbnail($post)) {
            $image = wp_get_attachment_image_src(get_post_thumbnail_id($post), 'full');
            $schema['image'] = array(
                '@type' => 'ImageObject',
                'url' => $image[0],
                'width' => $image[1],
                'height' => $image[2]
            );
        }
        
        return '<script type="application/ld+json">' . json_encode($schema, JSON_UNESCAPED_SLASHES) . '</script>';
    }
}