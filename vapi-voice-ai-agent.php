<?php
/*
Plugin Name: Vapi Voice AI Agent
Description: Embeds Vapi Web Snippet into WordPress with customizable button settings and comprehensive admin interface.
Version: 1.0.0
Author: Imran Hoshain
Author URI: http://github.com/imranhoshain
License: GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: vapi-voice-ai-agent
Domain Path: /languages
Requires at least: 5.0
Tested up to: 6.4
Requires PHP: 7.4
Network: false
*/

if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('VAPI_PLUGIN_VERSION', '1.0.0');
define('VAPI_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('VAPI_PLUGIN_URL', plugin_dir_url(__FILE__));
define('VAPI_TEXT_DOMAIN', 'vapi-voice-ai-agent');

add_action('init', 'vapi_load_textdomain');
function vapi_load_textdomain()
{
    load_plugin_textdomain(VAPI_TEXT_DOMAIN, false, dirname(plugin_basename(__FILE__)) . '/languages');
}

// Check if we need to run cleanup on plugin load
add_action('plugins_loaded', 'vapi_check_version_and_cleanup');

// Include admin files
require_once VAPI_PLUGIN_PATH . 'admin/dashboard.php';
require_once VAPI_PLUGIN_PATH . 'admin/configuration.php';
require_once VAPI_PLUGIN_PATH . 'admin/tools.php';
require_once VAPI_PLUGIN_PATH . 'admin/about.php';

// Plugin activation and deactivation hooks
register_activation_hook(__FILE__, 'vapi_plugin_activate');
register_deactivation_hook(__FILE__, 'vapi_plugin_deactivate');

register_activation_hook(__FILE__, 'vapi_plugin_activate');
register_uninstall_hook(__FILE__, 'vapi_plugin_uninstall');

function vapi_get_default_settings()
{
    return [
        'vapi_api_key' => '',
        'vapi_private_api_key' => '',
        'vapi_assistant_id' => '',
        'vapi_selected_assistant' => '',
        'vapi_training_notes' => '',
        'vapi_first_message' => '',
        'vapi_end_call_message' => '',
        'vapi_voicemail_message' => '',
        'vapi_system_prompt' => '',
        'vapi_button_position' => 'bottom-right',
        'vapi_button_fixed' => 1,
        'vapi_button_offset' => '40px',
        'vapi_button_width' => '50px',
        'vapi_button_height' => '50px',
        'vapi_idle_color' => 'rgb(93, 254, 202)',
        'vapi_idle_type' => 'pill',
        'vapi_idle_title' => 'Call now?',
        'vapi_idle_subtitle' => '',
        'vapi_idle_icon' => 'https://unpkg.com/lucide-static@0.544.0/icons/audio-waveform.svg',
        'vapi_loading_color' => 'rgb(93, 124, 202)',
        'vapi_loading_type' => 'pill',
        'vapi_loading_title' => 'Connecting...',
        'vapi_loading_subtitle' => 'Please wait',
        'vapi_loading_icon' => 'https://unpkg.com/lucide-static@0.544.0/icons/loader-2.svg',
        'vapi_active_color' => 'rgb(255, 0, 0)',
        'vapi_active_type' => 'pill',
        'vapi_active_title' => 'Call is in progress...',
        'vapi_active_subtitle' => 'End the call.',
        'vapi_active_icon' => 'https://unpkg.com/lucide-static@0.544.0/icons/phone-off.svg',
    ];
}

function vapi_plugin_activate()
{
    $default_options = vapi_get_default_settings();

    // Migrate settings from previous plugins
    vapi_migrate_previous_settings();

    // Clean up any conflicting options from previous plugins
    vapi_cleanup_conflicting_options();

    $existing_options = get_option('vapi_settings', []);
    $options = array_merge($default_options, $existing_options);
    update_option('vapi_settings', $options);
}

function vapi_plugin_deactivate()
{
    // Reserved for future cleanup tasks; ensures the deactivation hook resolves to a callable.
}

function vapi_plugin_uninstall()
{
    global $wpdb;

    delete_option('vapi_settings');
    delete_option('vapi_plugin_version');

    $legacy_options = [
        'vapi_api_key', 'vapi_assistant_id', 'vapi_private_api_key',
        'voice_ai_agent_general_settings', 'voice_ai_agent_vapi_settings',
        'voice_ai_agent_elevenlabs_settings', 'Vapi_api_key', 'Vapi_assistant_id',
        'Vapi_button_position', 'Vapi_button_fixed', 'Vapi_private_api_key',
        'Vapi_public_api_key', 'Vapi_use_widget', 'Vapi_enable_analytics',
        'Vapi_enable_transcripts'
    ];

    foreach ($legacy_options as $legacy_option) {
        delete_option($legacy_option);
    }

    $table_names = [
        $wpdb->prefix . 'vapi_analytics',
        $wpdb->prefix . 'Vapi_analytics',
        $wpdb->prefix . 'voice_ai_agent_analytics'
    ];

    foreach ($table_names as $table_name) {
        $wpdb->query("DROP TABLE IF EXISTS `$table_name`");
    }
}

// Migrate settings from previous plugin versions
function vapi_migrate_previous_settings()
{
    $current_settings = get_option('vapi_settings', []);

    // Migration mapping from old to new setting names
    $migration_mappings = [
        // From voice_ai_agent_vapi_settings
        'voice_ai_agent_vapi_settings' => [
            'vapi_api_key' => 'vapi_api_key',
            'vapi_assistant_id' => 'vapi_assistant_id',
            'vapi_button_position' => 'vapi_button_position',
            'vapi_button_fixed' => 'vapi_button_fixed',
            'vapi_button_offset' => 'vapi_button_offset',
            'vapi_button_width' => 'vapi_button_width',
            'vapi_button_height' => 'vapi_button_height',
        ],
        // From standalone Vapi options
        'standalone' => [
            'Vapi_api_key' => 'vapi_api_key',
            'Vapi_assistant_id' => 'vapi_assistant_id',
            'Vapi_private_api_key' => 'vapi_private_api_key',
            'Vapi_button_position' => 'vapi_button_position',
            'Vapi_button_fixed' => 'vapi_button_fixed',
        ]
    ];

    // Migrate from grouped settings
    foreach ($migration_mappings as $source_option => $mappings) {
        if ($source_option === 'standalone') {
            // Handle standalone options
            foreach ($mappings as $old_key => $new_key) {
                $old_value = get_option($old_key, null);
                if ($old_value !== null && !isset($current_settings[$new_key])) {
                    $current_settings[$new_key] = $old_value;
                    error_log("VAPI: Migrated $old_key to $new_key");
                }
            }
        } else {
            // Handle grouped options
            $old_settings = get_option($source_option, []);
            if (!empty($old_settings) && is_array($old_settings)) {
                foreach ($mappings as $old_key => $new_key) {
                    if (isset($old_settings[$old_key]) && !isset($current_settings[$new_key])) {
                        $current_settings[$new_key] = $old_settings[$old_key];
                        error_log("VAPI: Migrated $source_option.$old_key to $new_key");
                    }
                }
            }
        }
    }

    // Save migrated settings
    if (!empty($current_settings)) {
        update_option('vapi_settings', $current_settings);
        error_log('VAPI: Migration completed with ' . count($current_settings) . ' settings');
    }
}

// Clean up conflicting options from previous plugin versions
function vapi_cleanup_conflicting_options()
{
    global $wpdb;

    // List of specific conflicting option names from previous plugins
    $conflicting_options_to_remove = [
        'voice_ai_agent_general_settings',
        'voice_ai_agent_vapi_settings',
        'voice_ai_agent_elevenlabs_settings',
        'Vapi_api_key',
        'Vapi_assistant_id',
        'Vapi_button_position',
        'Vapi_button_fixed',
        'Vapi_private_api_key',
        'Vapi_public_api_key',
        'Vapi_use_widget',
        'Vapi_enable_analytics',
        'Vapi_enable_transcripts'
    ];

    // Remove specific conflicting options
    foreach ($conflicting_options_to_remove as $option_name) {
        if (get_option($option_name) !== false) {
            delete_option($option_name);
            error_log('VAPI: Removed conflicting option: ' . $option_name);
        }
    }

    // Also check for pattern-based conflicts but be more careful
    $pattern_options = $wpdb->get_results(
        "SELECT option_name FROM {$wpdb->options} WHERE
        (option_name LIKE 'voice_ai_agent_%' OR
         option_name LIKE 'Vapi_%' OR
         option_name LIKE 'VAPI_%')
        AND option_name != 'vapi_settings'
        AND option_name != 'vapi_plugin_version'"
    );

    foreach ($pattern_options as $option) {
        delete_option($option->option_name);
        error_log('VAPI: Removed pattern-matched conflicting option: ' . $option->option_name);
    }

    // Clean up any conflicting database tables
    $table_names = [
        $wpdb->prefix . 'vapi_analytics',
        $wpdb->prefix . 'Vapi_analytics',
        $wpdb->prefix . 'voice_ai_agent_analytics'
    ];

    foreach ($table_names as $table_name) {
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'");
        if ($table_exists) {
            $wpdb->query("DROP TABLE IF EXISTS `$table_name`");
            error_log('VAPI: Removed conflicting table: ' . $table_name);
        }
    }
}

// Check version and run cleanup if needed
function vapi_check_version_and_cleanup()
{
    $installed_version = get_option('vapi_plugin_version', '0.0');

    if (version_compare($installed_version, VAPI_PLUGIN_VERSION, '<')) {
        // Run migration first
        vapi_migrate_previous_settings();

        // Then cleanup conflicts
        vapi_cleanup_conflicting_options();

        // Update version
        update_option('vapi_plugin_version', VAPI_PLUGIN_VERSION);

        error_log('VAPI: Plugin updated to version ' . VAPI_PLUGIN_VERSION . ', migration and cleanup completed');
    }
}

// Register admin page
add_action('admin_menu', 'vapi_add_admin_menu');
add_action('admin_enqueue_scripts', 'vapi_enqueue_admin_assets');
add_action('wp_ajax_vapi_fetch_assistants', 'vapi_fetch_assistants');
add_action('wp_ajax_vapi_update_assistant', 'vapi_update_assistant');

function vapi_enqueue_admin_assets($hook)
{
    // Only load on our plugin pages
    $allowed_hooks = [
        'toplevel_page_vapi_agent',
        'vapi-agent_page_vapi_agent',
        'vapi-agent_page_vapi_config',
        'vapi-agent_page_vapi_tools',
        'vapi-agent_page_vapi_about',
    ];

    $is_allowed = in_array($hook, $allowed_hooks, true) || strpos($hook, 'vapi') !== false;

    if (!$is_allowed) {
        return;
    }

    $style_version = VAPI_PLUGIN_VERSION;
    $style_file = VAPI_PLUGIN_PATH . 'admin/css/admin-style.css';
    if (file_exists($style_file)) {
        $style_version .= '-' . filemtime($style_file);
    }

    // Enqueue admin CSS
    wp_enqueue_style(
        'vapi-admin-style',
        VAPI_PLUGIN_URL . 'admin/css/admin-style.css',
        [],
        $style_version
    );

    $script_version = VAPI_PLUGIN_VERSION;
    $script_file = VAPI_PLUGIN_PATH . 'admin/js/admin-script.js';
    if (file_exists($script_file)) {
        $script_version .= '-' . filemtime($script_file);
    }

    // Enqueue admin JavaScript
    wp_enqueue_script(
        'vapi-admin-script',
        VAPI_PLUGIN_URL . 'admin/js/admin-script.js',
        ['jquery'],
        $script_version,
        true
    );

    // Localize script with AJAX data
    wp_localize_script('vapi-admin-script', 'vapiAjax', [
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'restUrl' => rest_url(),
        'nonce' => wp_create_nonce('vapi_admin_nonce'),
        'pluginUrl' => VAPI_PLUGIN_URL,
        'strings' => [
            'saving' => __('Saving...', VAPI_TEXT_DOMAIN),
            'saved' => __('Saved!', VAPI_TEXT_DOMAIN),
            'error' => __('Error occurred', VAPI_TEXT_DOMAIN),
            'confirm' => __('Are you sure?', VAPI_TEXT_DOMAIN),
            'assistantsLoading' => __('Loading assistants...', VAPI_TEXT_DOMAIN),
            'assistantsError' => __('Unable to load assistants. Check your private API key and try again.', VAPI_TEXT_DOMAIN),
            'assistantsEmpty' => __('No assistants found for this account.', VAPI_TEXT_DOMAIN),
            'assistantsSelect' => __('Select an assistant', VAPI_TEXT_DOMAIN),
            'assistantsPlaceholder' => __('No assistant selected', VAPI_TEXT_DOMAIN),
            'assistantsCached' => __('Previously saved assistant (cached)', VAPI_TEXT_DOMAIN),
            'assistantsCopySuccess' => __('Assistant ID copied to clipboard.', VAPI_TEXT_DOMAIN),
            'assistantsCopyFail' => __('Unable to copy assistant ID.', VAPI_TEXT_DOMAIN),
        ]
    ]);
}

function vapi_fetch_assistants()
{
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => __('You do not have permission to perform this action.', VAPI_TEXT_DOMAIN)], 403);
    }

    check_ajax_referer('vapi_admin_nonce', 'nonce');

    $settings = get_option('vapi_settings', []);
    $private_key = isset($settings['vapi_private_api_key']) ? trim($settings['vapi_private_api_key']) : '';

    if (empty($private_key)) {
        wp_send_json_error(['message' => __('Private API key is missing. Save it under API Configuration.', VAPI_TEXT_DOMAIN)], 400);
    }

    $request_args = [
        'headers' => [
            'Authorization' => 'Bearer ' . $private_key,
            'Accept' => 'application/json',
        ],
        'timeout' => 20,
    ];

    $response = wp_remote_get('https://api.vapi.ai/assistant?limit=100', $request_args);

    if (is_wp_error($response)) {
        wp_send_json_error([
            'message' => sprintf(__('Request failed: %s', VAPI_TEXT_DOMAIN), $response->get_error_message()),
        ], 500);
    }

    $code = wp_remote_retrieve_response_code($response);
    $body = wp_remote_retrieve_body($response);

    if ($code < 200 || $code >= 300) {
        wp_send_json_error([
            'message' => sprintf(__('API responded with status %d.', VAPI_TEXT_DOMAIN), $code),
        ], $code);
    }

    $decoded = json_decode($body, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        wp_send_json_error([
            'message' => __('Unexpected response format from Vapi API.', VAPI_TEXT_DOMAIN),
        ], 500);
    }

    if (isset($decoded['data']) && is_array($decoded['data'])) {
        $decoded = $decoded['data'];
    }

    if (!is_array($decoded)) {
        wp_send_json_error([
            'message' => __('Unexpected response format from Vapi API.', VAPI_TEXT_DOMAIN),
        ], 500);
    }

    wp_send_json_success($decoded);
}

function vapi_update_assistant()
{
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => __('You do not have permission to perform this action.', VAPI_TEXT_DOMAIN)], 403);
    }

    check_ajax_referer('vapi_admin_nonce', 'nonce');

    $assistant_id = isset($_POST['assistantId']) ? sanitize_text_field(wp_unslash($_POST['assistantId'])) : '';
    if (empty($assistant_id)) {
        wp_send_json_error(['message' => __('Assistant ID is required.', VAPI_TEXT_DOMAIN)], 400);
    }

    $settings = get_option('vapi_settings', []);
    $private_key = isset($settings['vapi_private_api_key']) ? trim($settings['vapi_private_api_key']) : '';

    if (empty($private_key)) {
        wp_send_json_error(['message' => __('Private API key is missing. Save it under API Configuration.', VAPI_TEXT_DOMAIN)], 400);
    }

    $payload = [
        'firstMessage' => isset($_POST['firstMessage']) ? sanitize_text_field(wp_unslash($_POST['firstMessage'])) : '',
        'endCallMessage' => isset($_POST['endCallMessage']) ? sanitize_text_field(wp_unslash($_POST['endCallMessage'])) : '',
        'voicemailMessage' => isset($_POST['voicemailMessage']) ? sanitize_text_field(wp_unslash($_POST['voicemailMessage'])) : '',
    ];

    $model_data = [];
    if (isset($_POST['model']) && is_array($_POST['model'])) {
        $model_data = wp_unslash($_POST['model']);
    }

    $system_prompt = '';
    if (isset($_POST['systemPrompt'])) {
        $system_prompt = wp_kses_post(wp_unslash($_POST['systemPrompt']));
    } elseif (isset($model_data['messages'][0]['content'])) {
        $system_prompt = wp_kses_post($model_data['messages'][0]['content']);
    }

    if ($system_prompt !== '') {
        $model_payload = [
            'messages' => [
                [
                    'role' => 'system',
                    'content' => $system_prompt,
                ],
            ],
        ];

        if (isset($model_data['provider'])) {
            $model_payload['provider'] = sanitize_text_field((string) $model_data['provider']);
        }

        if (isset($model_data['model'])) {
            $model_payload['model'] = sanitize_text_field((string) $model_data['model']);
        }

        $payload['model'] = $model_payload;
    }

    $payload = array_filter($payload, function ($value) {
        if (is_array($value)) {
            return !empty($value['messages'][0]['content']);
        }
        return $value !== '';
    });

    // Use PATCH so only provided fields update and other assistant properties remain untouched.
    $response = wp_remote_post('https://api.vapi.ai/assistant/' . rawurlencode($assistant_id), [
        'method' => 'PATCH',
        'headers' => [
            'Authorization' => 'Bearer ' . $private_key,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ],
        'body' => wp_json_encode($payload),
        'timeout' => 20,
    ]);

    if (is_wp_error($response)) {
        wp_send_json_error([
            'message' => sprintf(__('Request failed: %s', VAPI_TEXT_DOMAIN), $response->get_error_message()),
        ], 500);
    }

    $code = wp_remote_retrieve_response_code($response);
    $body = wp_remote_retrieve_body($response);

    $decoded = json_decode($body, true);
    if ($code < 200 || $code >= 300) {
        $message = isset($decoded['message']) ? $decoded['message'] : sprintf(__('API responded with status %d.', VAPI_TEXT_DOMAIN), $code);
        wp_send_json_error(['message' => $message], $code);
    }

    wp_send_json_success($decoded);
}

function vapi_add_admin_menu()
{
    // Top-level menu
    add_menu_page(
        __('Vapi Agent', VAPI_TEXT_DOMAIN),
        __('Vapi Agent', VAPI_TEXT_DOMAIN),
        'manage_options',
        'vapi_agent',
        'vapi_dashboard_page',
        'dashicons-microphone',
        58
    );

    // Submenus
    add_submenu_page('vapi_agent', __('Dashboard', VAPI_TEXT_DOMAIN), __('Dashboard', VAPI_TEXT_DOMAIN), 'manage_options', 'vapi_agent', 'vapi_dashboard_page');
    add_submenu_page('vapi_agent', __('Vapi Configuration', VAPI_TEXT_DOMAIN), __('Vapi Configuration', VAPI_TEXT_DOMAIN), 'manage_options', 'vapi_config', 'vapi_config_page');
    add_submenu_page('vapi_agent', __('Tools', VAPI_TEXT_DOMAIN), __('Tools', VAPI_TEXT_DOMAIN), 'manage_options', 'vapi_tools', 'vapi_tools_page');
    add_submenu_page('vapi_agent', __('About', VAPI_TEXT_DOMAIN), __('About', VAPI_TEXT_DOMAIN), 'manage_options', 'vapi_about', 'vapi_about_page');
}

// Settings registration removed - now using custom form handlers

// Legacy functions - no longer used since we handle forms manually
// Kept for backward compatibility but not called anywhere

function vapi_render_field($args)
{
    // This function is no longer used - forms are handled manually in admin pages
}

function vapi_settings_section_callback()
{
    // This function is no longer used - forms are handled manually in admin pages
}


// Register REST API endpoint
add_action('rest_api_init', function () {
    register_rest_route('vapi/v1', '/config', [
        'methods' => 'GET',
        'callback' => 'vapi_get_config',
        'permission_callback' => '__return_true',
    ]);
});

function vapi_get_config()
{
    $options = get_option('vapi_settings');
    return [
        'apiKey' => $options['vapi_api_key'] ?? '',
        'assistant' => $options['vapi_assistant_id'] ?? '',
        'buttonConfig' => [
            'position' => $options['vapi_button_position'] ?? 'bottom-right',
            'fixed' => isset($options['vapi_button_fixed']) ? (bool) $options['vapi_button_fixed'] : false,
            'offset' => $options['vapi_button_offset'] ?? '40px',
            'width' => $options['vapi_button_width'] ?? '50px',
            'height' => $options['vapi_button_height'] ?? '50px',
            'idle' => [
                'color' => $options['vapi_idle_color'] ?? 'rgb(93, 254, 202)',
                'type' => $options['vapi_idle_type'] ?? 'pill',
                'title' => $options['vapi_idle_title'] ?? 'Call Photon AI?',
                'subtitle' => $options['vapi_idle_subtitle'] ?? '',
                'icon' => $options['vapi_idle_icon'] ?? 'https://unpkg.com/browse/lucide-static@0.473.0/icons/audio-waveform.svg',
            ],
            'loading' => [
                'color' => $options['vapi_loading_color'] ?? 'rgb(93, 124, 202)',
                'type' => $options['vapi_loading_type'] ?? 'pill',
                'title' => $options['vapi_loading_title'] ?? 'Connecting...',
                'subtitle' => $options['vapi_loading_subtitle'] ?? 'Please wait',
                'icon' => $options['vapi_loading_icon'] ?? 'https://unpkg.com/lucide-static@0.321.0/icons/loader-2.svg',
            ],
            'active' => [
                'color' => $options['vapi_active_color'] ?? 'rgb(255, 0, 0)',
                'type' => $options['vapi_active_type'] ?? 'pill',
                'title' => $options['vapi_active_title'] ?? 'Call is in progress...',
                'subtitle' => $options['vapi_active_subtitle'] ?? 'End the call.',
                'icon' => $options['vapi_active_icon'] ?? 'https://unpkg.com/lucide-static@0.321.0/icons/phone-off.svg',
            ],
        ]
    ];
}

// Enqueue Vapi script
add_action('wp_enqueue_scripts', 'vapi_enqueue_script');

function vapi_enqueue_script()
{
    wp_enqueue_script('vapi-embed', 'https://cdn.jsdelivr.net/gh/VapiAI/html-script-tag@latest/dist/assets/index.js', [], null, true);
    wp_add_inline_script('vapi-embed', 'var vapiRestApiEndpoint = "' . esc_url_raw(rest_url('vapi/v1/config')) . '";');
    wp_add_inline_script('vapi-embed', vapi_get_inline_script());
}

function vapi_get_inline_script()
{
    return <<<EOT
(function (d, t) {
    var g = document.createElement(t),
        s = d.getElementsByTagName(t)[0];
    g.src = "https://cdn.jsdelivr.net/gh/VapiAI/html-script-tag@latest/dist/assets/index.js";
    g.defer = true;
    g.async = true;
    s.parentNode.insertBefore(g, s);

    g.onload = function () {
        fetch(vapiRestApiEndpoint)
            .then(response => response.json())
            .then(config => {
                window.vapiSDK.run({
                    apiKey: config.apiKey,
                    assistant: config.assistant,
                    config: config.buttonConfig
                });
                
                if (config.buttonConfig.fixed) {
                    document.querySelector('.vapi-btn').style.position = 'fixed';
                }
            })
            .catch(error => console.error('Error fetching Vapi config:', error));
    };
})(document, "script");
EOT;
}

// Handle tools actions: clear settings and export
add_action('admin_init', function () {
    // Handle export settings
    if (isset($_POST['vapi_export_settings'])) {
        if (!current_user_can('manage_options')) {
            return;
        }

        vapi_export_settings();
        exit;
    }

    // Handle clear settings
    if (isset($_POST['vapi_clear_settings'])) {
        if (!current_user_can('manage_options')) {
            return;
        }

        if (!wp_verify_nonce($_POST['vapi_clear_nonce'], 'vapi_clear_settings')) {
            wp_die(__('Security check failed. Please try again.', VAPI_TEXT_DOMAIN));
        }

        // Properly reset settings with cleanup and reinitialization
        vapi_reset_all_settings();
        wp_redirect(add_query_arg('vapi_cleared', '1', admin_url('admin.php?page=vapi_tools')));
        exit;
    }
});

// Properly reset all settings with cleanup and reinitialization
function vapi_reset_all_settings()
{
    error_log('VAPI: Starting complete settings reset');

    // Step 1: Run cleanup to remove any conflicting options
    vapi_cleanup_conflicting_options();

    // Step 2: Delete our main settings
    delete_option('vapi_settings');
    delete_option('vapi_plugin_version');

    // Step 3: Set up fresh default options (same as activation)
    $default_options = vapi_get_default_settings();

    // Step 4: Initialize fresh settings
    wp_cache_delete('vapi_settings', 'options');
    update_option('vapi_settings', $default_options);
    update_option('vapi_plugin_version', VAPI_PLUGIN_VERSION);

    error_log('VAPI: Settings reset completed successfully');
}

// Export settings function
function vapi_export_settings()
{
    $options = get_option('vapi_settings', []);
    $export_data = [
        'version' => '1.0.0',
        'export_date' => current_time('mysql'),
        'settings' => $options,
    ];

    $filename = 'vapi-settings-' . date('Y-m-d-H-i-s') . '.json';

    header('Content-Type: application/json');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . strlen(wp_json_encode($export_data)));

    echo wp_json_encode($export_data, JSON_PRETTY_PRINT);
    exit;
}

// Import settings function
function vapi_import_settings($file)
{
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => __('File upload error.', VAPI_TEXT_DOMAIN)];
    }

    if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        return ['success' => false, 'message' => __('File upload could not be verified.', VAPI_TEXT_DOMAIN)];
    }

    $content = file_get_contents($file['tmp_name']);
    if ($content === false) {
        return ['success' => false, 'message' => __('Unable to read the uploaded file.', VAPI_TEXT_DOMAIN)];
    }

    $content = ltrim($content, "\xEF\xBB\xBF\x00\x00\xFE\xFF\xFF\xFE\x00\x00");
    $content = trim($content);

    if ($content === '') {
        return ['success' => false, 'message' => __('The uploaded file is empty.', VAPI_TEXT_DOMAIN)];
    }

    if (substr($content, 0, 2) === "\x1f\x8b") {
        $decoded = @gzdecode($content);
        if ($decoded !== false) {
            $content = $decoded;
        }
    }

    $data = json_decode($content, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        $fallback = html_entity_decode($content, ENT_QUOTES | ENT_XML1, 'UTF-8');
        if ($fallback !== $content) {
            $data = json_decode($fallback, true);
        }
    }

    if (json_last_error() !== JSON_ERROR_NONE) {
        return [
            'success' => false,
            'message' => sprintf(__('Invalid JSON file: %s', VAPI_TEXT_DOMAIN), json_last_error_msg()),
        ];
    }

    if (isset($data['settings']) && is_array($data['settings'])) {
        $settings_payload = $data['settings'];
    } elseif (is_array($data) && array_filter(array_keys($data), static function ($key) {
        return strpos((string) $key, 'vapi_') === 0;
    })) {
        $settings_payload = $data;
    } else {
        return ['success' => false, 'message' => __('Invalid settings file format.', VAPI_TEXT_DOMAIN)];
    }

    $sanitized_settings = [];
    foreach ($settings_payload as $key => $value) {
        if (strpos($key, 'vapi_') === 0) {
            if (is_array($value) || is_object($value)) {
                $value = wp_json_encode($value);
            }
            if (in_array($key, ['vapi_system_prompt', 'vapi_training_notes'], true)) {
                $sanitized_settings[$key] = sanitize_textarea_field($value);
            } else {
                $sanitized_settings[$key] = sanitize_text_field($value);
            }
        }
    }

    update_option('vapi_settings', $sanitized_settings);

    return ['success' => true, 'message' => __('Settings imported successfully!', VAPI_TEXT_DOMAIN)];
}

// Sanitize callback for vapi_settings
function vapi_sanitize_settings($input)
{
    $out = [];
    if (!is_array($input)) {
        return $out;
    }

    foreach ($input as $key => $val) {
        // Simple sanitization rules; preserve keys used earlier
        if (strpos($key, 'vapi_') === 0) {
            // Special handling for color fields - convert hex to rgb
            if (strpos($key, '_color') !== false && is_string($val)) {
                if (strpos($val, '#') === 0) {
                    // Convert hex to RGB
                    $hex = ltrim($val, '#');
                    if (strlen($hex) === 6) {
                        $r = hexdec(substr($hex, 0, 2));
                        $g = hexdec(substr($hex, 2, 2));
                        $b = hexdec(substr($hex, 4, 2));
                        $out[$key] = "rgb($r, $g, $b)";
                    } else {
                        $out[$key] = sanitize_text_field($val);
                    }
                } else {
                    $out[$key] = sanitize_text_field($val);
                }
            } elseif (is_string($val)) {
                $out[$key] = sanitize_text_field($val);
            } elseif (is_numeric($val)) {
                $out[$key] = $val;
            } elseif (is_bool($val)) {
                $out[$key] = $val ? 1 : 0;
            } else {
                $out[$key] = sanitize_text_field((string) $val);
            }
        }
    }

    return $out;
}

// Combined sanitize and merge function for register_setting
function vapi_sanitize_and_merge_settings($input)
{
    // Get existing settings to preserve values not in current form
    $existing_settings = get_option('vapi_settings', []);

    // Debug: Log what we're receiving
    error_log('VAPI DEBUG - New value received: ' . print_r($input, true));
    error_log('VAPI DEBUG - Existing settings: ' . print_r($existing_settings, true));

    // Sanitize the new values
    $sanitized_new = vapi_sanitize_settings($input);

    // Debug: Log sanitized values
    error_log('VAPI DEBUG - Sanitized new: ' . print_r($sanitized_new, true));

    // Merge with existing settings to preserve other tab values
    $merged_settings = array_merge($existing_settings, $sanitized_new);

    // Debug: Log final merged settings
    error_log('VAPI DEBUG - Final merged: ' . print_r($merged_settings, true));

    return $merged_settings;
}
