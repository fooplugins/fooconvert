jQuery(document).ready(function ($) {
    // Fetch data via AJAX
    function fetchDashboardData() {
        var $spinner = $('<span class="spinner is-active"></span>'),
            $container = $('.fooconvert-top-performers-container');
        $container.html($spinner);
        $.ajax({
            url: fooconvertData.ajaxUrl,
            method: 'POST',
            data: {
                action: 'fooconvert_dashboard_top_performers',
                sort: $('.fooconvert-top-performers-sort').val(),
                nonce: fooconvertData.nonce
            },
            success: function (response) {
                $('.fooconvert-top-performers-container').html(response.html);
            },
            error: function () {
                $container.html( '<p>ERROR : Failed to fetch top performers!</p>' );
            }
        });
    }

    // Fetch and display the dashboard data on page load
    fetchDashboardData();

    $('.fooconvert-top-performers-sort').change(function() {
        fetchDashboardData();
    });

    // Update stats
    $('.fooconvert-update-stats').click(function(e) {
        e.preventDefault();
        var $spinner = $('.fooconvert-update-stats-spinner'),
            $button = $(this);
        $spinner.addClass('is-active');
        $button.attr('disabled', 'disabled');
        $.ajax({
            url: fooconvertData.ajaxUrl,
            method: 'POST',
            data: {
                action: 'fooconvert_update_stats',
                nonce: fooconvertData.nonce
            },
            success: function (response) {
                $spinner.removeClass('is-active');
                $button.removeAttr('disabled');
                $('.fooconvert-last-updated').html(response.message);
                fetchDashboardData();
            },
            error: function () {
                $spinner.removeClass('is-active');
                $button.removeAttr('disabled');
                alert('Failed to update stats!');
            }
        });
    });

    // Create demo widgets
    $('.fooconvert-create-demo-widgets').click(function(e) {
        e.preventDefault();
        var $spinner = $('.fooconvert-create-demo-widgets-spinner');
        $spinner.addClass('is-active');
        $(this).attr('disabled', 'disabled');
        $.ajax({
            url: fooconvertData.ajaxUrl,
            method: 'POST',
            data: {
                action: 'fooconvert_create_demo_widgets',
                nonce: fooconvertData.nonce
            },
            success: function (response) {
                $spinner.removeClass('is-active');
                $('.fooconvert-create-demo-container').html(response.message);
            },
            error: function () {
                $spinner.removeClass('is-active');
                alert('Failed to create demo widgets!');
            }
        });
    });

    // Delete demo widgets
    $('.fooconvert-delete-demo-widgets').click(function(e) {
        e.preventDefault();
        var $spinner = $('.fooconvert-delete-demo-widgets-spinner');
        $spinner.addClass('is-active');
        $(this).attr('disabled', 'disabled');
        $.ajax({
            url: fooconvertData.ajaxUrl,
            method: 'POST',
            data: {
                action: 'fooconvert_delete_demo_widgets',
                nonce: fooconvertData.nonce
            },
            success: function (response) {
                $spinner.removeClass('is-active');
                $('.fooconvert-delete-demo-container').html(response.message);
            },
            error: function () {
                $spinner.removeClass('is-active');
                alert('Failed to delete demo widgets!');
            }
        });
    })
});