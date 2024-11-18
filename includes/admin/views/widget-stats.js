jQuery(document).ready(function ($) {
    // Fetch stats via AJAX
    function fetchStats() {
        $.ajax({
            url: fooconvertData.ajaxUrl,
            method: 'POST',
            data: {
                action: 'fooconvert_fetch_stats',
                widget_id: $('.fooconvert-stats-container').data('widget-id'),
                nonce: fooconvertData.nonce
            },
            success: function (response) {
                // Render basic metrics
                if ( response.metrics ) {
                    //loop through metrics
                    $.each(response.metrics, function(key, value) {
                        // Use the key to find the element and set its text
                        $('#metric-' + key).text(value);
                    });
                }

                // Render line chart for recent activity
                renderLineChart(response.recent_activity);

                // Render PRO metrics if available
                $('#conversion-rate').text(response.conversion_rate + '%');
                $('#geo-breakdown').text(response.geo_breakdown);
                $('#device-browser-analytics').text(response.device_browser);

                // Render PRO-only charts
                renderPieChart(response.conversion_breakdown);
                renderEngagementTrendChart(response.engagement_trend.labels, response.engagement_trend.data);
            },
            error: function () {
                console.error("Failed to fetch stats.");
            }
        });
    }

    // Render Line Chart for Recent Activity
    function renderLineChart(recent_activity) {
        var ctx = document.getElementById('lineChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: recent_activity.labels,
                datasets: [
                    {
                        label: 'Views',
                        data: recent_activity.views,
                        borderColor: 'rgba(75, 192, 192, 1)',
                        fill: false
                    },
                    {
                        label: 'Clicks',
                        data: recent_activity.clicks,
                        borderColor: 'rgba(255, 99, 132, 1)',
                        fill: false
                    },
                    {
                        label: 'Unique Visitors',
                        data: recent_activity.unique_visitors,
                        borderColor: 'rgba(54, 162, 235, 1)',
                        fill: false
                    }
                ]
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
});