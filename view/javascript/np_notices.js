(function() {
  document.addEventListener("click", function(e) {
    if (e.target.matches(".notice.is-dismissible[data-dismissible-id] button.notice-dismiss")) {
      let noticeId = e.target.closest(".notice.is-dismissible[data-dismissible-id]").dataset.dismissibleId;
      document.cookie = "dismissed_notice_" + noticeId + "=1;path=/;max-age=" + (86400 * 30) + "; secure";
    }
  }, true);


  document.addEventListener("click", function(e) {
    if (e.target.matches(".rml_btn")) {
      var xhr = new XMLHttpRequest();
      var data = new FormData();
      data.append('action', 'nitropack_rml_notification');
      data.append('notification_id', e.target.dataset.notification_id);
      data.append('notification_end', e.target.dataset.notification_end);

      xhr.onreadystatechange = function() {
        if (xhr.readyState === XMLHttpRequest.DONE) {
          if (xhr.status === 200) {
            var response = JSON.parse(xhr.responseText);
            if (response.transient_status === true) {
              var notifications_count = document.getElementById('notifications-list').querySelectorAll('li').length;

              if ( notifications_count === 1 ) {
                document.getElementById('notifications').parentNode.parentNode.remove();
              } else {
                e.target.parentNode.parentNode.remove();
              }

            }
          } else {
            console.log('Error: ' + xhr.status);
          }
        }
      };
      xhr.open('POST', ajaxurl);
      xhr.send(data);
    }
  }, true);

})();

var loadDismissibleNotices = function() {
  var $ = jQuery;

  $(".notice.is-dismissible").each(function() {
    var b = $(this)
      , c = $('<button type="button" class="notice-dismiss"><span class="screen-reader-text"></span></button>');
      c.on("click.wp-dismiss-notice", function($) {
        $.preventDefault(),
          b.fadeTo(100, 0, function() {
            b.slideUp(100, function() {
              b.remove()
            })
          })
      }),
      b.append(c)
  });
}