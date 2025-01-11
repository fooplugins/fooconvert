jQuery(document).ready(function ($) {
    // Fetch stats via AJAX
    function fetchStats() {
        $.ajax({
            url: fooconvertData.ajaxUrl,
            method: 'POST',
            data: {
                action: 'fooconvert_fetch_stats',
                widget_id: $('.fooconvert-stats-container').data('widget-id'),
                days: $('.fooconvert-recent-activity-days').val(),
                nonce: fooconvertData.nonce
            },
            success: function (response) {
                // Remove loading state from metrics
                $('.metric').removeClass('loading');

                // Render basic metrics
                if ( response.metrics ) {
                    //loop through metrics
                    $.each(response.metrics, function(key, value) {
                        // Use the key to find the element and set its text
                        $('#metric-' + key).text(value);
                    });
                }

                // Remove loading state from chart container before rendering
                $('.fooconvert-recent-activity-container').removeClass('loading');

                // Render chart for recent activity
                renderRecentActivityChart(response);

                // Render PRO metrics if available
                // $('#conversion-rate').text(response.conversion_rate + '%');
                // $('#geo-breakdown').text(response.geo_breakdown);
                // $('#device-browser-analytics').text(response.device_browser);
                //
                // // Render PRO-only charts
                // renderPieChart(response.conversion_breakdown);
                // renderEngagementTrendChart(response.engagement_trend.labels, response.engagement_trend.data);
            },
            error: function () {
                // Remove loading states even on error to show empty state
                $('.metric, .fooconvert-recent-activity-container').removeClass('loading');
                console.error("Failed to fetch stats.");
            }
        });
    }

    // Render Line Chart for Recent Activity
    function renderRecentActivityChart(response) {
        if ( !response.recent_activity ) {
            return;
        }

        var recent_activity = response.recent_activity;

        var ctx = document.getElementById('recentActivityChart').getContext('2d');

        // Check if the chart already exists
        if (window.recentActivityChartInstance) {
            window.recentActivityChartInstance.destroy();
            window.recentActivityChartUpdated = false;
        }

        // Default options
        const defaultOptions = {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        };

        // Allow options to be overridden by external files
        const overrideOptions = response.options_override || {};

        // Merge default options with overridden options
        const finalOptions = {
            ...defaultOptions,
            ...overrideOptions,
            plugins: {
                ...defaultOptions.plugins,
                ...overrideOptions.plugins,
            },
            scales: {
                ...defaultOptions.scales,
                ...overrideOptions.scales,
            }
        };

        // Create a new chart instance
        window.recentActivityChartInstance = new Chart(ctx, {
            type: 'line',
            data: {
                labels: recent_activity.labels,
                datasets: recent_activity.datasets
            },
            options: finalOptions,
            plugins: [{
                afterLayout: (chart) => {
                    if ( window.recentActivityChartUpdated ) {
                        return;
                    }

                    window.recentActivityChartUpdated = true;

                    // Check if the annotation plugin is defined
                    if (chart.options.plugins.annotation && chart.options.plugins.annotation.annotations) {
                        const annotations = chart.options.plugins.annotation.annotations;

                        // Loop through each annotation and update properties using yScaleMax
                        Object.keys(annotations).forEach((key) => {
                            const annotation = annotations[key];
                            if (annotation.type === 'line') {
                                // Update Y-axis related properties dynamically
                                annotation.enter = ({ element }) => {
                                    element.label.options.display = true; // Show the label
                                    return true; // Force chart re-drawing
                                };

                                annotation.leave = ({ element }) => {
                                    element.label.options.display = false; // Hide the label
                                    return true; // Force chart re-drawing
                                };
                            }
                        });
                    }
                }
            }]
        });
    }

    // Render Pie Chart for Conversion Rate Breakdown (PRO only)
    function renderPieChart(data) {
        var ctx = document.getElementById('pieChart').getContext('2d');
        new Chart(ctx, {
            type: 'pie',
            data: {
                labels: ['Converted', 'Not Converted'],
                datasets: [{
                    data: data,
                    backgroundColor: [
                        'rgba(54, 162, 235, 0.8)',
                        'rgba(255, 99, 132, 0.8)'
                    ],
                }]
            },
            options: {
                responsive: true,
            }
        });
    }

    // Render Engagement Trend Chart (PRO only)
    function renderEngagementTrendChart(labels, data) {
        var ctx = document.getElementById('engagementTrendChart').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Engagement',
                    data: data,
                    backgroundColor: 'rgba(153, 102, 255, 0.6)',
                    borderColor: 'rgba(153, 102, 255, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    }

    // Fetch and display the stats on page load
    fetchStats();

    $('.fooconvert-recent-activity-days').change( function() {
        $('.fooconvert-recent-activity-container').addClass('loading');
        fetchStats();
    });
});