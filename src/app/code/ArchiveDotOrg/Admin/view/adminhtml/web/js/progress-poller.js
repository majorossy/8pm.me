/**
 * Progress Poller Module
 * Polls the server for real-time import progress updates
 */
define([
    'jquery',
    'mage/translate'
], function ($, $t) {
    'use strict';

    return {
        pollInterval: null,
        pollFrequency: 2000, // Poll every 2 seconds
        maxRetries: 3,
        retryCount: 0,

        /**
         * Initialize progress polling for a specific artist
         * @param {string} artist - Artist identifier
         * @param {string} progressElementId - DOM element ID for progress bar
         */
        init: function(artist, progressElementId) {
            this.artist = artist;
            this.progressElement = document.getElementById(progressElementId);

            if (!this.progressElement) {
                console.error('Progress element not found: ' + progressElementId);
                return;
            }

            this.startPolling();
        },

        /**
         * Start polling the server for progress updates
         */
        startPolling: function() {
            if (this.pollInterval) {
                clearInterval(this.pollInterval);
            }

            this.pollInterval = setInterval(function() {
                this.fetchProgress();
            }.bind(this), this.pollFrequency);

            // Fetch immediately on start
            this.fetchProgress();
        },

        /**
         * Stop polling
         */
        stopPolling: function() {
            if (this.pollInterval) {
                clearInterval(this.pollInterval);
                this.pollInterval = null;
            }
        },

        /**
         * Fetch progress data from server
         */
        fetchProgress: function() {
            $.ajax({
                url: '/admin/archivedotorg/progress/status',
                method: 'GET',
                data: { artist: this.artist },
                dataType: 'json',
                success: function(response) {
                    this.retryCount = 0; // Reset retry count on success
                    this.updateProgressDisplay(response);
                }.bind(this),
                error: function(xhr, status, error) {
                    this.handleError(error);
                }.bind(this)
            });
        },

        /**
         * Update the progress display with new data
         * @param {Object} data - Progress data from server
         */
        updateProgressDisplay: function(data) {
            if (!data || data.status === 'idle') {
                this.showIdle();
                this.stopPolling();
                return;
            }

            if (data.status === 'running') {
                this.updateProgressBar(data.current, data.total);
                this.updateStatus(data);
                this.updateETA(data.eta);
            } else if (data.status === 'completed') {
                this.showCompleted(data);
                this.stopPolling();
            } else if (data.status === 'failed') {
                this.showFailed(data);
                this.stopPolling();
            }
        },

        /**
         * Update the progress bar
         * @param {number} current - Current progress
         * @param {number} total - Total items
         */
        updateProgressBar: function(current, total) {
            if (total === 0) {
                return;
            }

            var percentage = Math.round((current / total) * 100);
            var progressBar = this.progressElement.querySelector('.progress-bar');
            var progressText = this.progressElement.querySelector('.progress-text');

            if (progressBar) {
                progressBar.style.width = percentage + '%';
                progressBar.setAttribute('aria-valuenow', percentage);
            }

            if (progressText) {
                progressText.textContent = current + ' / ' + total + ' (' + percentage + '%)';
            }
        },

        /**
         * Update status text
         * @param {Object} data - Progress data
         */
        updateStatus: function(data) {
            var statusElement = this.progressElement.querySelector('.status-text');
            if (statusElement) {
                var statusText = $t('Processing: ') + data.processed + ' ' + $t('items processed');
                if (data.correlation_id) {
                    statusText += ' (ID: ' + data.correlation_id.substring(0, 8) + ')';
                }
                statusElement.textContent = statusText;
            }

            // Show spinner
            var spinner = this.progressElement.querySelector('.spinner');
            if (spinner) {
                spinner.style.display = 'inline-block';
            }
        },

        /**
         * Update ETA display
         * @param {string} eta - Estimated time of completion (ISO 8601 format)
         */
        updateETA: function(eta) {
            if (!eta) {
                return;
            }

            var etaElement = this.progressElement.querySelector('.eta-text');
            if (etaElement) {
                var etaDate = new Date(eta);
                var now = new Date();
                var diffMs = etaDate - now;

                if (diffMs > 0) {
                    var diffMins = Math.round(diffMs / 60000);
                    var etaText = $t('ETA: ') + diffMins + ' ' + $t('minutes');
                    etaElement.textContent = etaText;
                }
            }
        },

        /**
         * Show idle state
         */
        showIdle: function() {
            var statusElement = this.progressElement.querySelector('.status-text');
            if (statusElement) {
                statusElement.textContent = $t('No active imports');
            }

            this.hideSpinner();
        },

        /**
         * Show completed state
         * @param {Object} data - Progress data
         */
        showCompleted: function(data) {
            var progressBar = this.progressElement.querySelector('.progress-bar');
            var statusElement = this.progressElement.querySelector('.status-text');

            if (progressBar) {
                progressBar.style.width = '100%';
                progressBar.classList.add('progress-bar-success');
            }

            if (statusElement) {
                statusElement.textContent = $t('Completed: ') + data.processed + ' ' + $t('items processed');
            }

            this.hideSpinner();
            this.showSuccessIcon();
        },

        /**
         * Show failed state
         * @param {Object} data - Progress data
         */
        showFailed: function(data) {
            var progressBar = this.progressElement.querySelector('.progress-bar');
            var statusElement = this.progressElement.querySelector('.status-text');

            if (progressBar) {
                progressBar.classList.add('progress-bar-danger');
            }

            if (statusElement) {
                statusElement.textContent = $t('Failed: ') + (data.error || $t('Unknown error'));
            }

            this.hideSpinner();
            this.showErrorIcon();
        },

        /**
         * Hide the spinner
         */
        hideSpinner: function() {
            var spinner = this.progressElement.querySelector('.spinner');
            if (spinner) {
                spinner.style.display = 'none';
            }
        },

        /**
         * Show success icon
         */
        showSuccessIcon: function() {
            var icon = this.progressElement.querySelector('.status-icon');
            if (icon) {
                icon.innerHTML = '✓';
                icon.className = 'status-icon success';
                icon.style.display = 'inline-block';
            }
        },

        /**
         * Show error icon
         */
        showErrorIcon: function() {
            var icon = this.progressElement.querySelector('.status-icon');
            if (icon) {
                icon.innerHTML = '✗';
                icon.className = 'status-icon error';
                icon.style.display = 'inline-block';
            }
        },

        /**
         * Handle AJAX error
         * @param {string} error - Error message
         */
        handleError: function(error) {
            this.retryCount++;

            if (this.retryCount >= this.maxRetries) {
                console.error('Progress polling failed after ' + this.maxRetries + ' retries');
                this.stopPolling();

                var statusElement = this.progressElement.querySelector('.status-text');
                if (statusElement) {
                    statusElement.textContent = $t('Connection error - progress updates unavailable');
                }
            } else {
                console.warn('Progress polling attempt ' + this.retryCount + ' failed, retrying...');
            }
        }
    };
});
