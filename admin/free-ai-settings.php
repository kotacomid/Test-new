<?php
/**
 * Free AI Settings Page
 */

if (!defined('ABSPATH')) {
    exit;
}

// Add submenu page
add_action('admin_menu', function() {
    add_submenu_page(
        'kotacom-ai',
        __('Free AI Providers', 'kotacom-ai'),
        __('Free AI', 'kotacom-ai'),
        'manage_options',
        'kotacom-ai-free',
        'kotacom_ai_free_settings_page'
    );
});

function kotacom_ai_free_settings_page() {
    $kotacom_ai = kotacom_ai();
    
    // Handle form submission
    if (isset($_POST['save_free_ai_settings']) && wp_verify_nonce($_POST['kotacom_ai_nonce'], 'kotacom_ai_settings')) {
        // Save free AI provider settings
        $free_providers = array(
            'huggingface_free',
            'ollama_local',
            'cohere_free',
            'together_free',
            'groq_free',
            'replicate_free'
        );
        
        foreach ($free_providers as $provider) {
            $api_key = sanitize_text_field($_POST[$provider . '_api_key'] ?? '');
            $model = sanitize_text_field($_POST[$provider . '_model'] ?? '');
            
            update_option('kotacom_ai_' . $provider . '_api_key', $api_key);
            update_option('kotacom_ai_' . $provider . '_model', $model);
        }
        
        // Save rotation strategy
        $rotation_strategy = sanitize_text_field($_POST['rotation_strategy'] ?? 'smart');
        update_option('kotacom_ai_rotation_strategy', $rotation_strategy);
        
        // Save Ollama settings
        $ollama_url = sanitize_url($_POST['ollama_url'] ?? 'http://localhost:11434');
        update_option('kotacom_ai_ollama_url', $ollama_url);
        
        // Save preferred providers
        $preferred_providers = array_map('sanitize_text_field', $_POST['preferred_providers'] ?? array());
        update_option('kotacom_ai_preferred_free_providers', $preferred_providers);
        
        echo '<div class="notice notice-success is-dismissible"><p>' . __('Free AI settings saved successfully!', 'kotacom-ai') . '</p></div>';
    }
    
    // Get current settings
    $rotation_strategy = get_option('kotacom_ai_rotation_strategy', 'smart');
    $ollama_url = get_option('kotacom_ai_ollama_url', 'http://localhost:11434');
    $preferred_providers = get_option('kotacom_ai_preferred_free_providers', array());
    
    ?>
    <div class="wrap">
        <h1><?php _e('Free AI Providers Settings', 'kotacom-ai'); ?></h1>
        
        <div class="nav-tab-wrapper">
            <a href="#providers" class="nav-tab nav-tab-active" onclick="showTab('providers')"><?php _e('Providers', 'kotacom-ai'); ?></a>
            <a href="#rotation" class="nav-tab" onclick="showTab('rotation')"><?php _e('Rotation Settings', 'kotacom-ai'); ?></a>
            <a href="#statistics" class="nav-tab" onclick="showTab('statistics')"><?php _e('Statistics', 'kotacom-ai'); ?></a>
            <a href="#local-setup" class="nav-tab" onclick="showTab('local-setup')"><?php _e('Local Setup', 'kotacom-ai'); ?></a>
        </div>
        
        <form method="post" action="">
            <?php wp_nonce_field('kotacom_ai_settings', 'kotacom_ai_nonce'); ?>
            
            <!-- Providers Tab -->
            <div id="providers-tab" class="tab-content">
                <h2><?php _e('Free AI Provider Configuration', 'kotacom-ai'); ?></h2>
                <p><?php _e('Configure your free AI service providers. These services offer free tiers or local hosting options.', 'kotacom-ai'); ?></p>
                
                <!-- Hugging Face -->
                <div class="postbox">
                    <div class="postbox-header">
                        <h3><?php _e('Hugging Face (Free Tier)', 'kotacom-ai'); ?></h3>
                    </div>
                    <div class="inside">
                        <table class="form-table">
                            <tr>
                                <th scope="row"><?php _e('API Key', 'kotacom-ai'); ?></th>
                                <td>
                                    <input type="text" name="huggingface_free_api_key" value="<?php echo esc_attr(get_option('kotacom_ai_huggingface_free_api_key')); ?>" class="regular-text" />
                                    <p class="description"><?php _e('Get your free API key from huggingface.co/settings/tokens', 'kotacom-ai'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php _e('Model', 'kotacom-ai'); ?></th>
                                <td>
                                    <select name="huggingface_free_model">
                                        <?php 
                                        $models = array(
                                            'microsoft/DialoGPT-medium' => 'DialoGPT Medium',
                                            'gpt2' => 'GPT-2',
                                            'facebook/blenderbot-400M-distill' => 'BlenderBot 400M',
                                            'microsoft/DialoGPT-large' => 'DialoGPT Large'
                                        );
                                        $current_model = get_option('kotacom_ai_huggingface_free_model', 'microsoft/DialoGPT-medium');
                                        foreach ($models as $value => $label) {
                                            echo '<option value="' . esc_attr($value) . '"' . selected($current_model, $value, false) . '>' . esc_html($label) . '</option>';
                                        }
                                        ?>
                                    </select>
                                </td>
                            </tr>
                        </table>
                        <button type="button" class="button test-provider" data-provider="huggingface_free"><?php _e('Test Connection', 'kotacom-ai'); ?></button>
                    </div>
                </div>
                
                <!-- Groq -->
                <div class="postbox">
                    <div class="postbox-header">
                        <h3><?php _e('Groq (Free Tier)', 'kotacom-ai'); ?></h3>
                    </div>
                    <div class="inside">
                        <table class="form-table">
                            <tr>
                                <th scope="row"><?php _e('API Key', 'kotacom-ai'); ?></th>
                                <td>
                                    <input type="text" name="groq_free_api_key" value="<?php echo esc_attr(get_option('kotacom_ai_groq_free_api_key')); ?>" class="regular-text" />
                                    <p class="description"><?php _e('Get your free API key from console.groq.com', 'kotacom-ai'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php _e('Model', 'kotacom-ai'); ?></th>
                                <td>
                                    <select name="groq_free_model">
                                        <?php 
                                        $models = array(
                                            'mixtral-8x7b-32768' => 'Mixtral 8x7B',
                                            'llama2-70b-4096' => 'Llama 2 70B',
                                            'gemma-7b-it' => 'Gemma 7B'
                                        );
                                        $current_model = get_option('kotacom_ai_groq_free_model', 'mixtral-8x7b-32768');
                                        foreach ($models as $value => $label) {
                                            echo '<option value="' . esc_attr($value) . '"' . selected($current_model, $value, false) . '>' . esc_html($label) . '</option>';
                                        }
                                        ?>
                                    </select>
                                </td>
                            </tr>
                        </table>
                        <button type="button" class="button test-provider" data-provider="groq_free"><?php _e('Test Connection', 'kotacom-ai'); ?></button>
                    </div>
                </div>
                
                <!-- Cohere -->
                <div class="postbox">
                    <div class="postbox-header">
                        <h3><?php _e('Cohere (Free Tier)', 'kotacom-ai'); ?></h3>
                    </div>
                    <div class="inside">
                        <table class="form-table">
                            <tr>
                                <th scope="row"><?php _e('API Key', 'kotacom-ai'); ?></th>
                                <td>
                                    <input type="text" name="cohere_free_api_key" value="<?php echo esc_attr(get_option('kotacom_ai_cohere_free_api_key')); ?>" class="regular-text" />
                                    <p class="description"><?php _e('Get your free API key from dashboard.cohere.ai', 'kotacom-ai'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php _e('Model', 'kotacom-ai'); ?></th>
                                <td>
                                    <select name="cohere_free_model">
                                        <?php 
                                        $models = array(
                                            'command' => 'Command',
                                            'command-nightly' => 'Command Nightly'
                                        );
                                        $current_model = get_option('kotacom_ai_cohere_free_model', 'command');
                                        foreach ($models as $value => $label) {
                                            echo '<option value="' . esc_attr($value) . '"' . selected($current_model, $value, false) . '>' . esc_html($label) . '</option>';
                                        }
                                        ?>
                                    </select>
                                </td>
                            </tr>
                        </table>
                        <button type="button" class="button test-provider" data-provider="cohere_free"><?php _e('Test Connection', 'kotacom-ai'); ?></button>
                    </div>
                </div>
                
                <!-- Together AI -->
                <div class="postbox">
                    <div class="postbox-header">
                        <h3><?php _e('Together AI (Free Credits)', 'kotacom-ai'); ?></h3>
                    </div>
                    <div class="inside">
                        <table class="form-table">
                            <tr>
                                <th scope="row"><?php _e('API Key', 'kotacom-ai'); ?></th>
                                <td>
                                    <input type="text" name="together_free_api_key" value="<?php echo esc_attr(get_option('kotacom_ai_together_free_api_key')); ?>" class="regular-text" />
                                    <p class="description"><?php _e('Get your free credits from api.together.xyz', 'kotacom-ai'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php _e('Model', 'kotacom-ai'); ?></th>
                                <td>
                                    <select name="together_free_model">
                                        <?php 
                                        $models = array(
                                            'togethercomputer/llama-2-7b-chat' => 'Llama 2 7B Chat',
                                            'teknium/OpenHermes-2.5-Mistral-7B' => 'OpenHermes 2.5',
                                            'mistralai/Mixtral-8x7B-Instruct-v0.1' => 'Mixtral 8x7B',
                                            'NousResearch/Nous-Hermes-2-Mixtral-8x7B-DPO' => 'Nous Hermes 2'
                                        );
                                        $current_model = get_option('kotacom_ai_together_free_model', 'togethercomputer/llama-2-7b-chat');
                                        foreach ($models as $value => $label) {
                                            echo '<option value="' . esc_attr($value) . '"' . selected($current_model, $value, false) . '>' . esc_html($label) . '</option>';
                                        }
                                        ?>
                                    </select>
                                </td>
                            </tr>
                        </table>
                        <button type="button" class="button test-provider" data-provider="together_free"><?php _e('Test Connection', 'kotacom-ai'); ?></button>
                    </div>
                </div>
                
                <!-- Replicate -->
                <div class="postbox">
                    <div class="postbox-header">
                        <h3><?php _e('Replicate (Free Credits)', 'kotacom-ai'); ?></h3>
                    </div>
                    <div class="inside">
                        <table class="form-table">
                            <tr>
                                <th scope="row"><?php _e('API Key', 'kotacom-ai'); ?></th>
                                <td>
                                    <input type="text" name="replicate_free_api_key" value="<?php echo esc_attr(get_option('kotacom_ai_replicate_free_api_key')); ?>" class="regular-text" />
                                    <p class="description"><?php _e('Get your free credits from replicate.com', 'kotacom-ai'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php _e('Model', 'kotacom-ai'); ?></th>
                                <td>
                                    <select name="replicate_free_model">
                                        <?php 
                                        $models = array(
                                            'meta/llama-2-70b-chat' => 'Llama 2 70B Chat',
                                            'mistralai/mistral-7b-instruct-v0.1' => 'Mistral 7B',
                                            'meta/llama-2-13b-chat' => 'Llama 2 13B Chat'
                                        );
                                        $current_model = get_option('kotacom_ai_replicate_free_model', 'meta/llama-2-70b-chat');
                                        foreach ($models as $value => $label) {
                                            echo '<option value="' . esc_attr($value) . '"' . selected($current_model, $value, false) . '>' . esc_html($label) . '</option>';
                                        }
                                        ?>
                                    </select>
                                </td>
                            </tr>
                        </table>
                        <button type="button" class="button test-provider" data-provider="replicate_free"><?php _e('Test Connection', 'kotacom-ai'); ?></button>
                    </div>
                </div>
            </div>
            
            <!-- Rotation Settings Tab -->
            <div id="rotation-tab" class="tab-content" style="display:none;">
                <h2><?php _e('API Rotation Strategy', 'kotacom-ai'); ?></h2>
                <p><?php _e('Configure how the plugin selects which AI provider to use for each request.', 'kotacom-ai'); ?></p>
                
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('Rotation Strategy', 'kotacom-ai'); ?></th>
                        <td>
                            <select name="rotation_strategy">
                                <option value="smart" <?php selected($rotation_strategy, 'smart'); ?>><?php _e('Smart (Recommended)', 'kotacom-ai'); ?></option>
                                <option value="round_robin" <?php selected($rotation_strategy, 'round_robin'); ?>><?php _e('Round Robin', 'kotacom-ai'); ?></option>
                                <option value="random" <?php selected($rotation_strategy, 'random'); ?>><?php _e('Random', 'kotacom-ai'); ?></option>
                                <option value="rate_based" <?php selected($rotation_strategy, 'rate_based'); ?>><?php _e('Rate Limit Based', 'kotacom-ai'); ?></option>
                            </select>
                            <p class="description">
                                <strong><?php _e('Smart:', 'kotacom-ai'); ?></strong> <?php _e('Uses multiple factors including response time, error rate, and usage limits to select the best provider.', 'kotacom-ai'); ?><br>
                                <strong><?php _e('Round Robin:', 'kotacom-ai'); ?></strong> <?php _e('Cycles through providers in order.', 'kotacom-ai'); ?><br>
                                <strong><?php _e('Random:', 'kotacom-ai'); ?></strong> <?php _e('Randomly selects an available provider.', 'kotacom-ai'); ?><br>
                                <strong><?php _e('Rate Limit Based:', 'kotacom-ai'); ?></strong> <?php _e('Prefers providers with the most remaining quota.', 'kotacom-ai'); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Preferred Providers', 'kotacom-ai'); ?></th>
                        <td>
                            <?php 
                            $providers = array(
                                'huggingface_free' => 'Hugging Face (Free)',
                                'groq_free' => 'Groq (Free)',
                                'cohere_free' => 'Cohere (Free)',
                                'together_free' => 'Together AI (Free)',
                                'replicate_free' => 'Replicate (Free)',
                                'ollama_local' => 'Ollama (Local)'
                            );
                            
                            foreach ($providers as $value => $label) {
                                $checked = in_array($value, $preferred_providers) ? 'checked' : '';
                                echo '<label><input type="checkbox" name="preferred_providers[]" value="' . esc_attr($value) . '" ' . $checked . '> ' . esc_html($label) . '</label><br>';
                            }
                            ?>
                            <p class="description"><?php _e('Preferred providers get a bonus in smart rotation strategy.', 'kotacom-ai'); ?></p>
                        </td>
                    </tr>
                </table>
            </div>
            
            <!-- Statistics Tab -->
            <div id="statistics-tab" class="tab-content" style="display:none;">
                <h2><?php _e('Provider Statistics', 'kotacom-ai'); ?></h2>
                <p><?php _e('Monitor the performance and usage of your free AI providers.', 'kotacom-ai'); ?></p>
                
                <div id="provider-stats">
                    <p><?php _e('Loading statistics...', 'kotacom-ai'); ?></p>
                </div>
                
                <button type="button" id="refresh-stats" class="button"><?php _e('Refresh Statistics', 'kotacom-ai'); ?></button>
                <button type="button" id="reset-stats" class="button button-secondary"><?php _e('Reset All Statistics', 'kotacom-ai'); ?></button>
            </div>
            
            <!-- Local Setup Tab -->
            <div id="local-setup-tab" class="tab-content" style="display:none;">
                <h2><?php _e('Local AI Setup', 'kotacom-ai'); ?></h2>
                <p><?php _e('Configure local AI services like Ollama for unlimited, free content generation.', 'kotacom-ai'); ?></p>
                
                <!-- Ollama Setup -->
                <div class="postbox">
                    <div class="postbox-header">
                        <h3><?php _e('Ollama (Local AI)', 'kotacom-ai'); ?></h3>
                    </div>
                    <div class="inside">
                        <table class="form-table">
                            <tr>
                                <th scope="row"><?php _e('Ollama URL', 'kotacom-ai'); ?></th>
                                <td>
                                    <input type="url" name="ollama_url" value="<?php echo esc_attr($ollama_url); ?>" class="regular-text" />
                                    <p class="description"><?php _e('URL where Ollama is running (default: http://localhost:11434)', 'kotacom-ai'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php _e('Model', 'kotacom-ai'); ?></th>
                                <td>
                                    <select name="ollama_local_model">
                                        <?php 
                                        $models = array(
                                            'llama2' => 'Llama 2',
                                            'codellama' => 'Code Llama',
                                            'mistral' => 'Mistral',
                                            'neural-chat' => 'Neural Chat',
                                            'starling-lm' => 'Starling LM'
                                        );
                                        $current_model = get_option('kotacom_ai_ollama_local_model', 'llama2');
                                        foreach ($models as $value => $label) {
                                            echo '<option value="' . esc_attr($value) . '"' . selected($current_model, $value, false) . '>' . esc_html($label) . '</option>';
                                        }
                                        ?>
                                    </select>
                                </td>
                            </tr>
                        </table>
                        
                        <h4><?php _e('Ollama Installation Guide', 'kotacom-ai'); ?></h4>
                        <ol>
                            <li><?php _e('Download Ollama from', 'kotacom-ai'); ?> <a href="https://ollama.ai" target="_blank">ollama.ai</a></li>
                            <li><?php _e('Install and start Ollama on your server', 'kotacom-ai'); ?></li>
                            <li><?php _e('Pull a model: ', 'kotacom-ai'); ?><code>ollama pull llama2</code></li>
                            <li><?php _e('Test the connection using the button below', 'kotacom-ai'); ?></li>
                        </ol>
                        
                        <button type="button" class="button test-provider" data-provider="ollama_local"><?php _e('Test Ollama Connection', 'kotacom-ai'); ?></button>
                    </div>
                </div>
                
                <div class="postbox">
                    <div class="postbox-header">
                        <h3><?php _e('Benefits of Local AI', 'kotacom-ai'); ?></h3>
                    </div>
                    <div class="inside">
                        <ul>
                            <li><?php _e('✓ Unlimited content generation (no API costs)', 'kotacom-ai'); ?></li>
                            <li><?php _e('✓ Complete privacy (data never leaves your server)', 'kotacom-ai'); ?></li>
                            <li><?php _e('✓ No rate limits or quotas', 'kotacom-ai'); ?></li>
                            <li><?php _e('✓ Works offline', 'kotacom-ai'); ?></li>
                            <li><?php _e('✓ Multiple models available', 'kotacom-ai'); ?></li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <p class="submit">
                <input type="submit" name="save_free_ai_settings" id="submit" class="button-primary" value="<?php _e('Save Settings', 'kotacom-ai'); ?>">
            </p>
        </form>
    </div>
    
    <style>
    .tab-content {
        margin-top: 20px;
    }
    .nav-tab-wrapper {
        margin-bottom: 0;
    }
    .postbox {
        margin-bottom: 20px;
    }
    .postbox .inside {
        padding: 12px;
    }
    .test-provider {
        margin-top: 10px;
    }
    .provider-stat {
        background: #f9f9f9;
        border: 1px solid #ddd;
        padding: 15px;
        margin-bottom: 15px;
        border-radius: 4px;
    }
    .provider-stat h4 {
        margin-top: 0;
    }
    .stat-row {
        display: flex;
        justify-content: space-between;
        margin-bottom: 5px;
    }
    .stat-value {
        font-weight: bold;
    }
    .status-available {
        color: #46b450;
    }
    .status-unavailable {
        color: #dc3232;
    }
    </style>
    
    <script>
    function showTab(tabName) {
        // Hide all tabs
        document.querySelectorAll('.tab-content').forEach(tab => {
            tab.style.display = 'none';
        });
        
        // Remove active class from all nav tabs
        document.querySelectorAll('.nav-tab').forEach(tab => {
            tab.classList.remove('nav-tab-active');
        });
        
        // Show selected tab
        document.getElementById(tabName + '-tab').style.display = 'block';
        
        // Add active class to clicked nav tab
        event.target.classList.add('nav-tab-active');
        
        // Load statistics if statistics tab is selected
        if (tabName === 'statistics') {
            loadProviderStats();
        }
    }
    
    function loadProviderStats() {
        const statsContainer = document.getElementById('provider-stats');
        statsContainer.innerHTML = '<p><?php _e('Loading statistics...', 'kotacom-ai'); ?></p>';
        
        fetch(ajaxurl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'kotacom_get_free_usage_stats',
                nonce: '<?php echo wp_create_nonce('kotacom_ai_nonce'); ?>'
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                let html = '';
                Object.entries(data.data).forEach(([provider, stats]) => {
                    const statusClass = stats.available ? 'status-available' : 'status-unavailable';
                    const statusText = stats.available ? '<?php _e('Available', 'kotacom-ai'); ?>' : '<?php _e('Unavailable', 'kotacom-ai'); ?>';
                    
                    html += `
                        <div class="provider-stat">
                            <h4>${stats.name} <span class="${statusClass}">(${statusText})</span></h4>
                            <div class="stat-row">
                                <span><?php _e('Usage:', 'kotacom-ai'); ?></span>
                                <span class="stat-value">${stats.usage}/${stats.rate_limit} (${stats.usage_percentage.toFixed(1)}%)</span>
                            </div>
                            <div class="stat-row">
                                <span><?php _e('Error Rate:', 'kotacom-ai'); ?></span>
                                <span class="stat-value">${stats.error_rate.toFixed(1)}%</span>
                            </div>
                            <div class="stat-row">
                                <span><?php _e('Avg Response Time:', 'kotacom-ai'); ?></span>
                                <span class="stat-value">${stats.avg_response_time.toFixed(2)}s</span>
                            </div>
                        </div>
                    `;
                });
                statsContainer.innerHTML = html;
            } else {
                statsContainer.innerHTML = '<p><?php _e('Error loading statistics.', 'kotacom-ai'); ?></p>';
            }
        })
        .catch(error => {
            statsContainer.innerHTML = '<p><?php _e('Error loading statistics.', 'kotacom-ai'); ?></p>';
        });
    }
    
    // Test provider connections
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.test-provider').forEach(button => {
            button.addEventListener('click', function() {
                const provider = this.dataset.provider;
                this.disabled = true;
                this.textContent = '<?php _e('Testing...', 'kotacom-ai'); ?>';
                
                fetch(ajaxurl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        action: 'kotacom_test_free_provider',
                        provider: provider,
                        nonce: '<?php echo wp_create_nonce('kotacom_ai_nonce'); ?>'
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('<?php _e('Success:', 'kotacom-ai'); ?> ' + data.data.message);
                    } else {
                        alert('<?php _e('Error:', 'kotacom-ai'); ?> ' + data.data.message);
                    }
                })
                .catch(error => {
                    alert('<?php _e('Connection error:', 'kotacom-ai'); ?> ' + error);
                })
                .finally(() => {
                    this.disabled = false;
                    this.textContent = '<?php _e('Test Connection', 'kotacom-ai'); ?>';
                });
            });
        });
        
        // Refresh stats button
        document.getElementById('refresh-stats').addEventListener('click', loadProviderStats);
        
        // Reset stats button
        document.getElementById('reset-stats').addEventListener('click', function() {
            if (confirm('<?php _e('Are you sure you want to reset all statistics?', 'kotacom-ai'); ?>')) {
                fetch(ajaxurl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        action: 'kotacom_reset_usage_stats',
                        nonce: '<?php echo wp_create_nonce('kotacom_ai_nonce'); ?>'
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert(data.data.message);
                        loadProviderStats();
                    } else {
                        alert('<?php _e('Error resetting statistics.', 'kotacom-ai'); ?>');
                    }
                })
                .catch(error => {
                    alert('<?php _e('Error resetting statistics.', 'kotacom-ai'); ?>');
                });
            }
        });
    });
    </script>
    <?php
}
?>