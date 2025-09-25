<?php
if (!defined('ABSPATH')) {
    exit;
}

function vapi_about_page()
{
    $plugin_version = defined('VAPI_PLUGIN_VERSION') ? VAPI_PLUGIN_VERSION : '1.0.0';
    $wp_version = get_bloginfo('version');
    $php_version = PHP_VERSION;
    $plugin_name = __('Vapi Voice AI Agent', VAPI_TEXT_DOMAIN);
    ?>
    <div class="wrap vapi-admin-page">
        <div class="vapi-header">
            <div class="vapi-header-content">
                <span class="vapi-chip info"><?php esc_html_e('Plugin insight', VAPI_TEXT_DOMAIN); ?></span>
                <h1>
                    <div class="vapi-header-icon">
                        <span class="dashicons dashicons-info"></span>
                    </div>
                    <?php echo esc_html($plugin_name); ?>
                </h1>
                <p><?php esc_html_e('Give visitors a delightful, human-like experience with Vapi’s realtime voice AI—fully managed from WordPress.', VAPI_TEXT_DOMAIN); ?></p>

                <div class="vapi-hero-meta">
                    <div class="vapi-hero-meta-item">
                        <span><?php esc_html_e('Current version', VAPI_TEXT_DOMAIN); ?></span>
                        <strong><?php echo esc_html($plugin_version); ?></strong>
                    </div>
                    <div class="vapi-hero-meta-item">
                        <span><?php esc_html_e('Requires WordPress', VAPI_TEXT_DOMAIN); ?></span>
                        <strong><?php echo esc_html($wp_version); ?>+</strong>
                    </div>
                    <div class="vapi-hero-meta-item">
                        <span><?php esc_html_e('PHP version', VAPI_TEXT_DOMAIN); ?></span>
                        <strong><?php echo esc_html($php_version); ?>+</strong>
                    </div>
                    <div class="vapi-hero-meta-item">
                        <span><?php esc_html_e('License', VAPI_TEXT_DOMAIN); ?></span>
                        <strong><?php esc_html_e('GPL2', VAPI_TEXT_DOMAIN); ?></strong>
                    </div>
                </div>
            </div>
        </div>

        <div class="vapi-content">
            <div class="vapi-grid vapi-grid-2">
                <div class="vapi-main-content">
                    <div class="vapi-card vapi-gradient-card vapi-mb-4">
                        <div class="vapi-card-header">
                            <h2 class="vapi-card-title">
                                <span class="dashicons dashicons-lightbulb"></span>
                                <?php esc_html_e('What this plugin delivers', VAPI_TEXT_DOMAIN); ?>
                            </h2>
                            <p class="vapi-card-subtitle"><?php esc_html_e('A unified workflow for embedding, styling, and training your Vapi-powered voice assistant.', VAPI_TEXT_DOMAIN); ?></p>
                        </div>
                        <div class="vapi-card-body">
                            <div class="vapi-icon-list">
                                <div class="vapi-icon-list-item">
                                    <div class="vapi-icon">
                                        <span class="dashicons dashicons-admin-site"></span>
                                    </div>
                                    <div>
                                        <strong><?php esc_html_e('Zero-code embedding', VAPI_TEXT_DOMAIN); ?></strong>
                                        <small><?php esc_html_e('Drop the Vapi widget into any WordPress site without editing templates.', VAPI_TEXT_DOMAIN); ?></small>
                                    </div>
                                </div>
                                <div class="vapi-icon-list-item">
                                    <div class="vapi-icon">
                                        <span class="dashicons dashicons-art"></span>
                                    </div>
                                    <div>
                                        <strong><?php esc_html_e('Beautiful customisation', VAPI_TEXT_DOMAIN); ?></strong>
                                        <small><?php esc_html_e('Control button placement, colour palettes, icons, and responsive sizing.', VAPI_TEXT_DOMAIN); ?></small>
                                    </div>
                                </div>
                                <div class="vapi-icon-list-item">
                                    <div class="vapi-icon">
                                        <span class="dashicons dashicons-chart-line"></span>
                                    </div>
                                    <div>
                                        <strong><?php esc_html_e('Operational tools', VAPI_TEXT_DOMAIN); ?></strong>
                                        <small><?php esc_html_e('Run diagnostics, export/import presets, and reset settings safely.', VAPI_TEXT_DOMAIN); ?></small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="vapi-card">
                        <div class="vapi-card-header">
                            <h2 class="vapi-card-title">
                                <span class="dashicons dashicons-feedback"></span>
                                <?php esc_html_e('Key capabilities', VAPI_TEXT_DOMAIN); ?>
                            </h2>
                        </div>
                        <div class="vapi-card-body">
                            <div class="vapi-icon-list">
                                <?php
                                $features = [
                                    ['icon' => 'dashicons-megaphone', 'title' => __('Realtime voice calls', VAPI_TEXT_DOMAIN), 'desc' => __('Initiate and manage calls with Vapi’s ultra-low latency voice layer.', VAPI_TEXT_DOMAIN)],
                                    ['icon' => 'dashicons-layout', 'title' => __('Adaptive layouts', VAPI_TEXT_DOMAIN), 'desc' => __('Ready-made layouts and responsive design for all device sizes.', VAPI_TEXT_DOMAIN)],
                                    ['icon' => 'dashicons-admin-users', 'title' => __('Personalised prompts', VAPI_TEXT_DOMAIN), 'desc' => __('Store notes that guide your assistant’s tone, persona, and fallback responses.', VAPI_TEXT_DOMAIN)],
                                    ['icon' => 'dashicons-shield', 'title' => __('Migration friendly', VAPI_TEXT_DOMAIN), 'desc' => __('Automatically wipes legacy settings from older plugins to prevent conflicts.', VAPI_TEXT_DOMAIN)],
                                ];
                                foreach ($features as $feature) :
                                    ?>
                                    <div class="vapi-icon-list-item">
                                        <div class="vapi-icon">
                                            <span class="dashicons <?php echo esc_attr($feature['icon']); ?>"></span>
                                        </div>
                                        <div>
                                            <strong><?php echo esc_html($feature['title']); ?></strong>
                                            <small><?php echo esc_html($feature['desc']); ?></small>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <div class="vapi-card">
                        <div class="vapi-card-header">
                            <h2 class="vapi-card-title">
                                <span class="dashicons dashicons-admin-generic"></span>
                                <?php esc_html_e('Getting started checklist', VAPI_TEXT_DOMAIN); ?>
                            </h2>
                            <p class="vapi-card-subtitle"><?php esc_html_e('Follow these quick steps to launch a production-ready assistant.', VAPI_TEXT_DOMAIN); ?></p>
                        </div>
                        <div class="vapi-card-body">
                            <ol class="vapi-icon-list">
                                <li class="vapi-icon-list-item">
                                    <div class="vapi-icon">
                                        <span class="dashicons dashicons-admin-site"></span>
                                    </div>
                                    <div>
                                        <strong><?php esc_html_e('Create your assistant in the Vapi dashboard', VAPI_TEXT_DOMAIN); ?></strong>
                                        <small><?php esc_html_e('Capture the API key and assistant ID that you want to surface on the site.', VAPI_TEXT_DOMAIN); ?></small>
                                    </div>
                                </li>
                                <li class="vapi-icon-list-item">
                                    <div class="vapi-icon">
                                        <span class="dashicons dashicons-admin-settings"></span>
                                    </div>
                                    <div>
                                        <strong><?php esc_html_e('Configure API credentials', VAPI_TEXT_DOMAIN); ?></strong>
                                        <small><?php esc_html_e('Paste credentials in the Configuration → API tab and verify the connection.', VAPI_TEXT_DOMAIN); ?></small>
                                    </div>
                                </li>
                                <li class="vapi-icon-list-item">
                                    <div class="vapi-icon">
                                        <span class="dashicons dashicons-art"></span>
                                    </div>
                                    <div>
                                        <strong><?php esc_html_e('Style the assistant button', VAPI_TEXT_DOMAIN); ?></strong>
                                        <small><?php esc_html_e('Pick colours, icons, offset, and behaviour that match your brand.', VAPI_TEXT_DOMAIN); ?></small>
                                    </div>
                                </li>
                                <li class="vapi-icon-list-item">
                                    <div class="vapi-icon">
                                        <span class="dashicons dashicons-welcome-learn-more"></span>
                                    </div>
                                    <div>
                                        <strong><?php esc_html_e('Add training guidance', VAPI_TEXT_DOMAIN); ?></strong>
                                        <small><?php esc_html_e('Document tone of voice, FAQs, and escalation rules in the Training tab.', VAPI_TEXT_DOMAIN); ?></small>
                                    </div>
                                </li>
                            </ol>
                        </div>
                    </div>

                    <div class="vapi-card">
                        <div class="vapi-card-header">
                            <h2 class="vapi-card-title">
                                <span class="dashicons dashicons-schedule"></span>
                                <?php esc_html_e('Changelog & roadmap', VAPI_TEXT_DOMAIN); ?>
                            </h2>
                            <p class="vapi-card-subtitle"><?php esc_html_e('Highlights from the latest release and a glimpse at what is coming next.', VAPI_TEXT_DOMAIN); ?></p>
                        </div>
                        <div class="vapi-card-body">
                            <div class="vapi-timeline">
                                <div class="vapi-timeline-item">
                                    <div class="vapi-timeline-time"><?php esc_html_e('Version 1.0.0', VAPI_TEXT_DOMAIN); ?></div>
                                    <div class="vapi-timeline-title"><?php esc_html_e('Initial release', VAPI_TEXT_DOMAIN); ?></div>
                                    <p class="vapi-timeline-description"><?php esc_html_e('Launched the core integration, admin pages, and appearance controls for embedding the Vapi assistant.', VAPI_TEXT_DOMAIN); ?></p>
                                    <div class="vapi-mt-1">
                                        <span class="vapi-chip success"><?php esc_html_e('Current', VAPI_TEXT_DOMAIN); ?></span>
                                    </div>
                                </div>
                                <div class="vapi-timeline-item">
                                    <div class="vapi-timeline-time"><?php esc_html_e('Upcoming', VAPI_TEXT_DOMAIN); ?></div>
                                    <div class="vapi-timeline-title"><?php esc_html_e('In-dashboard analytics', VAPI_TEXT_DOMAIN); ?></div>
                                    <p class="vapi-timeline-description"><?php esc_html_e('Planned insights for call outcomes, popular intents, and assistant sentiment.', VAPI_TEXT_DOMAIN); ?></p>
                                    <div class="vapi-mt-1">
                                        <span class="vapi-chip warning"><?php esc_html_e('Roadmap', VAPI_TEXT_DOMAIN); ?></span>
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
                                <span class="dashicons dashicons-admin-network"></span>
                                <?php esc_html_e('Plugin snapshot', VAPI_TEXT_DOMAIN); ?>
                            </h3>
                        </div>
                        <div class="vapi-card-body">
                            <div class="vapi-plugin-info">
                                <div class="vapi-info-row">
                                    <span class="vapi-info-label"><?php esc_html_e('Plugin name', VAPI_TEXT_DOMAIN); ?></span>
                                    <span class="vapi-info-value"><?php echo esc_html($plugin_name); ?></span>
                                </div>
                                <div class="vapi-info-row">
                                    <span class="vapi-info-label"><?php esc_html_e('Version', VAPI_TEXT_DOMAIN); ?></span>
                                    <span class="vapi-info-value"><?php echo esc_html($plugin_version); ?></span>
                                </div>
                                <div class="vapi-info-row">
                                    <span class="vapi-info-label"><?php esc_html_e('Author', VAPI_TEXT_DOMAIN); ?></span>
                                    <span class="vapi-info-value">
                                        <a href="http://github.com/imranhoshain" target="_blank" rel="noopener noreferrer">Imran Hoshain</a>
                                    </span>
                                </div>
                                <div class="vapi-info-row">
                                    <span class="vapi-info-label"><?php esc_html_e('Text domain', VAPI_TEXT_DOMAIN); ?></span>
                                    <span class="vapi-info-value">vapi-voice-ai-agent</span>
                                </div>
                                <div class="vapi-info-row">
                                    <span class="vapi-info-label"><?php esc_html_e('Contact', VAPI_TEXT_DOMAIN); ?></span>
                                    <span class="vapi-info-value">
                                        <a href="mailto:iforuimran@gmail.com">iforuimran@gmail.com</a>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="vapi-card">
                        <div class="vapi-card-header">
                            <h3 class="vapi-card-title">
                                <span class="dashicons dashicons-shield"></span>
                                <?php esc_html_e('System requirements', VAPI_TEXT_DOMAIN); ?>
                            </h3>
                        </div>
                        <div class="vapi-card-body">
                            <ul class="vapi-icon-list">
                                <li class="vapi-icon-list-item">
                                    <div class="vapi-icon">
                                        <span class="dashicons dashicons-wordpress"></span>
                                    </div>
                                    <div>
                                        <strong><?php esc_html_e('WordPress 5.0+', VAPI_TEXT_DOMAIN); ?></strong>
                                        <small><?php esc_html_e('Tested up to the latest stable release.', VAPI_TEXT_DOMAIN); ?></small>
                                    </div>
                                </li>
                                <li class="vapi-icon-list-item">
                                    <div class="vapi-icon">
                                        <span class="dashicons dashicons-editor-code"></span>
                                    </div>
                                    <div>
                                        <strong><?php esc_html_e('PHP 7.4+', VAPI_TEXT_DOMAIN); ?></strong>
                                        <small><?php esc_html_e('Matches WordPress core recommendations.', VAPI_TEXT_DOMAIN); ?></small>
                                    </div>
                                </li>
                                <li class="vapi-icon-list-item">
                                    <div class="vapi-icon">
                                        <span class="dashicons dashicons-admin-site"></span>
                                    </div>
                                    <div>
                                        <strong><?php esc_html_e('HTTPS recommended', VAPI_TEXT_DOMAIN); ?></strong>
                                        <small><?php esc_html_e('Secures voice calls and authentication details.', VAPI_TEXT_DOMAIN); ?></small>
                                    </div>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <div class="vapi-card">
                        <div class="vapi-card-header">
                            <h3 class="vapi-card-title">
                                <span class="dashicons dashicons-groups"></span>
                                <?php esc_html_e('Support & community', VAPI_TEXT_DOMAIN); ?>
                            </h3>
                        </div>
                        <div class="vapi-card-body">
                            <div class="vapi-resource-links">
                                <a href="https://docs.vapi.ai" target="_blank" class="vapi-resource-link">
                                    <span class="dashicons dashicons-book"></span>
                                    <div>
                                        <strong><?php esc_html_e('Official documentation', VAPI_TEXT_DOMAIN); ?></strong>
                                        <small><?php esc_html_e('Deep dive into architecture, webhooks, and SDK options.', VAPI_TEXT_DOMAIN); ?></small>
                                    </div>
                                </a>
                                <a href="https://vapi.ai/dashboard" target="_blank" class="vapi-resource-link">
                                    <span class="dashicons dashicons-admin-links"></span>
                                    <div>
                                        <strong><?php esc_html_e('Vapi dashboard', VAPI_TEXT_DOMAIN); ?></strong>
                                        <small><?php esc_html_e('Manage assistants, review analytics, download transcripts.', VAPI_TEXT_DOMAIN); ?></small>
                                    </div>
                                </a>
                                <a href="https://wordpress.org/plugins/vapi-voice-ai-agent/" target="_blank" class="vapi-resource-link">
                                    <span class="dashicons dashicons-star-filled"></span>
                                    <div>
                                        <strong><?php esc_html_e('Leave a review', VAPI_TEXT_DOMAIN); ?></strong>
                                        <small><?php esc_html_e('Help others discover the plugin and support ongoing updates.', VAPI_TEXT_DOMAIN); ?></small>
                                    </div>
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="vapi-cta-card">
                        <h3><?php esc_html_e('Shape the roadmap', VAPI_TEXT_DOMAIN); ?></h3>
                        <p><?php esc_html_e('Share feature requests or voice UX challenges—your feedback helps prioritise what we build next.', VAPI_TEXT_DOMAIN); ?></p>
                        <a href="mailto:hello@vapi.ai" class="vapi-button">
                            <span class="dashicons dashicons-email"></span>
                            <?php esc_html_e('Contact the maintainer', VAPI_TEXT_DOMAIN); ?>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php
}
