jQuery(window).on("load", _ => {
    function clearCacheSingleHandler(clearCacheAction, elem) {
        jQuery.ajax({
            url: frontendajax.ajaxurl,
            type: 'POST',
            data: {
                action: "nitropack_" + clearCacheAction + "_single_cache",
                postUrl: window.location.href.split('#')[0],
                postId: -1,
                nonce: frontendajax.nitroNonce
            },
            dataType: 'json',
            beforeSend: function () {
                elem.find('i').remove();
                elem.find('a').first().append('<i class="fa fa-refresh fa-spin nitro"></i>');
            },
            success: function (data) {
                if (data.type == 'error') {
                    elem.find('i').removeClass('fa-refresh').removeClass('fa-spin').addClass('fa-exclamation');
                    alert(data.message);
                } else {
                    elem.find('i').removeClass('fa-refresh').removeClass('fa-spin').addClass('fa-check');
                }
            }
        });
    }

    function clearCacheEntireHandler(clearCacheAction, elem) {
        jQuery.ajax({
            url: frontendajax.ajaxurl,
            type: 'POST',
            data: {
                action: "nitropack_" + clearCacheAction + "_entire_cache",
                nonce: frontendajax.nitroNonce
            },
            dataType: 'json',
            beforeSend: function () {
                elem.find('i').remove();
                elem.find('a').first().append('<i class="fa fa-refresh fa-spin nitro"></i>');
            },
            success: function (data) {
                if (data.type == 'error') {
                    elem.find('i').removeClass('fa-refresh').removeClass('fa-spin').addClass('fa-exclamation');
                    alert(data.message);
                } else {
                    elem.find('i').removeClass('fa-refresh').removeClass('fa-spin').addClass('fa-check');
                }
            }
        });
    }

    jQuery('.nitropack-invalidate-cache').click(function (e) {
        e.preventDefault();
        clearCacheSingleHandler("invalidate", jQuery(this))
        return false;
    });

    jQuery('.nitropack-purge-cache').click(function (e) {
        e.preventDefault();
        clearCacheSingleHandler("purge", jQuery(this))
        return false;
    });

    jQuery('.nitropack-purge-cache-entire-site').click(function (e) {
        e.preventDefault();
        clearCacheEntireHandler("purge", jQuery(this))
        return false;
    });

});

