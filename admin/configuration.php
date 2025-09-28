<?php
if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('vapi_recursive_sanitize_text')) {
    function vapi_recursive_sanitize_text($value)
    {
        if (is_array($value)) {
            $sanitized = [];
            foreach ($value as $key => $item) {
                $sanitized_key = is_string($key) ? sanitize_text_field($key) : $key;
                $sanitized[$sanitized_key] = vapi_recursive_sanitize_text($item);
            }
            return $sanitized;
        }

        return sanitize_text_field((string) $value);
    }
}

add_action('admin_init', 'vapi_handle_config_form_submission');

/**
 * Process configuration form submissions early to avoid header output warnings.
 */
function vapi_handle_config_form_submission()
{
    if (!is_admin()) {
        return;
    }

    $page = filter_input(INPUT_GET, 'page', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $page = is_string($page) ? sanitize_key($page) : '';

    if ('vapi_config' !== $page) {
        return;
    }

    $request_method = filter_input(INPUT_SERVER, 'REQUEST_METHOD', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $request_method = is_string($request_method) ? strtoupper(sanitize_text_field($request_method)) : '';

    if ('POST' !== $request_method) {
        return;
    }

    $active_tab_raw = filter_input(INPUT_GET, 'tab', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $active_tab = is_string($active_tab_raw) ? sanitize_key($active_tab_raw) : 'api';

    $debug_param = filter_input(INPUT_GET, 'debug', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $debug_mode = ('1' === $debug_param);

    $post_data = filter_input_array(INPUT_POST, FILTER_UNSAFE_RAW);
    if (!is_array($post_data)) {
        $post_data = [];
    }

    if ($active_tab === 'api' && isset($post_data['vapi_api_action'])) {
        vapi_handle_api_form_submission($debug_mode, $post_data);
        return;
    }

    if ($active_tab === 'appearance' && isset($post_data['vapi_appearance_action'])) {
        vapi_handle_appearance_form_submission($debug_mode, $post_data);
        return;
    }

    if ($active_tab === 'api_config' && isset($post_data['vapi_private_api_action'])) {
        vapi_handle_private_api_form_submission($debug_mode, $post_data);
        return;
    }

    if ($active_tab === 'training' && isset($post_data['vapi_training_action'])) {
        vapi_handle_training_form_submission($debug_mode, $post_data);
        return;
    }
}

/**
 * Handle API tab form submission.
 */
function vapi_handle_api_form_submission($debug_mode, array $post_data)
{
    $nonce = isset($post_data['vapi_api_nonce']) ? sanitize_text_field(wp_unslash($post_data['vapi_api_nonce'])) : '';

    if ('' === $nonce || !wp_verify_nonce($nonce, 'vapi_api_action')) {
        add_settings_error('vapi_messages', 'vapi_api_nonce', esc_html__('Security verification failed. Please try again.', 'vapi-voice-ai-agent'), 'error');
        return;
    }

    $options = get_option('vapi_settings', []);
    $options['vapi_api_key'] = sanitize_text_field(wp_unslash($post_data['vapi_api_key'] ?? ''));
    $options['vapi_assistant_id'] = sanitize_text_field(wp_unslash($post_data['vapi_assistant_id'] ?? ''));

    update_option('vapi_settings', $options);

    if ($debug_mode) {
        global $vapi_debug_logs;
        $vapi_debug_logs['api'] = [
            'posted' => vapi_recursive_sanitize_text(wp_unslash($post_data)),
            'options' => $options,
        ];
        add_settings_error('vapi_messages', 'vapi_api_saved', esc_html__('Settings saved successfully (debug mode, no redirect).', 'vapi-voice-ai-agent'), 'success');
        return;
    }

    $redirect_args = ['page' => 'vapi_config', 'tab' => 'api', 'saved' => '1'];
    wp_safe_redirect(add_query_arg($redirect_args, admin_url('admin.php')));
    exit;
}

/**
 * Handle private API tab form submission.
 */
function vapi_handle_private_api_form_submission($debug_mode, array $post_data)
{
    $nonce = isset($post_data['vapi_private_api_nonce']) ? sanitize_text_field(wp_unslash($post_data['vapi_private_api_nonce'])) : '';

    if ('' === $nonce || !wp_verify_nonce($nonce, 'vapi_private_api_action')) {
        add_settings_error('vapi_messages', 'vapi_private_api_nonce', esc_html__('Security verification failed. Please try again.', 'vapi-voice-ai-agent'), 'error');
        return;
    }

    $options = get_option('vapi_settings', []);
    $options['vapi_private_api_key'] = sanitize_text_field(wp_unslash($post_data['vapi_private_api_key'] ?? ''));

    update_option('vapi_settings', $options);

    if ($debug_mode) {
        global $vapi_debug_logs;
        $vapi_debug_logs['private_api'] = [
            'posted' => vapi_recursive_sanitize_text(wp_unslash($post_data)),
            'options' => $options,
        ];
        add_settings_error('vapi_messages', 'vapi_private_api_saved', esc_html__('Private API key saved (debug mode, no redirect).', 'vapi-voice-ai-agent'), 'success');
        return;
    }

    $redirect_args = ['page' => 'vapi_config', 'tab' => 'api_config', 'saved' => '1'];
    wp_safe_redirect(add_query_arg($redirect_args, admin_url('admin.php')));
    exit;
}

/**
 * Handle appearance tab form submission.
 */
function vapi_handle_appearance_form_submission($debug_mode, array $post_data)
{
    $nonce = isset($post_data['vapi_appearance_nonce']) ? sanitize_text_field(wp_unslash($post_data['vapi_appearance_nonce'])) : '';

    if ('' === $nonce || !wp_verify_nonce($nonce, 'vapi_appearance_action')) {
        add_settings_error('vapi_messages', 'vapi_appearance_nonce', esc_html__('Security verification failed. Please try again.', 'vapi-voice-ai-agent'), 'error');
        return;
    }

    $appearance_fields = [
        'vapi_button_position', 'vapi_button_offset',
        'vapi_button_width', 'vapi_button_height',
        'vapi_idle_color', 'vapi_idle_title', 'vapi_idle_subtitle', 'vapi_idle_icon',
        'vapi_loading_color', 'vapi_loading_title', 'vapi_loading_subtitle', 'vapi_loading_icon',
        'vapi_active_color', 'vapi_active_title', 'vapi_active_subtitle', 'vapi_active_icon'
    ];

    $options = get_option('vapi_settings', []);
    $old_options = $options;

    foreach ($appearance_fields as $field) {
        if (!isset($post_data[$field])) {
            continue;
        }

        $raw_value = wp_unslash($post_data[$field]);

        if (strpos($field, '_color') !== false && strpos($raw_value, '#') === 0) {
            $hex = ltrim($raw_value, '#');
            if (strlen($hex) === 6) {
                $r = hexdec(substr($hex, 0, 2));
                $g = hexdec(substr($hex, 2, 2));
                $b = hexdec(substr($hex, 4, 2));
                $options[$field] = "rgb($r, $g, $b)";
            } else {
                $options[$field] = sanitize_text_field($raw_value);
            }
        } else {
            $options[$field] = sanitize_text_field($raw_value);
        }
    }

    $button_fixed_value = isset($post_data['vapi_button_fixed']) ? sanitize_text_field(wp_unslash($post_data['vapi_button_fixed'])) : '';
    $options['vapi_button_fixed'] = '' !== $button_fixed_value ? 1 : 0;

    $result = update_option('vapi_settings', $options);
    $new_options = get_option('vapi_settings', []);

    $values_match = true;
    foreach ($options as $key => $value) {
        if (!array_key_exists($key, $new_options) || $new_options[$key] !== $value) {
            $values_match = false;
            break;
        }
    }

    if ($result || $values_match) {
        if ($debug_mode) {
            global $vapi_debug_logs;
            $changed_fields = [];
            foreach ($options as $key => $value) {
                if (!isset($old_options[$key]) || $old_options[$key] !== $value) {
                    $changed_fields[$key] = [
                        'old' => $old_options[$key] ?? 'NOT_SET',
                        'new' => $value,
                    ];
                }
            }

            $option_exists = get_option('vapi_settings', null) !== null;

            $vapi_debug_logs['appearance'] = [
                'submitted' => $options,
                'old' => $old_options,
                'new' => $new_options,
                'changed' => $changed_fields,
                'option_exists' => (bool) $option_exists,
            ];

            add_settings_error('vapi_messages', 'vapi_appearance_saved', esc_html__('Settings saved successfully (debug mode, no redirect).', 'vapi-voice-ai-agent'), 'success');
            return;
        }

        $redirect_args = ['page' => 'vapi_config', 'tab' => 'appearance', 'saved' => '1'];
        wp_safe_redirect(add_query_arg($redirect_args, admin_url('admin.php')));
        exit;
    }

    add_settings_error('vapi_messages', 'vapi_appearance_failed', esc_html__('Failed to save appearance settings!', 'vapi-voice-ai-agent'), 'error');
}

/**
 * Handle training tab form submission.
 */
function vapi_handle_training_form_submission($debug_mode, array $post_data)
{
    $nonce = isset($post_data['vapi_training_nonce']) ? sanitize_text_field(wp_unslash($post_data['vapi_training_nonce'])) : '';

    if ('' === $nonce || !wp_verify_nonce($nonce, 'vapi_training_action')) {
        add_settings_error('vapi_messages', 'vapi_training_nonce', esc_html__('Security verification failed. Please try again.', 'vapi-voice-ai-agent'), 'error');
        return;
    }

    $options = get_option('vapi_settings', []);
    if (isset($post_data['vapi_training_notes'])) {
        $options['vapi_training_notes'] = sanitize_textarea_field(wp_unslash($post_data['vapi_training_notes']));
    }

    if (isset($post_data['vapi_selected_assistant'])) {
        $options['vapi_selected_assistant'] = sanitize_text_field(wp_unslash($post_data['vapi_selected_assistant']));
    }

    if (isset($post_data['vapi_first_message'])) {
        $options['vapi_first_message'] = sanitize_text_field(wp_unslash($post_data['vapi_first_message']));
    }

    if (isset($post_data['vapi_end_call_message'])) {
        $options['vapi_end_call_message'] = sanitize_text_field(wp_unslash($post_data['vapi_end_call_message']));
    }

    if (isset($post_data['vapi_voicemail_message'])) {
        $options['vapi_voicemail_message'] = sanitize_text_field(wp_unslash($post_data['vapi_voicemail_message']));
    }

    if (isset($post_data['vapi_system_prompt'])) {
        $options['vapi_system_prompt'] = sanitize_textarea_field(wp_unslash($post_data['vapi_system_prompt']));
    }

    $result = update_option('vapi_settings', $options);

    if ($result !== false || isset($options['vapi_training_notes'])) {
        if ($debug_mode) {
            global $vapi_debug_logs;
            $vapi_debug_logs['training'] = [
                'assistant' => $options['vapi_selected_assistant'] ?? '',
                'messages' => [
                    'first' => $options['vapi_first_message'] ?? '',
                    'voicemail' => $options['vapi_voicemail_message'] ?? '',
                    'end' => $options['vapi_end_call_message'] ?? '',
                ],
                'system_prompt' => $options['vapi_system_prompt'] ?? '',
            ];
            add_settings_error('vapi_messages', 'vapi_training_saved', esc_html__('Assistant defaults saved (debug mode, no redirect).', 'vapi-voice-ai-agent'), 'success');
            return;
        }

        $redirect_args = ['page' => 'vapi_config', 'tab' => 'training', 'saved' => '1'];
        wp_safe_redirect(add_query_arg($redirect_args, admin_url('admin.php')));
        exit;
    }

    add_settings_error('vapi_messages', 'vapi_training_failed', esc_html__('Failed to save assistant defaults!', 'vapi-voice-ai-agent'), 'error');
}

function vapi_config_page()
{
    $active_tab = isset($_GET['tab']) ? sanitize_text_field(wp_unslash($_GET['tab'])) : 'api';

    // Debug mode toggle
    $debug_mode = isset($_GET['debug']) && '1' === sanitize_text_field(wp_unslash($_GET['debug']));

    // Show success message if redirected after save
    if (isset($_GET['saved']) && '1' === sanitize_text_field(wp_unslash($_GET['saved']))) {
        echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Settings saved successfully!', 'vapi-voice-ai-agent') . '</p></div>';
    }

    settings_errors('vapi_messages');

    // Debug information
    if ($debug_mode) {
        global $vapi_debug_logs;
        echo '<div class="notice notice-info"><p><strong>' . esc_html__('Debug mode active', 'vapi-voice-ai-agent') . '</strong></p></div>';
        echo '<div class="notice notice-info">';
        $request_method = isset($_SERVER['REQUEST_METHOD']) ? sanitize_text_field(wp_unslash($_SERVER['REQUEST_METHOD'])) : 'GET';
        echo '<p><strong>' . esc_html__('Request method:', 'vapi-voice-ai-agent') . '</strong> ' . esc_html($request_method) . '</p>';
        echo '<p><strong>' . esc_html__('Active tab:', 'vapi-voice-ai-agent') . '</strong> ' . esc_html($active_tab) . '</p>';

        if (!empty($_POST)) {
            $post_dump = wp_json_encode(vapi_recursive_sanitize_text(wp_unslash($_POST)), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            if ($post_dump) {
                echo '<p><strong>' . esc_html__('POST data:', 'vapi-voice-ai-agent') . '</strong><br><pre>' . esc_html($post_dump) . '</pre></p>';
            }
        }

        $current_options = get_option('vapi_settings', []);
        $options_dump = wp_json_encode($current_options, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        if ($options_dump) {
            echo '<p><strong>' . esc_html__('Current DB options:', 'vapi-voice-ai-agent') . '</strong><br><pre>' . esc_html($options_dump) . '</pre></p>';
        }

        if (!empty($vapi_debug_logs)) {
            $logs_dump = wp_json_encode(vapi_recursive_sanitize_text($vapi_debug_logs), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            if ($logs_dump) {
                echo '<p><strong>' . esc_html__('Captured debug logs:', 'vapi-voice-ai-agent') . '</strong><br><pre>' . esc_html($logs_dump) . '</pre></p>';
            }
        }
        echo '</div>';
    }

    // Get options AFTER processing forms to ensure updated values are displayed
    $options = get_option('vapi_settings', []);
    $stored_assistant = '';
    if (!empty($options['vapi_assistant_id'])) {
        $stored_assistant = (string) $options['vapi_assistant_id'];
    } elseif (!empty($options['vapi_selected_assistant'])) {
        $stored_assistant = (string) $options['vapi_selected_assistant'];
    }

    $is_configured = !empty($options['vapi_api_key']) && '' !== trim($stored_assistant);
    $button_position = $options['vapi_button_position'] ?? 'bottom-right';
    $button_position_label = ucwords(str_replace('-', ' ', $button_position));
    $system_prompt_length = strlen($options['vapi_system_prompt'] ?? '');
    $tab_urls = [
        'api' => add_query_arg([
            'page' => 'vapi_config',
            'tab' => 'api',
        ], admin_url('admin.php')),
        'api_config' => add_query_arg([
            'page' => 'vapi_config',
            'tab' => 'api_config',
        ], admin_url('admin.php')),
        'appearance' => add_query_arg([
            'page' => 'vapi_config',
            'tab' => 'appearance',
        ], admin_url('admin.php')),
        'training' => add_query_arg([
            'page' => 'vapi_config',
            'tab' => 'training',
        ], admin_url('admin.php')),
    ];

    ?>
    <div class="wrap vapi-admin-page">
        <!-- Modern Header -->
        <div class="vapi-header">
            <div class="vapi-header-content">
                <span class="vapi-chip <?php echo $is_configured ? 'success' : 'warning'; ?>">
                    <?php echo $is_configured ? esc_html__('Status: Connected', 'vapi-voice-ai-agent') : esc_html__('Status: Needs configuration', 'vapi-voice-ai-agent'); ?>
                </span>
                <h1>
                    <div class="vapi-header-icon">
                        <span class="dashicons dashicons-admin-settings"></span>
                    </div>
                    <?php esc_html_e('Vapi Configuration', 'vapi-voice-ai-agent'); ?>
                </h1>
                <p><?php esc_html_e('Configure your voice assistant settings and appearance', 'vapi-voice-ai-agent'); ?></p>

                <div class="vapi-hero-meta">
                    <div class="vapi-hero-meta-item">
                        <span><?php esc_html_e('Voice AI key', 'vapi-voice-ai-agent'); ?></span>
                        <strong><?php echo !empty($options['vapi_api_key']) ? esc_html__('Connected', 'vapi-voice-ai-agent') : esc_html__('Missing', 'vapi-voice-ai-agent'); ?></strong>
                    </div>
                    <div class="vapi-hero-meta-item">
                        <span><?php esc_html_e('Assistant ID', 'vapi-voice-ai-agent'); ?></span>
                        <strong><?php echo '' !== trim($stored_assistant) ? esc_html__('Assigned', 'vapi-voice-ai-agent') : esc_html__('Not set', 'vapi-voice-ai-agent'); ?></strong>
                    </div>
                    <div class="vapi-hero-meta-item">
                        <span><?php esc_html_e('Private API key', 'vapi-voice-ai-agent'); ?></span>
                        <strong><?php echo !empty($options['vapi_private_api_key']) ? esc_html__('Stored', 'vapi-voice-ai-agent') : esc_html__('Missing', 'vapi-voice-ai-agent'); ?></strong>
                    </div>
                    <div class="vapi-hero-meta-item">
                        <span><?php esc_html_e('Button position', 'vapi-voice-ai-agent'); ?></span>
                        <strong><?php echo esc_html($button_position_label); ?></strong>
                    </div>
                    <div class="vapi-hero-meta-item">
                        <span><?php esc_html_e('System prompt', 'vapi-voice-ai-agent'); ?></span>
                        <strong><?php echo $system_prompt_length ? esc_html__('Configured', 'vapi-voice-ai-agent') : esc_html__('Empty', 'vapi-voice-ai-agent'); ?></strong>
                    </div>
                </div>
            </div>
        </div>

        <div class="vapi-content">
            <!-- Modern Navigation Tabs -->
            <div class="vapi-nav-tabs">
                <a class="vapi-nav-tab <?php echo esc_attr($active_tab === 'api' ? 'active' : ''); ?>" href="<?php echo esc_url($tab_urls['api']); ?>">
                    <span class="dashicons dashicons-admin-network"></span>
                    <?php esc_html_e('Voice AI Settings', 'vapi-voice-ai-agent'); ?>
                </a>
                <a class="vapi-nav-tab <?php echo esc_attr($active_tab === 'api_config' ? 'active' : ''); ?>" href="<?php echo esc_url($tab_urls['api_config']); ?>">
                    <span class="dashicons dashicons-lock"></span>
                    <?php esc_html_e('API Configuration', 'vapi-voice-ai-agent'); ?>
                </a>
                <a class="vapi-nav-tab <?php echo esc_attr($active_tab === 'appearance' ? 'active' : ''); ?>" href="<?php echo esc_url($tab_urls['appearance']); ?>">
                    <span class="dashicons dashicons-admin-appearance"></span>
                    <?php esc_html_e('Appearance', 'vapi-voice-ai-agent'); ?>
                </a>
                <a class="vapi-nav-tab <?php echo esc_attr($active_tab === 'training' ? 'active' : ''); ?>" href="<?php echo esc_url($tab_urls['training']); ?>">
                    <span class="dashicons dashicons-welcome-learn-more"></span>
                    <?php esc_html_e('Training', 'vapi-voice-ai-agent'); ?>
                </a>
            </div>

        <?php if ($active_tab === 'api'): ?>
            <div class="vapi-tab-content vapi-fade-in">
                <form method="post">
                    <?php wp_nonce_field('vapi_api_action', 'vapi_api_nonce'); ?>
                    <input type="hidden" name="vapi_api_action" value="save_api" />

                    <div class="vapi-card">
                        <div class="vapi-card-header">
                            <h2 class="vapi-card-title">
                                <span class="dashicons dashicons-admin-network"></span>
                                <?php esc_html_e('Voice AI Settings', 'vapi-voice-ai-agent'); ?>
                            </h2>
                            <p class="vapi-card-subtitle"><?php esc_html_e('Enter your Voice AI settings credentials to connect your voice assistant. You can find these in your Vapi dashboard.', 'vapi-voice-ai-agent'); ?></p>
                        </div>
                        <div class="vapi-card-body">
                            <table class="vapi-form-table">
                            <tr>
                                <th scope="row">
                                    <label for="vapi_api_key"><?php esc_html_e('Public API Key', 'vapi-voice-ai-agent'); ?> <span style="color: red;">*</span></label>
                                </th>
                                <td>
                                    <input type="password" id="vapi_api_key" name="vapi_api_key"
                                           value="<?php echo esc_attr($options['vapi_api_key'] ?? ''); ?>"
                                           class="vapi-form-control large" placeholder="<?php echo esc_attr__('sk-...', 'vapi-voice-ai-agent'); ?>" required />
                                    <p class="vapi-form-description">
                                        <?php esc_html_e('Your Vapi API key from', 'vapi-voice-ai-agent'); ?>
                                        <a href="https://vapi.ai/dashboard" target="_blank"><?php esc_html_e('Vapi Dashboard', 'vapi-voice-ai-agent'); ?></a>
                                    </p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="vapi_assistant_id"><?php esc_html_e('Assistant ID', 'vapi-voice-ai-agent'); ?> <span style="color: red;">*</span></label>
                                </th>
                                <td>
                                    <input type="text" id="vapi_assistant_id" name="vapi_assistant_id"
                                           value="<?php echo esc_attr($options['vapi_assistant_id'] ?? ''); ?>"
                                           class="vapi-form-control large" placeholder="<?php echo esc_attr__('assistant_...', 'vapi-voice-ai-agent'); ?>" required />
                                    <p class="vapi-form-description">
                                        <?php esc_html_e('The ID of your voice assistant from your Vapi dashboard', 'vapi-voice-ai-agent'); ?>
                                    </p>
                                </td>
                            </tr>
                        </table>

                        </div>
                        <div class="vapi-card-footer">
                            <div class="vapi-alert info">
                                <span class="dashicons dashicons-info"></span>
                                <div>
                                    <h4><?php esc_html_e('Connection Test', 'vapi-voice-ai-agent'); ?></h4>
                                    <p><?php esc_html_e('After saving your credentials, the voice button will appear on your website if the configuration is correct.', 'vapi-voice-ai-agent'); ?></p>
                                    <button type="button" id="vapi-test-connection" class="vapi-button secondary vapi-mt-2">
                                        <span class="dashicons dashicons-admin-tools"></span>
                                        <?php esc_html_e('Test Connection', 'vapi-voice-ai-agent'); ?>
                                    </button>
                                    <div id="vapi-test-result" class="vapi-mt-2"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="vapi-form-footer">
                        <button type="submit" class="vapi-button">
                            <span class="dashicons dashicons-yes"></span>
                            <?php esc_html_e('Save Voice AI Settings', 'vapi-voice-ai-agent'); ?>
                        </button>
                    </div>
                </form>
            </div>
        <?php elseif ($active_tab === 'api_config'): ?>
            <div class="vapi-tab-content vapi-fade-in">
                <form method="post">
                    <?php wp_nonce_field('vapi_private_api_action', 'vapi_private_api_nonce'); ?>
                    <input type="hidden" name="vapi_private_api_action" value="save_private_api" />

                    <div class="vapi-card">
                        <div class="vapi-card-header">
                            <h2 class="vapi-card-title">
                                <span class="dashicons dashicons-lock"></span>
                                <?php esc_html_e('API Configuration', 'vapi-voice-ai-agent'); ?>
                            </h2>
                            <p class="vapi-card-subtitle"><?php esc_html_e('Store your private API key for secure server-side requests such as listing assistants or orchestrating calls.', 'vapi-voice-ai-agent'); ?></p>
                        </div>
                        <div class="vapi-card-body">
                            <table class="vapi-form-table">
                                <tr>
                                    <th scope="row">
                                        <label for="vapi_private_api_key"><?php esc_html_e('Private API Key', 'vapi-voice-ai-agent'); ?> <span style="color: red;">*</span></label>
                                    </th>
                                    <td>
                                        <input type="password" id="vapi_private_api_key" name="vapi_private_api_key"
                                               value="<?php echo esc_attr($options['vapi_private_api_key'] ?? ''); ?>"
                                               class="vapi-form-control large" placeholder="<?php echo esc_attr__('45652c35-8383-4b46-91a4-46bbe94e7eaf', 'vapi-voice-ai-agent'); ?>" required />
                                        <p class="vapi-form-description">
                                            <?php esc_html_e('Use this server-side key to call protected endpoints, for example:', 'vapi-voice-ai-agent'); ?><br />
                                            <code>curl https://api.vapi.ai/assistant -H "Authorization: Bearer YOUR_PRIVATE_KEY"</code>
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <div class="vapi-form-footer">
                        <button type="submit" class="vapi-button">
                            <span class="dashicons dashicons-yes"></span>
                            <?php esc_html_e('Save Private API Key', 'vapi-voice-ai-agent'); ?>
                        </button>
                    </div>
                </form>
            </div>

        <?php elseif ($active_tab === 'appearance'): ?>
            <div class="vapi-tab-content vapi-fade-in">
                <form method="post">
                    <?php wp_nonce_field('vapi_appearance_action', 'vapi_appearance_nonce'); ?>
                    <input type="hidden" name="vapi_appearance_action" value="save_appearance" />

                    <div class="vapi-card">
                        <div class="vapi-card-header">
                            <h2 class="vapi-card-title">
                                <span class="dashicons dashicons-admin-appearance"></span>
                                <?php esc_html_e('Button Positioning', 'vapi-voice-ai-agent'); ?>
                            </h2>
                            <p class="vapi-card-subtitle"><?php esc_html_e('Configure where the voice button appears on your website', 'vapi-voice-ai-agent'); ?></p>
                        </div>
                        <div class="vapi-card-body">
                            <div class="vapi-alert info">
                                <span class="dashicons dashicons-info"></span>
                                <div>
                                    <h4><?php esc_html_e('Icon tips', 'vapi-voice-ai-agent'); ?></h4>
                                    <p>
                                        <?php
                                        $icon_help = sprintf(
                                            /* translators: 1: Link to Lucide icons page, 2: example SVG icon URL. */
                                            esc_html__('Browse free SVG icons on the %1$s. Click an icon and open it in a new tab to copy the CDN URL (for example %2$s). You can also upload your own SVG to the Media Library and paste its URL below.', 'vapi-voice-ai-agent'),
                                            '<a href="' . esc_url('https://app.unpkg.com/lucide-static@0.544.0/files/icons') . '" target="_blank" rel="noopener noreferrer">' . esc_html__('Lucide icons page', 'vapi-voice-ai-agent') . '</a>',
                                            '<code>https://unpkg.com/lucide-static@0.544.0/icons/audio-waveform.svg</code>'
                                        );
                                        echo wp_kses(
                                            $icon_help,
                                            [
                                                'a' => ['href' => [], 'target' => [], 'rel' => []],
                                                'code' => [],
                                            ]
                                        );
                                        ?>
                                    </p>
                                </div>
                            </div>
                            <table class="vapi-form-table">
                            <tr>
                                <th scope="row"><label for="vapi_button_position"><?php esc_html_e('Button Position', 'vapi-voice-ai-agent'); ?></label></th>
                                <td>
                                    <?php
                                    $positions = [
                                        "bottom-right" => "Bottom Right",
                                        "bottom" => "Bottom",
                                        "top" => "Top",
                                        "left" => "Left",
                                        "right" => "Right",
                                        "top-right" => "Top Right",
                                        "top-left" => "Top Left",
                                        "bottom-left" => "Bottom Left"
                                    ];
                                    $current_position = $options['vapi_button_position'] ?? 'bottom-right';
                                    ?>
                                    <select name="vapi_button_position" id="vapi_button_position" class="vapi-form-control">
                                        <?php foreach ($positions as $value => $label): ?>
                                            <option value="<?php echo esc_attr($value); ?>" <?php selected($current_position, $value); ?>>
                                                <?php echo esc_html($label); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <p class="vapi-form-description"><?php esc_html_e('Choose where the voice button appears on your website', 'vapi-voice-ai-agent'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="vapi_button_fixed"><?php esc_html_e('Fixed Position', 'vapi-voice-ai-agent'); ?></label></th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="vapi_button_fixed" value="1"
                                               <?php checked(isset($options['vapi_button_fixed']) ? $options['vapi_button_fixed'] : 0, 1); ?> />
                                        <?php esc_html_e('Button follows scroll (fixed position)', 'vapi-voice-ai-agent'); ?>
                                    </label>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="vapi_button_offset"><?php esc_html_e('Button Offset', 'vapi-voice-ai-agent'); ?></label></th>
                                <td>
                                    <input type="text" name="vapi_button_offset"
                                           value="<?php echo esc_attr($options['vapi_button_offset'] ?? '40px'); ?>"
                                           placeholder="<?php echo esc_attr__('40px', 'vapi-voice-ai-agent'); ?>" class="vapi-form-control" />
                                    <p class="vapi-form-description"><?php esc_html_e('Distance from edge (e.g., 40px, 2rem)', 'vapi-voice-ai-agent'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="vapi_button_width"><?php esc_html_e('Button Width', 'vapi-voice-ai-agent'); ?></label></th>
                                <td>
                                    <input type="text" name="vapi_button_width"
                                           value="<?php echo esc_attr($options['vapi_button_width'] ?? '50px'); ?>"
                                           placeholder="<?php echo esc_attr__('50px', 'vapi-voice-ai-agent'); ?>" class="vapi-form-control" />
                                    <p class="vapi-form-description"><?php esc_html_e('Width of the voice button', 'vapi-voice-ai-agent'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="vapi_button_height"><?php esc_html_e('Button Height', 'vapi-voice-ai-agent'); ?></label></th>
                                <td>
                                    <input type="text" name="vapi_button_height"
                                           value="<?php echo esc_attr($options['vapi_button_height'] ?? '50px'); ?>"
                                           placeholder="<?php echo esc_attr__('50px', 'vapi-voice-ai-agent'); ?>" class="vapi-form-control" />
                                    <p class="vapi-form-description"><?php esc_html_e('Height of the voice button', 'vapi-voice-ai-agent'); ?></p>
                                </td>
                            </tr>
                            </table>
                        </div>
                    </div>

                    <?php
                    $states = [
                        'idle' => ['title' => 'Idle State', 'description' => 'When the button is ready to start a conversation'],
                        'loading' => ['title' => 'Loading State', 'description' => 'When connecting to the voice assistant'],
                        'active' => ['title' => 'Active State', 'description' => 'When a conversation is in progress']
                    ];

                    foreach ($states as $state => $state_info):
                    ?>
                        <div class="vapi-card">
                            <div class="vapi-card-header">
                                <h2 class="vapi-card-title">
                                    <span class="dashicons dashicons-art"></span>
                                    <?php echo esc_html($state_info['title']); ?>
                                </h2>
                                <p class="vapi-card-subtitle"><?php echo esc_html($state_info['description']); ?></p>
                            </div>
                            <div class="vapi-card-body">
                                <table class="vapi-form-table">
                                <tr>
                                    <th scope="row"><label><?php esc_html_e('Color', 'vapi-voice-ai-agent'); ?></label></th>
                                    <td>
                                        <?php
                                        // Convert RGB to hex for color picker if needed
                                        $color_value = $options["vapi_{$state}_color"] ?? ($state === 'idle' ? 'rgb(93, 254, 202)' : ($state === 'loading' ? 'rgb(93, 124, 202)' : 'rgb(255, 0, 0)'));
                                        if (strpos($color_value, 'rgb') === 0) {
                                            // Convert rgb(r, g, b) to hex
                                            preg_match('/rgb\((\d+),\s*(\d+),\s*(\d+)\)/', $color_value, $matches);
                                            if (count($matches) === 4) {
                                                $color_value = sprintf("#%02x%02x%02x", $matches[1], $matches[2], $matches[3]);
                                            }
                                        }
                                        ?>
                                        <input type="color" name="vapi_<?php echo esc_attr($state); ?>_color"
                                               value="<?php echo esc_attr($color_value); ?>" class="vapi-color-picker" />
                                        <p class="vapi-form-description"><?php esc_html_e('Color for the button in this state', 'vapi-voice-ai-agent'); ?></p>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><label><?php esc_html_e('Title', 'vapi-voice-ai-agent'); ?></label></th>
                                    <td>
                                        <input type="text" name="vapi_<?php echo esc_attr($state); ?>_title"
                                               value="<?php echo esc_attr($options["vapi_{$state}_title"] ?? ''); ?>"
                                               class="vapi-form-control" placeholder="<?php echo esc_attr__('Button title text', 'vapi-voice-ai-agent'); ?>" />
                                        <p class="vapi-form-description"><?php esc_html_e('Text displayed on the button', 'vapi-voice-ai-agent'); ?></p>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><label><?php esc_html_e('Subtitle', 'vapi-voice-ai-agent'); ?></label></th>
                                    <td>
                                        <input type="text" name="vapi_<?php echo esc_attr($state); ?>_subtitle"
                                               value="<?php echo esc_attr($options["vapi_{$state}_subtitle"] ?? ''); ?>"
                                               class="vapi-form-control" placeholder="<?php echo esc_attr__('Optional subtitle', 'vapi-voice-ai-agent'); ?>" />
                                        <p class="vapi-form-description"><?php esc_html_e('Additional text below the title (optional)', 'vapi-voice-ai-agent'); ?></p>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><label><?php esc_html_e('Icon URL', 'vapi-voice-ai-agent'); ?></label></th>
                                    <td>
                                        <input type="url" name="vapi_<?php echo esc_attr($state); ?>_icon"
                                               value="<?php echo esc_attr($options["vapi_{$state}_icon"] ?? ''); ?>"
                                               class="vapi-form-control" placeholder="<?php echo esc_attr__('https://example.com/icon.svg', 'vapi-voice-ai-agent'); ?>" />
                                        <p class="vapi-form-description"><?php esc_html_e('Paste the SVG URL for this state (Lucide CDN link or one from your Media Library).', 'vapi-voice-ai-agent'); ?></p>
                                    </td>
                                </tr>
                                </table>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <div class="vapi-form-footer">
                        <button type="submit" class="vapi-button">
                            <span class="dashicons dashicons-art"></span>
                            <?php esc_html_e('Save Appearance Settings', 'vapi-voice-ai-agent'); ?>
                        </button>
                    </div>
                </form>
            </div>

        <?php else: // Training tab ?>
            <div class="vapi-tab-content vapi-fade-in vapi-training-tab">
                <form method="post" id="vapi-training-form">
                    <?php wp_nonce_field('vapi_training_action', 'vapi_training_nonce'); ?>
                    <input type="hidden" name="vapi_training_action" value="save_notes" />

                    <?php
                    // Add hidden fields to preserve other settings when saving assistant defaults
                    $other_fields = [
                        'vapi_api_key', 'vapi_private_api_key', 'vapi_assistant_id', 'vapi_selected_assistant',
                        'vapi_button_position', 'vapi_button_fixed', 'vapi_button_offset',
                        'vapi_button_width', 'vapi_button_height',
                        'vapi_idle_color', 'vapi_idle_type', 'vapi_idle_title', 'vapi_idle_subtitle', 'vapi_idle_icon',
                        'vapi_loading_color', 'vapi_loading_type', 'vapi_loading_title', 'vapi_loading_subtitle', 'vapi_loading_icon',
                        'vapi_active_color', 'vapi_active_type', 'vapi_active_title', 'vapi_active_subtitle', 'vapi_active_icon'
                    ];

                    foreach ($other_fields as $field) {
                        if (isset($options[$field])) {
                            echo '<input type="hidden" name="vapi_settings[' . esc_attr($field) . ']" value="' . esc_attr($options[$field]) . '" />';
                        }
                    }
                    ?>

                    <div class="vapi-card vapi-gradient-card">
                        <div class="vapi-card-header">
                            <h2 class="vapi-card-title">
                                <span class="dashicons dashicons-admin-users"></span>
                                <?php esc_html_e('Assistant Library', 'vapi-voice-ai-agent'); ?>
                            </h2>
                            <p class="vapi-card-subtitle"><?php esc_html_e('Fetch assistants from your Vapi account using the private API key and review their conversation defaults.', 'vapi-voice-ai-agent'); ?></p>
                        </div>
                        <div class="vapi-card-body">
                            <label for="vapi_assistant_selector" class="vapi-form-description" style="padding-left:0;">
                                <?php esc_html_e('Choose an assistant to inspect its configuration', 'vapi-voice-ai-agent'); ?>
                            </label>
                            <div class="vapi-assistant-select-row">
                                <select id="vapi_assistant_selector" name="vapi_selected_assistant"
                                        class="vapi-form-control"
                                        data-selected="<?php echo esc_attr($options['vapi_selected_assistant'] ?? ''); ?>">
                                    <option value=""><?php esc_html_e('Select an assistant', 'vapi-voice-ai-agent'); ?></option>
                                    <?php if (!empty($options['vapi_selected_assistant'])): ?>
                                        <option value="<?php echo esc_attr($options['vapi_selected_assistant']); ?>" selected>
                                            <?php
                                            printf(
                                                /* translators: %s: Assistant ID stored in the settings. */
                                                esc_html__('Assistant %s (loading details...)', 'vapi-voice-ai-agent'),
                                                esc_html($options['vapi_selected_assistant'])
                                            );
                                            ?>
                                        </option>
                                    <?php endif; ?>
                                </select>
                                <button type="button" id="vapi-assistant-copy" class="vapi-button secondary small" disabled aria-label="<?php esc_attr_e('Copy assistant ID', 'vapi-voice-ai-agent'); ?>">
                                    <span class="dashicons dashicons-clipboard"></span>
                                    <?php esc_html_e('Copy ID', 'vapi-voice-ai-agent'); ?>
                                </button>
                            </div>
                            <p class="vapi-form-description" id="vapi-assistant-loading" style="margin-top:0.75rem; display:none;"></p>
                            <div id="vapi-assistant-error" class="vapi-alert error" style="display:none; margin-top:1rem;"></div>

                            <div id="vapi-assistant-details" class="vapi-assistant-meta-grid">
                                <div class="vapi-assistant-meta-card">
                                    <h4><?php esc_html_e('Model', 'vapi-voice-ai-agent'); ?></h4>
                                    <p id="vapi-assistant-model">—</p>
                                </div>
                                <div class="vapi-assistant-meta-card">
                                    <h4><?php esc_html_e('Transcriber', 'vapi-voice-ai-agent'); ?></h4>
                                    <p id="vapi-assistant-transcriber">—</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="vapi-card">
                        <div class="vapi-card-header">
                            <h2 class="vapi-card-title">
                                <span class="dashicons dashicons-admin-generic"></span>
                                <?php esc_html_e('Conversation Defaults', 'vapi-voice-ai-agent'); ?>
                            </h2>
                            <p class="vapi-card-subtitle"><?php esc_html_e('Adjust the messages your assistant uses at key call moments.', 'vapi-voice-ai-agent'); ?></p>
                        </div>
                        <div class="vapi-card-body">
                            <table class="vapi-form-table">
                                <tr>
                                    <th scope="row"><label for="vapi_first_message"><?php esc_html_e('First Message', 'vapi-voice-ai-agent'); ?></label></th>
                                    <td>
                                        <input type="text" id="vapi_first_message" name="vapi_first_message"
                                               class="vapi-form-control"
                                               value="<?php echo esc_attr($options['vapi_first_message'] ?? ''); ?>"
                                               placeholder="<?php echo esc_attr__('Hello...', 'vapi-voice-ai-agent'); ?>" />
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><label for="vapi_end_call_message"><?php esc_html_e('End Call Message', 'vapi-voice-ai-agent'); ?></label></th>
                                    <td>
                                        <input type="text" id="vapi_end_call_message" name="vapi_end_call_message"
                                               class="vapi-form-control"
                                               value="<?php echo esc_attr($options['vapi_end_call_message'] ?? ''); ?>"
                                               placeholder="<?php echo esc_attr__('Goodbye.', 'vapi-voice-ai-agent'); ?>" />
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><label for="vapi_voicemail_message"><?php esc_html_e('Voicemail Message', 'vapi-voice-ai-agent'); ?></label></th>
                                    <td>
                                        <input type="text" id="vapi_voicemail_message" name="vapi_voicemail_message"
                                               class="vapi-form-control"
                                               value="<?php echo esc_attr($options['vapi_voicemail_message'] ?? ''); ?>"
                                               placeholder="<?php echo esc_attr__('Please call back when you\'re available.', 'vapi-voice-ai-agent'); ?>" />
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><label for="vapi_system_prompt"><?php esc_html_e('System Prompt', 'vapi-voice-ai-agent'); ?></label></th>
                                    <td>
                                        <textarea id="vapi_system_prompt" name="vapi_system_prompt"
                                                  rows="8" class="vapi-form-control" placeholder="<?php echo esc_attr__('Define the assistant\'s behaviour and persona...', 'vapi-voice-ai-agent'); ?>"><?php echo esc_textarea($options['vapi_system_prompt'] ?? ''); ?></textarea>
                                    </td>
                                </tr>
                            </table>

                            <div class="vapi-form-footer">
                                <button type="submit" class="vapi-button">
                                    <span class="dashicons dashicons-yes"></span>
                                    <?php esc_html_e('Save Assistant Defaults', 'vapi-voice-ai-agent'); ?>
                                </button>
                            </div>
                        </div>
                    </div>
                </form>

                <div class="vapi-card">
                    <div class="vapi-card-header">
                        <h3 class="vapi-card-title">
                            <span class="dashicons dashicons-external"></span>
                            <?php esc_html_e('Quick Links', 'vapi-voice-ai-agent'); ?>
                        </h3>
                    </div>
                    <div class="vapi-card-body">
                        <ul style="margin-left: 20px;">
                            <li><a href="https://vapi.ai/dashboard" target="_blank" class="vapi-text-primary"><?php esc_html_e('Vapi Dashboard - Main Training Interface', 'vapi-voice-ai-agent'); ?></a></li>
                            <li><a href="https://docs.vapi.ai" target="_blank" class="vapi-text-primary"><?php esc_html_e('Vapi Documentation', 'vapi-voice-ai-agent'); ?></a></li>
                            <li><a href="https://docs.vapi.ai/assistants" target="_blank" class="vapi-text-primary"><?php esc_html_e('Assistant Configuration Guide', 'vapi-voice-ai-agent'); ?></a></li>
                        </ul>
                    </div>
                </div>

                <div class="vapi-card">
                    <div class="vapi-card-header">
                        <h3 class="vapi-card-title">
                            <span class="dashicons dashicons-lightbulb"></span>
                            <?php esc_html_e('Training Tips', 'vapi-voice-ai-agent'); ?>
                        </h3>
                    </div>
                    <div class="vapi-card-body">
                        <ul style="margin-left: 20px;">
                            <li><?php esc_html_e('Use clear, specific instructions in your assistant\'s system prompt', 'vapi-voice-ai-agent'); ?></li>
                            <li><?php esc_html_e('Test your assistant thoroughly with different types of questions', 'vapi-voice-ai-agent'); ?></li>
                            <li><?php esc_html_e('Consider your website\'s specific use case and target audience', 'vapi-voice-ai-agent'); ?></li>
                            <li><?php esc_html_e('Update your assistant\'s knowledge base regularly', 'vapi-voice-ai-agent'); ?></li>
                        </ul>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const testButton = document.getElementById('vapi-test-connection');
        const resultDiv = document.getElementById('vapi-test-result');

        if (testButton) {
            testButton.addEventListener('click', function() {
                resultDiv.innerHTML = '<p style="color: #0073aa;">Testing connection...</p>';

                const apiKey = document.getElementById('vapi_api_key').value;
                const assistantId = document.getElementById('vapi_assistant_id').value;

                if (!apiKey || !assistantId) {
                    resultDiv.innerHTML = '<p style="color: #d63638;">Please enter both API Key and Assistant ID before testing.</p>';
                    return;
                }

                const endpointUrl = new URL('<?php echo esc_url_raw(rest_url('vapi/v1/config')); ?>');
                endpointUrl.searchParams.set('_vapi_ts', Date.now().toString());

                fetch(endpointUrl.toString(), {
                    cache: 'no-store',
                    credentials: 'same-origin'
                })
                    .then(response => response.json())
                    .then(data => {
                        const configured = Boolean(data && (data.configured || (data.apiKey && data.assistant)));

                        if (configured) {
                            resultDiv.innerHTML = '<p style="color: #46b450;">✓ Configuration looks good! The voice button should work on your website.</p>';
                        } else {
                            resultDiv.innerHTML = '<p style="color: #d63638;">✗ Configuration incomplete. Please save your settings first.</p>';
                        }
                    })
                    .catch(error => {
                        resultDiv.innerHTML = '<p style="color: #d63638;">✗ Error testing connection. Please check your settings.</p>';
                    });
            });
        }

        // Color picker preview functionality for appearance tab
        const colorInputs = document.querySelectorAll('input[type="color"]');
        colorInputs.forEach(function(input) {
            input.addEventListener('change', function() {
                // Show a preview of the selected color
                const preview = this.nextElementSibling;
                if (preview && preview.classList.contains('description')) {
                    preview.style.backgroundColor = this.value;
                    preview.style.color = getContrastColor(this.value);
                    preview.style.padding = '5px';
                    preview.style.borderRadius = '3px';
                    preview.style.marginTop = '5px';
                }
            });
        });

        // Function to get contrasting text color
        function getContrastColor(hexColor) {
            const r = parseInt(hexColor.substr(1, 2), 16);
            const g = parseInt(hexColor.substr(3, 2), 16);
            const b = parseInt(hexColor.substr(5, 2), 16);
            const brightness = (r * 299 + g * 587 + b * 114) / 1000;
            return brightness > 128 ? '#000000' : '#ffffff';
        }
    });
    </script>
    <?php
}
