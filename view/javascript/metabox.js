(function($) {
  $(document).ready(function() {
    var statusHideTimeout = null;

    function clean_single_cache(postId, postUrl, type) {
      postUrl = postUrl || [];
      var action = type == "purge" ? "nitropack_purge_single_cache" : "nitropack_invalidate_single_cache";
      if (statusHideTimeout) {
        clearTimeout(statusHideTimeout);
      }

      $("#nitropack-status-msg").html('Working..&nbsp;&nbsp;<i class="fa fa-spinner fa-spin"></i>').show();
      $.ajax({
        url: ajaxurl,
        type: "POST",
        data: {
          action: action,
          postId: postId,
          postUrl: postUrl,
          nonce: metaboxdata.nitroNonce
        },
        success: function() {
          $("#nitropack-status-msg").html('<span style="color: green;">Success</span>');
          statusHideTimeout = setTimeout(function() {
            $("#nitropack-status-msg").fadeOut();
          }, 3000);
        },
        error: function() {
          $("#nitropack-status-msg").html('<span style="color: red;">Error. Please try again.</span>');
          statusHideTimeout = setTimeout(function() {
            $("#nitropack-status-msg").fadeOut();
          }, 3000);
        }
      });
    }

    $(".nitropack-purge-single").on("click", function() {
      var postId = $(this).data("post_id");
      var postUrl = $(this).data("post_url");
      clean_single_cache(postId, postUrl, "purge");
    });

    $(".nitropack-invalidate-single").on("click", function() {
      var postId = $(this).data("post_id");
      var postUrl = $(this).data("post_url");
      clean_single_cache(postId, postUrl, "invalidate");
    });
  });
})(jQuery);
