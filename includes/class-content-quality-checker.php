<?php
/**
 * Content Quality Checker - Advanced analysis to bypass spam detection
 * Ensures high-quality, natural content generation
 */

if (!defined('ABSPATH')) {
    exit;
}

class KotacomAI_Content_Quality_Checker {
    
    private $readability_analyzer;
    private $spam_detector;
    private $uniqueness_checker;
    
    public function __construct() {
        $this->init();
    }
    
    private function init() {
        // Hook into content generation process safely
        if (function_exists('add_filter')) {
            add_filter('kotacom_ai_generated_content', array($this, 'analyze_and_improve_content'), 5, 3);
            add_action('wp_head', array($this, 'add_quality_schema_markup'));
        }
    }
    
    /**
     * Main content analysis and improvement function
     */
    public function analyze_and_improve_content($content, $keyword, $parameters) {
        // Run comprehensive quality checks
        $quality_report = $this->run_quality_analysis($content, $keyword);
        
        // Apply improvements based on analysis
        if ($quality_report['needs_improvement']) {
            $content = $this->apply_quality_improvements($content, $quality_report, $keyword);
        }
        
        return $content;
    }
    
    /**
     * Run comprehensive quality analysis
     */
    public function run_quality_analysis($content, $keyword) {
        $report = array(
            'overall_score' => 0,
            'needs_improvement' => false,
            'issues' => array(),
            'recommendations' => array(),
            'metrics' => array()
        );
        
        // Analyze various quality aspects
        $report['readability'] = $this->analyze_readability($content);
        $report['spam_signals'] = $this->detect_spam_patterns($content, $keyword);
        $report['keyword_optimization'] = $this->analyze_keyword_usage($content, $keyword);
        $report['structure_quality'] = $this->analyze_content_structure($content);
        $report['uniqueness'] = $this->check_content_uniqueness($content);
        $report['natural_language'] = $this->analyze_natural_language_patterns($content);
        
        // Calculate overall score
        $scores = array(
            $report['readability']['score'],
            $report['spam_signals']['score'],
            $report['keyword_optimization']['score'],
            $report['structure_quality']['score'],
            $report['uniqueness']['score'],
            $report['natural_language']['score']
        );
        
        $report['overall_score'] = array_sum($scores) / count($scores);
        $report['needs_improvement'] = $report['overall_score'] < 70;
        
        return $report;
    }
    
    /**
     * Analyze content readability
     */
    private function analyze_readability($content) {
        $text = wp_strip_all_tags($content);
        $sentences = preg_split('/[.!?]+/', $text);
        $words = str_word_count($text, 1);
        $syllables = $this->count_syllables($text);
        
        $sentence_count = count(array_filter($sentences));
        $word_count = count($words);
        $avg_sentence_length = $word_count / max(1, $sentence_count);
        $avg_syllables_per_word = $syllables / max(1, $word_count);
        
        // Calculate Flesch Reading Ease Score
        $flesch_score = 206.835 - (1.015 * $avg_sentence_length) - (84.6 * $avg_syllables_per_word);
        $flesch_score = max(0, min(100, $flesch_score));
        
        $readability_level = $this->get_readability_level($flesch_score);
        
        return array(
            'score' => $flesch_score,
            'level' => $readability_level,
            'avg_sentence_length' => round($avg_sentence_length, 1),
            'avg_syllables_per_word' => round($avg_syllables_per_word, 2),
            'recommendations' => $this->get_readability_recommendations($flesch_score, $avg_sentence_length)
        );
    }
    
    /**
     * Count syllables in text (approximation)
     */
    private function count_syllables($text) {
        $words = str_word_count(strtolower($text), 1);
        $total_syllables = 0;
        
        foreach ($words as $word) {
            $syllables = preg_match_all('/[aeiouy]+/', $word);
            $syllables = max(1, $syllables); // At least 1 syllable per word
            
            // Adjust for silent 'e'
            if (substr($word, -1) === 'e' && $syllables > 1) {
                $syllables--;
            }
            
            $total_syllables += $syllables;
        }
        
        return $total_syllables;
    }
    
    /**
     * Get readability level from Flesch score
     */
    private function get_readability_level($score) {
        if ($score >= 90) return 'Very Easy';
        if ($score >= 80) return 'Easy';
        if ($score >= 70) return 'Fairly Easy';
        if ($score >= 60) return 'Standard';
        if ($score >= 50) return 'Fairly Difficult';
        if ($score >= 30) return 'Difficult';
        return 'Very Difficult';
    }
    
    /**
     * Get readability improvement recommendations
     */
    private function get_readability_recommendations($score, $avg_sentence_length) {
        $recommendations = array();
        
        if ($score < 60) {
            $recommendations[] = 'Consider simplifying sentence structure';
            $recommendations[] = 'Use shorter, more common words when possible';
        }
        
        if ($avg_sentence_length > 25) {
            $recommendations[] = 'Break down long sentences into shorter ones';
        }
        
        if ($avg_sentence_length < 10) {
            $recommendations[] = 'Consider combining some short sentences for better flow';
        }
        
        return $recommendations;
    }
    
    /**
     * Detect spam patterns in content
     */
    private function detect_spam_patterns($content, $keyword) {
        $issues = array();
        $score = 100; // Start with perfect score, deduct points for issues
        
        $text = wp_strip_all_tags($content);
        $word_count = str_word_count($text);
        $keyword_count = substr_count(strtolower($text), strtolower($keyword));
        $keyword_density = ($keyword_count / max(1, $word_count)) * 100;
        
        // Check keyword density (should be 1-3%)
        if ($keyword_density > 5) {
            $issues[] = 'Keyword density too high (' . round($keyword_density, 1) . '%)';
            $score -= 20;
        } elseif ($keyword_density < 0.5) {
            $issues[] = 'Keyword density too low (' . round($keyword_density, 1) . '%)';
            $score -= 10;
        }
        
        // Check for keyword stuffing patterns
        $stuffing_patterns = array(
            '/\b' . preg_quote($keyword, '/') . '\b.*?\b' . preg_quote($keyword, '/') . '\b.*?\b' . preg_quote($keyword, '/') . '\b/i'
        );
        
        foreach ($stuffing_patterns as $pattern) {
            if (preg_match($pattern, $text)) {
                $issues[] = 'Potential keyword stuffing detected';
                $score -= 15;
                break;
            }
        }
        
        // Check for repetitive phrases
        $sentences = preg_split('/[.!?]+/', $text);
        $sentence_similarity = $this->check_sentence_similarity($sentences);
        if ($sentence_similarity > 0.3) {
            $issues[] = 'High sentence similarity detected';
            $score -= 10;
        }
        
        // Check for unnatural link patterns
        $link_count = preg_match_all('/<a[^>]*>/', $content);
        $link_density = ($link_count / max(1, $word_count)) * 100;
        if ($link_density > 2) {
            $issues[] = 'Link density too high';
            $score -= 15;
        }
        
        // Check for common spam phrases
        $spam_phrases = array(
            'click here', 'buy now', 'limited time', 'act fast', 'guaranteed',
            'free money', 'make money fast', 'work from home', 'get rich quick'
        );
        
        foreach ($spam_phrases as $phrase) {
            if (stripos($text, $phrase) !== false) {
                $issues[] = 'Potential spam phrase detected: ' . $phrase;
                $score -= 5;
            }
        }
        
        return array(
            'score' => max(0, $score),
            'keyword_density' => round($keyword_density, 2),
            'issues' => $issues,
            'link_density' => round($link_density, 2)
        );
    }
    
    /**
     * Check similarity between sentences
     */
    private function check_sentence_similarity($sentences) {
        if (count($sentences) < 2) return 0;
        
        $similarities = array();
        for ($i = 0; $i < count($sentences) - 1; $i++) {
            for ($j = $i + 1; $j < count($sentences); $j++) {
                $similarity = $this->calculate_text_similarity(trim($sentences[$i]), trim($sentences[$j]));
                $similarities[] = $similarity;
            }
        }
        
        return count($similarities) > 0 ? array_sum($similarities) / count($similarities) : 0;
    }
    
    /**
     * Calculate text similarity using Levenshtein distance
     */
    private function calculate_text_similarity($text1, $text2) {
        if (empty($text1) || empty($text2)) return 0;
        
        $len1 = strlen($text1);
        $len2 = strlen($text2);
        $max_len = max($len1, $len2);
        
        if ($max_len == 0) return 1;
        
        $distance = levenshtein($text1, $text2);
        return 1 - ($distance / $max_len);
    }
    
    /**
     * Analyze keyword usage and optimization
     */
    private function analyze_keyword_usage($content, $keyword) {
        $text = wp_strip_all_tags($content);
        $score = 0;
        $recommendations = array();
        
        // Check keyword in title (H1)
        if (preg_match('/<h1[^>]*>.*?' . preg_quote($keyword, '/') . '.*?<\/h1>/i', $content)) {
            $score += 20;
        } else {
            $recommendations[] = 'Consider including keyword in main heading';
        }
        
        // Check keyword in subheadings
        $subheading_matches = preg_match_all('/<h[2-6][^>]*>.*?' . preg_quote($keyword, '/') . '.*?<\/h[2-6]>/i', $content);
        if ($subheading_matches > 0) {
            $score += 15;
        } else {
            $recommendations[] = 'Consider including keyword in subheadings';
        }
        
        // Check keyword in first paragraph
        $paragraphs = explode("\n\n", $text);
        if (!empty($paragraphs[0]) && stripos($paragraphs[0], $keyword) !== false) {
            $score += 15;
        } else {
            $recommendations[] = 'Include keyword in first paragraph';
        }
        
        // Check keyword variations
        $variations = $this->generate_keyword_variations($keyword);
        $variation_count = 0;
        foreach ($variations as $variation) {
            if (stripos($text, $variation) !== false) {
                $variation_count++;
            }
        }
        $score += min(25, $variation_count * 5);
        
        // Check keyword placement distribution
        $keyword_positions = array();
        $offset = 0;
        while (($pos = stripos($text, $keyword, $offset)) !== false) {
            $keyword_positions[] = $pos / strlen($text);
            $offset = $pos + 1;
        }
        
        if (count($keyword_positions) > 1) {
            $distribution_score = $this->calculate_distribution_score($keyword_positions);
            $score += $distribution_score * 25;
        }
        
        return array(
            'score' => min(100, $score),
            'variations_found' => $variation_count,
            'recommendations' => $recommendations,
            'keyword_positions' => $keyword_positions
        );
    }
    
    /**
     * Generate keyword variations
     */
    private function generate_keyword_variations($keyword) {
        $variations = array();
        
        // Add plurals/singulars
        if (substr($keyword, -1) === 's') {
            $variations[] = substr($keyword, 0, -1);
        } else {
            $variations[] = $keyword . 's';
        }
        
        // Add gerund forms
        if (!preg_match('/ing$/', $keyword)) {
            $variations[] = $keyword . 'ing';
        }
        
        // Add past tense
        if (!preg_match('/ed$/', $keyword)) {
            $variations[] = $keyword . 'ed';
        }
        
        // Add related terms
        $related_terms = array(
            'guide' => array('tutorial', 'how-to', 'instructions'),
            'tips' => array('advice', 'suggestions', 'recommendations'),
            'best' => array('top', 'leading', 'optimal'),
            'tool' => array('software', 'platform', 'application')
        );
        
        foreach ($related_terms as $term => $synonyms) {
            if (stripos($keyword, $term) !== false) {
                foreach ($synonyms as $synonym) {
                    $variations[] = str_ireplace($term, $synonym, $keyword);
                }
            }
        }
        
        return array_unique($variations);
    }
    
    /**
     * Calculate keyword distribution score
     */
    private function calculate_distribution_score($positions) {
        if (count($positions) < 2) return 0;
        
        // Check if keywords are evenly distributed
        $intervals = array();
        for ($i = 1; $i < count($positions); $i++) {
            $intervals[] = $positions[$i] - $positions[$i-1];
        }
        
        $avg_interval = array_sum($intervals) / count($intervals);
        $variance = 0;
        foreach ($intervals as $interval) {
            $variance += pow($interval - $avg_interval, 2);
        }
        $variance /= count($intervals);
        $std_dev = sqrt($variance);
        
        // Lower standard deviation = better distribution
        return max(0, 1 - ($std_dev * 4));
    }
    
    /**
     * Analyze content structure quality
     */
    private function analyze_content_structure($content) {
        $score = 0;
        $recommendations = array();
        
        // Check for headings
        $heading_count = preg_match_all('/<h[1-6][^>]*>/', $content);
        if ($heading_count >= 3) {
            $score += 25;
        } elseif ($heading_count >= 1) {
            $score += 15;
            $recommendations[] = 'Add more subheadings for better structure';
        } else {
            $recommendations[] = 'Add headings to structure your content';
        }
        
        // Check for lists
        $list_count = preg_match_all('/<[ou]l>/', $content);
        if ($list_count >= 2) {
            $score += 20;
        } elseif ($list_count >= 1) {
            $score += 10;
        } else {
            $recommendations[] = 'Consider adding lists for better readability';
        }
        
        // Check paragraph length
        $paragraphs = explode("\n\n", wp_strip_all_tags($content));
        $avg_paragraph_length = 0;
        $paragraph_count = 0;
        
        foreach ($paragraphs as $paragraph) {
            $word_count = str_word_count(trim($paragraph));
            if ($word_count > 0) {
                $avg_paragraph_length += $word_count;
                $paragraph_count++;
            }
        }
        
        if ($paragraph_count > 0) {
            $avg_paragraph_length /= $paragraph_count;
            if ($avg_paragraph_length >= 50 && $avg_paragraph_length <= 150) {
                $score += 20;
            } else {
                $recommendations[] = 'Optimize paragraph length (50-150 words ideal)';
            }
        }
        
        // Check for internal links
        $internal_links = preg_match_all('/<a[^>]*href=["\'][^"\']*' . preg_quote(home_url(), '/') . '[^"\']*["\'][^>]*>/', $content);
        if ($internal_links >= 2) {
            $score += 15;
        } elseif ($internal_links >= 1) {
            $score += 10;
        } else {
            $recommendations[] = 'Add internal links to related content';
        }
        
        // Check for images
        $image_count = preg_match_all('/<img[^>]*>/', $content);
        if ($image_count >= 1) {
            $score += 10;
        } else {
            $recommendations[] = 'Consider adding relevant images';
        }
        
        // Check content length
        $word_count = str_word_count(wp_strip_all_tags($content));
        if ($word_count >= 800) {
            $score += 10;
        } elseif ($word_count >= 500) {
            $score += 5;
        } else {
            $recommendations[] = 'Consider expanding content length (500+ words recommended)';
        }
        
        return array(
            'score' => $score,
            'word_count' => $word_count,
            'heading_count' => $heading_count,
            'list_count' => $list_count,
            'avg_paragraph_length' => round($avg_paragraph_length, 1),
            'recommendations' => $recommendations
        );
    }
    
    /**
     * Check content uniqueness
     */
    private function check_content_uniqueness($content) {
        $text = wp_strip_all_tags($content);
        $sentences = array_filter(preg_split('/[.!?]+/', $text));
        
        // Check against existing posts (sample check)
        $similar_posts = $this->find_similar_content($text);
        
        $uniqueness_score = 100;
        if (!empty($similar_posts)) {
            $uniqueness_score -= count($similar_posts) * 10;
        }
        
        // Check for duplicate sentences within the content
        $duplicate_sentences = $this->find_duplicate_sentences($sentences);
        if (!empty($duplicate_sentences)) {
            $uniqueness_score -= count($duplicate_sentences) * 5;
        }
        
        return array(
            'score' => max(0, $uniqueness_score),
            'similar_posts' => $similar_posts,
            'duplicate_sentences' => $duplicate_sentences
        );
    }
    
    /**
     * Find similar content in existing posts
     */
    private function find_similar_content($text) {
        // Get recent posts for comparison
        $recent_posts = get_posts(array(
            'numberposts' => 50,
            'post_status' => 'publish',
            'meta_query' => array(
                array(
                    'key' => 'kotacom_ai_generated',
                    'value' => true,
                    'compare' => '='
                )
            )
        ));
        
        $similar_posts = array();
        $text_words = array_unique(str_word_count(strtolower($text), 1));
        
        foreach ($recent_posts as $post) {
            $post_content = wp_strip_all_tags($post->post_content);
            $post_words = array_unique(str_word_count(strtolower($post_content), 1));
            
            $common_words = array_intersect($text_words, $post_words);
            $similarity = count($common_words) / max(count($text_words), count($post_words));
            
            if ($similarity > 0.6) { // 60% similarity threshold
                $similar_posts[] = array(
                    'post_id' => $post->ID,
                    'title' => $post->post_title,
                    'similarity' => round($similarity, 2)
                );
            }
        }
        
        return $similar_posts;
    }
    
    /**
     * Find duplicate sentences within content
     */
    private function find_duplicate_sentences($sentences) {
        $duplicates = array();
        $seen_sentences = array();
        
        foreach ($sentences as $sentence) {
            $clean_sentence = trim(strtolower($sentence));
            if (strlen($clean_sentence) > 20) { // Only check sentences longer than 20 chars
                if (in_array($clean_sentence, $seen_sentences)) {
                    $duplicates[] = $sentence;
                } else {
                    $seen_sentences[] = $clean_sentence;
                }
            }
        }
        
        return $duplicates;
    }
    
    /**
     * Analyze natural language patterns
     */
    private function analyze_natural_language_patterns($content) {
        $text = wp_strip_all_tags($content);
        $score = 100;
        $issues = array();
        
        // Check sentence variety
        $sentences = array_filter(preg_split('/[.!?]+/', $text));
        $sentence_lengths = array_map('str_word_count', $sentences);
        
        if (count($sentence_lengths) > 1) {
            $length_variance = $this->calculate_variance($sentence_lengths);
            if ($length_variance < 10) {
                $issues[] = 'Low sentence length variety';
                $score -= 15;
            }
        }
        
        // Check transition word usage
        $transition_words = array(
            'however', 'therefore', 'furthermore', 'moreover', 'additionally',
            'consequently', 'meanwhile', 'nevertheless', 'similarly', 'likewise',
            'in contrast', 'on the other hand', 'for example', 'for instance',
            'in conclusion', 'as a result', 'in addition'
        );
        
        $transition_count = 0;
        foreach ($transition_words as $word) {
            if (stripos($text, $word) !== false) {
                $transition_count++;
            }
        }
        
        $word_count = str_word_count($text);
        $transition_ratio = ($transition_count / max(1, $word_count)) * 1000;
        
        if ($transition_ratio < 5) {
            $issues[] = 'Low transition word usage';
            $score -= 10;
        }
        
        // Check for natural conversation elements
        $conversation_elements = array(
            'you know', 'of course', 'obviously', 'clearly', 'naturally',
            'interestingly', 'surprisingly', 'importantly', 'notably'
        );
        
        $conversation_count = 0;
        foreach ($conversation_elements as $element) {
            if (stripos($text, $element) !== false) {
                $conversation_count++;
            }
        }
        
        if ($conversation_count === 0 && $word_count > 200) {
            $issues[] = 'Content lacks conversational elements';
            $score -= 5;
        }
        
        return array(
            'score' => max(0, $score),
            'sentence_variety' => isset($length_variance) ? round($length_variance, 1) : 0,
            'transition_ratio' => round($transition_ratio, 2),
            'issues' => $issues
        );
    }
    
    /**
     * Calculate variance of an array
     */
    private function calculate_variance($values) {
        if (count($values) < 2) return 0;
        
        $mean = array_sum($values) / count($values);
        $variance = 0;
        
        foreach ($values as $value) {
            $variance += pow($value - $mean, 2);
        }
        
        return $variance / count($values);
    }
    
    /**
     * Apply quality improvements to content
     */
    private function apply_quality_improvements($content, $quality_report, $keyword) {
        // Fix keyword density issues
        if (isset($quality_report['spam_signals']['keyword_density']) && 
            $quality_report['spam_signals']['keyword_density'] > 4) {
            $content = $this->reduce_keyword_density($content, $keyword);
        }
        
        // Improve sentence variety
        if (isset($quality_report['natural_language']['sentence_variety']) && 
            $quality_report['natural_language']['sentence_variety'] < 15) {
            $content = $this->improve_sentence_variety($content);
        }
        
        // Add transition words
        if (isset($quality_report['natural_language']['transition_ratio']) && 
            $quality_report['natural_language']['transition_ratio'] < 5) {
            $content = $this->add_transition_words($content);
        }
        
        // Improve structure if needed
        if ($quality_report['structure_quality']['score'] < 60) {
            $content = $this->improve_content_structure($content);
        }
        
        return $content;
    }
    
    /**
     * Reduce keyword density
     */
    private function reduce_keyword_density($content, $keyword) {
        $variations = array('this solution', 'the approach', 'this method', 'the system');
        
        // Replace every 3rd occurrence with a variation
        $count = 0;
        $content = preg_replace_callback('/\b' . preg_quote($keyword, '/') . '\b/i', 
            function($matches) use ($variations, &$count) {
                $count++;
                if ($count % 3 === 0) {
                    return $variations[array_rand($variations)];
                }
                return $matches[0];
            }, $content);
        
        return $content;
    }
    
    /**
     * Improve sentence variety
     */
    private function improve_sentence_variety($content) {
        $sentences = preg_split('/([.!?]+)/', $content, -1, PREG_SPLIT_DELIM_CAPTURE);
        
        for ($i = 0; $i < count($sentences) - 2; $i += 2) {
            $sentence = trim($sentences[$i]);
            if (str_word_count($sentence) < 8) {
                // Extend short sentences
                $extenders = array(
                    ', which provides additional benefits',
                    ', making it highly effective',
                    ', ensuring optimal results',
                    ', delivering excellent outcomes'
                );
                $sentences[$i] = $sentence . $extenders[array_rand($extenders)];
            }
        }
        
        return implode('', $sentences);
    }
    
    /**
     * Add transition words to content
     */
    private function add_transition_words($content) {
        $paragraphs = explode("\n\n", $content);
        $transitions = array(
            'Furthermore', 'Additionally', 'Moreover', 'However', 'Therefore', 
            'In addition', 'As a result', 'Consequently', 'Meanwhile'
        );
        
        for ($i = 1; $i < count($paragraphs); $i++) {
            if (rand(0, 100) < 40) { // 40% chance to add transition
                $transition = $transitions[array_rand($transitions)];
                $paragraphs[$i] = $transition . ', ' . lcfirst(trim($paragraphs[$i]));
            }
        }
        
        return implode("\n\n", $paragraphs);
    }
    
    /**
     * Improve content structure
     */
    private function improve_content_structure($content) {
        // Add subheadings if missing
        $heading_count = preg_match_all('/<h[2-6][^>]*>/', $content);
        if ($heading_count < 2) {
            $paragraphs = explode("\n\n", $content);
            if (count($paragraphs) >= 4) {
                // Add heading before middle paragraph
                $middle_index = floor(count($paragraphs) / 2);
                $paragraphs[$middle_index] = "\n## Key Considerations\n\n" . $paragraphs[$middle_index];
            }
            $content = implode("\n\n", $paragraphs);
        }
        
        return $content;
    }
    
    /**
     * Add schema markup for content quality
     */
    public function add_quality_schema_markup() {
        if (is_singular() && get_post_meta(get_the_ID(), 'kotacom_ai_generated', true)) {
            $quality_score = get_post_meta(get_the_ID(), 'content_quality_score', true);
            if ($quality_score) {
                echo '<!-- Content Quality Score: ' . $quality_score . '/100 -->';
            }
        }
    }
}