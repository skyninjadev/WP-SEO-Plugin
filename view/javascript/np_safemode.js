(function ($) {
    $(window).on("load", function () {
        let deactivateLink = 'tr[data-slug="nitropack"] #deactivate-nitropack';
        let deactivateLinkUrl = $(deactivateLink).attr('href');

        $(deactivateLink).parent().append($('#nitropack-safemode-popup'));


        $(deactivateLink).on('click', function (e) {
            e.preventDefault();
            $('#nitropack-safemode-veil').show();
            $('#nitropack-safemode-popup').show();
            let ref1 = $(window).scrollTop()+$(window).height();
            let ref2 = $('#nitropack-safemode-popup').offset().top + $('#nitropack-safemode-popup').outerHeight(true);
            if (ref2 > ref1) {
                $('html,body').animate({scrollTop: $('#nitropack-safemode-popup').offset().top - ($(window).height() - $('#nitropack-safemode-popup').outerHeight(true) - 10)}, 200);
            }

            $('#nitropack-safemode-veil').on('click', function () {
                $('#nitropack-safemode-popup').hide();
                $('#nitropack-safemode-veil').hide();
            });
        });

        $('#np-safemode-nogo').on('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            $(deactivateLink).unbind('click');
            $('#nitropack-safemode-popup').hide();
            $('#nitropack-safemode-popup').remove();
            if (deactivateLinkUrl !== '') {location.href = deactivateLinkUrl;}
        });

        $('#np-safemode-go').on('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            $(deactivateLink).unbind('click');

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {action: 'nitropack_enable_safemode', nonce: frontendajax.nitroNonce},
                beforeSend: function () {
                    $('#nitropack-safemode-popup').height($('#np-safemode-content').height());
                    $('#np-safemode-content').hide();
                    $('#np-safemode-loader-loading').show();
                },
                success: function (response) {
                    $('#np-safemode-loader-loading').hide();
                    let responseResult = JSON.parse(response);
                    if (responseResult.type === 'success') {
                        $('#np-safemode-loader-success').show();
                    } else {
                        $('#np-safemode-loader-error').show();
                    }
                },
                error: function () {
                    $('#np-safemode-loader-loading').hide();
                    $('#np-safemode-loader-error').show();
                },
                complete: function() {
                    setTimeout(function(){
                        $('#np-safemode-loader-success').hide();
                        $('#np-safemode-loader-error').hide();
                        $('#nitropack-safemode-popup').remove();
                        $('#nitropack-safemode-veil').hide();
                    }, 1500);
                }
            });
        });
    });
})(jQuery);