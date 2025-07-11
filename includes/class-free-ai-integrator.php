<?php
/**
 * Free AI Services Integrator with Enhanced API Rotation
 * Integrates multiple free AI services for content generation
 */

if (!defined('ABSPATH')) {
    exit;
}

class KotacomAI_Free_AI_Integrator {
    
    private $free_providers = array(
        'huggingface_free' => array(
            'name' => 'Hugging Face (Free)',
            'models' => array(
                'microsoft/DialoGPT-medium' => 'DialoGPT Medium',
                'gpt2' => 'GPT-2',
                'facebook/blenderbot-400M-distill' => 'BlenderBot 400M',
                'microsoft/DialoGPT-large' => 'DialoGPT Large'
            ),
            'rate_limit' => 1000, // requests per hour
            'max_tokens' => 1024
        ),
        'ollama_local' => array(
            'name' => 'Ollama (Local)',
            'models' => array(
                'llama2' => 'Llama 2',
                'codellama' => 'Code Llama',
                'mistral' => 'Mistral',
                'neural-chat' => 'Neural Chat',
                'starling-lm' => 'Starling LM'
            ),
            'rate_limit' => 999999, // unlimited for local
            'max_tokens' => 4096
        ),
        'cohere_free' => array(
            'name' => 'Cohere (Free Tier)',
            'models' => array(
                'command' => 'Command',
                'command-nightly' => 'Command Nightly'
            ),
            'rate_limit' => 100, // requests per month free tier
            'max_tokens' => 2048
        ),
        'together_free' => array(
            'name' => 'Together AI (Free Credits)',
            'models' => array(
                'togethercomputer/llama-2-7b-chat' => 'Llama 2 7B Chat',
                'teknium/OpenHermes-2.5-Mistral-7B' => 'OpenHermes 2.5',
                'mistralai/Mixtral-8x7B-Instruct-v0.1' => 'Mixtral 8x7B',
                'NousResearch/Nous-Hermes-2-Mixtral-8x7B-DPO' => 'Nous Hermes 2'
            ),
            'rate_limit' => 600, // requests per hour
            'max_tokens' => 8192
        ),
        'groq_free' => array(
            'name' => 'Groq (Free Tier)',
            'models' => array(
                'mixtral-8x7b-32768' => 'Mixtral 8x7B',
                'llama2-70b-4096' => 'Llama 2 70B',
                'gemma-7b-it' => 'Gemma 7B'
            ),
            'rate_limit' => 30, // requests per minute
            'max_tokens' => 32768
        ),
        'replicate_free' => array(
            'name' => 'Replicate (Free Credits)',
            'models' => array(
                'meta/llama-2-70b-chat' => 'Llama 2 70B Chat',
                'mistralai/mistral-7b-instruct-v0.1' => 'Mistral 7B',
                'meta/llama-2-13b-chat' => 'Llama 2 13B Chat'
            ),
            'rate_limit' => 200, // requests per hour
            'max_tokens' => 4096
        )
    );
    
    private $api_usage = array();
    private $rotation_strategy = 'smart'; // smart, round_robin, random, rate_based
    
    public function __construct() {
        $this->init();
        $this->load_usage_stats();
    }
    
    private function init() {
        // Hook into API selection process safely
        if (function_exists('add_filter')) {
            add_filter('kotacom_ai_select_provider', array($this, 'select_optimal_provider'), 10, 2);
            add_filter('kotacom_ai_api_providers', array($this, 'add_free_providers'));
            add_action('kotacom_ai_api_request_complete', array($this, 'track_api_usage'), 10, 3);
            
            // Admin hooks for free AI management
            add_action('wp_ajax_kotacom_test_free_provider', array($this, 'ajax_test_free_provider'));
            add_action('wp_ajax_kotacom_get_free_usage_stats', array($this, 'ajax_get_free_usage_stats'));
            add_action('wp_ajax_kotacom_reset_usage_stats', array($this, 'ajax_reset_usage_stats'));
        }
    }
    
    /**
     * Add free providers to available providers list
     */
    public function add_free_providers($providers) {
        return array_merge($providers, $this->free_providers);
    }
    
    /**
     * Select optimal provider based on usage and availability
     */
    public function select_optimal_provider($current_provider, $context = array()) {
        $strategy = get_option('kotacom_ai_rotation_strategy', $this->rotation_strategy);
        
        switch ($strategy) {
            case 'smart':
                return $this->smart_provider_selection($context);
            case 'round_robin':
                return $this->round_robin_selection();
            case 'random':
                return $this->random_selection();
            case 'rate_based':
                return $this->rate_based_selection();
            default:
                return $this->smart_provider_selection($context);
        }
    }
    
    /**
     * Smart provider selection based on multiple factors
     */
    private function smart_provider_selection($context = array()) {
        $available_providers = $this->get_available_providers();
        $best_provider = null;
        $best_score = 0;
        
        foreach ($available_providers as $provider => $data) {
            $score = $this->calculate_provider_score($provider, $data, $context);
            
            if ($score > $best_score) {
                $best_score = $score;
                $best_provider = $provider;
            }
        }
        
        return $best_provider ?: $this->get_fallback_provider();
    }
    
    /**
     * Calculate provider score based on multiple factors
     */
    private function calculate_provider_score($provider, $data, $context) {
        $score = 100; // Start with perfect score
        
        // Rate limit factor (higher remaining = higher score)
        $usage = $this->get_provider_usage($provider);
        $rate_limit = $data['rate_limit'];
        $usage_percentage = ($usage / $rate_limit) * 100;
        $score -= $usage_percentage * 0.5; // Reduce score based on usage
        
        // Response time factor
        $avg_response_time = $this->get_average_response_time($provider);
        if ($avg_response_time > 5) {
            $score -= 20; // Penalty for slow providers
        } elseif ($avg_response_time < 2) {
            $score += 10; // Bonus for fast providers
        }
        
        // Error rate factor
        $error_rate = $this->get_error_rate($provider);
        $score -= $error_rate * 30; // Significant penalty for errors
        
        // Content length compatibility
        $required_length = $context['length'] ?? 500;
        if ($required_length > $data['max_tokens']) {
            $score -= 30; // Penalty if provider can't handle required length
        }
        
        // Provider preference (admin setting)
        $preferred_providers = get_option('kotacom_ai_preferred_free_providers', array());
        if (in_array($provider, $preferred_providers)) {
            $score += 15; // Bonus for preferred providers
        }
        
        // Local provider bonus (no API costs)
        if (strpos($provider, 'local') !== false || strpos($provider, 'ollama') !== false) {
            $score += 25; // Significant bonus for local providers
        }
        
        return max(0, $score);
    }
    
    /**
     * Round robin provider selection
     */
    private function round_robin_selection() {
        $available_providers = array_keys($this->get_available_providers());
        $last_used = get_option('kotacom_ai_last_used_provider', '');
        
        $current_index = array_search($last_used, $available_providers);
        $next_index = ($current_index + 1) % count($available_providers);
        
        $selected = $available_providers[$next_index];
        update_option('kotacom_ai_last_used_provider', $selected);
        
        return $selected;
    }
    
    /**
     * Random provider selection
     */
    private function random_selection() {
        $available_providers = array_keys($this->get_available_providers());
        return $available_providers[array_rand($available_providers)];
    }
    
    /**
     * Rate-based provider selection (prefer providers with more remaining quota)
     */
    private function rate_based_selection() {
        $available_providers = $this->get_available_providers();
        $best_provider = null;
        $highest_remaining = 0;
        
        foreach ($available_providers as $provider => $data) {
            $usage = $this->get_provider_usage($provider);
            $remaining = $data['rate_limit'] - $usage;
            
            if ($remaining > $highest_remaining) {
                $highest_remaining = $remaining;
                $best_provider = $provider;
            }
        }
        
        return $best_provider ?: $this->get_fallback_provider();
    }
    
    /**
     * Get available providers (those with API keys configured and quota remaining)
     */
    private function get_available_providers() {
        $available = array();
        
        foreach ($this->free_providers as $provider => $data) {
            if ($this->is_provider_available($provider)) {
                $available[$provider] = $data;
            }
        }
        
        return $available;
    }
    
    /**
     * Check if provider is available and has quota remaining
     */
    private function is_provider_available($provider) {
        // Check if API key is configured
        $api_key = get_option('kotacom_ai_' . $provider . '_api_key', '');
        
        // Local providers don't need API keys
        if (strpos($provider, 'local') !== false || strpos($provider, 'ollama') !== false) {
            return $this->check_local_service($provider);
        }
        
        if (empty($api_key)) {
            return false;
        }
        
        // Check rate limit
        $usage = $this->get_provider_usage($provider);
        $rate_limit = $this->free_providers[$provider]['rate_limit'];
        
        return $usage < $rate_limit;
    }
    
    /**
     * Check if local AI service is running
     */
    private function check_local_service($provider) {
        if ($provider === 'ollama_local') {
            return $this->check_ollama_status();
        }
        
        return false;
    }
    
    /**
     * Check Ollama service status
     */
    private function check_ollama_status() {
        $ollama_url = get_option('kotacom_ai_ollama_url', 'http://localhost:11434');
        
        $response = wp_remote_get($ollama_url . '/api/tags', array(
            'timeout' => 5,
            'sslverify' => false
        ));
        
        return !is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200;
    }
    
    /**
     * Get provider usage for current time period
     */
    private function get_provider_usage($provider) {
        $time_window = $this->get_rate_limit_window($provider);
        $usage_key = 'kotacom_ai_usage_' . $provider . '_' . $time_window;
        
        return get_transient($usage_key) ?: 0;
    }
    
    /**
     * Get rate limit time window for provider
     */
    private function get_rate_limit_window($provider) {
        // Different providers have different rate limit windows
        switch ($provider) {
            case 'groq_free':
                return date('Y-m-d-H-i'); // Per minute
            case 'cohere_free':
                return date('Y-m'); // Per month
            case 'huggingface_free':
            case 'together_free':
            case 'replicate_free':
                return date('Y-m-d-H'); // Per hour
            default:
                return date('Y-m-d-H'); // Default to hourly
        }
    }
    
    /**
     * Track API usage
     */
    public function track_api_usage($provider, $success, $response_time) {
        // Update usage counter
        $time_window = $this->get_rate_limit_window($provider);
        $usage_key = 'kotacom_ai_usage_' . $provider . '_' . $time_window;
        $current_usage = get_transient($usage_key) ?: 0;
        set_transient($usage_key, $current_usage + 1, HOUR_IN_SECONDS);
        
        // Track response time
        $this->update_response_time($provider, $response_time);
        
        // Track error rate
        $this->update_error_rate($provider, $success);
        
        // Store in persistent usage log
        $this->log_usage($provider, $success, $response_time);
    }
    
    /**
     * Update average response time for provider
     */
    private function update_response_time($provider, $response_time) {
        $key = 'kotacom_ai_response_times_' . $provider;
        $times = get_option($key, array());
        
        // Keep last 100 response times
        $times[] = $response_time;
        if (count($times) > 100) {
            $times = array_slice($times, -100);
        }
        
        update_option($key, $times);
    }
    
    /**
     * Update error rate for provider
     */
    private function update_error_rate($provider, $success) {
        $key = 'kotacom_ai_errors_' . $provider;
        $errors = get_option($key, array('total' => 0, 'errors' => 0));
        
        $errors['total']++;
        if (!$success) {
            $errors['errors']++;
        }
        
        // Reset if we have too many data points
        if ($errors['total'] > 1000) {
            $errors['total'] = 100;
            $errors['errors'] = intval($errors['errors'] * 0.1);
        }
        
        update_option($key, $errors);
    }
    
    /**
     * Get average response time for provider
     */
    private function get_average_response_time($provider) {
        $times = get_option('kotacom_ai_response_times_' . $provider, array());
        
        if (empty($times)) {
            return 3; // Default moderate response time
        }
        
        return array_sum($times) / count($times);
    }
    
    /**
     * Get error rate for provider
     */
    private function get_error_rate($provider) {
        $errors = get_option('kotacom_ai_errors_' . $provider, array('total' => 0, 'errors' => 0));
        
        if ($errors['total'] === 0) {
            return 0;
        }
        
        return ($errors['errors'] / $errors['total']) * 100;
    }
    
    /**
     * Get fallback provider
     */
    private function get_fallback_provider() {
        // Return the most reliable free provider
        return 'huggingface_free';
    }
    
    /**
     * Load usage statistics
     */
    private function load_usage_stats() {
        $this->api_usage = get_option('kotacom_ai_usage_stats', array());
    }
    
    /**
     * Log usage for analytics
     */
    private function log_usage($provider, $success, $response_time) {
        $log_entry = array(
            'timestamp' => time(),
            'provider' => $provider,
            'success' => $success,
            'response_time' => $response_time,
            'date' => date('Y-m-d')
        );
        
        $log_key = 'kotacom_ai_usage_log';
        $log = get_option($log_key, array());
        
        // Add entry and keep last 1000 entries
        $log[] = $log_entry;
        if (count($log) > 1000) {
            $log = array_slice($log, -1000);
        }
        
        update_option($log_key, $log);
    }
    
    /**
     * Generate content using free AI providers
     */
    public function generate_content($prompt, $parameters = array()) {
        $provider = $this->select_optimal_provider(null, $parameters);
        
        if (!$provider) {
            return array(
                'success' => false,
                'error' => 'No available free AI providers'
            );
        }
        
        $start_time = microtime(true);
        $result = $this->call_provider_api($provider, $prompt, $parameters);
        $response_time = microtime(true) - $start_time;
        
        // Track usage
        $this->track_api_usage($provider, $result['success'], $response_time);
        
        return $result;
    }
    
    /**
     * Call specific provider API
     */
    private function call_provider_api($provider, $prompt, $parameters) {
        switch ($provider) {
            case 'huggingface_free':
                return $this->call_huggingface_api($prompt, $parameters);
            case 'ollama_local':
                return $this->call_ollama_api($prompt, $parameters);
            case 'cohere_free':
                return $this->call_cohere_api($prompt, $parameters);
            case 'together_free':
                return $this->call_together_api($prompt, $parameters);
            case 'groq_free':
                return $this->call_groq_api($prompt, $parameters);
            case 'replicate_free':
                return $this->call_replicate_api($prompt, $parameters);
            default:
                return array('success' => false, 'error' => 'Unknown provider: ' . $provider);
        }
    }
    
    /**
     * Call Hugging Face API
     */
    private function call_huggingface_api($prompt, $parameters) {
        $api_key = get_option('kotacom_ai_huggingface_free_api_key');
        $model = get_option('kotacom_ai_huggingface_free_model', 'microsoft/DialoGPT-medium');
        
        if (empty($api_key)) {
            return array('success' => false, 'error' => 'Hugging Face API key not configured');
        }
        
        $url = "https://api-inference.huggingface.co/models/{$model}";
        
        $response = wp_remote_post($url, array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json'
            ),
            'body' => json_encode(array(
                'inputs' => $prompt,
                'parameters' => array(
                    'max_length' => intval($parameters['length'] ?? 500),
                    'temperature' => floatval($parameters['temperature'] ?? 0.7),
                    'do_sample' => true
                )
            )),
            'timeout' => 30
        ));
        
        if (is_wp_error($response)) {
            return array('success' => false, 'error' => $response->get_error_message());
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (isset($data[0]['generated_text'])) {
            return array(
                'success' => true,
                'content' => $data[0]['generated_text'],
                'provider' => 'huggingface_free'
            );
        }
        
        return array('success' => false, 'error' => 'Invalid response from Hugging Face API');
    }
    
    /**
     * Call Ollama local API
     */
    private function call_ollama_api($prompt, $parameters) {
        $ollama_url = get_option('kotacom_ai_ollama_url', 'http://localhost:11434');
        $model = get_option('kotacom_ai_ollama_local_model', 'llama2');
        
        $url = $ollama_url . '/api/generate';
        
        $response = wp_remote_post($url, array(
            'headers' => array(
                'Content-Type' => 'application/json'
            ),
            'body' => json_encode(array(
                'model' => $model,
                'prompt' => $prompt,
                'stream' => false,
                'options' => array(
                    'temperature' => floatval($parameters['temperature'] ?? 0.7),
                    'num_predict' => intval($parameters['length'] ?? 500)
                )
            )),
            'timeout' => 60
        ));
        
        if (is_wp_error($response)) {
            return array('success' => false, 'error' => $response->get_error_message());
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (isset($data['response'])) {
            return array(
                'success' => true,
                'content' => $data['response'],
                'provider' => 'ollama_local'
            );
        }
        
        return array('success' => false, 'error' => 'Invalid response from Ollama API');
    }
    
    /**
     * Call Cohere API
     */
    private function call_cohere_api($prompt, $parameters) {
        $api_key = get_option('kotacom_ai_cohere_free_api_key');
        $model = get_option('kotacom_ai_cohere_free_model', 'command');
        
        if (empty($api_key)) {
            return array('success' => false, 'error' => 'Cohere API key not configured');
        }
        
        $response = wp_remote_post('https://api.cohere.ai/v1/generate', array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json'
            ),
            'body' => json_encode(array(
                'model' => $model,
                'prompt' => $prompt,
                'max_tokens' => intval($parameters['length'] ?? 500),
                'temperature' => floatval($parameters['temperature'] ?? 0.7)
            )),
            'timeout' => 30
        ));
        
        if (is_wp_error($response)) {
            return array('success' => false, 'error' => $response->get_error_message());
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (isset($data['generations'][0]['text'])) {
            return array(
                'success' => true,
                'content' => $data['generations'][0]['text'],
                'provider' => 'cohere_free'
            );
        }
        
        return array('success' => false, 'error' => 'Invalid response from Cohere API');
    }
    
    /**
     * Call Together AI API
     */
    private function call_together_api($prompt, $parameters) {
        $api_key = get_option('kotacom_ai_together_free_api_key');
        $model = get_option('kotacom_ai_together_free_model', 'togethercomputer/llama-2-7b-chat');
        
        if (empty($api_key)) {
            return array('success' => false, 'error' => 'Together AI API key not configured');
        }
        
        $response = wp_remote_post('https://api.together.xyz/inference', array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json'
            ),
            'body' => json_encode(array(
                'model' => $model,
                'prompt' => $prompt,
                'max_tokens' => intval($parameters['length'] ?? 500),
                'temperature' => floatval($parameters['temperature'] ?? 0.7)
            )),
            'timeout' => 30
        ));
        
        if (is_wp_error($response)) {
            return array('success' => false, 'error' => $response->get_error_message());
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (isset($data['output']['choices'][0]['text'])) {
            return array(
                'success' => true,
                'content' => $data['output']['choices'][0]['text'],
                'provider' => 'together_free'
            );
        }
        
        return array('success' => false, 'error' => 'Invalid response from Together AI API');
    }
    
    /**
     * Call Groq API
     */
    private function call_groq_api($prompt, $parameters) {
        $api_key = get_option('kotacom_ai_groq_free_api_key');
        $model = get_option('kotacom_ai_groq_free_model', 'mixtral-8x7b-32768');
        
        if (empty($api_key)) {
            return array('success' => false, 'error' => 'Groq API key not configured');
        }
        
        $response = wp_remote_post('https://api.groq.com/openai/v1/chat/completions', array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json'
            ),
            'body' => json_encode(array(
                'model' => $model,
                'messages' => array(
                    array('role' => 'user', 'content' => $prompt)
                ),
                'max_tokens' => intval($parameters['length'] ?? 500),
                'temperature' => floatval($parameters['temperature'] ?? 0.7)
            )),
            'timeout' => 30
        ));
        
        if (is_wp_error($response)) {
            return array('success' => false, 'error' => $response->get_error_message());
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (isset($data['choices'][0]['message']['content'])) {
            return array(
                'success' => true,
                'content' => $data['choices'][0]['message']['content'],
                'provider' => 'groq_free'
            );
        }
        
        return array('success' => false, 'error' => 'Invalid response from Groq API');
    }
    
    /**
     * Call Replicate API
     */
    private function call_replicate_api($prompt, $parameters) {
        $api_key = get_option('kotacom_ai_replicate_free_api_key');
        $model = get_option('kotacom_ai_replicate_free_model', 'meta/llama-2-70b-chat');
        
        if (empty($api_key)) {
            return array('success' => false, 'error' => 'Replicate API key not configured');
        }
        
        $response = wp_remote_post('https://api.replicate.com/v1/predictions', array(
            'headers' => array(
                'Authorization' => 'Token ' . $api_key,
                'Content-Type' => 'application/json'
            ),
            'body' => json_encode(array(
                'version' => $model,
                'input' => array(
                    'prompt' => $prompt,
                    'max_length' => intval($parameters['length'] ?? 500),
                    'temperature' => floatval($parameters['temperature'] ?? 0.7)
                )
            )),
            'timeout' => 30
        ));
        
        if (is_wp_error($response)) {
            return array('success' => false, 'error' => $response->get_error_message());
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        // Replicate requires polling for results
        if (isset($data['urls']['get'])) {
            $result = $this->poll_replicate_result($data['urls']['get'], $api_key);
            if ($result['success']) {
                return array(
                    'success' => true,
                    'content' => implode('', $result['output']),
                    'provider' => 'replicate_free'
                );
            }
            return $result;
        }
        
        return array('success' => false, 'error' => 'Invalid response from Replicate API');
    }
    
    /**
     * Poll Replicate for result
     */
    private function poll_replicate_result($url, $api_key, $max_attempts = 30) {
        for ($i = 0; $i < $max_attempts; $i++) {
            sleep(2); // Wait 2 seconds between checks
            
            $response = wp_remote_get($url, array(
                'headers' => array(
                    'Authorization' => 'Token ' . $api_key
                ),
                'timeout' => 10
            ));
            
            if (is_wp_error($response)) {
                continue;
            }
            
            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body, true);
            
            if (isset($data['status'])) {
                if ($data['status'] === 'succeeded' && isset($data['output'])) {
                    return array('success' => true, 'output' => $data['output']);
                } elseif ($data['status'] === 'failed') {
                    return array('success' => false, 'error' => 'Replicate prediction failed');
                }
            }
        }
        
        return array('success' => false, 'error' => 'Replicate prediction timeout');
    }
    
    /**
     * AJAX: Test free provider
     */
    public function ajax_test_free_provider() {
        check_ajax_referer('kotacom_ai_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Permission denied'));
        }
        
        $provider = sanitize_text_field($_POST['provider']);
        
        if (!isset($this->free_providers[$provider])) {
            wp_send_json_error(array('message' => 'Invalid provider'));
        }
        
        $test_prompt = "Write a short paragraph about artificial intelligence.";
        $result = $this->call_provider_api($provider, $test_prompt, array('length' => 100));
        
        if ($result['success']) {
            wp_send_json_success(array(
                'message' => 'Provider test successful',
                'response' => substr($result['content'], 0, 200) . '...'
            ));
        } else {
            wp_send_json_error(array(
                'message' => 'Provider test failed: ' . $result['error']
            ));
        }
    }
    
    /**
     * AJAX: Get free usage stats
     */
    public function ajax_get_free_usage_stats() {
        check_ajax_referer('kotacom_ai_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Permission denied'));
        }
        
        $stats = array();
        
        foreach ($this->free_providers as $provider => $data) {
            $usage = $this->get_provider_usage($provider);
            $error_rate = $this->get_error_rate($provider);
            $avg_response_time = $this->get_average_response_time($provider);
            
            $stats[$provider] = array(
                'name' => $data['name'],
                'usage' => $usage,
                'rate_limit' => $data['rate_limit'],
                'usage_percentage' => ($usage / $data['rate_limit']) * 100,
                'error_rate' => $error_rate,
                'avg_response_time' => $avg_response_time,
                'available' => $this->is_provider_available($provider)
            );
        }
        
        wp_send_json_success($stats);
    }
    
    /**
     * AJAX: Reset usage stats
     */
    public function ajax_reset_usage_stats() {
        check_ajax_referer('kotacom_ai_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Permission denied'));
        }
        
        // Clear all usage transients
        foreach ($this->free_providers as $provider => $data) {
            $this->clear_provider_stats($provider);
        }
        
        // Clear usage log
        delete_option('kotacom_ai_usage_log');
        
        wp_send_json_success(array('message' => 'Usage statistics reset successfully'));
    }
    
    /**
     * Clear provider statistics
     */
    private function clear_provider_stats($provider) {
        // Clear usage transients
        global $wpdb;
        $wpdb->query($wpdb->prepare(
            "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
            '_transient_kotacom_ai_usage_' . $provider . '%'
        ));
        
        // Clear response times
        delete_option('kotacom_ai_response_times_' . $provider);
        
        // Clear error rates
        delete_option('kotacom_ai_errors_' . $provider);
    }
    
    /**
     * Get free provider statistics for admin dashboard
     */
    public function get_provider_dashboard_stats() {
        $stats = array();
        
        foreach ($this->free_providers as $provider => $data) {
            $stats[] = array(
                'provider' => $provider,
                'name' => $data['name'],
                'available' => $this->is_provider_available($provider),
                'usage' => $this->get_provider_usage($provider),
                'rate_limit' => $data['rate_limit'],
                'error_rate' => $this->get_error_rate($provider),
                'response_time' => $this->get_average_response_time($provider)
            );
        }
        
        return $stats;
    }
}