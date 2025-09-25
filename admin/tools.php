<?php
if (!defined('ABSPATH')) {
    exit;
}

function vapi_tools_page()
{
    $options = get_option('vapi_settings', []);
    $plugin_version = defined('VAPI_PLUGIN_VERSION') ? VAPI_PLUGIN_VERSION : '1.0.0';
    $wp_version = get_bloginfo('version');
    $php_version = PHP_VERSION;
    $rest_endpoint = rest_url('vapi/v1/config');
    $config_json = wp_json_encode($options, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    if (!$config_json) {
        $config_json = '{}';
    }
    $is_configured = !empty($options['vapi_api_key']) && !empty($options['vapi_assistant_id']);

    if (isset($_GET['vapi_cleared']) && $_GET['vapi_cleared'] === '1') {
        echo '<div class="notice notice-success"><p>' . esc_html__('All settings have been reset successfully! The plugin has been restored to factory defaults and any database conflicts have been resolved.', VAPI_TEXT_DOMAIN) . '</p></div>';
    }

    if (isset($_POST['vapi_import_settings']) && isset($_FILES['settings_file'])) {
        $result = vapi_import_settings($_FILES['settings_file']);
        echo '<div class="notice ' . ($result['success'] ? 'notice-success' : 'notice-error') . '"><p>' . esc_html($result['message']) . '</p></div>';
    }
    ?>
    <div class="wrap vapi-admin-page">
        <div class="vapi-header">
            <div class="vapi-header-content">
                <span class="vapi-chip info"><?php esc_html_e('Operations centre', VAPI_TEXT_DOMAIN); ?></span>
                <h1>
                    <div class="vapi-header-icon">
                        <span class="dashicons dashicons-admin-tools"></span>
                    </div>
                    <?php esc_html_e('Vapi Tools & Diagnostics', VAPI_TEXT_DOMAIN); ?>
                </h1>
                <p><?php esc_html_e('Run health checks, migrate settings, and reset the plugin when you need a clean slate.', VAPI_TEXT_DOMAIN); ?></p>

                <div class="vapi-hero-meta">
                    <div class="vapi-hero-meta-item">
                        <span><?php esc_html_e('Plugin version', VAPI_TEXT_DOMAIN); ?></span>
                        <strong><?php echo esc_html($plugin_version); ?></strong>
                    </div>
                    <div class="vapi-hero-meta-item">
                        <span><?php esc_html_e('WordPress', VAPI_TEXT_DOMAIN); ?></span>
                        <strong><?php echo esc_html($wp_version); ?>+</strong>
                    </div>
                    <div class="vapi-hero-meta-item">
                        <span><?php esc_html_e('PHP', VAPI_TEXT_DOMAIN); ?></span>
                        <strong><?php echo esc_html($php_version); ?>+</strong>
                    </div>
                    <div class="vapi-hero-meta-item">
                        <span><?php esc_html_e('Configuration', VAPI_TEXT_DOMAIN); ?></span>
                        <strong><?php echo esc_html($is_configured ? __('Ready', VAPI_TEXT_DOMAIN) : __('Needs setup', VAPI_TEXT_DOMAIN)); ?></strong>
                    </div>
                </div>
            </div>
        </div>

        <div class="vapi-content">
            <div class="vapi-grid vapi-grid-2">
                <div class="vapi-main-content">
                    <div class="vapi-card">
                        <div class="vapi-card-header">
                            <h2 class="vapi-card-title">
                                <span class="dashicons dashicons-shield"></span>
                                <?php esc_html_e('System diagnostics', VAPI_TEXT_DOMAIN); ?>
                            </h2>
                            <p class="vapi-card-subtitle"><?php esc_html_e('Review environment details and verify key endpoints.', VAPI_TEXT_DOMAIN); ?></p>
                        </div>
                        <div class="vapi-card-body">
                            <div class="vapi-plugin-info">
                                <div class="vapi-info-row">
                                    <span class="vapi-info-label"><?php esc_html_e('Plugin version', VAPI_TEXT_DOMAIN); ?></span>
                                    <span class="vapi-info-value"><?php echo esc_html($plugin_version); ?></span>
                                </div>
                                <div class="vapi-info-row">
                                    <span class="vapi-info-label"><?php esc_html_e('WordPress version', VAPI_TEXT_DOMAIN); ?></span>
                                    <span class="vapi-info-value"><?php echo esc_html($wp_version); ?></span>
                                </div>
                                <div class="vapi-info-row">
                                    <span class="vapi-info-label"><?php esc_html_e('PHP version', VAPI_TEXT_DOMAIN); ?></span>
                                    <span class="vapi-info-value"><?php echo esc_html($php_version); ?></span>
                                </div>
                                <div class="vapi-info-row">
                                    <span class="vapi-info-label"><?php esc_html_e('Assistant status', VAPI_TEXT_DOMAIN); ?></span>
                                    <span class="vapi-status <?php echo $is_configured ? 'success' : 'warning'; ?>">
                                        <span class="dashicons <?php echo $is_configured ? 'dashicons-yes-alt' : 'dashicons-warning'; ?>"></span>
                                        <?php echo esc_html($is_configured ? __('Configured', VAPI_TEXT_DOMAIN) : __('Incomplete', VAPI_TEXT_DOMAIN)); ?>
                                    </span>
                                </div>
                            </div>

                            <div class="vapi-mt-3">
                                <div class="vapi-config-grid">
                                    <div class="vapi-config-item">
                                        <div class="vapi-config-label">
                                            <span class="dashicons dashicons-rest-api"></span>
                                            <?php esc_html_e('REST endpoint', VAPI_TEXT_DOMAIN); ?>
                                        </div>
                                    <div class="vapi-config-value" style="flex-wrap: wrap;">
                                        <code><?php echo esc_html($rest_endpoint); ?></code>
                                    </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="vapi-card">
                        <div class="vapi-card-header">
                            <h2 class="vapi-card-title">
                                <span class="dashicons dashicons-backup"></span>
                                <?php esc_html_e('Configuration backup & restore', VAPI_TEXT_DOMAIN); ?>
                            </h2>
                            <p class="vapi-card-subtitle"><?php esc_html_e('Snapshot your current configuration or import settings from another environment.', VAPI_TEXT_DOMAIN); ?></p>
                        </div>
                        <div class="vapi-card-body">
                            <form method="post" class="vapi-mb-4">
                                <h3><?php esc_html_e('Export settings', VAPI_TEXT_DOMAIN); ?></h3>
                                <p class="vapi-form-description"><?php esc_html_e('Download your active plugin settings as a formatted JSON file.', VAPI_TEXT_DOMAIN); ?></p>
                                <button type="submit" name="vapi_export_settings" value="1" class="vapi-button secondary">
                                    <span class="dashicons dashicons-download"></span>
                                    <?php esc_html_e('Export settings', VAPI_TEXT_DOMAIN); ?>
                                </button>
                            </form>

                            <form method="post" enctype="multipart/form-data">
                                <h3><?php esc_html_e('Import settings', VAPI_TEXT_DOMAIN); ?></h3>
                                <p class="vapi-form-description"><?php esc_html_e('Upload a JSON export to restore the assistant configuration.', VAPI_TEXT_DOMAIN); ?></p>
                                <div class="vapi-form-table">
                                    <label for="settings_file" class="vapi-form-description" style="padding-left:0;"> <?php esc_html_e('Select settings file (.json)', VAPI_TEXT_DOMAIN); ?> </label>
                                    <input type="file" name="settings_file" id="settings_file" accept=".json" required class="vapi-form-control vapi-mt-1">
                                </div>
                                <button type="submit" name="vapi_import_settings" value="1" class="vapi-button">
                                    <span class="dashicons dashicons-upload"></span>
                                    <?php esc_html_e('Import settings', VAPI_TEXT_DOMAIN); ?>
                                </button>
                            </form>
                        </div>
                    </div>

                    <div class="vapi-card">
                        <div class="vapi-card-header">
                            <h2 class="vapi-card-title">
                                <span class="dashicons dashicons-visibility"></span>
                                <?php esc_html_e('Current configuration JSON', VAPI_TEXT_DOMAIN); ?>
                            </h2>
                            <p class="vapi-card-subtitle"><?php esc_html_e('Inspect the exact data stored in the WordPress options table.', VAPI_TEXT_DOMAIN); ?></p>
                        </div>
                        <div class="vapi-card-body">
                            <button type="button" class="vapi-button secondary" id="show-config-summary">
                                <span class="dashicons dashicons-media-text"></span>
                                <?php esc_html_e('Show Current Configuration', VAPI_TEXT_DOMAIN); ?>
                            </button>
                            <div id="config-summary" class="vapi-code-block vapi-mt-2" style="display: none;">
                                <pre><?php echo esc_html($config_json); ?></pre>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="vapi-sidebar">
                    <div class="vapi-card">
                        <div class="vapi-card-header">
                            <h3 class="vapi-card-title">
                                <span class="dashicons dashicons-performance"></span>
                                <?php esc_html_e('Quick diagnostics', VAPI_TEXT_DOMAIN); ?>
                            </h3>
                            <p class="vapi-card-subtitle"><?php esc_html_e('Verify that scripts are reachable from your hosting environment.', VAPI_TEXT_DOMAIN); ?></p>
                        </div>
                        <div class="vapi-card-body">
                            <div class="vapi-icon-list">
                                <div class="vapi-icon-list-item">
                                    <div class="vapi-icon">
                                        <span class="dashicons dashicons-admin-links"></span>
                                    </div>
                                    <div>
                                        <strong><?php esc_html_e('REST API endpoint', VAPI_TEXT_DOMAIN); ?></strong>
                                        <small><?php esc_html_e('Checks if the configuration endpoint responds correctly.', VAPI_TEXT_DOMAIN); ?></small>
                                        <div class="vapi-mt-1" id="api-test-result"></div>
                                    </div>
                                </div>
                                <div class="vapi-icon-list-item">
                                    <div class="vapi-icon">
                                        <span class="dashicons dashicons-cloud"></span>
                                    </div>
                                    <div>
                                        <strong><?php esc_html_e('Vapi script availability', VAPI_TEXT_DOMAIN); ?></strong>
                                        <small><?php esc_html_e('Ensures the public CDN for the embed script is accessible.', VAPI_TEXT_DOMAIN); ?></small>
                                        <div class="vapi-mt-1" id="script-test-result"></div>
                                    </div>
                                </div>
                            </div>
                            <div class="vapi-mt-3 vapi-action-buttons">
                                <button type="button" class="vapi-button secondary" id="test-api-endpoint">
                                    <span class="dashicons dashicons-admin-links"></span>
                                    <?php esc_html_e('Run REST test', VAPI_TEXT_DOMAIN); ?>
                                </button>
                                <button type="button" class="vapi-button secondary" id="test-script-loading">
                                    <span class="dashicons dashicons-update"></span>
                                    <?php esc_html_e('Test Vapi script', VAPI_TEXT_DOMAIN); ?>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="vapi-card">
                        <div class="vapi-card-header">
                            <h3 class="vapi-card-title" style="color: #d63638;">
                                <span class="dashicons dashicons-warning"></span>
                                <?php esc_html_e('Danger zone', VAPI_TEXT_DOMAIN); ?>
                            </h3>
                            <p class="vapi-card-subtitle"><?php esc_html_e('Reset all plugin data, remove migrations, and restore defaults.', VAPI_TEXT_DOMAIN); ?></p>
                        </div>
                        <div class="vapi-card-body">
                            <ul class="vapi-icon-list">
                                <li class="vapi-icon-list-item">
                                    <div class="vapi-icon">
                                        <span class="dashicons dashicons-no"></span>
                                    </div>
                                    <div>
                                        <strong><?php esc_html_e('Deletes stored credentials', VAPI_TEXT_DOMAIN); ?></strong>
                                        <small><?php esc_html_e('API keys, assistant IDs, and appearance settings will be wiped.', VAPI_TEXT_DOMAIN); ?></small>
                                    </div>
                                </li>
                                <li class="vapi-icon-list-item">
                                    <div class="vapi-icon">
                                        <span class="dashicons dashicons-editor-table"></span>
                                    </div>
                                    <div>
                                        <strong><?php esc_html_e('Removes legacy tables/options', VAPI_TEXT_DOMAIN); ?></strong>
                                        <small><?php esc_html_e('Helpful when upgrading from older or conflicting plugins.', VAPI_TEXT_DOMAIN); ?></small>
                                    </div>
                                </li>
                            </ul>

                            <form method="post" onsubmit="return confirm('<?php echo esc_js(__('Are you sure you want to reset all settings? This will restore factory defaults and cannot be undone!', VAPI_TEXT_DOMAIN)); ?>');">
                                <?php wp_nonce_field('vapi_clear_settings', 'vapi_clear_nonce'); ?>
                                <button type="submit" name="vapi_clear_settings" value="1" class="vapi-button danger vapi-mt-2">
                                    <span class="dashicons dashicons-trash"></span>
                                    <?php esc_html_e('Reset all settings', VAPI_TEXT_DOMAIN); ?>
                                </button>
                            </form>
                        </div>
                    </div>

                    <div class="vapi-card">
                        <div class="vapi-card-header">
                            <h3 class="vapi-card-title">
                                <span class="dashicons dashicons-groups"></span>
                                <?php esc_html_e('Support resources', VAPI_TEXT_DOMAIN); ?>
                            </h3>
                        </div>
                        <div class="vapi-card-body">
                            <div class="vapi-resource-links">
                                <a href="https://docs.vapi.ai" target="_blank" class="vapi-resource-link">
                                    <span class="dashicons dashicons-book"></span>
                                    <div>
                                        <strong><?php esc_html_e('Developer documentation', VAPI_TEXT_DOMAIN); ?></strong>
                                        <small><?php esc_html_e('Comprehensive guides for APIs, webhooks, and SDKs.', VAPI_TEXT_DOMAIN); ?></small>
                                    </div>
                                </a>
                                <a href="https://discord.gg/vapi" target="_blank" class="vapi-resource-link">
                                    <span class="dashicons dashicons-format-chat"></span>
                                    <div>
                                        <strong><?php esc_html_e('Community Discord', VAPI_TEXT_DOMAIN); ?></strong>
                                        <small><?php esc_html_e('Chat with other builders, share prompts, request features.', VAPI_TEXT_DOMAIN); ?></small>
                                    </div>
                                </a>
                                <a href="https://wordpress.org/plugins/vapi-voice-ai-agent/" target="_blank" class="vapi-resource-link">
                                    <span class="dashicons dashicons-star-half"></span>
                                    <div>
                                        <strong><?php esc_html_e('Leave a review', VAPI_TEXT_DOMAIN); ?></strong>
                                        <small><?php esc_html_e('Your feedback helps the plugin grow and stay maintained.', VAPI_TEXT_DOMAIN); ?></small>
                                    </div>
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="vapi-cta-card">
                        <h3><?php esc_html_e('Need a deeper audit?', VAPI_TEXT_DOMAIN); ?></h3>
                        <p><?php esc_html_e('Reach the maintainer for white-glove migrations, troubleshooting, or bespoke integrations.', VAPI_TEXT_DOMAIN); ?></p>
                        <a href="mailto:hello@vapi.ai" class="vapi-button">
                            <span class="dashicons dashicons-email"></span>
                            <?php esc_html_e('Contact support', VAPI_TEXT_DOMAIN); ?>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const apiButton = document.getElementById('test-api-endpoint');
        const apiResult = document.getElementById('api-test-result');
        const scriptButton = document.getElementById('test-script-loading');
        const scriptResult = document.getElementById('script-test-result');
        const configToggle = document.getElementById('show-config-summary');
        const configSummary = document.getElementById('config-summary');

        function testApiEndpoint() {
            if (!apiResult) {
                return;
            }
            apiResult.innerHTML = '<span style="color: #0073aa;">' + <?php echo wp_json_encode(__('Testing...', VAPI_TEXT_DOMAIN)); ?> + '</span>';

            fetch(<?php echo wp_json_encode(esc_url_raw($rest_endpoint)); ?>)
                .then(response => response.json())
                .then(() => {
                    apiResult.innerHTML = '<span style="color: #46b450;">' + <?php echo wp_json_encode(__('✓ API endpoint working', VAPI_TEXT_DOMAIN)); ?> + '</span>';
                })
                .catch(() => {
                    apiResult.innerHTML = '<span style="color: #d63638;">' + <?php echo wp_json_encode(__('✗ API endpoint error', VAPI_TEXT_DOMAIN)); ?> + '</span>';
                });
        }

        if (apiButton) {
            apiButton.addEventListener('click', testApiEndpoint);
        }

        if (scriptButton && scriptResult) {
            scriptButton.addEventListener('click', function() {
                scriptResult.innerHTML = '<span style="color: #0073aa;">' + <?php echo wp_json_encode(__('Testing script availability...', VAPI_TEXT_DOMAIN)); ?> + '</span>';

                fetch('https://cdn.jsdelivr.net/gh/VapiAI/html-script-tag@latest/dist/assets/index.js')
                    .then(response => {
                        if (response.ok) {
                            scriptResult.innerHTML = '<span style="color: #46b450;">' + <?php echo wp_json_encode(__('✓ Vapi script is accessible', VAPI_TEXT_DOMAIN)); ?> + '</span>';
                        } else {
                            scriptResult.innerHTML = '<span style="color: #d63638;">' + <?php echo wp_json_encode(__('✗ Vapi script not accessible', VAPI_TEXT_DOMAIN)); ?> + '</span>';
                        }
                    })
                    .catch(() => {
                        scriptResult.innerHTML = '<span style="color: #d63638;">' + <?php echo wp_json_encode(__('✗ Error checking script availability', VAPI_TEXT_DOMAIN)); ?> + '</span>';
                    });
            });
        }

        if (configToggle && configSummary) {
            configToggle.addEventListener('click', function() {
                const isHidden = configSummary.style.display === 'none' || configSummary.style.display === '';
                configSummary.style.display = isHidden ? 'block' : 'none';
                this.innerHTML = isHidden
                    ? '<span class="dashicons dashicons-media-text"></span> ' + <?php echo wp_json_encode(__('Hide Configuration', VAPI_TEXT_DOMAIN)); ?>
                    : '<span class="dashicons dashicons-media-text"></span> ' + <?php echo wp_json_encode(__('Show Current Configuration', VAPI_TEXT_DOMAIN)); ?>;
            });
        }
    });
    </script>
    <?php
}
