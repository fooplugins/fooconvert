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
                action: 'fooconvert_dashboard_task',
                task: 'fetch_top_performers',
                sort: $('.fooconvert-top-performers-sort').val(),
                nonce: fooconvertData.nonce
            },
            success: function (response) {
                $('.fooconvert-top-performers-container').html(response.html);
            },
            error: function () {
                $container.html('<p>ERROR : Failed to fetch top performers!</p>');
            }
        });
    }

    // Fetch and display the dashboard data on page load
    fetchDashboardData();

    $('.fooconvert-top-performers-sort').change(function () {
        fetchDashboardData();
    });

    // Update stats
    $('.fooconvert-update-stats').click(function (e) {
        e.preventDefault();
        var $spinner = $('.fooconvert-update-stats-spinner'),
            $button = $(this);
        $spinner.addClass('is-active');
        $button.attr('disabled', 'disabled');
        $.ajax({
            url: fooconvertData.ajaxUrl,
            method: 'POST',
            data: {
                action: 'fooconvert_dashboard_task',
                task: 'update_stats',
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
    $('.fooconvert-create-demo-widgets').click(function (e) {
        e.preventDefault();
        var $spinner = $('.fooconvert-create-demo-widgets-spinner');
        $spinner.addClass('is-active');
        $(this).attr('disabled', 'disabled');
        $.ajax({
            url: fooconvertData.ajaxUrl,
            method: 'POST',
            data: {
                action: 'fooconvert_dashboard_task',
                task: 'create_demo_widgets',
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
    $('.fooconvert-delete-demo-widgets').click(function (e) {
        e.preventDefault();
        var $spinner = $('.fooconvert-delete-demo-widgets-spinner');
        $spinner.addClass('is-active');
        $(this).attr('disabled', 'disabled');
        $.ajax({
            url: fooconvertData.ajaxUrl,
            method: 'POST',
            data: {
                action: 'fooconvert_dashboard_task',
                task: 'delete_demo_widgets',
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
    });

    // Hide panel
    $('.fooconvert-hide-panel').click(function (e) {
        e.preventDefault();
        var $spinner = $('<span class="spinner is-active panel-hide-spinner"></span>'),
            panel = $(this).data('panel'),
            $panel = $('.fooconvert-panel[data-panel="' + panel + '"]');

        $(this).hide().after($spinner);

        $.ajax({
            url: fooconvertData.ajaxUrl,
            method: 'POST',
            data: {
                action: 'fooconvert_dashboard_task',
                task: 'hide_panel',
                panel: panel,
                nonce: fooconvertData.nonce
            },
            success: function (response) {
                $spinner.remove();
                $panel.hide();
            },
            error: function () {
                $spinner.remove();
                alert('Failed to hide panel!');
            }
        });
    });
});

document.addEventListener('DOMContentLoaded', function () {
    const sliderWrapper = document.querySelector('.fooconvert-slider-wrapper');
    const slides = document.querySelectorAll('.fooconvert-slide');
    const sliderNav = document.querySelector('.fooconvert-slider-nav');
    const prevButton = document.querySelector('.fooconvert-slider-prev');
    const nextButton = document.querySelector('.fooconvert-slider-next');

    let currentIndex = 0;

    // Hide navigation buttons if there is only one slide
    if (slides.length > 1) {
        sliderNav.style.display = 'flex';
    } else {
        return; // Exit script since we don't need navigation
    }

    function updateSliderPosition() {
        sliderWrapper.style.transform = `translateX(-${currentIndex * 50}%)`;
    }

    prevButton.addEventListener('click', () => {
        currentIndex = (currentIndex > 0) ? currentIndex - 1 : slides.length - 1;
        updateSliderPosition();
    });

    nextButton.addEventListener('click', () => {
        currentIndex = (currentIndex < slides.length - 1) ? currentIndex + 1 : 0;
        updateSliderPosition();
    });

    // Auto slide every 5 seconds
    setInterval(() => {
        currentIndex = (currentIndex < slides.length - 1) ? currentIndex + 1 : 0;
        updateSliderPosition();
    }, 5000);
});
