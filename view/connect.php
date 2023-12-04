<div id="nitropack-container" class="wrap" style="visibility: hidden">
    <div class="row">
        <div class="col-md-12">
            <div id="login-container">
                <h3><?php esc_html_e( 'Welcome to NitroPack for WordPress!', 'nitropack' ); ?></h3>
                <p><?php esc_html_e( 'This page will help you to connect your WordPress site with NitroPack in few steps.', 'nitropack' ); ?></p>
                <img src="<?= plugin_dir_url(__FILE__) ?>/images/nitropackwp.jpg" alt="NitroPack"/>
                <hr />
                <h3><?php esc_html_e( 'Let\'s Get Started!', 'nitropack' ); ?></h3>
                <p><?php esc_html_e( 'In order to connect NitroPack with WordPress you need to configure your API details. More information how to obtain these values can be found', 'nitropack' ); ?> <a href="https://nitropack.io/blog/post/how-to-get-your-site-id-and-site-secret" target="_blank">here <i class="fa fa-external-link"></i></a></p>
                <form class="form-default" action="options.php" method="post" id="api-details-form">
                    <?php settings_fields( NITROPACK_OPTION_GROUP );
                    do_settings_sections( NITROPACK_OPTION_GROUP ); ?>
                    <div id="submitdiv" class="postbox ">
                        <h3><?php esc_html_e( 'Welcome!', 'nitropack' ); ?></h3>
                        <div id="manual-connect-fields" style="display:none;">
                          <h2><?php esc_html_e( 'Enter site ID and site secret to start using NitroPack', 'nitropack' ); ?></h2>
                          <input id="nitropack-siteid-input" name="nitropack-siteId" type="text" class="form-control" placeholder="<?php esc_html_e( 'Site ', 'nitropack' ); ?>">
                          <input id="nitropack-sitesecret-input" name="nitropack-siteSecret" type="text" class="form-control" placeholder="<?php esc_html_e( 'Site Secret', 'nitropack' ); ?>">
                        </div>
                        <div class="e-submit">
                            <a class="btn btn-primary white" id="api-details-form-submit" href="javascript:void(0);">
                                <i id="connect-spinner" class="fa fa-spinner fa-spin white" style="display:none;"></i>
                                <span id="connect-text"><?php esc_html_e( 'Connect to NitroPack', 'nitropack' ); ?></span>
                            </a>
                            <h1 id="connect-success" style="display:none;margin-bottom:auto;font-size:36px;"><i class="fa fa-check-circle"></i></h1>
                        </div>
                        <div class="clearfix"></div>
                        <a id="switch-connect-type" data-state="manual"><small><?php esc_html_e( 'Connect manually', 'nitropack' ); ?></small></a>
                    </div>
                </form>
                <p><?php esc_html_e( 'Having trouble connecting? Head over to', 'nitropack' ); ?> <a href="<?php echo NITROPACK_SUPPORT_BUBBLE_URL; ?>" target="_blank" rel="noreferrer noopener"><?php echo NITROPACK_SUPPORT_BUBBLE_URL; ?></a>.</p>
            </div>
        </div>
    </div>
</div>

<script>
(function($) {

    let connectPopup = null;
    let homePageUrl = "<?php echo get_home_url(); ?>";
    let nitroNonce = '<?php echo wp_create_nonce(NITROPACK_NONCE); ?>';

    window.addEventListener("message", function(e) {
      if (e.data.messageType == "nitropack-connect") {
        $("#nitropack-siteid-input").val(e.data.api.key);
        $("#nitropack-sitesecret-input").val(e.data.api.secret);
        $("#api-details-form-submit").click();
        connectPopup.close();
        connectPopup = null;
      }
    });

    $("#api-details-form-submit").on("click", function(e) {

      let siteId = $("#nitropack-siteid-input").val();
      let siteSecret = $("#nitropack-sitesecret-input").val();
      let isManualConnect = $("#manual-connect-fields").is(":visible");

      if (isManualConnect || (siteId && siteSecret)) {
        e.preventDefault();
        $("#connect-spinner").show();
        $("#connect-text").hide();
        jQuery.post(ajaxurl, {
            action: 'nitropack_verify_connect',
            siteId: siteId,
            siteSecret: siteSecret,
            nonce: nitroNonce
        }, function(response) {
            $("#connect-spinner").hide();

            var resp = JSON.parse(response);
            if (resp.status == "success") {
                location.reload();
                $("#connect-success").show();
                $("#api-details-form-submit").hide();
                //$("#api-details-form").ajaxSubmit({
                //    complete: function() {
                //        location.reload();
                //    }
                //});
                return;
            } else {
              if (!isManualConnect) {
                $("#nitropack-siteid-input").val("");
                $("#nitropack-sitesecret-input").val("");
              }

                jQuery("#submitdiv .notice").remove();

                if (resp.message) {
                    jQuery('#submitdiv').prepend('<div class="notice notice-error is-dismissible"><p style="word-break: break-word;">' + resp.message + '</p></div>');
                } else {
                    jQuery('#submitdiv').prepend('<div class="notice notice-error is-dismissible"><p style="word-break: break-word;"><?php esc_html_e( "Api details verification failed! Please check whether you entered correct details.", "nitropack" ); ?></p></div>');
                }

                jQuery(".notice").addClass("shake"); //Adds Shake animation to the error notice box to catch user attention.
                loadDismissibleNotices();
            }
            $("#connect-text").show();
        });
      } else if (!isManualConnect) {
          if (!connectPopup || !connectPopup.window) {
              let screenWidth = window.screen.availWidth;
              let screenHeight = window.screen.availHeight;
              let windowWidth = 500;
              let windowHeight = 700;
              let leftPos = window.top.outerWidth / 2 + window.top.screenX - ( windowWidth / 2);
              let topPos = window.top.outerHeight / 2 + window.top.screenY - ( windowHeight / 2);

              connectPopup = window.open("https://<?php echo NITROPACKIO_HOST; ?>/auth?website=" + homePageUrl, "QuickConnect", "width=" + windowWidth + ",height=" + windowHeight + ",left=" + leftPos + ",top=" + topPos);
          } else if (connectPopup && connectPopup.window) {
              connectPopup.focus();
          }
      }
    });

    $('#switch-connect-type').on('click', function(){
        let connectType = $(this).attr('data-state');

        if ( 'auto' === connectType ) {
            $('#manual-connect-fields').hide();
            $('#submitdiv .notice').hide();
            $('#switch-connect-type').html("<small><?php esc_html_e( 'Connect manually', 'nitropack' ); ?></small>");
            $('#switch-connect-type').attr('data-state', 'manual');
        }

        if ( 'manual' === connectType ) {
            $('#manual-connect-fields').show();
            $('#submitdiv .notice').show();
            $('#switch-connect-type').html("<small><?php esc_html_e( 'Connect automatically', 'nitropack' ); ?></small>");
            $('#switch-connect-type').attr('data-state', 'auto');
        }
    });

})(jQuery);
</script>

