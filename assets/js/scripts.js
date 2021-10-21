jQuery(document).ready(function ($) {
    $dashboard = $('.custom-dashboard-page');
    if ($dashboard.length > 0) {
        $(window).load(function () {
            pageUrl = window.location.href;
            urlArr = pageUrl.split('#')
            if ( urlArr.length > 1 ) {
                itemSelector = '#' + urlArr[ urlArr.length - 1 ];
                $(itemSelector).trigger( 'showSection' );
            }
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

        
        $('.dashboard-services-list__serviceStockswitch').click(function () {
            service_id = $(this).closest('.dashboard-services-list__service').data('id');
            service_status = $(this).closest('.dashboard-services-list__service').data('status');
            if (service_status=='inactive') {
                var param = 'beefluence-service-activate='+service_id;
            } else {
                var param = 'beefluence-service-deactivate='+service_id;
            }
            var url = window.location.href.split('#')[0];
            url = url.split('?')[0];
            window.location.href = url + '?' + param;
        });


        $('.dashboard-services-list__ordersToggle').click(function () {
            $(this).closest('.dashboard-services-list__serviceOrders').find('.dashboard-services-list__orders').toggle();
        });

    }
});