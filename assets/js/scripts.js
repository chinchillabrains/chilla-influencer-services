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

        
        $('.dashboard-services-list__serviceStockswitch').click(function (e) {
            e.preventDefault();
            $variation = $(this).closest('.dashboard-services-list__variation');
            var $label = $variation.find('.dashboard-services-list__serviceStockstatus');
            service_id = $variation.data('id');
            is_active = $variation.hasClass('active');
            service_price = $variation.find('.beefluence-dashboard-price-input').val();
            if ( $variation.find('.beefluence-variation-empty-price').length > 0 ) {
                alert( 'Η υπηρεσία δεν έχει τιμή!' );
                return;
            }
            if (is_active == false) {
                var param = 'beefluence-service-activate='+service_id;
                $label.text('Ενεργή');
                $label.removeClass('dashboard-services-list__service--red');
                $label.addClass('dashboard-services-list__service--green');
                $variation.removeClass('inactive');
                $variation.addClass('active');
                $variation.find('[type="checkbox"]').prop('checked', true);
            } else {
                var param = 'beefluence-service-deactivate='+service_id;
                $label.text('Ανενεργή');
                $label.removeClass('dashboard-services-list__service--green');
                $label.addClass('dashboard-services-list__service--red');
                $variation.removeClass('active');
                $variation.addClass('inactive');
                $variation.find('[type="checkbox"]').prop('checked', false);
            }
            var url = window.location.href.split('#')[0];
            url = url.split('?')[0];
            // window.location.href = url + '?' + param;
            const xhttp = new XMLHttpRequest();
            xhttp.open("GET", url + '?' + param, true);
            xhttp.send();
            // fetch(url + '?' + param);
        });

        $('.price-tooltip').mouseover(function () {
            $(this).find('.price-tooltip__txt').show();
        });

        $('.price-tooltip').mouseout(function () {
            $(this).find('.price-tooltip__txt').hide();
        });

        $('form.beefluence-dashboard-price-update').submit(function (e) {
            e.preventDefault();
            service_id = $(this).closest('.dashboard-services-list__variation').data('id');
            service_price = $(this).find('.beefluence-dashboard-price-input').val();
            param = 'beefluence-service-price-update='+service_id+'&beefluence-service-price='+service_price;
            var url = window.location.href.split('#')[0];
            url = url.split('?')[0];
            window.location.href = url + '?' + param;
        });

        
        $('form.beefluence-dashboard-all-prices-update').submit(function (e) {
            e.preventDefault();
            var $variations = $(this).closest('.dashboard-services-list__variations').find('.dashboard-services-list__variation');
            $variations.each(function () {
                service_id = $(this).data('id');
                service_price = parseInt($(this).find('.beefluence-dashboard-price-input').val());
                if ( service_price > 0 ) {
                    param = 'beefluence-service-price-update='+service_id+'&beefluence-service-price='+service_price;
                    var url = window.location.href.split('#')[0];
                    url = url.split('?')[0];
                    const xhttp = new XMLHttpRequest();
                    xhttp.open("GET", url + '?' + param, true);
                    xhttp.send();
                    // fetch(url + '?' + param);
                }
            });
            // window.location.href = url + '?' + param;
            setTimeout(() => {location.reload()}, 5500);
        });

        $('form.beefluence-dashboard-all-prices-update input').click(function (e) {
            e.preventDefault();
            $(this).css('opacity', '0.4');
            $(this).css('pointerEvents', 'none');
            $form = $(this).closest('.beefluence-dashboard-all-prices-update');
            $form.css('cursor', 'wait');
            $form.submit();
        });

        $('.dashboard-services-list__ordersToggle').click(function () {
            $(this).closest('.dashboard-services-list__serviceOrders').find('.dashboard-services-list__orders').toggle();
        });

        $('.dashboard-services-list__variationsToggle').click(function () {
            $(this).closest('.dashboard-services-list__variations').find('.dashboard-services-list__variationsList').toggle();
        });

    }

    // Change Dashboard menu buttons
    if ( $('.user-is-advertiser').length > 0 ) {
        $('.influencer-dashboard-button').hide();
        $('.menu-dashboard-button a').attr('href', '/brand-dashboard');
    }
    if ( $('.user-is-influencer').length > 0 ) {
        $('.advertiser-dashboard-button').hide();
        $('.menu-dashboard-button a').attr('href', '/influencer-dashboard');
    }


    // Filters Mobile

    $('#beefluence-filters-toggle').click(function () {
        $('.elementor-element-3592c3c').toggleClass('fixed');
        addCloseButton();
    });
    function addCloseButton () {
        if ($('#beefluence-filters-close').length == 0) {
            $('.elementor-element-3592c3c').prepend('<button id="beefluence-filters-close">X</button>');
            $('#beefluence-filters-close').click(function () {
        $('.elementor-element-3592c3c').removeClass('fixed');
    });
        }
    }
    // END - Filters Mobile


    if ( $('.woocommerce-checkout').length > 0 ) {
        $table = $('#alg_checkout_files_upload_form_1');
        $title = $table.find('[for="alg_checkout_files_upload_1"]');
        $title.css('pointerEvents', 'none');
        if ($('.alg_checkout_files_upload_result_1').length > 5) {
            $('#alg_checkout_files_upload_button_1').attr('style', 'display: none !important');
        }
        $(document.body).on('checkout-images-updated', function () {
            if ($('.alg_checkout_files_upload_result_1').length > 5) {
                $('#alg_checkout_files_upload_button_1').attr('style', 'display: none !important');
            } else {
                $('#alg_checkout_files_upload_button_1').attr('style', 'display: block !important');
            }
        });
        jQuery(document).ready(function ($) {
            if ($('#checkout-images-explanation').length == 0) {
                $('<small id="checkout-images-explanation"><em> - Εδώ μπορείτε να ανεβάσετε τις εικόνες που θα θέλατε να προωθήσει ο Influencer.</em><small>').insertAfter('label[for="alg_checkout_files_upload_1"]');
            }
        });
    }

    // Remove parameters from URL
    var url = window.location.href.split('#')[0];
    url_base = url.split('?')[0];
    url_params = url.split('?')[1];
    params_to_remove = [
        'beefluence-service-activate',
        'beefluence-service-deactivate',
        'beefluence-service-price-update',
        'beefluence-service-price',
        'beefluence-service-price',
    ];
    for (var i = 0; i < params_to_remove.length; i++ ) {
        if ( url_params.includes( params_to_remove[ i ] ) ) {
            window.history.replaceState({}, document.title, url_base);
            break;
        }
    }
});