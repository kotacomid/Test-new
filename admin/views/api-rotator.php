<?php
/**
 * API Key Rotator admin page
 */

if (!defined('ABSPATH')) {
    exit;
}

$kotacom_ai = kotacom_ai();
$api_handler = $kotacom_ai->api_handler;
$api_key_rotator = $kotacom_ai->api_key_rotator;
$providers = $api_handler->get_providers();
?>

<div class="wrap">
    <h1><?php _e('API Key Rotator', 'kotacom-ai'); ?></h1>
    
    <!-- Overview -->
    <div class="info-card">
        <h3><span class="dashicons dashicons-update"></span> <?php _e('API Key Rotation', 'kotacom-ai'); ?></h3>
        <p><?php _e('Manage multiple API keys per provider for automatic rotation when rate limits are hit.', 'kotacom-ai'); ?></p>
        <ul style="margin-left: 20px;">
            <li><strong><?php _e('Automatic Rotation:', 'kotacom-ai'); ?></strong> <?php _e('Keys are automatically rotated when rate limit errors are detected', 'kotacom-ai'); ?></li>
            <li><strong><?php _e('Cooldown Protection:', 'kotacom-ai'); ?></strong> <?php _e('Rate-limited keys are put on cooldown to prevent immediate reuse', 'kotacom-ai'); ?></li>
            <li><strong><?php _e('Backup & Redundancy:', 'kotacom-ai'); ?></strong> <?php _e('Multiple keys ensure continuous operation even if some hit limits', 'kotacom-ai'); ?></li>
        </ul>
    </div>
    
    <!-- Statistics -->
    <div class="postbox">
        <h2 class="hndle"><?php _e('Rotation Statistics', 'kotacom-ai'); ?></h2>
        <div class="inside">
            <div id="rotation-stats-loading" class="spinner is-active"></div>
            <div id="rotation-stats-content" style="display: none;">
                <div class="stats-grid">
                    <div class="stat-item">
                        <h4><?php _e('Total Rotations', 'kotacom-ai'); ?></h4>
                        <span id="stat-total" class="stat-number">-</span>
                    </div>
                    <div class="stat-item">
                        <h4><?php _e('Last 24 Hours', 'kotacom-ai'); ?></h4>
                        <span id="stat-24h" class="stat-number">-</span>
                    </div>
                    <div class="stat-item">
                        <h4><?php _e('Last 7 Days', 'kotacom-ai'); ?></h4>
                        <span id="stat-7d" class="stat-number">-</span>
                    </div>
                </div>
                <div id="stats-by-provider"></div>
            </div>
        </div>
    </div>
    
    <!-- Provider Management -->
    <div class="postbox">
        <h2 class="hndle"><?php _e('API Key Management', 'kotacom-ai'); ?></h2>
        <div class="inside">
            
            <!-- Provider Selector -->
            <div class="provider-selector">
                <label for="selected-provider"><?php _e('Select Provider:', 'kotacom-ai'); ?></label>
                <select id="selected-provider">
                    <option value=""><?php _e('-- Select Provider --', 'kotacom-ai'); ?></option>
                    <?php foreach ($providers as $key => $provider): ?>
                        <option value="<?php echo esc_attr($key); ?>"><?php echo esc_html($provider['name']); ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="button" id="load-provider-keys" class="button"><?php _e('Load Keys', 'kotacom-ai'); ?></button>
            </div>
            
            <!-- Provider Keys Display -->
            <div id="provider-keys-container" style="display: none;">
                <h3 id="provider-name-display"></h3>
                
                <!-- Add New Key -->
                <div class="add-key-form">
                    <h4><?php _e('Add New API Key', 'kotacom-ai'); ?></h4>
                    <div class="form-row">
                        <input type="password" id="new-api-key" placeholder="<?php _e('Enter API key...', 'kotacom-ai'); ?>" class="regular-text">
                        <button type="button" id="add-api-key" class="button button-primary"><?php _e('Add Key', 'kotacom-ai'); ?></button>
                    </div>
                </div>
                
                <!-- Current Keys -->
                <div class="current-keys">
                    <h4><?php _e('Current API Keys', 'kotacom-ai'); ?></h4>
                    <div id="keys-list"></div>
                </div>
                
                <!-- Test Keys -->
                <div class="test-keys">
                    <button type="button" id="test-all-keys" class="button button-secondary"><?php _e('Test All Keys', 'kotacom-ai'); ?></button>
                    <span class="spinner"></span>
                    <div id="test-results"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.info-card {
    background: #f9f9f9;
    border: 1px solid #ddd;
    padding: 15px;
    margin-bottom: 20px;
    border-radius: 4px;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 20px;
}

.stat-item {
    text-align: center;
    padding: 15px;
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.stat-item h4 {
    margin: 0 0 10px 0;
    color: #666;
    font-size: 14px;
}

.stat-number {
    font-size: 24px;
    font-weight: bold;
    color: #2271b1;
}

.provider-selector {
    margin-bottom: 20px;
    padding: 15px;
    background: #f9f9f9;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.provider-selector label {
    margin-right: 10px;
    font-weight: bold;
}

.provider-selector select {
    margin-right: 10px;
    min-width: 200px;
}

.add-key-form {
    margin: 20px 0;
    padding: 15px;
    background: #f0f8ff;
    border: 1px solid #2271b1;
    border-radius: 4px;
}

.add-key-form h4 {
    margin-top: 0;
}

.form-row {
    display: flex;
    gap: 10px;
    align-items: center;
}

.current-keys {
    margin: 20px 0;
}

.key-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 10px;
    margin: 5px 0;
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.key-item.active {
    border-color: #2271b1;
    background: #f0f8ff;
}

.key-item.cooldown {
    border-color: #d63638;
    background: #fef7f7;
}

.key-info {
    flex: 1;
}

.key-masked {
    font-family: monospace;
    font-weight: bold;
}

.key-status {
    margin-left: 10px;
}

.status-active {
    color: #2271b1;
    font-weight: bold;
}

.status-cooldown {
    color: #d63638;
    font-weight: bold;
}

.key-actions {
    display: flex;
    gap: 5px;
}

.test-keys {
    margin: 20px 0;
    padding: 15px;
    background: #f9f9f9;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.test-result {
    margin: 10px 0;
    padding: 10px;
    border-radius: 4px;
}

.test-result.success {
    background: #d1ecf1;
    border: 1px solid #bee5eb;
    color: #0c5460;
}

.test-result.error {
    background: #f8d7da;
    border: 1px solid #f5c6cb;
    color: #721c24;
}

.provider-stats {
    margin: 10px 0;
    padding: 10px;
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.provider-stats strong {
    color: #2271b1;
}
</style>

<script>
jQuery(document).ready(function($) {
    let currentProvider = '';
    
    // Load rotation statistics on page load
    loadRotationStats();
    
    // Load provider keys
    $('#load-provider-keys').on('click', function() {
        const provider = $('#selected-provider').val();
        if (!provider) {
            alert('<?php _e('Please select a provider', 'kotacom-ai'); ?>');
            return;
        }
        
        currentProvider = provider;
        loadProviderKeys(provider);
    });
    
    // Add new API key
    $('#add-api-key').on('click', function() {
        const apiKey = $('#new-api-key').val().trim();
        if (!apiKey) {
            alert('<?php _e('Please enter an API key', 'kotacom-ai'); ?>');
            return;
        }
        
        addApiKey(currentProvider, apiKey);
    });
    
    // Test all keys
    $('#test-all-keys').on('click', function() {
        if (!currentProvider) return;
        testAllKeys(currentProvider);
    });
    
    // Remove key event delegation
    $(document).on('click', '.remove-key', function() {
        const index = $(this).data('index');
        if (confirm('<?php _e('Are you sure you want to remove this API key?', 'kotacom-ai'); ?>')) {
            removeApiKey(currentProvider, index);
        }
    });
    
    function loadRotationStats() {
        $('#rotation-stats-loading').show();
        $('#rotation-stats-content').hide();
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'kotacom_get_rotation_stats',
                nonce: '<?php echo wp_create_nonce('kotacom_ai_nonce'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    const stats = response.data.stats;
                    $('#stat-total').text(stats.total_rotations);
                    $('#stat-24h').text(stats.last_24h);
                    $('#stat-7d').text(stats.last_7d);
                    
                    // Display by provider
                    let providerHtml = '';
                    for (const [provider, count] of Object.entries(stats.by_provider)) {
                        providerHtml += `<div class="provider-stats"><strong>${provider}:</strong> ${count} rotations</div>`;
                    }
                    $('#stats-by-provider').html(providerHtml);
                    
                    $('#rotation-stats-loading').hide();
                    $('#rotation-stats-content').show();
                }
            }
        });
    }
    
    function loadProviderKeys(provider) {
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'kotacom_get_provider_keys',
                provider: provider,
                nonce: '<?php echo wp_create_nonce('kotacom_ai_nonce'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    const keys = response.data.keys;
                    displayProviderKeys(provider, keys);
                    $('#provider-keys-container').show();
                } else {
                    alert('Error: ' + response.data.message);
                }
            }
        });
    }
    
    function displayProviderKeys(provider, keys) {
        const providerName = $('#selected-provider option:selected').text();
        $('#provider-name-display').text(providerName + ' API Keys');
        
        let keysHtml = '';
        if (keys.length === 0) {
            keysHtml = '<p><?php _e('No API keys configured for this provider.', 'kotacom-ai'); ?></p>';
        } else {
            keys.forEach(function(key, index) {
                let statusClass = '';
                let statusText = '';
                
                if (key.active) {
                    statusClass = 'active';
                    statusText = '<span class="status-active"><?php _e('ACTIVE', 'kotacom-ai'); ?></span>';
                }
                
                if (key.in_cooldown) {
                    statusClass += ' cooldown';
                    statusText += ' <span class="status-cooldown"><?php _e('COOLDOWN', 'kotacom-ai'); ?></span>';
                }
                
                keysHtml += `
                    <div class="key-item ${statusClass}">
                        <div class="key-info">
                            <span class="key-masked">${key.masked}</span>
                            <span class="key-status">${statusText}</span>
                        </div>
                        <div class="key-actions">
                            <button type="button" class="button button-small remove-key" data-index="${index}">
                                <?php _e('Remove', 'kotacom-ai'); ?>
                            </button>
                        </div>
                    </div>
                `;
            });
        }
        
        $('#keys-list').html(keysHtml);
    }
    
    function addApiKey(provider, apiKey) {
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'kotacom_add_api_key',
                provider: provider,
                api_key: apiKey,
                nonce: '<?php echo wp_create_nonce('kotacom_ai_nonce'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    $('#new-api-key').val('');
                    loadProviderKeys(provider); // Refresh the list
                    alert('<?php _e('API key added successfully!', 'kotacom-ai'); ?>');
                } else {
                    alert('Error: ' + response.data.message);
                }
            }
        });
    }
    
    function removeApiKey(provider, index) {
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'kotacom_remove_api_key',
                provider: provider,
                index: index,
                nonce: '<?php echo wp_create_nonce('kotacom_ai_nonce'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    loadProviderKeys(provider); // Refresh the list
                    alert('<?php _e('API key removed successfully!', 'kotacom-ai'); ?>');
                } else {
                    alert('Error: ' + response.data.message);
                }
            }
        });
    }
    
    function testAllKeys(provider) {
        $('#test-all-keys').prop('disabled', true);
        $('#test-all-keys').next('.spinner').addClass('is-active');
        $('#test-results').empty();
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'kotacom_test_all_keys',
                provider: provider,
                nonce: '<?php echo wp_create_nonce('kotacom_ai_nonce'); ?>'
            },
            success: function(response) {
                $('#test-all-keys').prop('disabled', false);
                $('#test-all-keys').next('.spinner').removeClass('is-active');
                
                if (response.success) {
                    const results = response.data.results;
                    let resultsHtml = '<h4><?php _e('Test Results:', 'kotacom-ai'); ?></h4>';
                    
                    Object.entries(results).forEach(function([index, result]) {
                        const resultClass = result.success ? 'success' : 'error';
                        const statusText = result.success ? '✓ <?php _e('Working', 'kotacom-ai'); ?>' : '✗ <?php _e('Failed', 'kotacom-ai'); ?>';
                        
                        resultsHtml += `
                            <div class="test-result ${resultClass}">
                                <strong>${result.key}:</strong> ${statusText}
                                ${result.message ? '<br><small>' + result.message + '</small>' : ''}
                                ${result.in_cooldown ? '<br><small><?php _e('(In cooldown)', 'kotacom-ai'); ?></small>' : ''}
                            </div>
                        `;
                    });
                    
                    $('#test-results').html(resultsHtml);
                } else {
                    alert('Error: ' + response.data.message);
                }
            }
        });
    }
});
</script>