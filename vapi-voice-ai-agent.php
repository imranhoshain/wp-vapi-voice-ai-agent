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
*/

if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('VAPI_PLUGIN_VERSION', '1.0.0');
define('VAPI_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('VAPI_PLUGIN_URL', plugin_dir_url(__FILE__));
define('VAPI_TEXT_DOMAIN', 'vapi-voice-ai-agent');

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

    vapi_drop_legacy_tables($table_names);
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
                }
            }
        } else {
            // Handle grouped options
            $old_settings = get_option($source_option, []);
            if (!empty($old_settings) && is_array($old_settings)) {
                foreach ($mappings as $old_key => $new_key) {
                    if (isset($old_settings[$old_key]) && !isset($current_settings[$new_key])) {
                        $current_settings[$new_key] = $old_settings[$old_key];
                    }
                }
            }
        }
    }

    // Save migrated settings
    if (!empty($current_settings)) {
        update_option('vapi_settings', $current_settings);
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
        }
    }

    // Also check for pattern-based conflicts but be more careful
    $like_patterns = [
        $wpdb->esc_like('voice_ai_agent_') . '%',
        $wpdb->esc_like('Vapi_') . '%',
        $wpdb->esc_like('VAPI_') . '%',
    ];

    $pattern_options = [];
    if (!empty($like_patterns)) {
        $cache_key = 'vapi_conflicting_options_' . md5(wp_json_encode($like_patterns));
        $pattern_options = wp_cache_get($cache_key, 'vapi_options_cleanup');

        if (false === $pattern_options) {
            $placeholders = implode(' OR ', array_fill(0, count($like_patterns), 'option_name LIKE %s'));
            $prepared = $wpdb->prepare(
                "SELECT option_name FROM {$wpdb->options} WHERE ($placeholders) AND option_name != %s AND option_name != %s",
                array_merge($like_patterns, ['vapi_settings', 'vapi_plugin_version'])
            );

            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- No core helper exists to fetch option names by wildcard, prepared statement ensures safety above.
            $pattern_options = (array) $wpdb->get_col($prepared);
            wp_cache_set($cache_key, $pattern_options, 'vapi_options_cleanup', MINUTE_IN_SECONDS);
        } else {
            $pattern_options = (array) $pattern_options;
        }
    }

    foreach ($pattern_options as $option_name) {
        if (!in_array($option_name, ['vapi_settings', 'vapi_plugin_version'], true)) {
            delete_option($option_name);
        }
    }

    // Clean up any conflicting database tables
    $table_names = [
        $wpdb->prefix . 'vapi_analytics',
        $wpdb->prefix . 'Vapi_analytics',
        $wpdb->prefix . 'voice_ai_agent_analytics'
    ];

    vapi_drop_legacy_tables($table_names);
}

function vapi_drop_legacy_tables(array $table_names)
{
    global $wpdb;

    if (empty($table_names)) {
        return;
    }

    if (!defined('ABSPATH')) {
        return;
    }

    foreach ($table_names as $table_name) {
        if (empty($table_name)) {
            continue;
        }

        $normalized_name = preg_replace('/[^A-Za-z0-9_]/', '', $table_name);
        if ('' === $normalized_name) {
            continue;
        }

        if (strpos($normalized_name, $wpdb->prefix) !== 0) {
            continue;
        }

        $full_table_name = $normalized_name;

        $like_pattern = str_replace(['\\', '_', '%'], ['\\\\', '\\_', '\\%'], $full_table_name);

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Needed to inspect legacy tables for removal.
        $existing_table = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $like_pattern));

        if ($existing_table !== $full_table_name) {
            continue;
        }

        $escaped_table = esc_sql($full_table_name);
        $drop_sql = "DROP TABLE IF EXISTS `{$escaped_table}`";

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared -- Dropping legacy tables requires a direct query without caching; table name sanitized via preg_replace and esc_sql above.
        $wpdb->query($drop_sql);
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
    }
}

// Register admin page
add_action('admin_menu', 'vapi_add_admin_menu');
add_action('admin_enqueue_scripts', 'vapi_enqueue_admin_assets');
add_action('wp_ajax_vapi_fetch_assistants', 'vapi_fetch_assistants');
add_action('wp_ajax_vapi_update_assistant', 'vapi_update_assistant');
add_action('admin_head', 'vapi_filter_third_party_notices', 99);

function vapi_filter_third_party_notices()
{
    if (!function_exists('get_current_screen')) {
        return;
    }

    $screen = get_current_screen();
    if (!$screen) {
        return;
    }

    $vapi_screens = [
        'toplevel_page_vapi_agent',
        'vapi-agent_page_vapi_agent',
        'vapi-agent_page_vapi_config',
        'vapi-agent_page_vapi_tools',
        'vapi-agent_page_vapi_about',
    ];

    if (!in_array($screen->id, $vapi_screens, true)) {
        return;
    }

    foreach (['admin_notices', 'all_admin_notices', 'network_admin_notices', 'user_admin_notices'] as $hook) {
        remove_all_actions($hook);
    }
}

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
            'loading' => __('Loading...', 'vapi-voice-ai-agent'),
            'saving' => __('Saving...', 'vapi-voice-ai-agent'),
            'saved' => __('Saved!', 'vapi-voice-ai-agent'),
            'error' => __('Error occurred', 'vapi-voice-ai-agent'),
            'confirm' => __('Are you sure?', 'vapi-voice-ai-agent'),
            'colorUpdated' => __('Colour updated', 'vapi-voice-ai-agent'),
            'testingConnection' => __('Testing connection...', 'vapi-voice-ai-agent'),
            'enterCredentials' => __('Please enter both API Key and Assistant ID before testing.', 'vapi-voice-ai-agent'),
            'configOk' => __('✓ Configuration looks good! The voice button should work on your website.', 'vapi-voice-ai-agent'),
            'configIncomplete' => __('✗ Configuration incomplete. Please save your settings first.', 'vapi-voice-ai-agent'),
            'configError' => __('✗ Error testing connection. Please check your settings.', 'vapi-voice-ai-agent'),
            'testing' => __('Testing...', 'vapi-voice-ai-agent'),
            'apiWorking' => __('✓ API endpoint working', 'vapi-voice-ai-agent'),
            'apiError' => __('✗ API endpoint error', 'vapi-voice-ai-agent'),
            'testingScript' => __('Testing script availability...', 'vapi-voice-ai-agent'),
            'scriptAccessible' => __('✓ Vapi script is accessible', 'vapi-voice-ai-agent'),
            'scriptUnavailable' => __('✗ Vapi script not accessible', 'vapi-voice-ai-agent'),
            'scriptError' => __('✗ Error checking script availability', 'vapi-voice-ai-agent'),
            'showConfig' => __('Show Current Configuration', 'vapi-voice-ai-agent'),
            'hideConfig' => __('Hide Configuration', 'vapi-voice-ai-agent'),
            'fieldRequired' => __('This field is required', 'vapi-voice-ai-agent'),
            'invalidEmail' => __('Please enter a valid email address', 'vapi-voice-ai-agent'),
            'invalidUrl' => __('Please enter a valid URL', 'vapi-voice-ai-agent'),
            'wpScriptsMissing' => __('WordPress admin scripts not loaded properly', 'vapi-voice-ai-agent'),
            'browserUnsupported' => __('Your browser may not support all features', 'vapi-voice-ai-agent'),
            'assistantsLoading' => __('Loading assistants...', 'vapi-voice-ai-agent'),
            'assistantsError' => __('Unable to load assistants. Check your private API key and try again.', 'vapi-voice-ai-agent'),
            'assistantsEmpty' => __('No assistants found for this account.', 'vapi-voice-ai-agent'),
            'assistantsSelect' => __('Select an assistant', 'vapi-voice-ai-agent'),
            'assistantsPlaceholder' => __('No assistant selected', 'vapi-voice-ai-agent'),
            'assistantsCached' => __('Previously saved assistant (cached)', 'vapi-voice-ai-agent'),
            'assistantsCopySuccess' => __('Assistant ID copied to clipboard.', 'vapi-voice-ai-agent'),
            'assistantsCopyFail' => __('Unable to copy assistant ID.', 'vapi-voice-ai-agent'),
        ]
    ]);
}

function vapi_fetch_assistants()
{
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => __('You do not have permission to perform this action.', 'vapi-voice-ai-agent')], 403);
    }

    check_ajax_referer('vapi_admin_nonce', 'nonce');

    $settings = get_option('vapi_settings', []);
    $private_key = isset($settings['vapi_private_api_key']) ? trim($settings['vapi_private_api_key']) : '';

    if (empty($private_key)) {
        wp_send_json_error(['message' => __('Private API key is missing. Save it under API Configuration.', 'vapi-voice-ai-agent')], 400);
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
            'message' => sprintf(
                /* translators: %s: Error message returned by the Vapi API request. */
                __('Request failed: %s', 'vapi-voice-ai-agent'),
                $response->get_error_message()
            ),
        ], 500);
    }

    $code = wp_remote_retrieve_response_code($response);
    $body = wp_remote_retrieve_body($response);

    if ($code < 200 || $code >= 300) {
        wp_send_json_error([
            'message' => sprintf(
                /* translators: %d: HTTP status code returned by the Vapi API. */
                __('API responded with status %d.', 'vapi-voice-ai-agent'),
                $code
            ),
        ], $code);
    }

    $decoded = json_decode($body, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        wp_send_json_error([
            'message' => __('Unexpected response format from Vapi API.', 'vapi-voice-ai-agent'),
        ], 500);
    }

    if (isset($decoded['data']) && is_array($decoded['data'])) {
        $decoded = $decoded['data'];
    }

    if (!is_array($decoded)) {
        wp_send_json_error([
            'message' => __('Unexpected response format from Vapi API.', 'vapi-voice-ai-agent'),
        ], 500);
    }

    wp_send_json_success($decoded);
}

function vapi_update_assistant()
{
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => __('You do not have permission to perform this action.', 'vapi-voice-ai-agent')], 403);
    }

    check_ajax_referer('vapi_admin_nonce', 'nonce');

    $assistant_raw = filter_input(INPUT_POST, 'assistantId', FILTER_UNSAFE_RAW);
    $assistant_id = is_string($assistant_raw) ? sanitize_text_field(wp_unslash($assistant_raw)) : '';
    if (empty($assistant_id)) {
        wp_send_json_error(['message' => __('Assistant ID is required.', 'vapi-voice-ai-agent')], 400);
    }

    $settings = get_option('vapi_settings', []);
    $private_key = isset($settings['vapi_private_api_key']) ? trim($settings['vapi_private_api_key']) : '';

    if (empty($private_key)) {
        wp_send_json_error(['message' => __('Private API key is missing. Save it under API Configuration.', 'vapi-voice-ai-agent')], 400);
    }

    $payload = [
        'firstMessage' => sanitize_text_field(wp_unslash(filter_input(INPUT_POST, 'firstMessage', FILTER_UNSAFE_RAW) ?? '')),
        'endCallMessage' => sanitize_text_field(wp_unslash(filter_input(INPUT_POST, 'endCallMessage', FILTER_UNSAFE_RAW) ?? '')),
        'voicemailMessage' => sanitize_text_field(wp_unslash(filter_input(INPUT_POST, 'voicemailMessage', FILTER_UNSAFE_RAW) ?? '')),
    ];

    $model_data = [];
    $model_input = filter_input(INPUT_POST, 'model', FILTER_UNSAFE_RAW, FILTER_REQUIRE_ARRAY);
    if (is_array($model_input)) {
        $model_raw = wp_unslash($model_input);
        if (isset($model_raw['messages']) && is_array($model_raw['messages'])) {
            foreach ($model_raw['messages'] as $index => $message) {
                if (!is_array($message)) {
                    continue;
                }

                $model_raw['messages'][$index]['role'] = isset($message['role']) ? sanitize_text_field($message['role']) : '';
                if (isset($message['content'])) {
                    $model_raw['messages'][$index]['content'] = wp_kses_post($message['content']);
                }
            }
        }

        if (isset($model_raw['provider'])) {
            $model_raw['provider'] = sanitize_text_field((string) $model_raw['provider']);
        }

        if (isset($model_raw['model'])) {
            $model_raw['model'] = sanitize_text_field((string) $model_raw['model']);
        }

        $model_data = $model_raw;
    }

    $system_prompt = '';
    $system_prompt_input = filter_input(INPUT_POST, 'systemPrompt', FILTER_UNSAFE_RAW);
    if (is_string($system_prompt_input)) {
        $system_prompt = wp_kses_post(wp_unslash($system_prompt_input));
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
            'message' => sprintf(
                /* translators: %s: Error message returned by the Vapi API request. */
                __('Request failed: %s', 'vapi-voice-ai-agent'),
                $response->get_error_message()
            ),
        ], 500);
    }

    $code = wp_remote_retrieve_response_code($response);
    $body = wp_remote_retrieve_body($response);

    $decoded = json_decode($body, true);
    if ($code < 200 || $code >= 300) {
        $message = isset($decoded['message'])
            ? $decoded['message']
            : sprintf(
                /* translators: %d: HTTP status code returned by the Vapi API. */
                __('API responded with status %d.', 'vapi-voice-ai-agent'),
                $code
            );
        wp_send_json_error(['message' => $message], $code);
    }

    wp_send_json_success($decoded);
}

function vapi_add_admin_menu()
{
    // Top-level menu
    add_menu_page(
        __('Vapi Agent', 'vapi-voice-ai-agent'),
        __('Vapi Agent', 'vapi-voice-ai-agent'),
        'manage_options',
        'vapi_agent',
        'vapi_dashboard_page',
        'dashicons-microphone',
        58
    );

    // Submenus
    add_submenu_page('vapi_agent', __('Dashboard', 'vapi-voice-ai-agent'), __('Dashboard', 'vapi-voice-ai-agent'), 'manage_options', 'vapi_agent', 'vapi_dashboard_page');
    add_submenu_page('vapi_agent', __('Vapi Configuration', 'vapi-voice-ai-agent'), __('Vapi Configuration', 'vapi-voice-ai-agent'), 'manage_options', 'vapi_config', 'vapi_config_page');
    add_submenu_page('vapi_agent', __('Tools', 'vapi-voice-ai-agent'), __('Tools', 'vapi-voice-ai-agent'), 'manage_options', 'vapi_tools', 'vapi_tools_page');
    add_submenu_page('vapi_agent', __('About', 'vapi-voice-ai-agent'), __('About', 'vapi-voice-ai-agent'), 'manage_options', 'vapi_about', 'vapi_about_page');
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
    $options = get_option('vapi_settings', []);
    if (!is_array($options)) {
        $options = (array) $options;
    }

    $api_key = isset($options['vapi_api_key']) ? trim((string) $options['vapi_api_key']) : '';
    $assistant_id = isset($options['vapi_assistant_id']) ? trim((string) $options['vapi_assistant_id']) : '';
    if ($assistant_id === '' && isset($options['vapi_selected_assistant'])) {
        $assistant_id = trim((string) $options['vapi_selected_assistant']);
    }

    $idle_title = $options['vapi_idle_title'] ?? null;
    if ($idle_title === null || $idle_title === '' || $idle_title === 'Call now?') {
        $idle_title = __('Call now?', 'vapi-voice-ai-agent');
    }

    $loading_title = $options['vapi_loading_title'] ?? null;
    if ($loading_title === null || $loading_title === '' || $loading_title === 'Connecting...') {
        $loading_title = __('Connecting...', 'vapi-voice-ai-agent');
    }

    $loading_subtitle = $options['vapi_loading_subtitle'] ?? null;
    if ($loading_subtitle === null || $loading_subtitle === '' || $loading_subtitle === 'Please wait') {
        $loading_subtitle = __('Please wait', 'vapi-voice-ai-agent');
    }

    $active_title = $options['vapi_active_title'] ?? null;
    if ($active_title === null || $active_title === '' || $active_title === 'Call is in progress...') {
        $active_title = __('Call is in progress...', 'vapi-voice-ai-agent');
    }

    $active_subtitle = $options['vapi_active_subtitle'] ?? null;
    if ($active_subtitle === null || $active_subtitle === '' || $active_subtitle === 'End the call.') {
        $active_subtitle = __('End the call.', 'vapi-voice-ai-agent');
    }

    $is_configured = ($api_key !== '' && $assistant_id !== '');

    nocache_headers();

    $response = [
        'configured' => $is_configured,
        'apiKey' => $api_key,
        'assistant' => $assistant_id,
        'buttonConfig' => [
            'position' => $options['vapi_button_position'] ?? 'bottom-right',
            'fixed' => isset($options['vapi_button_fixed']) ? (bool) $options['vapi_button_fixed'] : false,
            'offset' => $options['vapi_button_offset'] ?? '40px',
            'width' => $options['vapi_button_width'] ?? '50px',
            'height' => $options['vapi_button_height'] ?? '50px',
            'idle' => [
                'color' => $options['vapi_idle_color'] ?? 'rgb(93, 254, 202)',
                'type' => $options['vapi_idle_type'] ?? 'pill',
                'title' => $idle_title,
                'subtitle' => $options['vapi_idle_subtitle'] ?? '',
                'icon' => $options['vapi_idle_icon'] ?? 'https://unpkg.com/browse/lucide-static@0.473.0/icons/audio-waveform.svg',
            ],
            'loading' => [
                'color' => $options['vapi_loading_color'] ?? 'rgb(93, 124, 202)',
                'type' => $options['vapi_loading_type'] ?? 'pill',
                'title' => $loading_title,
                'subtitle' => $loading_subtitle,
                'icon' => $options['vapi_loading_icon'] ?? 'https://unpkg.com/lucide-static@0.321.0/icons/loader-2.svg',
            ],
            'active' => [
                'color' => $options['vapi_active_color'] ?? 'rgb(255, 0, 0)',
                'type' => $options['vapi_active_type'] ?? 'pill',
                'title' => $active_title,
                'subtitle' => $active_subtitle,
                'icon' => $options['vapi_active_icon'] ?? 'https://unpkg.com/lucide-static@0.321.0/icons/phone-off.svg',
            ],
        ]
    ];

    $rest_response = rest_ensure_response($response);
    $rest_response->set_headers([
        'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
        'Pragma' => 'no-cache',
        'Expires' => '0',
    ]);

    return $rest_response;
}

// Enqueue Vapi script
add_action('wp_enqueue_scripts', 'vapi_enqueue_script');

function vapi_enqueue_script()
{
    wp_enqueue_script(
        'vapi-voice-agent-loader',
        VAPI_PLUGIN_URL . 'public/js/vapi-embed-loader.js',
        [],
        VAPI_PLUGIN_VERSION,
        true
    );

    wp_localize_script('vapi-voice-agent-loader', 'vapiVoiceAgentConfig', [
        'endpoint' => esc_url_raw(rest_url('vapi/v1/config')),
        'remote' => 'https://cdn.jsdelivr.net/gh/VapiAI/html-script-tag@latest/dist/assets/index.js',
    ]);
}

// Handle tools actions: clear settings and export
add_action('admin_init', function () {
    // Handle export settings
    if (filter_has_var(INPUT_POST, 'vapi_export_settings')) {
        if (!current_user_can('manage_options')) {
            return;
        }

        $export_nonce_input = filter_input(INPUT_POST, 'vapi_export_nonce', FILTER_UNSAFE_RAW);
        $export_nonce = is_string($export_nonce_input) ? sanitize_text_field(wp_unslash($export_nonce_input)) : '';

        if ('' === $export_nonce || !wp_verify_nonce($export_nonce, 'vapi_export_settings')) {
            wp_die(esc_html__('Security check failed. Please try again.', 'vapi-voice-ai-agent'));
        }

        vapi_export_settings();
        exit;
    }

    // Handle clear settings
    if (filter_has_var(INPUT_POST, 'vapi_clear_settings')) {
        if (!current_user_can('manage_options')) {
            return;
        }

        $clear_nonce_input = filter_input(INPUT_POST, 'vapi_clear_nonce', FILTER_UNSAFE_RAW);
        $clear_nonce = is_string($clear_nonce_input) ? sanitize_text_field(wp_unslash($clear_nonce_input)) : '';

        if ('' === $clear_nonce || !wp_verify_nonce($clear_nonce, 'vapi_clear_settings')) {
            wp_die(esc_html__('Security check failed. Please try again.', 'vapi-voice-ai-agent'));
        }

        // Properly reset settings with cleanup and reinitialization
        vapi_reset_all_settings();
        wp_safe_redirect(add_query_arg('vapi_cleared', '1', admin_url('admin.php?page=vapi_tools')));
        exit;
    }
});

// Properly reset all settings with cleanup and reinitialization
function vapi_reset_all_settings()
{

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

    $filename = 'vapi-settings-' . gmdate('Y-m-d-H-i-s') . '.json';

    header('Content-Type: application/json');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . strlen(wp_json_encode($export_data)));

    echo wp_json_encode($export_data, JSON_PRETTY_PRINT);
    exit;
}

// Import settings function
function vapi_import_settings($file)
{
    if (!current_user_can('manage_options')) {
        return ['success' => false, 'message' => __('You do not have permission to import settings.', 'vapi-voice-ai-agent')];
    }

    if (!is_array($file)) {
        return ['success' => false, 'message' => __('Invalid file payload received.', 'vapi-voice-ai-agent')];
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => __('File upload error.', 'vapi-voice-ai-agent')];
    }

    if (!isset($file['tmp_name']) || !is_uploaded_file(wp_unslash($file['tmp_name']))) {
        return ['success' => false, 'message' => __('File upload could not be verified.', 'vapi-voice-ai-agent')];
    }

    $tmp_name = isset($file['tmp_name']) ? wp_unslash($file['tmp_name']) : '';
    $filename = isset($file['name']) ? $file['name'] : '';

    $filetype = wp_check_filetype_and_ext($tmp_name, $filename, ['json' => 'application/json']);
    if ((!empty($filetype['ext']) && 'json' !== $filetype['ext']) || (!empty($filetype['type']) && 'application/json' !== $filetype['type'])) {
        return ['success' => false, 'message' => __('Invalid file type supplied. Please upload a JSON file.', 'vapi-voice-ai-agent')];
    }

    $content = file_get_contents($tmp_name);
    if ($content === false) {
        return ['success' => false, 'message' => __('Unable to read the uploaded file.', 'vapi-voice-ai-agent')];
    }

    $content = ltrim($content, "\xEF\xBB\xBF\x00\x00\xFE\xFF\xFF\xFE\x00\x00");
    $content = trim($content);

    if ($content === '') {
        return ['success' => false, 'message' => __('The uploaded file is empty.', 'vapi-voice-ai-agent')];
    }

    if (substr($content, 0, 2) === "\x1f\x8b") {
        $decoded = @gzdecode($content);
        if ($decoded !== false) {
            $content = $decoded;
        }
    }

    $decoded = vapi_decode_json_payload($content);

    if (is_wp_error($decoded)) {
        return [
            'success' => false,
            'message' => sprintf(
                /* translators: %s: JSON error message while importing settings. */
                __('Invalid JSON file: %s', 'vapi-voice-ai-agent'),
                $decoded->get_error_message()
            ),
        ];
    }

    $data = $decoded;

    if (isset($data['settings']) && is_array($data['settings'])) {
        $settings_payload = $data['settings'];
    } elseif (is_array($data) && array_filter(array_keys($data), static function ($key) {
        return strpos((string) $key, 'vapi_') === 0;
    })) {
        $settings_payload = $data;
    } else {
        return ['success' => false, 'message' => __('Invalid settings file format.', 'vapi-voice-ai-agent')];
    }

    $sanitized_settings = vapi_sanitize_settings($settings_payload);

    update_option('vapi_settings', $sanitized_settings);

    return ['success' => true, 'message' => __('Settings imported successfully!', 'vapi-voice-ai-agent')];
}

function vapi_decode_json_payload($content)
{
    if (!is_string($content) || $content === '') {
        return new WP_Error('vapi_invalid_json', __('Empty JSON payload.', 'vapi-voice-ai-agent'));
    }

    $candidates = [$content];

    $fallback = html_entity_decode($content, ENT_QUOTES | ENT_XML1, 'UTF-8');
    if (is_string($fallback) && $fallback !== $content) {
        $candidates[] = $fallback;
    }

    $utf8_content = wp_check_invalid_utf8($content, true);
    if (is_string($utf8_content) && $utf8_content !== $content) {
        $candidates[] = $utf8_content;
    }

    $sanitized_content = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $content);
    if (is_string($sanitized_content) && $sanitized_content !== $content) {
        $candidates[] = $sanitized_content;
    }

    $candidates = array_values(array_unique($candidates));

    $json_options = [0];
    if (defined('JSON_INVALID_UTF8_SUBSTITUTE')) {
        $json_options[] = JSON_INVALID_UTF8_SUBSTITUTE;
    }

    foreach ($candidates as $candidate) {
        foreach ($json_options as $options) {
            $decoded = json_decode($candidate, true, 512, $options);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $decoded;
            }
        }
    }

    $message = function_exists('json_last_error_msg') ? json_last_error_msg() : __('Failed to decode JSON.', 'vapi-voice-ai-agent');

    return new WP_Error('vapi_invalid_json', $message);
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
            if (in_array($key, ['vapi_system_prompt', 'vapi_training_notes'], true) && is_string($val)) {
                $out[$key] = sanitize_textarea_field($val);
            } elseif (strpos($key, '_color') !== false && is_string($val)) {
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

    // Sanitize the new values
    $sanitized_new = vapi_sanitize_settings($input);

    // Merge with existing settings to preserve other tab values
    return array_merge($existing_settings, $sanitized_new);
}
