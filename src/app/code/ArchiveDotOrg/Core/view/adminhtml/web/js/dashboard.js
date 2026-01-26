/**
 * ArchiveDotOrg Core Module - Dashboard JavaScript
 */
define([
    'jquery',
    'mage/translate',
    'Magento_Ui/js/modal/alert',
    'Magento_Ui/js/modal/confirm'
], function ($, $t, alert, confirm) {
    'use strict';

    return function (config, element) {
        var $dashboard = $(element);
        var urls = $dashboard.data('urls');
        var formKey = $('#form-key').val();
        var pollInterval = null;
        var idlePollInterval = null;
        var hasActiveJobs = false;

        /**
         * Initialize the dashboard
         */
        function init() {
            loadStatus();
            loadActivityLog();
            bindEvents();
            startIdlePolling();
        }

        /**
         * Bind event handlers
         */
        function bindEvents() {
            // Import form
            $('#import-form').on('submit', handleImportSubmit);
            $('#artist-select').on('change', handleArtistChange);

            // Quick actions
            $('#btn-sync-albums').on('click', showSyncModal);
            $('#btn-cleanup-products').on('click', showCleanupModal);
            $('#btn-test-api').on('click', handleTestApi);
            $('#btn-view-products').on('click', function () {
                window.location.href = urls.products_grid;
            });

            // Modal actions
            $('#execute-sync-btn').on('click', handleSyncSubmit);
            $('#execute-cleanup-btn').on('click', handleCleanupSubmit);
            $('.modal-close').on('click', closeModals);

            // Activity log
            $('#activity-filter').on('change', loadActivityLog);
            $('#refresh-activity-btn').on('click', loadActivityLog);

            // Cancel job buttons (delegated)
            $(document).on('click', '.cancel-job-btn', handleCancelJob);

            // Close modals on overlay click
            $('.modal-popup').on('click', function (e) {
                if ($(e.target).hasClass('modal-popup')) {
                    closeModals();
                }
            });
        }

        /**
         * Load dashboard status
         */
        function loadStatus() {
            $.ajax({
                url: urls.status,
                type: 'GET',
                dataType: 'json',
                data: { form_key: formKey },
                success: function (response) {
                    if (response.success) {
                        updateStats(response.stats);
                        updateActiveJobs(response.active_jobs || []);
                    }
                },
                error: function () {
                    showError($t('Failed to load dashboard status.'));
                }
            });
        }

        /**
         * Update stats cards
         */
        function updateStats(stats) {
            // Total products
            $('#stat-total-products-value').text(formatNumber(stats.total_products || 0));

            // API status
            var apiConnected = stats.api_connected;
            var $apiStatus = $('#stat-api-status-value');
            $apiStatus.find('.status-indicator')
                .removeClass('status-unknown status-connected status-disconnected')
                .addClass(apiConnected ? 'status-connected' : 'status-disconnected');
            $apiStatus.find('.status-text').text(apiConnected ? $t('Connected') : $t('Disconnected'));

            // Last import
            var lastImport = stats.last_import;
            if (lastImport) {
                $('#stat-last-import-value').text(formatRelativeTime(lastImport));
            } else {
                $('#stat-last-import-value').text($t('Never'));
            }

            // Module status
            var moduleEnabled = stats.module_enabled;
            var $moduleStatus = $('#stat-module-status-value');
            $moduleStatus.find('.status-indicator')
                .removeClass('status-unknown status-connected status-disconnected')
                .addClass(moduleEnabled ? 'status-connected' : 'status-disconnected');
            $moduleStatus.find('.status-text').text(moduleEnabled ? $t('Enabled') : $t('Disabled'));
        }

        /**
         * Update active jobs list
         */
        function updateActiveJobs(jobs) {
            var $container = $('#active-jobs-list');
            var $noJobs = $('#no-active-jobs');

            hasActiveJobs = jobs.length > 0;

            if (!hasActiveJobs) {
                $container.hide();
                $noJobs.show();
                stopActivePolling();
                return;
            }

            $noJobs.hide();
            $container.show();

            var template = $('#job-card-template').html();
            var html = '';

            jobs.forEach(function (job) {
                var jobHtml = template
                    .replace(/\{\{job_id\}\}/g, escapeHtml(job.job_id))
                    .replace(/\{\{artist_name\}\}/g, escapeHtml(job.artist_name))
                    .replace(/\{\{status\}\}/g, escapeHtml(job.status))
                    .replace(/\{\{progress\}\}/g, Math.round(job.progress || 0))
                    .replace(/\{\{processed_shows\}\}/g, job.processed_shows || 0)
                    .replace(/\{\{total_shows\}\}/g, job.total_shows || 0)
                    .replace(/\{\{tracks_created\}\}/g, job.tracks_created || 0)
                    .replace(/\{\{tracks_updated\}\}/g, job.tracks_updated || 0)
                    .replace(/\{\{error_count\}\}/g, job.error_count || 0);
                html += jobHtml;
            });

            $container.html(html);
            startActivePolling();
        }

        /**
         * Load activity log
         */
        function loadActivityLog() {
            var actionType = $('#activity-filter').val();

            $.ajax({
                url: urls.activity_log,
                type: 'GET',
                dataType: 'json',
                data: {
                    form_key: formKey,
                    action_type: actionType,
                    limit: 50
                },
                success: function (response) {
                    if (response.success) {
                        updateActivityLog(response.activities || []);
                    }
                }
            });
        }

        /**
         * Update activity log list
         */
        function updateActivityLog(activities) {
            var $container = $('#activity-list');
            var $noActivity = $('#no-activity');

            if (activities.length === 0) {
                $container.hide();
                $noActivity.show();
                return;
            }

            $noActivity.hide();
            $container.show();

            var template = $('#activity-item-template').html();
            var html = '';

            activities.forEach(function (activity) {
                var activityHtml = template
                    .replace(/\{\{action_type\}\}/g, escapeHtml(activity.action_type))
                    .replace(/\{\{action_label\}\}/g, escapeHtml(formatActionType(activity.action_type)))
                    .replace(/\{\{details\}\}/g, escapeHtml(activity.details || ''))
                    .replace(/\{\{admin_username\}\}/g, escapeHtml(activity.admin_username || 'system'))
                    .replace(/\{\{status\}\}/g, escapeHtml(activity.status))
                    .replace(/\{\{formatted_time\}\}/g, escapeHtml(formatDateTime(activity.created_at)));
                html += activityHtml;
            });

            $container.html(html);
        }

        /**
         * Handle import form submit
         */
        function handleImportSubmit(e) {
            e.preventDefault();

            var artistName = $('#artist-select').val();
            var collectionId = $('#collection-id').val();
            var limit = $('#import-limit').val();
            var offset = $('#import-offset').val();
            var dryRun = $('#dry-run').is(':checked') ? 1 : 0;

            if (!artistName) {
                showError($t('Please select an artist.'));
                return;
            }

            var $btn = $('#start-import-btn');
            $btn.prop('disabled', true).addClass('loading');

            $.ajax({
                url: urls.start_import,
                type: 'POST',
                dataType: 'json',
                data: {
                    form_key: formKey,
                    artist_name: artistName,
                    collection_id: collectionId,
                    limit: limit,
                    offset: offset,
                    dry_run: dryRun
                },
                success: function (response) {
                    if (response.success) {
                        showSuccess(response.message || $t('Import job queued successfully.'));
                        loadStatus();
                        loadActivityLog();
                    } else {
                        showError(response.error || $t('Failed to start import.'));
                    }
                },
                error: function () {
                    showError($t('Failed to start import.'));
                },
                complete: function () {
                    $btn.prop('disabled', false).removeClass('loading');
                }
            });
        }

        /**
         * Handle artist dropdown change
         */
        function handleArtistChange() {
            var $option = $(this).find(':selected');
            var collectionId = $option.data('collection') || '';
            $('#collection-id').val(collectionId);
        }

        /**
         * Handle test API button
         */
        function handleTestApi() {
            var $btn = $('#btn-test-api');
            $btn.prop('disabled', true).addClass('loading');

            $.ajax({
                url: urls.test_api,
                type: 'POST',
                dataType: 'json',
                data: { form_key: formKey },
                success: function (response) {
                    if (response.success && response.connected) {
                        showSuccess($t('API connection successful! Response time: ') + response.response_time_ms + 'ms');
                    } else {
                        showError(response.error || $t('API connection failed.'));
                    }
                    loadStatus();
                },
                error: function () {
                    showError($t('Failed to test API connection.'));
                },
                complete: function () {
                    $btn.prop('disabled', false).removeClass('loading');
                }
            });
        }

        /**
         * Handle cancel job button
         */
        function handleCancelJob(e) {
            var jobId = $(this).data('job-id');

            confirm({
                title: $t('Cancel Import Job'),
                content: $t('Are you sure you want to cancel this import job?'),
                actions: {
                    confirm: function () {
                        $.ajax({
                            url: urls.cancel_job,
                            type: 'POST',
                            dataType: 'json',
                            data: {
                                form_key: formKey,
                                job_id: jobId
                            },
                            success: function (response) {
                                if (response.success) {
                                    showSuccess(response.message || $t('Job cancelled.'));
                                    loadStatus();
                                    loadActivityLog();
                                } else {
                                    showError(response.error || $t('Failed to cancel job.'));
                                }
                            }
                        });
                    }
                }
            });
        }

        /**
         * Show sync albums modal
         */
        function showSyncModal() {
            $('#sync-modal').show();
        }

        /**
         * Show cleanup products modal
         */
        function showCleanupModal() {
            $('#cleanup-modal').show();
        }

        /**
         * Close all modals
         */
        function closeModals() {
            $('.modal-popup').hide();
        }

        /**
         * Handle sync albums submit
         */
        function handleSyncSubmit() {
            var threshold = $('#sync-threshold').val();
            var dryRun = $('#sync-dry-run').is(':checked') ? 1 : 0;

            var $btn = $('#execute-sync-btn');
            $btn.prop('disabled', true).addClass('loading');

            $.ajax({
                url: urls.sync_albums,
                type: 'POST',
                dataType: 'json',
                data: {
                    form_key: formKey,
                    threshold: threshold,
                    dry_run: dryRun
                },
                success: function (response) {
                    closeModals();
                    if (response.success) {
                        showSuccess(response.message || $t('Albums synced successfully.'));
                        loadActivityLog();
                    } else {
                        showError(response.error || $t('Failed to sync albums.'));
                    }
                },
                error: function () {
                    showError($t('Failed to sync albums.'));
                },
                complete: function () {
                    $btn.prop('disabled', false).removeClass('loading');
                }
            });
        }

        /**
         * Handle cleanup products submit
         */
        function handleCleanupSubmit() {
            var collection = $('#cleanup-collection').val();
            var olderThan = $('#cleanup-older-than').val();
            var dryRun = $('#cleanup-dry-run').is(':checked') ? 1 : 0;

            if (!collection && !olderThan) {
                showError($t('Please specify either a collection or age filter.'));
                return;
            }

            var $btn = $('#execute-cleanup-btn');
            $btn.prop('disabled', true).addClass('loading');

            $.ajax({
                url: urls.cleanup_products,
                type: 'POST',
                dataType: 'json',
                data: {
                    form_key: formKey,
                    collection: collection,
                    older_than: olderThan,
                    dry_run: dryRun
                },
                success: function (response) {
                    closeModals();
                    if (response.success) {
                        showSuccess(response.message || $t('Cleanup completed.'));
                        loadStatus();
                        loadActivityLog();
                    } else {
                        showError(response.error || $t('Failed to cleanup products.'));
                    }
                },
                error: function () {
                    showError($t('Failed to cleanup products.'));
                },
                complete: function () {
                    $btn.prop('disabled', false).removeClass('loading');
                }
            });
        }

        /**
         * Start polling for active jobs (every 2 seconds)
         */
        function startActivePolling() {
            if (pollInterval) return;

            pollInterval = setInterval(function () {
                $.ajax({
                    url: urls.job_status,
                    type: 'GET',
                    dataType: 'json',
                    data: { form_key: formKey },
                    success: function (response) {
                        if (response.success) {
                            updateActiveJobs(response.jobs || []);
                        }
                    }
                });
            }, 2000);
        }

        /**
         * Stop active job polling
         */
        function stopActivePolling() {
            if (pollInterval) {
                clearInterval(pollInterval);
                pollInterval = null;
            }
        }

        /**
         * Start idle polling (every 30 seconds)
         */
        function startIdlePolling() {
            idlePollInterval = setInterval(function () {
                if (!hasActiveJobs) {
                    loadStatus();
                }
            }, 30000);
        }

        /**
         * Format number with commas
         */
        function formatNumber(num) {
            return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ',');
        }

        /**
         * Format relative time
         */
        function formatRelativeTime(dateString) {
            if (!dateString) return $t('Never');

            var date = new Date(dateString);
            var now = new Date();
            var diffMs = now - date;
            var diffMins = Math.floor(diffMs / 60000);
            var diffHours = Math.floor(diffMins / 60);
            var diffDays = Math.floor(diffHours / 24);

            if (diffMins < 1) return $t('Just now');
            if (diffMins < 60) return diffMins + ' ' + (diffMins === 1 ? $t('minute ago') : $t('minutes ago'));
            if (diffHours < 24) return diffHours + ' ' + (diffHours === 1 ? $t('hour ago') : $t('hours ago'));
            if (diffDays < 7) return diffDays + ' ' + (diffDays === 1 ? $t('day ago') : $t('days ago'));

            return formatDateTime(dateString);
        }

        /**
         * Format date/time
         */
        function formatDateTime(dateString) {
            if (!dateString) return '';
            var date = new Date(dateString);
            return date.toLocaleDateString() + ' ' + date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        }

        /**
         * Format action type for display
         */
        function formatActionType(actionType) {
            var labels = {
                'import_started': $t('Import Started'),
                'import_completed': $t('Import Completed'),
                'import_cancelled': $t('Import Cancelled'),
                'sync_albums': $t('Sync Albums'),
                'cleanup_products': $t('Cleanup Products')
            };
            return labels[actionType] || actionType;
        }

        /**
         * Escape HTML
         */
        function escapeHtml(text) {
            if (!text) return '';
            var div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        /**
         * Show success message
         */
        function showSuccess(message) {
            alert({
                title: $t('Success'),
                content: message,
                modalClass: 'confirm _success'
            });
        }

        /**
         * Show error message
         */
        function showError(message) {
            alert({
                title: $t('Error'),
                content: message,
                modalClass: 'confirm _error'
            });
        }

        // Initialize on document ready
        init();
    };
});
