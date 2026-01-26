'use strict';

define(
    [
        'uiComponent',
        'jquery',
        'ko',
        'MSP_ReCaptcha/js/registry'
    ],
    function (Component, $, ko, registry, undefined) {

        return function (reCaptcha) {
            return reCaptcha.extend({
                /**
                 * Avoid js error this.settings is undefined
                 * @private
                 */
                _loadApi: function () {
                    var element, scriptTag;

                    if (this._isApiRegistered !== undefined) {
                        if (this._isApiRegistered === true) {
                            $(window).trigger('recaptchaapiready');
                        }

                        return;
                    }
                    this._isApiRegistered = false;

                    // global function
                    window.globalOnRecaptchaOnLoadCallback = function() {
                        this._isApiRegistered = true;
                        $(window).trigger('recaptchaapiready');
                    }.bind(this);

                    element   = document.createElement('script');
                    scriptTag = document.getElementsByTagName('script')[0];

                    element.async = true;
                    element.src = 'https://www.google.com/recaptcha/api.js'
                        + '?onload=globalOnRecaptchaOnLoadCallback&render=explicit'
                        + (this.settings !== undefined && this.settings.lang ? '&hl=' + this.settings.lang : '');

                    scriptTag.parentNode.insertBefore(element, scriptTag);

                }
            });
        }
    }
);