<?php
if (!defined('ABSPATH')) {
    exit;
}

function vapi_get_sanitized_uploaded_file($key)
{
    $sanitized_key = sanitize_key((string) $key);

    if ('' === $sanitized_key) {
        return null;
    }

    $files = isset($_FILES) ? wp_unslash($_FILES) : [];

    if (!isset($files[$sanitized_key]) || !is_array($files[$sanitized_key])) {
        return null;
    }

    $raw_file = $files[$sanitized_key];

    $sanitized = [
        'name' => '',
        'type' => '',
        'tmp_name' => '',
        'error' => UPLOAD_ERR_NO_FILE,
        'size' => 0,
    ];

    if (isset($raw_file['name'])) {
        $sanitized['name'] = sanitize_file_name(wp_unslash($raw_file['name']));
    }

    if (isset($raw_file['type'])) {
        $sanitized['type'] = sanitize_mime_type(wp_unslash($raw_file['type']));
    }

    if (isset($raw_file['tmp_name'])) {
        $tmp_name = is_string($raw_file['tmp_name']) ? wp_unslash($raw_file['tmp_name']) : '';

        if ($tmp_name !== '') {
            $tmp_real = realpath($tmp_name);
            if ($tmp_real && strpos($tmp_real, realpath(sys_get_temp_dir())) === 0) {
                $sanitized['tmp_name'] = $tmp_real;
            } else {
                $sanitized['tmp_name'] = $tmp_name;
            }
        }
    }

    if (isset($raw_file['error'])) {
        $sanitized['error'] = (int) $raw_file['error'];
    }

    if (isset($raw_file['size'])) {
        $sanitized['size'] = (int) $raw_file['size'];
    }

    if (isset($raw_file['full_path'])) {
        $sanitized['full_path'] = sanitize_text_field(wp_unslash($raw_file['full_path']));
    }

    return $sanitized;
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
    $stored_assistant = '';
    if (!empty($options['vapi_assistant_id'])) {
        $stored_assistant = (string) $options['vapi_assistant_id'];
    } elseif (!empty($options['vapi_selected_assistant'])) {
        $stored_assistant = (string) $options['vapi_selected_assistant'];
    }

    $is_configured = !empty($options['vapi_api_key']) && '' !== trim($stored_assistant);

    if (isset($_GET['vapi_cleared']) && '1' === sanitize_text_field(wp_unslash($_GET['vapi_cleared']))) {
        echo '<div class="notice notice-success"><p>' . esc_html__('All settings have been reset successfully! The plugin has been restored to factory defaults and any database conflicts have been resolved.', 'vapi-voice-ai-agent') . '</p></div>';
    }

    if (isset($_POST['vapi_import_settings'])) {
        check_admin_referer('vapi_import_settings', 'vapi_import_nonce');
        $file = vapi_get_sanitized_uploaded_file('settings_file');

        if (null === $file) {
            echo '<div class="notice notice-error"><p>' . esc_html__('No file was uploaded. Please choose a valid JSON settings file.', 'vapi-voice-ai-agent') . '</p></div>';
        } else {
            $result = vapi_import_settings($file);
            echo '<div class="notice ' . ($result['success'] ? 'notice-success' : 'notice-error') . '"><p>' . esc_html($result['message']) . '</p></div>';
        }
    }
    ?>
    <div class="wrap vapi-admin-page">
        <div class="vapi-header">
            <div class="vapi-header-content">
                <span class="vapi-chip info"><?php esc_html_e('Operations centre', 'vapi-voice-ai-agent'); ?></span>
                <h1>
                    <div class="vapi-header-icon">
                        <span class="dashicons dashicons-admin-tools"></span>
                    </div>
                    <?php esc_html_e('Vapi Tools & Diagnostics', 'vapi-voice-ai-agent'); ?>
                </h1>
                <p><?php esc_html_e('Run health checks, migrate settings, and reset the plugin when you need a clean slate.', 'vapi-voice-ai-agent'); ?></p>

                <div class="vapi-hero-meta">
                    <div class="vapi-hero-meta-item">
                        <span><?php esc_html_e('Plugin version', 'vapi-voice-ai-agent'); ?></span>
                        <strong><?php echo esc_html($plugin_version); ?></strong>
                    </div>
                    <div class="vapi-hero-meta-item">
                        <span><?php esc_html_e('WordPress', 'vapi-voice-ai-agent'); ?></span>
                        <strong><?php echo esc_html($wp_version); ?>+</strong>
                    </div>
                    <div class="vapi-hero-meta-item">
                        <span><?php esc_html_e('PHP', 'vapi-voice-ai-agent'); ?></span>
                        <strong><?php echo esc_html($php_version); ?>+</strong>
                    </div>
                    <div class="vapi-hero-meta-item">
                        <span><?php esc_html_e('Configuration', 'vapi-voice-ai-agent'); ?></span>
                        <strong><?php echo $is_configured ? esc_html__('Ready', 'vapi-voice-ai-agent') : esc_html__('Needs setup', 'vapi-voice-ai-agent'); ?></strong>
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
                                <?php esc_html_e('System diagnostics', 'vapi-voice-ai-agent'); ?>
                            </h2>
                            <p class="vapi-card-subtitle"><?php esc_html_e('Review environment details and verify key endpoints.', 'vapi-voice-ai-agent'); ?></p>
                        </div>
                        <div class="vapi-card-body">
                            <div class="vapi-plugin-info">
                                <div class="vapi-info-row">
                                    <span class="vapi-info-label"><?php esc_html_e('Plugin version', 'vapi-voice-ai-agent'); ?></span>
                                    <span class="vapi-info-value"><?php echo esc_html($plugin_version); ?></span>
                                </div>
                                <div class="vapi-info-row">
                                    <span class="vapi-info-label"><?php esc_html_e('WordPress version', 'vapi-voice-ai-agent'); ?></span>
                                    <span class="vapi-info-value"><?php echo esc_html($wp_version); ?></span>
                                </div>
                                <div class="vapi-info-row">
                                    <span class="vapi-info-label"><?php esc_html_e('PHP version', 'vapi-voice-ai-agent'); ?></span>
                                    <span class="vapi-info-value"><?php echo esc_html($php_version); ?></span>
                                </div>
                                <div class="vapi-info-row">
                                    <span class="vapi-info-label"><?php esc_html_e('Assistant status', 'vapi-voice-ai-agent'); ?></span>
                                    <span class="vapi-status <?php echo $is_configured ? 'success' : 'warning'; ?>">
                                        <span class="dashicons <?php echo $is_configured ? 'dashicons-yes-alt' : 'dashicons-warning'; ?>"></span>
                                        <?php echo $is_configured ? esc_html__('Configured', 'vapi-voice-ai-agent') : esc_html__('Incomplete', 'vapi-voice-ai-agent'); ?>
                                    </span>
                                </div>
                            </div>

                            <div class="vapi-mt-3">
                                <div class="vapi-config-grid">
                                    <div class="vapi-config-item">
                                        <div class="vapi-config-label">
                                            <span class="dashicons dashicons-rest-api"></span>
                                            <?php esc_html_e('REST endpoint', 'vapi-voice-ai-agent'); ?>
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
                                <?php esc_html_e('Configuration backup & restore', 'vapi-voice-ai-agent'); ?>
                            </h2>
                            <p class="vapi-card-subtitle"><?php esc_html_e('Snapshot your current configuration or import settings from another environment.', 'vapi-voice-ai-agent'); ?></p>
                        </div>
                        <div class="vapi-card-body">
                            <form method="post" class="vapi-mb-4">
                                <h3><?php esc_html_e('Export settings', 'vapi-voice-ai-agent'); ?></h3>
                                <p class="vapi-form-description"><?php esc_html_e('Download your active plugin settings as a formatted JSON file.', 'vapi-voice-ai-agent'); ?></p>
                                <?php wp_nonce_field('vapi_export_settings', 'vapi_export_nonce'); ?>
                                <button type="submit" name="vapi_export_settings" value="1" class="vapi-button secondary">
                                    <span class="dashicons dashicons-download"></span>
                                    <?php esc_html_e('Export settings', 'vapi-voice-ai-agent'); ?>
                                </button>
                            </form>

                            <form method="post" enctype="multipart/form-data">
                                <h3><?php esc_html_e('Import settings', 'vapi-voice-ai-agent'); ?></h3>
                                <p class="vapi-form-description"><?php esc_html_e('Upload a JSON export to restore the assistant configuration.', 'vapi-voice-ai-agent'); ?></p>
                                <div class="vapi-form-table">
                                    <label for="settings_file" class="vapi-form-description" style="padding-left:0;"> <?php esc_html_e('Select settings file (.json)', 'vapi-voice-ai-agent'); ?> </label>
                                    <input type="file" name="settings_file" id="settings_file" accept=".json" required class="vapi-form-control vapi-mt-1">
                                </div>
                                <?php wp_nonce_field('vapi_import_settings', 'vapi_import_nonce'); ?>
                                <button type="submit" name="vapi_import_settings" value="1" class="vapi-button">
                                    <span class="dashicons dashicons-upload"></span>
                                    <?php esc_html_e('Import settings', 'vapi-voice-ai-agent'); ?>
                                </button>
                            </form>
                        </div>
                    </div>

                    <div class="vapi-card">
                        <div class="vapi-card-header">
                            <h2 class="vapi-card-title">
                                <span class="dashicons dashicons-visibility"></span>
                                <?php esc_html_e('Current configuration JSON', 'vapi-voice-ai-agent'); ?>
                            </h2>
                            <p class="vapi-card-subtitle"><?php esc_html_e('Inspect the exact data stored in the WordPress options table.', 'vapi-voice-ai-agent'); ?></p>
                        </div>
                        <div class="vapi-card-body">
                            <button type="button" class="vapi-button secondary" id="show-config-summary">
                                <span class="dashicons dashicons-media-text"></span>
                                <?php esc_html_e('Show Current Configuration', 'vapi-voice-ai-agent'); ?>
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
                                <?php esc_html_e('Quick diagnostics', 'vapi-voice-ai-agent'); ?>
                            </h3>
                            <p class="vapi-card-subtitle"><?php esc_html_e('Verify that scripts are reachable from your hosting environment.', 'vapi-voice-ai-agent'); ?></p>
                        </div>
                        <div class="vapi-card-body">
                            <div class="vapi-icon-list">
                                <div class="vapi-icon-list-item">
                                    <div class="vapi-icon">
                                        <span class="dashicons dashicons-admin-links"></span>
                                    </div>
                                    <div>
                                        <strong><?php esc_html_e('REST API endpoint', 'vapi-voice-ai-agent'); ?></strong>
                                        <small><?php esc_html_e('Checks if the configuration endpoint responds correctly.', 'vapi-voice-ai-agent'); ?></small>
                                        <div class="vapi-mt-1" id="api-test-result"></div>
                                    </div>
                                </div>
                                <div class="vapi-icon-list-item">
                                    <div class="vapi-icon">
                                        <span class="dashicons dashicons-cloud"></span>
                                    </div>
                                    <div>
                                        <strong><?php esc_html_e('Vapi script availability', 'vapi-voice-ai-agent'); ?></strong>
                                        <small><?php esc_html_e('Ensures the public CDN for the embed script is accessible.', 'vapi-voice-ai-agent'); ?></small>
                                        <div class="vapi-mt-1" id="script-test-result"></div>
                                    </div>
                                </div>
                            </div>
                            <div class="vapi-mt-3 vapi-action-buttons">
                                <button type="button" class="vapi-button secondary" id="test-api-endpoint">
                                    <span class="dashicons dashicons-admin-links"></span>
                                    <?php esc_html_e('Run REST test', 'vapi-voice-ai-agent'); ?>
                                </button>
                                <button type="button" class="vapi-button secondary" id="test-script-loading">
                                    <span class="dashicons dashicons-update"></span>
                                    <?php esc_html_e('Test Vapi script', 'vapi-voice-ai-agent'); ?>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="vapi-card">
                        <div class="vapi-card-header">
                            <h3 class="vapi-card-title" style="color: #d63638;">
                                <span class="dashicons dashicons-warning"></span>
                                <?php esc_html_e('Danger zone', 'vapi-voice-ai-agent'); ?>
                            </h3>
                            <p class="vapi-card-subtitle"><?php esc_html_e('Reset all plugin data, remove migrations, and restore defaults.', 'vapi-voice-ai-agent'); ?></p>
                        </div>
                        <div class="vapi-card-body">
                            <ul class="vapi-icon-list">
                                <li class="vapi-icon-list-item">
                                    <div class="vapi-icon">
                                        <span class="dashicons dashicons-no"></span>
                                    </div>
                                    <div>
                                        <strong><?php esc_html_e('Deletes stored credentials', 'vapi-voice-ai-agent'); ?></strong>
                                        <small><?php esc_html_e('API keys, assistant IDs, and appearance settings will be wiped.', 'vapi-voice-ai-agent'); ?></small>
                                    </div>
                                </li>
                                <li class="vapi-icon-list-item">
                                    <div class="vapi-icon">
                                        <span class="dashicons dashicons-editor-table"></span>
                                    </div>
                                    <div>
                                        <strong><?php esc_html_e('Removes legacy tables/options', 'vapi-voice-ai-agent'); ?></strong>
                                        <small><?php esc_html_e('Helpful when upgrading from older or conflicting plugins.', 'vapi-voice-ai-agent'); ?></small>
                                    </div>
                                </li>
                            </ul>

                            <form method="post" onsubmit="return confirm('<?php echo esc_js(__('Are you sure you want to reset all settings? This will restore factory defaults and cannot be undone!', 'vapi-voice-ai-agent')); ?>');">
                                <?php wp_nonce_field('vapi_clear_settings', 'vapi_clear_nonce'); ?>
                                <button type="submit" name="vapi_clear_settings" value="1" class="vapi-button danger vapi-mt-2">
                                    <span class="dashicons dashicons-trash"></span>
                                    <?php esc_html_e('Reset all settings', 'vapi-voice-ai-agent'); ?>
                                </button>
                            </form>
                        </div>
                    </div>

                    <div class="vapi-card">
                        <div class="vapi-card-header">
                            <h3 class="vapi-card-title">
                                <span class="dashicons dashicons-groups"></span>
                                <?php esc_html_e('Support resources', 'vapi-voice-ai-agent'); ?>
                            </h3>
                        </div>
                        <div class="vapi-card-body">
                            <div class="vapi-resource-links">
                                <a href="https://docs.vapi.ai" target="_blank" class="vapi-resource-link">
                                    <span class="dashicons dashicons-book"></span>
                                    <div>
                                        <strong><?php esc_html_e('Developer documentation', 'vapi-voice-ai-agent'); ?></strong>
                                        <small><?php esc_html_e('Comprehensive guides for APIs, webhooks, and SDKs.', 'vapi-voice-ai-agent'); ?></small>
                                    </div>
                                </a>
                                <a href="https://discord.gg/vapi" target="_blank" class="vapi-resource-link">
                                    <span class="dashicons dashicons-format-chat"></span>
                                    <div>
                                        <strong><?php esc_html_e('Community Discord', 'vapi-voice-ai-agent'); ?></strong>
                                        <small><?php esc_html_e('Chat with other builders, share prompts, request features.', 'vapi-voice-ai-agent'); ?></small>
                                    </div>
                                </a>
                                <a href="https://wordpress.org/plugins/vapi-voice-ai-agent/" target="_blank" class="vapi-resource-link">
                                    <span class="dashicons dashicons-star-half"></span>
                                    <div>
                                        <strong><?php esc_html_e('Leave a review', 'vapi-voice-ai-agent'); ?></strong>
                                        <small><?php esc_html_e('Your feedback helps the plugin grow and stay maintained.', 'vapi-voice-ai-agent'); ?></small>
                                    </div>
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="vapi-cta-card">
                        <h3><?php esc_html_e('Need a deeper audit?', 'vapi-voice-ai-agent'); ?></h3>
                        <p><?php esc_html_e('Reach the maintainer for white-glove migrations, troubleshooting, or bespoke integrations.', 'vapi-voice-ai-agent'); ?></p>
                        <a href="mailto:hello@vapi.ai" class="vapi-button">
                            <span class="dashicons dashicons-email"></span>
                            <?php esc_html_e('Contact support', 'vapi-voice-ai-agent'); ?>
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
            apiResult.innerHTML = '<span style="color: #0073aa;">' + <?php echo wp_json_encode(__('Testing...', 'vapi-voice-ai-agent')); ?> + '</span>';

            fetch(<?php echo wp_json_encode(esc_url_raw($rest_endpoint)); ?>)
                .then(response => response.json())
                .then(() => {
                    apiResult.innerHTML = '<span style="color: #46b450;">' + <?php echo wp_json_encode(__('✓ API endpoint working', 'vapi-voice-ai-agent')); ?> + '</span>';
                })
                .catch(() => {
                    apiResult.innerHTML = '<span style="color: #d63638;">' + <?php echo wp_json_encode(__('✗ API endpoint error', 'vapi-voice-ai-agent')); ?> + '</span>';
                });
        }

        if (apiButton) {
            apiButton.addEventListener('click', testApiEndpoint);
        }

        if (scriptButton && scriptResult) {
            scriptButton.addEventListener('click', function() {
                scriptResult.innerHTML = '<span style="color: #0073aa;">' + <?php echo wp_json_encode(__('Testing script availability...', 'vapi-voice-ai-agent')); ?> + '</span>';

                fetch('https://cdn.jsdelivr.net/gh/VapiAI/html-script-tag@latest/dist/assets/index.js')
                    .then(response => {
                        if (response.ok) {
                            scriptResult.innerHTML = '<span style="color: #46b450;">' + <?php echo wp_json_encode(__('✓ Vapi script is accessible', 'vapi-voice-ai-agent')); ?> + '</span>';
                        } else {
                            scriptResult.innerHTML = '<span style="color: #d63638;">' + <?php echo wp_json_encode(__('✗ Vapi script not accessible', 'vapi-voice-ai-agent')); ?> + '</span>';
                        }
                    })
                    .catch(() => {
                        scriptResult.innerHTML = '<span style="color: #d63638;">' + <?php echo wp_json_encode(__('✗ Error checking script availability', 'vapi-voice-ai-agent')); ?> + '</span>';
                    });
            });
        }

        if (configToggle && configSummary) {
            configToggle.addEventListener('click', function() {
                const isHidden = configSummary.style.display === 'none' || configSummary.style.display === '';
                configSummary.style.display = isHidden ? 'block' : 'none';
                this.innerHTML = isHidden
                    ? '<span class="dashicons dashicons-media-text"></span> ' + <?php echo wp_json_encode(__('Hide Configuration', 'vapi-voice-ai-agent')); ?>
                    : '<span class="dashicons dashicons-media-text"></span> ' + <?php echo wp_json_encode(__('Show Current Configuration', 'vapi-voice-ai-agent')); ?>;
            });
        }
    });
    </script>
    <?php
}
