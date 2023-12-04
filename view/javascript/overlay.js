if (typeof window.requestAnimFrame == 'undefined') {
    window.requestAnimFrame = (function(){
      return window.requestAnimationFrame || window.webkitRequestAnimationFrame || window.mozRequestAnimationFrame ||
        function( callback ){
            window.setTimeout(callback, 1000 / 60);
        };
    })();
}

($ => {
    var intervals = new Map();
    var targets = [];

    const renderOverlay = target => {
        if (typeof $(target).data('overlay') == 'undefined') return;

        if ($(target).is(':visible')) {
            $(target).data('overlay').removeClass('card-overlay-hidden');

            $($(target).data('overlay')).css({
                'position': 'absolute',
                'top': $(target).offset().top.toString().concat('px'),
                'left': $(target).offset().left.toString().concat('px'),
                'width': $(target).outerWidth().toString().concat('px'),
                'height': $(target).outerHeight().toString().concat('px'),
            });
        } else {
            $(target).data('overlay').addClass('card-overlay-hidden');
        }
    }

    (function frameLoop() {
        targets.forEach(renderOverlay);
        window.requestAnimFrame(frameLoop);
    })();

    $.fn.cardOverlay = function(method, data = null) {
        return $(this).each(function(index, target) {
            $(target).data('overlay', undefined);
            $(target).data('interval', null);

            const dismissOverlay = (timeout = 0, skipClassAmendment = false) => {
                clearInterval(intervals.get(target));
                intervals.delete(target);

                var doDismiss = _ => {
                    targets = targets.filter(currentTarget => currentTarget != target)

                    $($(target).data('overlay')).remove();

                    if (!skipClassAmendment) {
                        $(target).removeClass('card-overlay-blurred');
                        $(target).removeClass('card-overlay-blurrable');
                    }
                }

                if (timeout) {
                    setTimeout(doDismiss, timeout);
                } else {
                    doDismiss();
                }
            }

            const methods = {
                error : function(data) {
                    dismissOverlay(0, true);

                    data = Object.assign({
                        message: '',
                        timeout: 0,
                        dismissable: true
                    }, data);

                    return makeOverlay('danger', data.message, data.timeout, data.dismissable);
                },
                success : function(data) {
                    dismissOverlay(0, true);

                    data = Object.assign({
                        message: '',
                        timeout: 0,
                        dismissable: true
                    }, data);

                    return makeOverlay('success', data.message, data.timeout, data.dismissable);
                },
                notify : function(data) {
                    dismissOverlay(0, true);

                    data = Object.assign({
                        message: '',
                        timeout: 0,
                        dismissable: true
                    }, data);

                    return makeOverlay('info', data.message, data.timeout, data.dismissable);
                },
                loading: function(data) {
                    dismissOverlay(0, true);

                    data = Object.assign({
                        message: ''
                    }, data);

                    return makeOverlay('muted', data.message, -1, false);
                },
                clear : dismissOverlay
            }

            const makeOverlay = (type, message, timeout, dismissable) => {
                var progressClass = "";

                $(target).data('overlay', $('<div class="card-overlay"></div>'));

                $($(target).data('overlay')).append( $('<div class="card-overlay-box"><div class="card-overlay-message text-' + type + '"><div class="card-overlay-text">' + message + '</div></div></div>') );

                if (dismissable) {
                    $($(target).data('overlay')).find('.card-overlay-message').prepend('<i class="card-overlay-dismiss fa fa-times"></i>');
                    $($(target).data('overlay')).find('.card-overlay-box').addClass('card-overlay-dismissable');
                }

                if (timeout) {
                    if (timeout >= 0) {
                        intervals.set(target, (time => {
                            var step = 1000;
                            var wait = 100;

                            return setInterval(_ => {
                                time = Math.max(time - step, 0);
                                var percent = Math.floor((time / timeout) * 100);

                                $($(target).data('overlay')).find('.progress-bar').css('width', percent + '%');

                                if (time == 0) {
                                    setTimeout(dismissOverlay, wait);
                                }
                            }, step);
                        })(timeout));
                    } else {
                        progressClass = " progress-bar-striped progress-bar-animated";
                    }

                    $($(target).data('overlay')).find('.card-overlay-box').prepend('<div class="progress"><div class="progress-bar bg-' + type + progressClass + '" role="progressbar" style="width: 100%"></div></div>');
                }

                $('body').append($(target).data('overlay'));

                $($(target).data('overlay')).on('click', '.card-overlay-dismiss', dismissOverlay);

                $(target).addClass('card-overlay-blurrable');
                $(target).addClass('card-overlay-blurred');

                targets.push(target);

                return target;
            }

            if (method && methods[method]) {
                return methods[method].call(target, data);
            } else {
                $.error('Method "' +  method + '" does not exist on jQuery.cardOverlay');
            }
        });
    };
})(jQuery);
