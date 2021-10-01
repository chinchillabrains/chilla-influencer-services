jQuery(document).ready(function ($) {
    $dashboard = $('.custom-dashboard-page');
    if ($dashboard.length > 0) {
        $(window).load(function () {
            pageUrl = window.location.href;
            urlArr = pageUrl.split('#')
            itemSelector = '#' + urlArr[ urlArr.length - 1 ];
            $(itemSelector).trigger( 'showSection' );
        });

        $navButtons = $('.beefluence-dashboard-nav__section:not(.beefluence-dashboard-nav__logout) > a');
        $navButtons.on( 'click', function () {
            var target = $(this).data('target');
            $targetElem = $dashboard.find('#dashboard-' + target);
            $targetElem.trigger( 'showSection' );
        } );

        $('.dashboard-section').on( 'showSection', function () {
            $('.dashboard-section').slideUp();
            $(this).slideDown();
        } );
    }
});