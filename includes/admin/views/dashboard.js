jQuery(document).ready(function ($) {
    // Fetch data via AJAX
    function fetchDashboardData() {
        $.ajax({
            url: fooconvertData.ajaxUrl,
            method: 'POST',
            data: {
                action: 'fooconvert_fetch_dashboard_data',
                nonce: fooconvertData.nonce
            },
            success: function (response) {

            },
            error: function () {
                console.error("Failed to fetch dashboard data!");
            }
        });
    }

    // Fetch and display the dashboard data on page load
    fetchDashboardData();

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