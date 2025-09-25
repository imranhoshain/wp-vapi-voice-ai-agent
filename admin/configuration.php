<?php
if (!defined('ABSPATH')) {
    exit;
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

    if (!isset($_GET['page']) || $_GET['page'] !== 'vapi_config') {
        return;
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return;
    }

    $active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'api';
    $debug_mode = isset($_GET['debug']) && $_GET['debug'] === '1';

    if ($active_tab === 'api' && isset($_POST['vapi_api_action'])) {
        vapi_handle_api_form_submission($debug_mode);
        return;
    }

    if ($active_tab === 'appearance' && isset($_POST['vapi_appearance_action'])) {
        vapi_handle_appearance_form_submission($debug_mode);
        return;
    }

    if ($active_tab === 'api_config' && isset($_POST['vapi_private_api_action'])) {
        vapi_handle_private_api_form_submission($debug_mode);
        return;
    }

    if ($active_tab === 'training' && isset($_POST['vapi_training_action'])) {
        vapi_handle_training_form_submission($debug_mode);
        return;
    }
}

/**
 * Handle API tab form submission.
 */
function vapi_handle_api_form_submission($debug_mode)
{
    if (!isset($_POST['vapi_api_nonce']) || !wp_verify_nonce($_POST['vapi_api_nonce'], 'vapi_api_action')) {
        add_settings_error('vapi_messages', 'vapi_api_nonce', __('Security verification failed. Please try again.', VAPI_TEXT_DOMAIN), 'error');
        return;
    }

    $options = get_option('vapi_settings', []);
    $options['vapi_api_key'] = sanitize_text_field(wp_unslash($_POST['vapi_api_key'] ?? ''));
    $options['vapi_assistant_id'] = sanitize_text_field(wp_unslash($_POST['vapi_assistant_id'] ?? ''));

    update_option('vapi_settings', $options);

    if ($debug_mode) {
        global $vapi_debug_logs;
        $vapi_debug_logs['api'] = [
            'posted' => $_POST,
            'options' => $options,
        ];
        add_settings_error('vapi_messages', 'vapi_api_saved', __('Settings saved successfully (debug mode, no redirect).', VAPI_TEXT_DOMAIN), 'success');
        return;
    }

    $redirect_args = ['page' => 'vapi_config', 'tab' => 'api', 'saved' => '1'];
    wp_safe_redirect(add_query_arg($redirect_args, admin_url('admin.php')));
    exit;
}

/**
 * Handle private API tab form submission.
 */
function vapi_handle_private_api_form_submission($debug_mode)
{
    if (!isset($_POST['vapi_private_api_nonce']) || !wp_verify_nonce($_POST['vapi_private_api_nonce'], 'vapi_private_api_action')) {
        add_settings_error('vapi_messages', 'vapi_private_api_nonce', __('Security verification failed. Please try again.', VAPI_TEXT_DOMAIN), 'error');
        return;
    }

    $options = get_option('vapi_settings', []);
    $options['vapi_private_api_key'] = sanitize_text_field(wp_unslash($_POST['vapi_private_api_key'] ?? ''));

    update_option('vapi_settings', $options);

    if ($debug_mode) {
        global $vapi_debug_logs;
        $vapi_debug_logs['private_api'] = [
            'posted' => $_POST,
            'options' => $options,
        ];
        add_settings_error('vapi_messages', 'vapi_private_api_saved', __('Private API key saved (debug mode, no redirect).', VAPI_TEXT_DOMAIN), 'success');
        return;
    }

    $redirect_args = ['page' => 'vapi_config', 'tab' => 'api_config', 'saved' => '1'];
    wp_safe_redirect(add_query_arg($redirect_args, admin_url('admin.php')));
    exit;
}

/**
 * Handle appearance tab form submission.
 */
function vapi_handle_appearance_form_submission($debug_mode)
{
    if (!isset($_POST['vapi_appearance_nonce']) || !wp_verify_nonce($_POST['vapi_appearance_nonce'], 'vapi_appearance_action')) {
        add_settings_error('vapi_messages', 'vapi_appearance_nonce', __('Security verification failed. Please try again.', VAPI_TEXT_DOMAIN), 'error');
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
        if (!isset($_POST[$field])) {
            continue;
        }

        $raw_value = wp_unslash($_POST[$field]);

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

    $options['vapi_button_fixed'] = isset($_POST['vapi_button_fixed']) ? 1 : 0;

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
            global $vapi_debug_logs, $wpdb;
            $changed_fields = [];
            foreach ($options as $key => $value) {
                if (!isset($old_options[$key]) || $old_options[$key] !== $value) {
                    $changed_fields[$key] = [
                        'old' => $old_options[$key] ?? 'NOT_SET',
                        'new' => $value,
                    ];
                }
            }

            $option_exists = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->options} WHERE option_name = %s", 'vapi_settings'));

            $vapi_debug_logs['appearance'] = [
                'submitted' => $options,
                'old' => $old_options,
                'new' => $new_options,
                'changed' => $changed_fields,
                'option_exists' => (bool) $option_exists,
            ];

            add_settings_error('vapi_messages', 'vapi_appearance_saved', __('Settings saved successfully (debug mode, no redirect).', VAPI_TEXT_DOMAIN), 'success');
            return;
        }

        $redirect_args = ['page' => 'vapi_config', 'tab' => 'appearance', 'saved' => '1'];
        wp_safe_redirect(add_query_arg($redirect_args, admin_url('admin.php')));
        exit;
    }

    add_settings_error('vapi_messages', 'vapi_appearance_failed', __('Failed to save appearance settings!', VAPI_TEXT_DOMAIN), 'error');
}

/**
 * Handle training tab form submission.
 */
function vapi_handle_training_form_submission($debug_mode)
{
    if (!isset($_POST['vapi_training_nonce']) || !wp_verify_nonce($_POST['vapi_training_nonce'], 'vapi_training_action')) {
        add_settings_error('vapi_messages', 'vapi_training_nonce', __('Security verification failed. Please try again.', VAPI_TEXT_DOMAIN), 'error');
        return;
    }

    $options = get_option('vapi_settings', []);
    if (isset($_POST['vapi_training_notes'])) {
        $options['vapi_training_notes'] = sanitize_textarea_field(wp_unslash($_POST['vapi_training_notes']));
    }

    if (isset($_POST['vapi_selected_assistant'])) {
        $options['vapi_selected_assistant'] = sanitize_text_field(wp_unslash($_POST['vapi_selected_assistant']));
    }

    if (isset($_POST['vapi_first_message'])) {
        $options['vapi_first_message'] = sanitize_text_field(wp_unslash($_POST['vapi_first_message']));
    }

    if (isset($_POST['vapi_end_call_message'])) {
        $options['vapi_end_call_message'] = sanitize_text_field(wp_unslash($_POST['vapi_end_call_message']));
    }

    if (isset($_POST['vapi_voicemail_message'])) {
        $options['vapi_voicemail_message'] = sanitize_text_field(wp_unslash($_POST['vapi_voicemail_message']));
    }

    if (isset($_POST['vapi_system_prompt'])) {
        $options['vapi_system_prompt'] = sanitize_textarea_field(wp_unslash($_POST['vapi_system_prompt']));
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
            add_settings_error('vapi_messages', 'vapi_training_saved', __('Assistant defaults saved (debug mode, no redirect).', VAPI_TEXT_DOMAIN), 'success');
            return;
        }

        $redirect_args = ['page' => 'vapi_config', 'tab' => 'training', 'saved' => '1'];
        wp_safe_redirect(add_query_arg($redirect_args, admin_url('admin.php')));
        exit;
    }

    add_settings_error('vapi_messages', 'vapi_training_failed', __('Failed to save assistant defaults!', VAPI_TEXT_DOMAIN), 'error');
}

function vapi_config_page()
{
    $active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'api';

    // Debug mode toggle
    $debug_mode = isset($_GET['debug']) && $_GET['debug'] === '1';

    // Show success message if redirected after save
    if (isset($_GET['saved']) && $_GET['saved'] === '1') {
        echo '<div class="notice notice-success is-dismissible"><p>' . __('Settings saved successfully!', VAPI_TEXT_DOMAIN) . '</p></div>';
    }

    settings_errors('vapi_messages');

    // Debug information
    if ($debug_mode) {
        global $vapi_debug_logs;
        echo '<div class="notice notice-info"><p><strong>DEBUG MODE ACTIVE</strong></p></div>';
        echo '<div class="notice notice-info">';
        echo '<p><strong>Request Method:</strong> ' . $_SERVER['REQUEST_METHOD'] . '</p>';
        echo '<p><strong>Active Tab:</strong> ' . $active_tab . '</p>';
        if (!empty($_POST)) {
            echo '<p><strong>POST Data:</strong><br><pre>' . print_r($_POST, true) . '</pre></p>';
        }
        $current_options = get_option('vapi_settings', []);
        echo '<p><strong>Current DB Options:</strong><br><pre>' . print_r($current_options, true) . '</pre></p>';

        if (!empty($vapi_debug_logs)) {
            echo '<p><strong>Captured Debug Logs:</strong><br><pre>' . print_r($vapi_debug_logs, true) . '</pre></p>';
        }
        echo '</div>';
    }

    // Get options AFTER processing forms to ensure updated values are displayed
    $options = get_option('vapi_settings', []);
    $is_configured = !empty($options['vapi_api_key']) && !empty($options['vapi_assistant_id']);
    $button_position = $options['vapi_button_position'] ?? 'bottom-right';
    $button_position_label = ucwords(str_replace('-', ' ', $button_position));
    $system_prompt_length = strlen($options['vapi_system_prompt'] ?? '');
    ?>
    <div class="wrap vapi-admin-page">
        <!-- Modern Header -->
        <div class="vapi-header">
            <div class="vapi-header-content">
                <span class="vapi-chip <?php echo $is_configured ? 'success' : 'warning'; ?>">
                    <?php echo esc_html($is_configured ? __('Status: Connected', VAPI_TEXT_DOMAIN) : __('Status: Needs configuration', VAPI_TEXT_DOMAIN)); ?>
                </span>
                <h1>
                    <div class="vapi-header-icon">
                        <span class="dashicons dashicons-admin-settings"></span>
                    </div>
                    <?php _e('Vapi Configuration', VAPI_TEXT_DOMAIN); ?>
                </h1>
                <p><?php _e('Configure your voice assistant settings and appearance', VAPI_TEXT_DOMAIN); ?></p>

                <div class="vapi-hero-meta">
                    <div class="vapi-hero-meta-item">
                        <span><?php _e('Voice AI key', VAPI_TEXT_DOMAIN); ?></span>
                        <strong><?php echo esc_html(!empty($options['vapi_api_key']) ? __('Connected', VAPI_TEXT_DOMAIN) : __('Missing', VAPI_TEXT_DOMAIN)); ?></strong>
                    </div>
                    <div class="vapi-hero-meta-item">
                        <span><?php _e('Assistant ID', VAPI_TEXT_DOMAIN); ?></span>
                        <strong><?php echo esc_html(!empty($options['vapi_assistant_id']) ? __('Assigned', VAPI_TEXT_DOMAIN) : __('Not set', VAPI_TEXT_DOMAIN)); ?></strong>
                    </div>
                    <div class="vapi-hero-meta-item">
                        <span><?php _e('Private API key', VAPI_TEXT_DOMAIN); ?></span>
                        <strong><?php echo esc_html(!empty($options['vapi_private_api_key']) ? __('Stored', VAPI_TEXT_DOMAIN) : __('Missing', VAPI_TEXT_DOMAIN)); ?></strong>
                    </div>
                    <div class="vapi-hero-meta-item">
                        <span><?php _e('Button position', VAPI_TEXT_DOMAIN); ?></span>
                        <strong><?php echo esc_html($button_position_label); ?></strong>
                    </div>
                    <div class="vapi-hero-meta-item">
                        <span><?php _e('System prompt', VAPI_TEXT_DOMAIN); ?></span>
                        <strong><?php echo esc_html($system_prompt_length ? __('Configured', VAPI_TEXT_DOMAIN) : __('Empty', VAPI_TEXT_DOMAIN)); ?></strong>
                    </div>
                </div>
            </div>
        </div>

        <div class="vapi-content">
            <!-- Modern Navigation Tabs -->
            <div class="vapi-nav-tabs">
                <a class="vapi-nav-tab <?php echo $active_tab === 'api' ? 'active' : ''; ?>" href="?page=vapi_config&tab=api">
                    <span class="dashicons dashicons-admin-network"></span>
                    <?php _e('Voice AI Settings', VAPI_TEXT_DOMAIN); ?>
                </a>
                <a class="vapi-nav-tab <?php echo $active_tab === 'api_config' ? 'active' : ''; ?>" href="?page=vapi_config&tab=api_config">
                    <span class="dashicons dashicons-lock"></span>
                    <?php _e('API Configuration', VAPI_TEXT_DOMAIN); ?>
                </a>
                <a class="vapi-nav-tab <?php echo $active_tab === 'appearance' ? 'active' : ''; ?>" href="?page=vapi_config&tab=appearance">
                    <span class="dashicons dashicons-admin-appearance"></span>
                    <?php _e('Appearance', VAPI_TEXT_DOMAIN); ?>
                </a>
                <a class="vapi-nav-tab <?php echo $active_tab === 'training' ? 'active' : ''; ?>" href="?page=vapi_config&tab=training">
                    <span class="dashicons dashicons-welcome-learn-more"></span>
                    <?php _e('Training', VAPI_TEXT_DOMAIN); ?>
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
                                <?php _e('Voice AI Settings', VAPI_TEXT_DOMAIN); ?>
                            </h2>
                            <p class="vapi-card-subtitle"><?php _e('Enter your Voice AI settings credentials to connect your voice assistant. You can find these in your Vapi dashboard.', VAPI_TEXT_DOMAIN); ?></p>
                        </div>
                        <div class="vapi-card-body">
                            <table class="vapi-form-table">
                            <tr>
                                <th scope="row">
                                    <label for="vapi_api_key"><?php _e('Public API Key', VAPI_TEXT_DOMAIN); ?> <span style="color: red;">*</span></label>
                                </th>
                                <td>
                                    <input type="password" id="vapi_api_key" name="vapi_api_key"
                                           value="<?php echo esc_attr($options['vapi_api_key'] ?? ''); ?>"
                                           class="vapi-form-control large" placeholder="sk-..." required />
                                    <p class="vapi-form-description">
                                        <?php _e('Your Vapi API key from', VAPI_TEXT_DOMAIN); ?>
                                        <a href="https://vapi.ai/dashboard" target="_blank"><?php _e('Vapi Dashboard', VAPI_TEXT_DOMAIN); ?></a>
                                    </p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="vapi_assistant_id"><?php _e('Assistant ID', VAPI_TEXT_DOMAIN); ?> <span style="color: red;">*</span></label>
                                </th>
                                <td>
                                    <input type="text" id="vapi_assistant_id" name="vapi_assistant_id"
                                           value="<?php echo esc_attr($options['vapi_assistant_id'] ?? ''); ?>"
                                           class="vapi-form-control large" placeholder="assistant_..." required />
                                    <p class="vapi-form-description">
                                        <?php _e('The ID of your voice assistant from your Vapi dashboard', VAPI_TEXT_DOMAIN); ?>
                                    </p>
                                </td>
                            </tr>
                        </table>

                        </div>
                        <div class="vapi-card-footer">
                            <div class="vapi-alert info">
                                <span class="dashicons dashicons-info"></span>
                                <div>
                                    <h4><?php _e('Connection Test', VAPI_TEXT_DOMAIN); ?></h4>
                                    <p><?php _e('After saving your credentials, the voice button will appear on your website if the configuration is correct.', VAPI_TEXT_DOMAIN); ?></p>
                                    <button type="button" id="vapi-test-connection" class="vapi-button secondary vapi-mt-2">
                                        <span class="dashicons dashicons-admin-tools"></span>
                                        <?php _e('Test Connection', VAPI_TEXT_DOMAIN); ?>
                                    </button>
                                    <div id="vapi-test-result" class="vapi-mt-2"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="vapi-form-footer">
                        <button type="submit" class="vapi-button">
                            <span class="dashicons dashicons-yes"></span>
                            <?php _e('Save Voice AI Settings', VAPI_TEXT_DOMAIN); ?>
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
                                <?php _e('API Configuration', VAPI_TEXT_DOMAIN); ?>
                            </h2>
                            <p class="vapi-card-subtitle"><?php _e('Store your private API key for secure server-side requests such as listing assistants or orchestrating calls.', VAPI_TEXT_DOMAIN); ?></p>
                        </div>
                        <div class="vapi-card-body">
                            <table class="vapi-form-table">
                                <tr>
                                    <th scope="row">
                                        <label for="vapi_private_api_key"><?php _e('Private API Key', VAPI_TEXT_DOMAIN); ?> <span style="color: red;">*</span></label>
                                    </th>
                                    <td>
                                        <input type="password" id="vapi_private_api_key" name="vapi_private_api_key"
                                               value="<?php echo esc_attr($options['vapi_private_api_key'] ?? ''); ?>"
                                               class="vapi-form-control large" placeholder="45652c35-8383-4b46-91a4-46bbe94e7eaf" required />
                                        <p class="vapi-form-description">
                                            <?php _e('Use this server-side key to call protected endpoints, for example:', VAPI_TEXT_DOMAIN); ?><br />
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
                            <?php _e('Save Private API Key', VAPI_TEXT_DOMAIN); ?>
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
                                <?php _e('Button Positioning', VAPI_TEXT_DOMAIN); ?>
                            </h2>
                            <p class="vapi-card-subtitle"><?php _e('Configure where the voice button appears on your website', VAPI_TEXT_DOMAIN); ?></p>
                        </div>
                        <div class="vapi-card-body">
                            <div class="vapi-alert info">
                                <span class="dashicons dashicons-info"></span>
                                <div>
                                    <h4><?php _e('Icon tips', VAPI_TEXT_DOMAIN); ?></h4>
                                    <p>
                                        <?php
                                        $icon_help = sprintf(
                                            __('Browse free SVG icons on the %1$s. Click an icon and open it in a new tab to copy the CDN URL (for example %2$s). You can also upload your own SVG to the Media Library and paste its URL below.', VAPI_TEXT_DOMAIN),
                                            '<a href="' . esc_url('https://app.unpkg.com/lucide-static@0.544.0/files/icons') . '" target="_blank" rel="noopener noreferrer">' . esc_html__('Lucide icons page', VAPI_TEXT_DOMAIN) . '</a>',
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
                                <th scope="row"><label for="vapi_button_position"><?php _e('Button Position', VAPI_TEXT_DOMAIN); ?></label></th>
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
                                    <p class="vapi-form-description"><?php _e('Choose where the voice button appears on your website', VAPI_TEXT_DOMAIN); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="vapi_button_fixed"><?php _e('Fixed Position', VAPI_TEXT_DOMAIN); ?></label></th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="vapi_button_fixed" value="1"
                                               <?php checked(isset($options['vapi_button_fixed']) ? $options['vapi_button_fixed'] : 0, 1); ?> />
                                        <?php _e('Button follows scroll (fixed position)', VAPI_TEXT_DOMAIN); ?>
                                    </label>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="vapi_button_offset"><?php _e('Button Offset', VAPI_TEXT_DOMAIN); ?></label></th>
                                <td>
                                    <input type="text" name="vapi_button_offset"
                                           value="<?php echo esc_attr($options['vapi_button_offset'] ?? '40px'); ?>"
                                           placeholder="40px" class="vapi-form-control" />
                                    <p class="vapi-form-description"><?php _e('Distance from edge (e.g., 40px, 2rem)', VAPI_TEXT_DOMAIN); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="vapi_button_width"><?php _e('Button Width', VAPI_TEXT_DOMAIN); ?></label></th>
                                <td>
                                    <input type="text" name="vapi_button_width"
                                           value="<?php echo esc_attr($options['vapi_button_width'] ?? '50px'); ?>"
                                           placeholder="50px" class="vapi-form-control" />
                                    <p class="vapi-form-description"><?php _e('Width of the voice button', VAPI_TEXT_DOMAIN); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="vapi_button_height"><?php _e('Button Height', VAPI_TEXT_DOMAIN); ?></label></th>
                                <td>
                                    <input type="text" name="vapi_button_height"
                                           value="<?php echo esc_attr($options['vapi_button_height'] ?? '50px'); ?>"
                                           placeholder="50px" class="vapi-form-control" />
                                    <p class="vapi-form-description"><?php _e('Height of the voice button', VAPI_TEXT_DOMAIN); ?></p>
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
                                    <th scope="row"><label><?php _e('Color', VAPI_TEXT_DOMAIN); ?></label></th>
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
                                        <input type="color" name="vapi_<?php echo $state; ?>_color"
                                               value="<?php echo esc_attr($color_value); ?>" class="vapi-color-picker" />
                                        <p class="vapi-form-description"><?php _e('Color for the button in this state', VAPI_TEXT_DOMAIN); ?></p>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><label><?php _e('Title', VAPI_TEXT_DOMAIN); ?></label></th>
                                    <td>
                                        <input type="text" name="vapi_<?php echo $state; ?>_title"
                                               value="<?php echo esc_attr($options["vapi_{$state}_title"] ?? ''); ?>"
                                               class="vapi-form-control" placeholder="<?php _e('Button title text', VAPI_TEXT_DOMAIN); ?>" />
                                        <p class="vapi-form-description"><?php _e('Text displayed on the button', VAPI_TEXT_DOMAIN); ?></p>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><label><?php _e('Subtitle', VAPI_TEXT_DOMAIN); ?></label></th>
                                    <td>
                                        <input type="text" name="vapi_<?php echo $state; ?>_subtitle"
                                               value="<?php echo esc_attr($options["vapi_{$state}_subtitle"] ?? ''); ?>"
                                               class="vapi-form-control" placeholder="<?php _e('Optional subtitle', VAPI_TEXT_DOMAIN); ?>" />
                                        <p class="vapi-form-description"><?php _e('Additional text below the title (optional)', VAPI_TEXT_DOMAIN); ?></p>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><label><?php _e('Icon URL', VAPI_TEXT_DOMAIN); ?></label></th>
                                    <td>
                                        <input type="url" name="vapi_<?php echo $state; ?>_icon"
                                               value="<?php echo esc_attr($options["vapi_{$state}_icon"] ?? ''); ?>"
                                               class="vapi-form-control" placeholder="https://example.com/icon.svg" />
                                        <p class="vapi-form-description"><?php _e('Paste the SVG URL for this state (Lucide CDN link or one from your Media Library).', VAPI_TEXT_DOMAIN); ?></p>
                                    </td>
                                </tr>
                                </table>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <div class="vapi-form-footer">
                        <button type="submit" class="vapi-button">
                            <span class="dashicons dashicons-art"></span>
                            <?php _e('Save Appearance Settings', VAPI_TEXT_DOMAIN); ?>
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
                                <?php _e('Assistant Library', VAPI_TEXT_DOMAIN); ?>
                            </h2>
                            <p class="vapi-card-subtitle"><?php _e('Fetch assistants from your Vapi account using the private API key and review their conversation defaults.', VAPI_TEXT_DOMAIN); ?></p>
                        </div>
                        <div class="vapi-card-body">
                            <label for="vapi_assistant_selector" class="vapi-form-description" style="padding-left:0;">
                                <?php _e('Choose an assistant to inspect its configuration', VAPI_TEXT_DOMAIN); ?>
                            </label>
                            <div class="vapi-assistant-select-row">
                                <select id="vapi_assistant_selector" name="vapi_selected_assistant"
                                        class="vapi-form-control"
                                        data-selected="<?php echo esc_attr($options['vapi_selected_assistant'] ?? ''); ?>">
                                    <option value=""><?php _e('Select an assistant', VAPI_TEXT_DOMAIN); ?></option>
                                    <?php if (!empty($options['vapi_selected_assistant'])): ?>
                                        <option value="<?php echo esc_attr($options['vapi_selected_assistant']); ?>" selected>
                                            <?php printf(esc_html__('Assistant %s (loading details...)', VAPI_TEXT_DOMAIN), esc_html($options['vapi_selected_assistant'])); ?>
                                        </option>
                                    <?php endif; ?>
                                </select>
                                <button type="button" id="vapi-assistant-copy" class="vapi-button secondary small" disabled aria-label="<?php esc_attr_e('Copy assistant ID', VAPI_TEXT_DOMAIN); ?>">
                                    <span class="dashicons dashicons-clipboard"></span>
                                    <?php _e('Copy ID', VAPI_TEXT_DOMAIN); ?>
                                </button>
                            </div>
                            <p class="vapi-form-description" id="vapi-assistant-loading" style="margin-top:0.75rem; display:none;"></p>
                            <div id="vapi-assistant-error" class="vapi-alert error" style="display:none; margin-top:1rem;"></div>

                            <div id="vapi-assistant-details" class="vapi-assistant-meta-grid">
                                <div class="vapi-assistant-meta-card">
                                    <h4><?php _e('Model', VAPI_TEXT_DOMAIN); ?></h4>
                                    <p id="vapi-assistant-model">—</p>
                                </div>
                                <div class="vapi-assistant-meta-card">
                                    <h4><?php _e('Transcriber', VAPI_TEXT_DOMAIN); ?></h4>
                                    <p id="vapi-assistant-transcriber">—</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="vapi-card">
                        <div class="vapi-card-header">
                            <h2 class="vapi-card-title">
                                <span class="dashicons dashicons-admin-generic"></span>
                                <?php _e('Conversation Defaults', VAPI_TEXT_DOMAIN); ?>
                            </h2>
                            <p class="vapi-card-subtitle"><?php _e('Adjust the messages your assistant uses at key call moments.', VAPI_TEXT_DOMAIN); ?></p>
                        </div>
                        <div class="vapi-card-body">
                            <table class="vapi-form-table">
                                <tr>
                                    <th scope="row"><label for="vapi_first_message"><?php _e('First Message', VAPI_TEXT_DOMAIN); ?></label></th>
                                    <td>
                                        <input type="text" id="vapi_first_message" name="vapi_first_message"
                                               class="vapi-form-control"
                                               value="<?php echo esc_attr($options['vapi_first_message'] ?? ''); ?>"
                                               placeholder="<?php _e('Hello...', VAPI_TEXT_DOMAIN); ?>" />
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><label for="vapi_end_call_message"><?php _e('End Call Message', VAPI_TEXT_DOMAIN); ?></label></th>
                                    <td>
                                        <input type="text" id="vapi_end_call_message" name="vapi_end_call_message"
                                               class="vapi-form-control"
                                               value="<?php echo esc_attr($options['vapi_end_call_message'] ?? ''); ?>"
                                               placeholder="<?php _e('Goodbye.', VAPI_TEXT_DOMAIN); ?>" />
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><label for="vapi_voicemail_message"><?php _e('Voicemail Message', VAPI_TEXT_DOMAIN); ?></label></th>
                                    <td>
                                        <input type="text" id="vapi_voicemail_message" name="vapi_voicemail_message"
                                               class="vapi-form-control"
                                               value="<?php echo esc_attr($options['vapi_voicemail_message'] ?? ''); ?>"
                                               placeholder="<?php _e('Please call back when you\'re available.', VAPI_TEXT_DOMAIN); ?>" />
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><label for="vapi_system_prompt"><?php _e('System Prompt', VAPI_TEXT_DOMAIN); ?></label></th>
                                    <td>
                                        <textarea id="vapi_system_prompt" name="vapi_system_prompt"
                                                  rows="8" class="vapi-form-control" placeholder="<?php _e('Define the assistant\'s behaviour and persona...', VAPI_TEXT_DOMAIN); ?>"><?php echo esc_textarea($options['vapi_system_prompt'] ?? ''); ?></textarea>
                                    </td>
                                </tr>
                            </table>

                            <div class="vapi-form-footer">
                                <button type="submit" class="vapi-button">
                                    <span class="dashicons dashicons-yes"></span>
                                    <?php _e('Save Assistant Defaults', VAPI_TEXT_DOMAIN); ?>
                                </button>
                            </div>
                        </div>
                    </div>
                </form>

                <div class="vapi-card">
                    <div class="vapi-card-header">
                        <h3 class="vapi-card-title">
                            <span class="dashicons dashicons-external"></span>
                            <?php _e('Quick Links', VAPI_TEXT_DOMAIN); ?>
                        </h3>
                    </div>
                    <div class="vapi-card-body">
                        <ul style="margin-left: 20px;">
                            <li><a href="https://vapi.ai/dashboard" target="_blank" class="vapi-text-primary"><?php _e('Vapi Dashboard - Main Training Interface', VAPI_TEXT_DOMAIN); ?></a></li>
                            <li><a href="https://docs.vapi.ai" target="_blank" class="vapi-text-primary"><?php _e('Vapi Documentation', VAPI_TEXT_DOMAIN); ?></a></li>
                            <li><a href="https://docs.vapi.ai/assistants" target="_blank" class="vapi-text-primary"><?php _e('Assistant Configuration Guide', VAPI_TEXT_DOMAIN); ?></a></li>
                        </ul>
                    </div>
                </div>

                <div class="vapi-card">
                    <div class="vapi-card-header">
                        <h3 class="vapi-card-title">
                            <span class="dashicons dashicons-lightbulb"></span>
                            <?php _e('Training Tips', VAPI_TEXT_DOMAIN); ?>
                        </h3>
                    </div>
                    <div class="vapi-card-body">
                        <ul style="margin-left: 20px;">
                            <li><?php _e('Use clear, specific instructions in your assistant\'s system prompt', VAPI_TEXT_DOMAIN); ?></li>
                            <li><?php _e('Test your assistant thoroughly with different types of questions', VAPI_TEXT_DOMAIN); ?></li>
                            <li><?php _e('Consider your website\'s specific use case and target audience', VAPI_TEXT_DOMAIN); ?></li>
                            <li><?php _e('Update your assistant\'s knowledge base regularly', VAPI_TEXT_DOMAIN); ?></li>
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

                fetch('<?php echo esc_url_raw(rest_url('vapi/v1/config')); ?>')
                    .then(response => response.json())
                    .then(data => {
                        if (data.apiKey && data.assistant) {
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
