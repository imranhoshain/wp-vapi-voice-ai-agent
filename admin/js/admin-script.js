/**
 * Vapi Voice AI Agent - Modern Admin JavaScript
 */

(function($) {
    'use strict';

    const strings = (typeof window !== 'undefined' && window.vapiAjax && window.vapiAjax.strings) ? window.vapiAjax.strings : {};

    const VapiAdmin = {
        getString: function(key, fallbackKey) {
            if (Object.prototype.hasOwnProperty.call(strings, key) && strings[key]) {
                return strings[key];
            }
            if (fallbackKey && Object.prototype.hasOwnProperty.call(strings, fallbackKey) && strings[fallbackKey]) {
                return strings[fallbackKey];
            }
            return '';
        },

        // Configuration
        config: {
            animationSpeed: 300,
            debounceDelay: 500,
            autoSaveDelay: 2000
        },

        assistantsCache: [],
        selectedAssistant: null,

        // Initialize the admin interface
        init: function() {
            this.setupEventHandlers();
            this.initializeComponents();
            this.setupFormValidation();
            this.setupAutoSave();
            this.initAssistantLoader();
        },

        // Setup event handlers
        setupEventHandlers: function() {
            // Tab switching with smooth transitions
            $(document).on('click', '.vapi-nav-tab', this.handleTabSwitch.bind(this));

            // Color picker enhancements
            $(document).on('change', 'input[type="color"]', this.handleColorChange.bind(this));

            // Form field enhancements
            $(document).on('input', '.vapi-form-control', this.handleFieldInput.bind(this));

            // Button click animations
            $(document).on('click', '.vapi-button', this.handleButtonClick.bind(this));

            // Connection testing
            $(document).on('click', '#vapi-test-connection', this.testConnection.bind(this));
            $(document).on('click', '#test-api-endpoint', this.testApiEndpoint.bind(this));
            $(document).on('click', '#test-script-loading', this.testScriptLoading.bind(this));

            // Configuration summary toggle
            $(document).on('click', '#show-config-summary', this.toggleConfigSummary.bind(this));

            // Assistant selection
            $(document).on('change', '#vapi_assistant_selector', this.handleAssistantChange.bind(this));
            $(document).on('click', '#vapi-assistant-copy', this.copyAssistantId.bind(this));
            $(document).on('submit', '#vapi-training-form', this.handleTrainingSubmit.bind(this));

            // Real-time preview updates
            $(document).on('input', '[name*="vapi_"]', this.debounce(this.updatePreview.bind(this), this.config.debounceDelay));
        },

        // Initialize components
        initializeComponents: function() {
            this.setupTooltips();
            this.setupColorPickers();
            this.checkRequirements();
        },

        // Handle tab switching with animations
        handleTabSwitch: function(e) {
            // Don't prevent default - let normal navigation work
            return true;
        },

        // Enhanced color picker handling
        handleColorChange: function(e) {
            const $input = $(e.target);
            const color = $input.val();

            // Create or update color preview
            let $preview = $input.siblings('.vapi-color-preview');
            if ($preview.length === 0) {
                $preview = $('<div class="vapi-color-preview"></div>');
                $input.after($preview);
            }

            $preview.css('background-color', color);

            this.showNotice(this.getString('colorUpdated', 'saved'), 'success');
        },

        // Handle form field input with validation
        handleFieldInput: function(e) {
            const $field = $(e.target);
            this.validateField($field);
            this.showFieldStatus($field);
        },

        // Add button click animation
        handleButtonClick: function(e) {
            const $button = $(e.currentTarget);

            // Prevent double-click
            if ($button.hasClass('vapi-loading')) {
                e.preventDefault();
                return;
            }

            // Add loading state if it's a form submit
            if ($button.attr('type') === 'submit') {
                this.setButtonLoading($button, true, { disable: false });
            }

            // IMPORTANT: Don't prevent default for submit buttons - let form submit normally
        },

        // Test connection functionality
        testConnection: function(e) {
            e.preventDefault();

            const $button = $(e.currentTarget);
            const $result = $('#vapi-test-result');

            this.setButtonLoading($button, true);
            $result.html(this.createLoadingIndicator(this.getString('testingConnection', 'testing')));

            const apiKey = $('#vapi_api_key').val();
            const assistantId = $('#vapi_assistant_id').val();

            if (!apiKey || !assistantId) {
                this.showTestResult($result, this.getString('enterCredentials'), 'error');
                this.setButtonLoading($button, false);
                return;
            }

            fetch(vapiAjax.restUrl + 'vapi/v1/config')
                .then(response => response.json())
                .then(data => {
                    if (data.apiKey && data.assistant) {
                        this.showTestResult($result, this.getString('configOk'), 'success');
                    } else {
                        this.showTestResult($result, this.getString('configIncomplete'), 'error');
                    }
                })
                .catch(error => {
                    this.showTestResult($result, this.getString('configError'), 'error');
                })
                .finally(() => {
                    this.setButtonLoading($button, false);
                });
        },

        // Test API endpoint
        testApiEndpoint: function(e) {
            e.preventDefault();

            const $button = $(e.currentTarget);
            const $result = $('#api-test-result');

            this.setButtonLoading($button, true);
            $result.html(this.createLoadingIndicator(this.getString('testing')));

            fetch(vapiAjax.restUrl + 'vapi/v1/config')
                .then(response => response.json())
                .then(data => {
                    this.showTestResult($result, this.getString('apiWorking'), 'success');
                })
                .catch(error => {
                    this.showTestResult($result, this.getString('apiError'), 'error');
                })
                .finally(() => {
                    this.setButtonLoading($button, false);
                });
        },

        // Test script loading
        testScriptLoading: function(e) {
            e.preventDefault();

            const $button = $(e.currentTarget);
            const $result = $('#script-test-result');

            this.setButtonLoading($button, true);
            $result.html(this.createLoadingIndicator(this.getString('testingScript', 'testing')));

            fetch('https://cdn.jsdelivr.net/gh/VapiAI/html-script-tag@latest/dist/assets/index.js')
                .then(response => {
                    if (response.ok) {
                        this.showTestResult($result, this.getString('scriptAccessible'), 'success');
                    } else {
                        this.showTestResult($result, this.getString('scriptUnavailable'), 'error');
                    }
                })
                .catch(() => {
                    this.showTestResult($result, this.getString('scriptError'), 'error');
                })
                .finally(() => {
                    this.setButtonLoading($button, false);
                });
        },

        // Toggle configuration summary
        toggleConfigSummary: function(e) {
            e.preventDefault();

            const $button = $(e.currentTarget);
            const $summary = $('#config-summary');

            if ($summary.is(':visible')) {
                $summary.slideUp(this.config.animationSpeed);
                $button.text(this.getString('showConfig'));
            } else {
                $summary.slideDown(this.config.animationSpeed);
                $button.text(this.getString('hideConfig'));
            }
        },

        // Setup form validation
        setupFormValidation: function() {
            // Real-time validation for required fields
            $('input[required], select[required], textarea[required]').each((index, element) => {
                const $field = $(element);
                $field.on('blur', () => this.validateField($field));
            });
        },

        // Validate individual field
        validateField: function($field) {
            const value = $field.val().trim();
            const isRequired = $field.attr('required');
            const fieldType = $field.attr('type');

            let isValid = true;
            let message = '';

            if (isRequired && !value) {
                isValid = false;
                message = this.getString('fieldRequired');
            } else if (fieldType === 'email' && value && !this.isValidEmail(value)) {
                isValid = false;
                message = this.getString('invalidEmail');
            } else if (fieldType === 'url' && value && !this.isValidUrl(value)) {
                isValid = false;
                message = this.getString('invalidUrl');
            }

            this.showFieldValidation($field, isValid, message);
            return isValid;
        },

        // Show field validation state
        showFieldValidation: function($field, isValid, message) {
            // Remove existing validation classes
            $field.removeClass('vapi-field-valid vapi-field-invalid');

            // Remove existing validation message
            $field.siblings('.vapi-field-message').remove();

            if (message) {
                $field.addClass(isValid ? 'vapi-field-valid' : 'vapi-field-invalid');

                const $message = $('<div class="vapi-field-message">' + message + '</div>');
                $message.addClass(isValid ? 'vapi-text-success' : 'vapi-text-danger');
                $field.after($message);
            }
        },

        // Setup auto-save functionality (disabled)
        setupAutoSave: function() {
            // Auto-save disabled - users must manually save changes
        },

        // Setup progress indicators
        // Setup tooltips
        setupTooltips: function() {
            // Add tooltips to form fields with descriptions
            $('.vapi-form-description').each((index, element) => {
                const $desc = $(element);
                const $field = $desc.siblings('.vapi-form-control');

                if ($field.length) {
                    $field.attr('title', $desc.text());
                }
            });
        },

        // Setup color pickers
        setupColorPickers: function() {
            $('input[type="color"]').each((index, element) => {
                const $input = $(element);
                const $wrapper = $('<div class="vapi-color-picker-wrapper"></div>');

                $input.wrap($wrapper);

                // Add color preview
                const $preview = $('<div class="vapi-color-preview"></div>');
                $preview.css('background-color', $input.val());
                $input.after($preview);
            });
        },

        // Check system requirements
        checkRequirements: function() {
            // Check if required WordPress functions are available
            if (typeof wp === 'undefined') {
                this.showNotice(this.getString('wpScriptsMissing'), 'warning');
            }

            // Check browser compatibility
            if (!window.fetch) {
                this.showNotice(this.getString('browserUnsupported'), 'warning');
            }
        },

        // Update live preview
        updatePreview: function() {
            // Update button preview based on current settings
            const position = $('[name="vapi_settings[vapi_button_position]"]').val();
            const width = $('[name="vapi_settings[vapi_button_width]"]').val();
            const height = $('[name="vapi_settings[vapi_button_height]"]').val();
            const idleColor = $('[name="vapi_settings[vapi_idle_color]"]').val();

            // Update preview if preview element exists
            const $preview = $('#vapi-button-preview');
            if ($preview.length) {
                $preview.css({
                    width: width,
                    height: height,
                    backgroundColor: idleColor,
                    position: 'fixed',
                    bottom: position.includes('bottom') ? '20px' : 'auto',
                    top: position.includes('top') ? '20px' : 'auto',
                    right: position.includes('right') ? '20px' : 'auto',
                    left: position.includes('left') ? '20px' : 'auto'
                });
            }
        },

        // Utility functions
        setButtonLoading: function($button, loading, options) {
            const settings = $.extend({
                disable: true,
                loadingText: this.getString('loading', 'saving')
            }, options);

            if (!$button.data('original-html')) {
                $button.data('original-html', $button.html());
            }

            if (loading) {
                $button.addClass('vapi-loading');
                if (settings.disable) {
                    $button.prop('disabled', true);
                }
                $button.html('<span class="vapi-spinner"></span> ' + settings.loadingText);
            } else {
                $button.removeClass('vapi-loading');
                if (settings.disable) {
                    $button.prop('disabled', false);
                }
                const originalHtml = $button.data('original-html');
                if (originalHtml) {
                    $button.html(originalHtml);
                }
            }
        },

        showFieldStatus: function($field) {
            const value = $field.val().trim();

            // Remove existing status
            $field.siblings('.vapi-field-status').remove();

            if (value) {
                const $status = $('<span class="vapi-field-status">✓</span>');
                $status.css({
                    color: 'var(--vapi-success)',
                    position: 'absolute',
                    right: '10px',
                    top: '50%',
                    transform: 'translateY(-50%)'
                });

                $field.parent().css('position', 'relative');
                $field.after($status);
            }
        },

        initAssistantLoader: function() {
            const $selector = $('#vapi_assistant_selector');
            if (!$selector.length) {
                return;
            }

            this.assistantsCache = [];
            this.updateAssistantDetails(null);
            this.toggleCopyButton();

            const $loading = $('#vapi-assistant-loading');
            const $notice = $('#vapi-assistant-error');
            const $copyButton = $('#vapi-assistant-copy');

            if ($notice.length) {
                $notice.hide();
            }

            if ($loading.length) {
                $loading.text(strings.assistantsLoading || '').show();
            }

            $selector.prop('disabled', true);
            if ($copyButton.length) {
                $copyButton.prop('disabled', true);
            }

            $.ajax({
                url: vapiAjax.ajaxUrl,
                method: 'POST',
                dataType: 'json',
                data: {
                    action: 'vapi_fetch_assistants',
                    nonce: vapiAjax.nonce
                }
            }).done((response) => {
                if (!response || !response.success || !Array.isArray(response.data)) {
                    const message = response && response.data && response.data.message ? response.data.message : (strings.assistantsError || '');
                    this.showAssistantError(message, 'error');
                    return;
                }

                const filtered = response.data.filter((assistant, index, arr) => {
                    if (!assistant || !assistant.id) {
                        return false;
                    }
                    const duplicateIndex = arr.findIndex((item) => item && item.id === assistant.id);
                    if (duplicateIndex !== index) {
                        return false;
                    }
                    return Boolean(assistant.name || assistant.firstMessage || (assistant.model && (assistant.model.model || assistant.model.provider)));
                });

                this.assistantsCache = filtered;
                this.populateAssistantOptions($selector, filtered);

                if ($loading.length) {
                    $loading.hide();
                }

                if ($notice.length) {
                    $notice.hide();
                }

                if (filtered.length) {
                    $selector.prop('disabled', false);
                    if ($copyButton.length) {
                        $copyButton.prop('disabled', false);
                    }
                }
            }).fail(() => {
                this.showAssistantError(strings.assistantsError || '', 'error');
            });
        },

        populateAssistantOptions: function($selector, assistants) {
            if (!$selector.length) {
                return;
            }

            const selected = $selector.data('selected') || '';

            $selector.empty();
            $selector.append($('<option></option>').val('').text(this.getString('assistantsSelect')));

            if (!assistants.length) {
                const cachedLabel = this.getString('assistantsCached', 'assistantsPlaceholder');
                const selectedOption = selected ? $('<option></option>').val(selected).text(selected + ' (' + cachedLabel + ')').prop('selected', true) : null;
                if (selectedOption) {
                    $selector.append(selectedOption);
                }
                this.showAssistantError(this.getString('assistantsEmpty'), 'info');
                this.toggleCopyButton();
                return;
            }

            assistants.forEach((assistant) => {
                if (!assistant || !assistant.id) {
                    return;
                }

                const name = assistant.name || assistant.displayName || '';
                const label = name.trim() !== '' ? name : assistant.id;
                const option = $('<option></option>').val(assistant.id).text(label);
                $selector.append(option);
            });

            if (selected && $selector.find('option[value="' + selected + '"]').length) {
                $selector.val(selected);
            }

            $('#vapi-assistant-error').hide();
            const current = $selector.val();
            this.updateAssistantDetails(this.findAssistantById(current));
            this.toggleCopyButton();
        },

        handleAssistantChange: function(e) {
            const value = $(e.currentTarget).val();
            const $notice = $('#vapi-assistant-error');
            if ($notice.length) {
                $notice.hide();
            }
            this.updateAssistantDetails(this.findAssistantById(value));
            this.toggleCopyButton();
        },

        handleTrainingSubmit: function(e) {
            const assistantId = $('#vapi_assistant_selector').val();
            const hasAssistant = Boolean(assistantId);

            const $form = $(e.currentTarget);

            if ($form.data('assistant-update-done')) {
                $form.removeData('assistant-update-done');
                return true;
            }

            const $submitButton = $form.find('button[type="submit"]');
            const originalText = $submitButton.html();

            if (!hasAssistant) {
                // No remote update needed; allow form to submit normally
                return true;
            }

            const assistantData = this.selectedAssistant || {};
            const systemPrompt = $('#vapi_system_prompt').val() || '';

            const payload = {
                action: 'vapi_update_assistant',
                nonce: vapiAjax.nonce,
                assistantId: assistantId,
            };

            const firstMessage = $('#vapi_first_message').val() || '';
            const endCallMessage = $('#vapi_end_call_message').val() || '';
            const voicemailMessage = $('#vapi_voicemail_message').val() || '';

            if (firstMessage.length) {
                payload.firstMessage = firstMessage;
            }
            if (endCallMessage.length) {
                payload.endCallMessage = endCallMessage;
            }
            if (voicemailMessage.length) {
                payload.voicemailMessage = voicemailMessage;
            }

            if (systemPrompt.length) {
                payload.model = {
                    messages: [
                        {
                            role: 'system',
                            content: systemPrompt,
                        },
                    ],
                };

                if (assistantData.model) {
                    if (assistantData.model.provider) {
                        payload.model.provider = assistantData.model.provider;
                    }
                    if (assistantData.model.model) {
                        payload.model.model = assistantData.model.model;
                    }
                }
            }

            this.setButtonLoading($submitButton, true, { disable: true, loadingText: this.getString('saving', 'loading') });

            $.ajax({
                url: vapiAjax.ajaxUrl,
                method: 'POST',
                dataType: 'json',
                data: payload,
            }).done((response) => {
                if (!response || !response.success) {
                    const message = response && response.data && response.data.message ? response.data.message : this.getString('error');
                    this.showNotice(message, 'error');
                    this.setButtonLoading($submitButton, false, { disable: false });
                    $submitButton.html(originalText);
                    return;
                }

                this.showNotice(this.getString('saved'), 'success');
                $form.data('assistant-update-done', true);
                this.setButtonLoading($submitButton, false, { disable: false });
                $submitButton.html(originalText);
                $form.get(0).submit();
            }).fail(() => {
                this.showNotice(this.getString('error'), 'error');
                this.setButtonLoading($submitButton, false, { disable: false });
                $submitButton.html(originalText);
            });

            e.preventDefault();
            return false;
        },

        findAssistantById: function(id) {
            if (!id || !Array.isArray(this.assistantsCache)) {
                return null;
            }

            for (let i = 0; i < this.assistantsCache.length; i += 1) {
                const assistant = this.assistantsCache[i];
                if (assistant && assistant.id === id) {
                    return assistant;
                }
            }

            return null;
        },

        updateAssistantDetails: function(assistant) {
            this.selectedAssistant = assistant || null;
            const placeholder = this.getString('assistantsPlaceholder');

            const $firstDisplay = $('#vapi-assistant-first-message');
            const $endDisplay = $('#vapi-assistant-end-message');
            const $voicemailDisplay = $('#vapi-assistant-voicemail');
            const $model = $('#vapi-assistant-model');
            const $systemDisplay = $('#vapi-assistant-system-message');
            const $transcriber = $('#vapi-assistant-transcriber');
            const $firstInput = $('#vapi_first_message');
            const $endInput = $('#vapi_end_call_message');
            const $voicemailInput = $('#vapi_voicemail_message');
            const $systemTextarea = $('#vapi_system_prompt');

            const textOrPlaceholder = (value) => (value && value.length ? value : placeholder);

            if ($firstDisplay.length) {
                $firstDisplay.text(textOrPlaceholder(assistant && assistant.firstMessage));
            }
            if ($endDisplay.length) {
                $endDisplay.text(textOrPlaceholder(assistant && assistant.endCallMessage));
            }
            if ($voicemailDisplay.length) {
                $voicemailDisplay.text(textOrPlaceholder(assistant && assistant.voicemailMessage));
            }

            let modelText = placeholder;
            let provider = '';
            let modelName = '';
            if (assistant && assistant.model) {
                provider = assistant.model.provider || '';
                modelName = assistant.model.model || '';
                const parts = [provider, modelName].filter(Boolean);
                modelText = parts.length ? parts.join(' • ') : placeholder;
            }
            if ($model.length) {
                $model.text(modelText);
            }

            let systemPrompt = placeholder;
            if (assistant && assistant.model && Array.isArray(assistant.model.messages)) {
                const systems = assistant.model.messages
                    .filter((message) => message && message.role === 'system' && message.content)
                    .map((message) => message.content.trim())
                    .filter(Boolean);
                if (systems.length) {
                    systemPrompt = systems.join('\n\n');
                }
            }
            if ($systemDisplay.length) {
                $systemDisplay.text(systemPrompt);
            }

            let transcriberText = placeholder;
            if (assistant && assistant.transcriber) {
                const transcriber = assistant.transcriber;
                const parts = [transcriber.provider, transcriber.model, transcriber.language].filter(Boolean);
                if (parts.length) {
                    transcriberText = parts.join(' • ');
                }
            }
            if ($transcriber.length) {
                $transcriber.text(transcriberText);
            }

            if (assistant) {
                if ($firstInput.length) {
                    $firstInput.val(assistant.firstMessage ? assistant.firstMessage : '');
                }
                if ($endInput.length) {
                    $endInput.val(assistant.endCallMessage ? assistant.endCallMessage : '');
                }
                if ($voicemailInput.length) {
                    $voicemailInput.val(assistant.voicemailMessage ? assistant.voicemailMessage : '');
                }
                if ($systemTextarea.length) {
                    const cleanedSystem = systemPrompt === placeholder ? '' : systemPrompt;
                    $systemTextarea.val(cleanedSystem);
                }
            }
        },

        showAssistantError: function(message, type = 'error') {
            const $selector = $('#vapi_assistant_selector');
            const $loading = $('#vapi-assistant-loading');
            const $notice = $('#vapi-assistant-error');
            const $copyButton = $('#vapi-assistant-copy');

            if ($loading.length) {
                $loading.hide();
            }

            if (!$notice.length) {
                return;
            }

            $notice.removeClass('error info').addClass(type === 'info' ? 'info' : 'error');
            $notice.text(message).show();

            if (type === 'error') {
                $selector.prop('disabled', true);
                if ($copyButton.length) {
                    $copyButton.prop('disabled', true);
                }
            }

            if (type === 'info') {
                $selector.prop('disabled', true);
            }

            this.updateAssistantDetails(null);
            this.toggleCopyButton();
        },

        toggleCopyButton: function() {
            const $copyButton = $('#vapi-assistant-copy');
            if (!$copyButton.length) {
                return;
            }

            const hasSelection = Boolean($('#vapi_assistant_selector').val());
            $copyButton.prop('disabled', !hasSelection);
        },

        copyAssistantId: function(e) {
            e.preventDefault();
            const assistantId = $('#vapi_assistant_selector').val();
            if (!assistantId) {
                return;
            }

            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(assistantId)
                    .then(() => {
                        this.showNotice(this.getString('assistantsCopySuccess'), 'success');
                    })
                    .catch(() => {
                        this.showNotice(this.getString('assistantsCopyFail'), 'error');
                    });
            } else {
                const $temp = $('<textarea />');
                $('body').append($temp);
                $temp.val(assistantId).select();
                try {
                    document.execCommand('copy');
                    this.showNotice(this.getString('assistantsCopySuccess'), 'success');
                } catch (err) {
                    this.showNotice(this.getString('assistantsCopyFail'), 'error');
                }
                $temp.remove();
            }
        },

        createLoadingIndicator: function(text) {
            return '<span class="vapi-loading"><span class="vapi-spinner"></span> ' + text + '</span>';
        },

        showTestResult: function($container, message, type) {
            const alertClass = type === 'success' ? 'vapi-alert success' : 'vapi-alert error';
            $container.html('<div class="' + alertClass + '">' + message + '</div>');
            $container.find('.vapi-alert').addClass('vapi-fade-in');
        },

        ensureNoticeContainer: function() {
            let $container = $('#vapi-inline-feedback');
            if ($container.length) {
                return $container;
            }

            $container = $('<div id="vapi-inline-feedback" class="vapi-inline-feedback" role="status" aria-live="polite"></div>');

            const $page = $('.vapi-admin-page').first();
            if ($page.length) {
                $page.prepend($container);
            } else {
                $('body').prepend($container);
            }

            return $container;
        },

        showNotice: function(message, type = 'info') {
            if (!message) {
                return;
            }

            if (typeof wp !== 'undefined' && wp.a11y && typeof wp.a11y.speak === 'function') {
                wp.a11y.speak(message);
            }

            const $container = this.ensureNoticeContainer();
            $container
                .removeClass('is-success is-error is-warning is-info')
                .addClass('is-' + type)
                .text(message)
                .show();
        },

        // Validation helpers
        isValidEmail: function(email) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return emailRegex.test(email);
        },

        isValidUrl: function(url) {
            try {
                new URL(url);
                return true;
            } catch {
                return false;
            }
        },

        // Debounce function
        debounce: function(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        }
    };

    // Initialize when document is ready
    $(document).ready(() => {
        VapiAdmin.init();
    });

    // Expose to global scope for external access
    window.VapiAdmin = VapiAdmin;

})(jQuery);
