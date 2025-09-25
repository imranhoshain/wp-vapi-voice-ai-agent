<?php
if (!defined('ABSPATH')) {
    exit;
}

function vapi_dashboard_page()
{
    $options = get_option('vapi_settings', []);
    $api_key = !empty($options['vapi_api_key']);
    $assistant_id = !empty($options['vapi_assistant_id']);
    $is_configured = $api_key && $assistant_id;
    $button_position = $options['vapi_button_position'] ?? 'bottom-right';
    $button_position_label = ucwords(str_replace('-', ' ', $button_position));
    $button_dimensions = trim(($options['vapi_button_width'] ?? '50px') . ' × ' . ($options['vapi_button_height'] ?? '50px'));
    $training_notes = $options['vapi_training_notes'] ?? '';
    $training_characters = strlen($training_notes);
    $plugin_version = defined('VAPI_PLUGIN_VERSION') ? VAPI_PLUGIN_VERSION : '1.0.0';
    $fixed_position = !empty($options['vapi_button_fixed']);
    $wp_version = get_bloginfo('version');

    $api_status_text = $api_key ? __('Connected', VAPI_TEXT_DOMAIN) : __('Pending', VAPI_TEXT_DOMAIN);
    $assistant_status_text = $assistant_id ? __('Configured', VAPI_TEXT_DOMAIN) : __('Not set', VAPI_TEXT_DOMAIN);
    $button_style_summary = sprintf(
        __('Customised to %1$s with %2$s dimensions.', VAPI_TEXT_DOMAIN),
        strtolower($button_position_label),
        $button_dimensions
    );
    $training_count_display = $training_characters
        ? sprintf(_n('%d character', '%d characters', $training_characters, VAPI_TEXT_DOMAIN), $training_characters)
        : __('Empty', VAPI_TEXT_DOMAIN);
    $timeline_step_three_status = $fixed_position ? __('Optimised', VAPI_TEXT_DOMAIN) : __('Review', VAPI_TEXT_DOMAIN);
    $timeline_step_four_status = $training_characters ? __('In progress', VAPI_TEXT_DOMAIN) : __('Start here', VAPI_TEXT_DOMAIN);
    ?>
    <div class="wrap vapi-admin-page">
        <div class="vapi-header">
            <div class="vapi-header-content">
                <span class="vapi-chip <?php echo $is_configured ? 'success' : 'warning'; ?>">
                    <?php echo esc_html($is_configured ? __('Status: Ready to greet visitors', VAPI_TEXT_DOMAIN) : __('Status: Action required', VAPI_TEXT_DOMAIN)); ?>
                </span>
                <h1>
                    <div class="vapi-header-icon">
                        <span class="dashicons dashicons-microphone"></span>
                    </div>
                    <?php esc_html_e('Vapi Voice AI Agent', VAPI_TEXT_DOMAIN); ?>
                </h1>
                <p><?php esc_html_e('Deliver a polished voice experience, monitor health, and keep your assistant aligned with your brand.', VAPI_TEXT_DOMAIN); ?></p>

                <div class="vapi-hero-meta">
                    <div class="vapi-hero-meta-item">
                        <span><?php esc_html_e('API connection', VAPI_TEXT_DOMAIN); ?></span>
                        <strong><?php echo esc_html($api_key ? __('Active', VAPI_TEXT_DOMAIN) : __('Pending', VAPI_TEXT_DOMAIN)); ?></strong>
                    </div>
                    <div class="vapi-hero-meta-item">
                        <span><?php esc_html_e('Assistant', VAPI_TEXT_DOMAIN); ?></span>
                        <strong><?php echo esc_html($assistant_id ? __('Configured', VAPI_TEXT_DOMAIN) : __('Not set', VAPI_TEXT_DOMAIN)); ?></strong>
                    </div>
                    <div class="vapi-hero-meta-item">
                        <span><?php esc_html_e('Button position', VAPI_TEXT_DOMAIN); ?></span>
                        <strong><?php echo esc_html($button_position_label); ?></strong>
                    </div>
                    <div class="vapi-hero-meta-item">
                        <span><?php esc_html_e('Plugin version', VAPI_TEXT_DOMAIN); ?></span>
                        <strong><?php echo esc_html($plugin_version); ?></strong>
                    </div>
                </div>
            </div>
        </div>

        <div class="vapi-content">
            <div class="vapi-grid vapi-grid-2">
                <div class="vapi-main-content">
                    <div class="vapi-card vapi-gradient-card vapi-mb-4">
                        <div class="vapi-card-body">
                            <div class="vapi-status <?php echo $is_configured ? 'success' : 'warning'; ?>">
                                <span class="dashicons <?php echo $is_configured ? 'dashicons-yes-alt' : 'dashicons-warning'; ?>"></span>
                                <?php echo esc_html($is_configured ? __('Your assistant is live and ready for conversations.', VAPI_TEXT_DOMAIN) : __('Complete the setup steps below to launch your assistant.', VAPI_TEXT_DOMAIN)); ?>
                            </div>

                            <p class="vapi-card-subtitle vapi-mt-3">
                                <?php echo esc_html($is_configured ? __('Nice! Everything is connected. Keep refining the experience to delight visitors.', VAPI_TEXT_DOMAIN) : __('Connect your Vapi API credentials and assign an assistant to unlock the full experience.', VAPI_TEXT_DOMAIN)); ?>
                            </p>

                            <div class="vapi-icon-list vapi-mt-3">
                                <div class="vapi-icon-list-item">
                                    <div class="vapi-icon">
                                        <span class="dashicons dashicons-admin-network"></span>
                                    </div>
                                    <div>
                                        <strong><?php esc_html_e('API Key', VAPI_TEXT_DOMAIN); ?></strong>
                                        <small><?php echo esc_html($api_key ? __('Securely stored in settings.', VAPI_TEXT_DOMAIN) : __('Add your secret key to start placing calls.', VAPI_TEXT_DOMAIN)); ?></small>
                                    </div>
                                </div>
                                <div class="vapi-icon-list-item">
                                    <div class="vapi-icon">
                                        <span class="dashicons dashicons-robot"></span>
                                    </div>
                                    <div>
                                        <strong><?php esc_html_e('Assistant ID', VAPI_TEXT_DOMAIN); ?></strong>
                                        <small><?php echo esc_html($assistant_id ? __('Linked to your Vapi project.', VAPI_TEXT_DOMAIN) : __('Provide the assistant ID you want to expose.', VAPI_TEXT_DOMAIN)); ?></small>
                                    </div>
                                </div>
                                <div class="vapi-icon-list-item">
                                    <div class="vapi-icon">
                                        <span class="dashicons dashicons-admin-appearance"></span>
                                    </div>
                                    <div>
                                        <strong><?php esc_html_e('Button styling', VAPI_TEXT_DOMAIN); ?></strong>
                                        <small><?php echo esc_html($button_style_summary); ?></small>
                                    </div>
                                </div>
                            </div>

                            <?php if (!$is_configured) : ?>
                                <div class="vapi-mt-3">
                                    <a href="<?php echo esc_url(admin_url('admin.php?page=vapi_config&tab=api')); ?>" class="vapi-button">
                                        <span class="dashicons dashicons-admin-network"></span>
                                        <?php esc_html_e('Complete API setup', VAPI_TEXT_DOMAIN); ?>
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="vapi-stats-grid">
                        <div class="vapi-stat-card">
                            <div class="vapi-stat-icon success">
                                <span class="dashicons dashicons-admin-network"></span>
                            </div>
                            <div class="vapi-stat-content">
                                <h3><?php esc_html_e('API status', VAPI_TEXT_DOMAIN); ?></h3>
                                <p class="vapi-stat-value <?php echo $api_key ? 'success' : 'warning'; ?>"><?php echo esc_html($api_status_text); ?></p>
                                <small class="vapi-text-muted"><?php echo esc_html($api_key ? __('We can reach Vapi services.', VAPI_TEXT_DOMAIN) : __('Provide your Vapi secret to continue.', VAPI_TEXT_DOMAIN)); ?></small>
                            </div>
                        </div>

                        <div class="vapi-stat-card">
                            <div class="vapi-stat-icon info">
                                <span class="dashicons dashicons-robot"></span>
                            </div>
                            <div class="vapi-stat-content">
                                <h3><?php esc_html_e('Assistant', VAPI_TEXT_DOMAIN); ?></h3>
                                <p class="vapi-stat-value <?php echo $assistant_id ? 'success' : 'warning'; ?>"><?php echo esc_html($assistant_status_text); ?></p>
                                <small class="vapi-text-muted"><?php echo esc_html($assistant_id ? __('Voice persona assigned.', VAPI_TEXT_DOMAIN) : __('Connect the voice persona you want to use.', VAPI_TEXT_DOMAIN)); ?></small>
                            </div>
                        </div>

                        <div class="vapi-stat-card">
                            <div class="vapi-stat-icon primary">
                                <span class="dashicons dashicons-admin-appearance"></span>
                            </div>
                            <div class="vapi-stat-content">
                                <h3><?php esc_html_e('Button position', VAPI_TEXT_DOMAIN); ?></h3>
                                <p class="vapi-stat-value"><?php echo esc_html($button_position_label); ?></p>
                                <small class="vapi-text-muted"><?php echo esc_html($fixed_position ? __('Fixed while scrolling', VAPI_TEXT_DOMAIN) : __('Static on page scroll', VAPI_TEXT_DOMAIN)); ?></small>
                            </div>
                        </div>

                        <div class="vapi-stat-card">
                            <div class="vapi-stat-icon warning">
                                <span class="dashicons dashicons-welcome-write-blog"></span>
                            </div>
                            <div class="vapi-stat-content">
                                <h3><?php esc_html_e('Training notes', VAPI_TEXT_DOMAIN); ?></h3>
                                <p class="vapi-stat-value <?php echo $training_characters ? 'success' : 'warning'; ?>"><?php echo esc_html($training_count_display); ?></p>
                                <small class="vapi-text-muted"><?php echo esc_html($training_characters ? __('Great! Keep refining prompts regularly.', VAPI_TEXT_DOMAIN) : __('Add guidance so your assistant knows how to act.', VAPI_TEXT_DOMAIN)); ?></small>
                            </div>
                        </div>
                    </div>

                    <div class="vapi-card">
                        <div class="vapi-card-header">
                            <h2 class="vapi-card-title">
                                <span class="dashicons dashicons-visibility"></span>
                                <?php esc_html_e('Configuration snapshot', VAPI_TEXT_DOMAIN); ?>
                            </h2>
                            <p class="vapi-card-subtitle"><?php esc_html_e('Review the current setup at a glance.', VAPI_TEXT_DOMAIN); ?></p>
                        </div>
                        <div class="vapi-card-body">
                            <div class="vapi-config-grid">
                                <div class="vapi-config-item">
                                    <div class="vapi-config-label">
                                        <span class="dashicons dashicons-admin-network"></span>
                                        <?php esc_html_e('API key', VAPI_TEXT_DOMAIN); ?>
                                    </div>
                                    <div class="vapi-config-value">
                                        <span class="vapi-pill <?php echo $api_key ? 'success' : 'warning'; ?>">
                                            <span class="dashicons <?php echo $api_key ? 'dashicons-yes-alt' : 'dashicons-warning'; ?>"></span>
                                            <?php echo esc_html($api_status_text); ?>
                                        </span>
                                    </div>
                                </div>

                                <div class="vapi-config-item">
                                    <div class="vapi-config-label">
                                        <span class="dashicons dashicons-robot"></span>
                                        <?php esc_html_e('Assistant ID', VAPI_TEXT_DOMAIN); ?>
                                    </div>
                                    <div class="vapi-config-value">
                                        <span class="vapi-pill <?php echo $assistant_id ? 'success' : 'warning'; ?>">
                                            <span class="dashicons <?php echo $assistant_id ? 'dashicons-yes-alt' : 'dashicons-warning'; ?>"></span>
                                            <?php echo esc_html($assistant_status_text); ?>
                                        </span>
                                    </div>
                                </div>

                                <div class="vapi-config-item">
                                    <div class="vapi-config-label">
                                        <span class="dashicons dashicons-admin-appearance"></span>
                                        <?php esc_html_e('Button appearance', VAPI_TEXT_DOMAIN); ?>
                                    </div>
                                    <div class="vapi-config-value">
                                        <div class="vapi-button-preview-mini" style="
                                            background-color: <?php echo esc_attr($options['vapi_idle_color'] ?? 'rgb(93, 254, 202)'); ?>;
                                            width: 28px;
                                            height: 28px;
                                            border-radius: 50%;
                                            display: inline-block;
                                            margin-right: 8px;
                                            vertical-align: middle;
                                            box-shadow: 0 0 0 3px rgba(255,255,255,0.6), 0 8px 15px -12px rgba(15,23,42,0.45);
                                        "></div>
                                        <span class="vapi-pill">
                                            <span class="dashicons dashicons-image-rotate"></span>
                                            <?php echo esc_html($button_dimensions); ?>
                                        </span>
                                    </div>
                                </div>

                                <div class="vapi-config-item">
                                    <div class="vapi-config-label">
                                        <span class="dashicons dashicons-migrate"></span>
                                        <?php esc_html_e('Scroll behaviour', VAPI_TEXT_DOMAIN); ?>
                                    </div>
                                    <div class="vapi-config-value">
                                        <?php if ($fixed_position) : ?>
                                            <span class="vapi-pill success">
                                                <span class="dashicons dashicons-yes"></span>
                                                <?php esc_html_e('Fixed enabled', VAPI_TEXT_DOMAIN); ?>
                                            </span>
                                        <?php else : ?>
                                            <span class="vapi-pill">
                                                <span class="dashicons dashicons-migrate"></span>
                                                <?php esc_html_e('Scrolls with page', VAPI_TEXT_DOMAIN); ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="vapi-config-item">
                                    <div class="vapi-config-label">
                                        <span class="dashicons dashicons-welcome-learn-more"></span>
                                        <?php esc_html_e('Training notes', VAPI_TEXT_DOMAIN); ?>
                                    </div>
                                    <div class="vapi-config-value">
                                        <?php if ($training_characters) : ?>
                                            <span class="vapi-pill success">
                                                <span class="dashicons dashicons-welcome-write-blog"></span>
                                                <?php echo esc_html($training_count_display); ?>
                                            </span>
                                        <?php else : ?>
                                            <span class="vapi-pill warning">
                                                <span class="dashicons dashicons-edit"></span>
                                                <?php esc_html_e('No notes yet', VAPI_TEXT_DOMAIN); ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="vapi-card">
                        <div class="vapi-card-header">
                            <h2 class="vapi-card-title">
                                <span class="dashicons dashicons-analytics"></span>
                                <?php esc_html_e('Integration journey', VAPI_TEXT_DOMAIN); ?>
                            </h2>
                            <p class="vapi-card-subtitle"><?php esc_html_e('Track the key milestones to keep your assistant responsive, accurate, and on brand.', VAPI_TEXT_DOMAIN); ?></p>
                        </div>
                        <div class="vapi-card-body">
                            <div class="vapi-timeline">
                                <div class="vapi-timeline-item">
                                    <div class="vapi-timeline-time"><?php esc_html_e('Step 01', VAPI_TEXT_DOMAIN); ?></div>
                                    <div class="vapi-timeline-title"><?php esc_html_e('Connect your Vapi API credentials', VAPI_TEXT_DOMAIN); ?></div>
                                    <p class="vapi-timeline-description"><?php echo esc_html($api_key ? __('Great! The plugin can talk to Vapi services.', VAPI_TEXT_DOMAIN) : __('Paste your secret API key from the Vapi dashboard to authenticate requests.', VAPI_TEXT_DOMAIN)); ?></p>
                                    <div class="vapi-mt-1">
                                        <span class="vapi-chip <?php echo $api_key ? 'success' : 'warning'; ?>"><?php echo esc_html($api_key ? __('Complete', VAPI_TEXT_DOMAIN) : __('Pending', VAPI_TEXT_DOMAIN)); ?></span>
                                    </div>
                                </div>
                                <div class="vapi-timeline-item">
                                    <div class="vapi-timeline-time"><?php esc_html_e('Step 02', VAPI_TEXT_DOMAIN); ?></div>
                                    <div class="vapi-timeline-title"><?php esc_html_e('Assign your assistant persona', VAPI_TEXT_DOMAIN); ?></div>
                                    <p class="vapi-timeline-description"><?php echo esc_html($assistant_id ? __('Assistant configured. You can switch personas anytime.', VAPI_TEXT_DOMAIN) : __('Add the assistant ID you want to embed for visitors.', VAPI_TEXT_DOMAIN)); ?></p>
                                    <div class="vapi-mt-1">
                                        <span class="vapi-chip <?php echo $assistant_id ? 'success' : 'warning'; ?>"><?php echo esc_html($assistant_id ? __('Complete', VAPI_TEXT_DOMAIN) : __('Pending', VAPI_TEXT_DOMAIN)); ?></span>
                                    </div>
                                </div>
                                <div class="vapi-timeline-item">
                                    <div class="vapi-timeline-time"><?php esc_html_e('Step 03', VAPI_TEXT_DOMAIN); ?></div>
                                    <div class="vapi-timeline-title"><?php esc_html_e('Fine-tune look and placement', VAPI_TEXT_DOMAIN); ?></div>
                                    <p class="vapi-timeline-description"><?php echo esc_html($fixed_position ? __('Button is fixed and styled with your brand colours.', VAPI_TEXT_DOMAIN) : __('Consider fixing the button for higher discoverability.', VAPI_TEXT_DOMAIN)); ?></p>
                                    <div class="vapi-mt-1">
                                        <span class="vapi-chip <?php echo $fixed_position ? 'success' : 'warning'; ?>"><?php echo esc_html($timeline_step_three_status); ?></span>
                                    </div>
                                </div>
                                <div class="vapi-timeline-item">
                                    <div class="vapi-timeline-time"><?php esc_html_e('Step 04', VAPI_TEXT_DOMAIN); ?></div>
                                    <div class="vapi-timeline-title"><?php esc_html_e('Keep training the conversation', VAPI_TEXT_DOMAIN); ?></div>
                                    <p class="vapi-timeline-description"><?php echo esc_html($training_characters ? __('Nice! Keep iterating prompts when you add new offerings.', VAPI_TEXT_DOMAIN) : __('Use the training notes tab to give your assistant context and brand tone.', VAPI_TEXT_DOMAIN)); ?></p>
                                    <div class="vapi-mt-1">
                                        <span class="vapi-chip <?php echo $training_characters ? 'success' : 'warning'; ?>"><?php echo esc_html($timeline_step_four_status); ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="vapi-sidebar">
                    <div class="vapi-card">
                        <div class="vapi-card-header">
                            <h3 class="vapi-card-title">
                                <span class="dashicons dashicons-performance"></span>
                                <?php esc_html_e('Quick actions', VAPI_TEXT_DOMAIN); ?>
                            </h3>
                            <p class="vapi-card-subtitle"><?php esc_html_e('Jump straight into the areas you manage most often.', VAPI_TEXT_DOMAIN); ?></p>
                        </div>
                        <div class="vapi-card-body">
                            <div class="vapi-resource-links">
                                <a href="<?php echo esc_url(admin_url('admin.php?page=vapi_config')); ?>" class="vapi-resource-link">
                                    <span class="dashicons dashicons-admin-settings"></span>
                                    <div>
                                        <strong><?php esc_html_e('Configuration', VAPI_TEXT_DOMAIN); ?></strong>
                                        <small><?php esc_html_e('Tune API, appearance, and training details.', VAPI_TEXT_DOMAIN); ?></small>
                                    </div>
                                </a>
                                <a href="<?php echo esc_url(admin_url('admin.php?page=vapi_tools')); ?>" class="vapi-resource-link">
                                    <span class="dashicons dashicons-admin-tools"></span>
                                    <div>
                                        <strong><?php esc_html_e('Diagnostics & tools', VAPI_TEXT_DOMAIN); ?></strong>
                                        <small><?php esc_html_e('Run resets, export settings, or test connectivity.', VAPI_TEXT_DOMAIN); ?></small>
                                    </div>
                                </a>
                                <a href="<?php echo esc_url(home_url()); ?>" target="_blank" class="vapi-resource-link">
                                    <span class="dashicons dashicons-external"></span>
                                    <div>
                                        <strong><?php esc_html_e('Preview on site', VAPI_TEXT_DOMAIN); ?></strong>
                                        <small><?php esc_html_e('Open your homepage with the assistant enabled.', VAPI_TEXT_DOMAIN); ?></small>
                                    </div>
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="vapi-card">
                        <div class="vapi-card-header">
                            <h3 class="vapi-card-title">
                                <span class="dashicons dashicons-shield"></span>
                                <?php esc_html_e('Environment snapshot', VAPI_TEXT_DOMAIN); ?>
                            </h3>
                        </div>
                        <div class="vapi-card-body">
                            <div class="vapi-icon-list">
                                <div class="vapi-icon-list-item">
                                    <div class="vapi-icon">
                                        <span class="dashicons dashicons-admin-network"></span>
                                    </div>
                                    <div>
                                        <strong><?php esc_html_e('API connectivity', VAPI_TEXT_DOMAIN); ?></strong>
                                        <small><?php echo esc_html($api_key ? __('Secure token present.', VAPI_TEXT_DOMAIN) : __('Missing secret – add credentials.', VAPI_TEXT_DOMAIN)); ?></small>
                                    </div>
                                </div>
                                <div class="vapi-icon-list-item">
                                    <div class="vapi-icon">
                                        <span class="dashicons dashicons-wordpress"></span>
                                    </div>
                                    <div>
                                        <strong><?php printf(esc_html__('WordPress %s', VAPI_TEXT_DOMAIN), esc_html($wp_version)); ?></strong>
                                        <small><?php esc_html_e('Compatible with the current plugin version.', VAPI_TEXT_DOMAIN); ?></small>
                                    </div>
                                </div>
                                <div class="vapi-icon-list-item">
                                    <div class="vapi-icon">
                                        <span class="dashicons dashicons-info"></span>
                                    </div>
                                    <div>
                                        <strong><?php printf(esc_html__('Plugin v%s', VAPI_TEXT_DOMAIN), esc_html($plugin_version)); ?></strong>
                                        <small><?php echo esc_html($is_configured ? __('Running with live assistant.', VAPI_TEXT_DOMAIN) : __('Finish setup to activate.', VAPI_TEXT_DOMAIN)); ?></small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="vapi-card">
                        <div class="vapi-card-header">
                            <h3 class="vapi-card-title">
                                <span class="dashicons dashicons-sos"></span>
                                <?php esc_html_e('Help & learning', VAPI_TEXT_DOMAIN); ?>
                            </h3>
                            <p class="vapi-card-subtitle"><?php esc_html_e('Deep-dive into best practices and product updates.', VAPI_TEXT_DOMAIN); ?></p>
                        </div>
                        <div class="vapi-card-body">
                            <div class="vapi-resource-links">
                                <a href="https://vapi.ai/dashboard" target="_blank" class="vapi-resource-link">
                                    <span class="dashicons dashicons-external"></span>
                                    <div>
                                        <strong><?php esc_html_e('Vapi dashboard', VAPI_TEXT_DOMAIN); ?></strong>
                                        <small><?php esc_html_e('Manage assistants, review analytics, and deploy updates.', VAPI_TEXT_DOMAIN); ?></small>
                                    </div>
                                </a>
                                <a href="https://docs.vapi.ai" target="_blank" class="vapi-resource-link">
                                    <span class="dashicons dashicons-book"></span>
                                    <div>
                                        <strong><?php esc_html_e('Developer docs', VAPI_TEXT_DOMAIN); ?></strong>
                                        <small><?php esc_html_e('API reference, webhooks, and styling guides.', VAPI_TEXT_DOMAIN); ?></small>
                                    </div>
                                </a>
                                <a href="<?php echo esc_url(admin_url('admin.php?page=vapi_about')); ?>" class="vapi-resource-link">
                                    <span class="dashicons dashicons-chart-line"></span>
                                    <div>
                                        <strong><?php esc_html_e('What’s new', VAPI_TEXT_DOMAIN); ?></strong>
                                        <small><?php esc_html_e('Review changelog, roadmap, and support links.', VAPI_TEXT_DOMAIN); ?></small>
                                    </div>
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="vapi-cta-card">
                        <h3><?php esc_html_e('Keep refining the conversation', VAPI_TEXT_DOMAIN); ?></h3>
                        <p><?php esc_html_e('Regularly test the voice assistant, update scripts, and review call transcripts to maintain a premium experience.', VAPI_TEXT_DOMAIN); ?></p>
                        <a href="<?php echo esc_url(admin_url('admin.php?page=vapi_config&tab=training')); ?>" class="vapi-button">
                            <span class="dashicons dashicons-welcome-learn-more"></span>
                            <?php esc_html_e('Open training workspace', VAPI_TEXT_DOMAIN); ?>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php
}
