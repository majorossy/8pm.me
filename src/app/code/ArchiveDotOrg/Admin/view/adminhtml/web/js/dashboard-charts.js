/**
 * Dashboard Charts Module
 * Uses ApexCharts to render import metrics and match rate visualizations
 */
define([
    'jquery',
    'apexcharts'
], function ($, ApexCharts) {
    'use strict';

    return {
        /**
         * Initialize all dashboard charts
         */
        init: function() {
            this.renderImportsPerDayChart();
            this.renderMatchRateGauge();
        },

        /**
         * Render bar chart showing imports per day for the last 7 days
         */
        renderImportsPerDayChart: function() {
            var chartElement = document.querySelector("#imports-per-day-chart");
            if (!chartElement) {
                return;
            }

            // Fetch data from server (would be populated by PHP in production)
            var dataUrl = chartElement.getAttribute('data-url');

            $.ajax({
                url: dataUrl || '/admin/archivedotorg/dashboard/importsPerDay',
                method: 'GET',
                dataType: 'json',
                success: function(response) {
                    this.drawImportsChart(chartElement, response);
                }.bind(this),
                error: function() {
                    // Fallback to demo data
                    var demoData = {
                        categories: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                        series: [45, 52, 38, 67, 55, 41, 60]
                    };
                    this.drawImportsChart(chartElement, demoData);
                }.bind(this)
            });
        },

        /**
         * Draw the bar chart
         */
        drawImportsChart: function(element, data) {
            var options = {
                chart: {
                    type: 'bar',
                    height: 350,
                    toolbar: {
                        show: false
                    }
                },
                series: [{
                    name: 'Shows Imported',
                    data: data.series
                }],
                xaxis: {
                    categories: data.categories,
                    labels: {
                        style: {
                            fontSize: '12px'
                        }
                    }
                },
                yaxis: {
                    title: {
                        text: 'Number of Shows'
                    }
                },
                colors: ['#2196F3'],
                plotOptions: {
                    bar: {
                        borderRadius: 4,
                        dataLabels: {
                            position: 'top'
                        }
                    }
                },
                dataLabels: {
                    enabled: true,
                    offsetY: -20,
                    style: {
                        fontSize: '12px',
                        colors: ['#304758']
                    }
                },
                grid: {
                    borderColor: '#e7e7e7',
                    row: {
                        colors: ['#f3f3f3', 'transparent'],
                        opacity: 0.5
                    }
                },
                tooltip: {
                    y: {
                        formatter: function(val) {
                            return val + " shows";
                        }
                    }
                }
            };

            var chart = new ApexCharts(element, options);
            chart.render();
        },

        /**
         * Render radial gauge showing overall match rate
         */
        renderMatchRateGauge: function() {
            var chartElement = document.querySelector("#match-rate-gauge");
            if (!chartElement) {
                return;
            }

            // Fetch data from server
            var dataUrl = chartElement.getAttribute('data-url');

            $.ajax({
                url: dataUrl || '/admin/archivedotorg/dashboard/matchRate',
                method: 'GET',
                dataType: 'json',
                success: function(response) {
                    this.drawMatchRateGauge(chartElement, response.matchRate);
                }.bind(this),
                error: function() {
                    // Fallback to demo data
                    this.drawMatchRateGauge(chartElement, 97.1);
                }.bind(this)
            });
        },

        /**
         * Draw the radial gauge
         */
        drawMatchRateGauge: function(element, matchRate) {
            var options = {
                chart: {
                    type: 'radialBar',
                    height: 350
                },
                series: [matchRate],
                colors: [this.getMatchRateColor(matchRate)],
                plotOptions: {
                    radialBar: {
                        hollow: {
                            size: '70%'
                        },
                        track: {
                            background: '#e7e7e7',
                            strokeWidth: '100%'
                        },
                        dataLabels: {
                            name: {
                                offsetY: -10,
                                show: true,
                                color: '#888',
                                fontSize: '13px'
                            },
                            value: {
                                color: '#111',
                                fontSize: '30px',
                                show: true,
                                formatter: function(val) {
                                    return val.toFixed(1) + '%';
                                }
                            }
                        }
                    }
                },
                labels: ['Match Rate'],
                stroke: {
                    lineCap: 'round'
                }
            };

            var chart = new ApexCharts(element, options);
            chart.render();
        },

        /**
         * Get color based on match rate threshold
         */
        getMatchRateColor: function(matchRate) {
            if (matchRate >= 95) {
                return '#00E396'; // Green - excellent
            } else if (matchRate >= 85) {
                return '#FEB019'; // Yellow - good
            } else {
                return '#FF4560'; // Red - needs attention
            }
        }
    };
});
