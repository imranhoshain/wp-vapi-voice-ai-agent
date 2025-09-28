(function (window, document) {
    'use strict';

    var settings = window.vapiVoiceAgentConfig || {};
    var remoteUrl = settings.remote || '';
    var endpoint = settings.endpoint || '';

    if (!remoteUrl || !endpoint) {
        return;
    }

    function ensureSdkReady(callback) {
        if (window.vapiSDK && typeof window.vapiSDK.run === 'function') {
            callback();
            return;
        }

        setTimeout(function () {
            ensureSdkReady(callback);
        }, 100);
    }

    function applyButtonConfig(config) {
        if (!config.buttonConfig || !config.buttonConfig.fixed) {
            return;
        }

        var button = document.querySelector('.vapi-btn');
        if (button) {
            button.style.position = 'fixed';
        }
    }

    function bootstrapAssistant() {
        var endpointUrl = endpoint;
        var cacheBust = 'vapi_ts=' + Date.now();
        if (endpointUrl.indexOf('?') === -1) {
            endpointUrl += '?' + cacheBust;
        } else {
            endpointUrl += '&' + cacheBust;
        }

        fetch(endpointUrl, {
            cache: 'no-store'
        })
            .then(function (response) {
                return response.json();
            })
            .then(function (config) {
                if (!config || !config.apiKey || !config.assistant) {
                    console.warn('Vapi Voice Agent: configuration incomplete. Please verify your API key and assistant ID.');
                    return;
                }

                ensureSdkReady(function () {
                    window.vapiSDK.run({
                        apiKey: config.apiKey,
                        assistant: config.assistant,
                        config: config.buttonConfig,
                    });
                    applyButtonConfig(config);
                });
            })
            .catch(function (error) {
                console.error('Error fetching Vapi config:', error);
            });
    }

    function loadRemoteScript() {
        var script = document.createElement('script');
        script.src = remoteUrl;
        script.defer = true;
        script.async = true;
        script.onload = bootstrapAssistant;
        document.head.appendChild(script);
    }

    loadRemoteScript();
})(window, document);
