<style>
    .rml_btn {
        float: right;
        color: #989898 !important;
        font-size: 13px;
        cursor: pointer;
        text-decoration: underline !important;
    }

    .rml_btn:hover{
        color: #212529 !important;
        /*text-decoration: underline !important;*/
    }

    .card {
      box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1);
      border: none;
    }
</style>

<div>
  <div class="row mt-0">
    <div class="col-md-12">
      <?php nitropack_display_admin_notices(); ?>
    </div>
  </div>
  <?php if (count(get_nitropack()->Notifications->get('system')) > 0) { ?>
  <div class="row mt-0">
    <div class="col-12 mb-3">
      <div class="card-overlay-blurrable np-widget" id="notifications">
        <div class="card card-d-item">
          <div class="card-body">
            <h5 class="card-title" style="display: inline-block;"><?php esc_html_e( 'Notifications', 'nitropack' ); ?></h5>
            <ul class="list-group list-group-flush" id="notifications-list">
              <?php foreach(get_nitropack()->Notifications->get('system') as $notification) : ?>
              <li class="list-group-item px-0 d-flex justify-content-between align-items-center">
                  <div class="col-10">
                       <?php echo $notification['message']; ?>
                  </div>
                  <div class="col-2"> <a class="rml_btn" data-notification_end="<?php echo $notification['end_date']; ?>" data-notification_id="<?php echo $notification['id']; ?>">Remind me later</a> </div>
              </li>
              <?php endforeach; ?>
            </ul>
          </div>
        </div>
      </div>
    </div>
  </div>
  <?php } ?>
  <div class="row">

    <div class="col-md-6 mb-3">
      <div class="card-overlay-blurrable np-widget rounded" id="optimizations-widget">
        <div class="card card-d-item">
          <div class="card-body">
            <h5 class="card-title font-weight-bold" style="color: #484848; font-size: 17px"><?php esc_html_e( 'Optimized pages', 'nitropack' ); ?>
              <span id="pending-optimizations-section" class="pull-right mt-1" style="display:none;font-size: 12px;color: #28a745"><?php esc_html_e( 'Processing', 'nitropack' ); ?>
                <span id="pending-optimizations-count">X</span> page(s) in the background&nbsp;&nbsp;<i class="fa fa-spinner fa-spin"></i>
                <a href="https://support.nitropack.io/hc/en-us/articles/4766337974801" target="_blank" rel="noopener noreferrer" class="pull-right"><i data-info-tooltip class="mx-2 info-tooltip fa fa-info-circle text-primary" data-toggle="tooltip" data-placement="top" title="<?php esc_html_e( 'Click to learn more', 'nitropack' ); ?>"></i></a>
              </span>               
    
            </h5>
            <div class="row mt-4" data-hideable>
              <div id="optimized-pages"><span data-optimized-pages-total>0</span></div>
              <div id="last-cache-purge" class="text-secondary"><?php esc_html_e( 'Last cache purge:', 'nitropack' ); ?> <span data-last-cache-purge><?php esc_html_e( 'Never', 'nitropack' ); ?></span></div>
              <div id="last-cache-purge-reason" class="text-secondary"><?php esc_html_e( 'Reason:', 'nitropack' ); ?> <span data-purge-reason><?php esc_html_e( 'Unknown', 'nitropack' ); ?></span></div>
            </div>
            <div class="row mt-4 optimizations-hidden" data-hideable>
              <div class="optimizations-subcount"><span data-optimized-pages-mobile>0</span> <?php esc_html_e( 'mobile pages', 'nitropack' ); ?></div>
              <div class="optimizations-subcount"><span data-optimized-pages-tablet>0</span> <?php esc_html_e( 'tablet pages', 'nitropack' ); ?></div>
              <div class="optimizations-subcount"><span data-optimized-pages-desktop>0</span> <?php esc_html_e( 'desktop pages', 'nitropack' ); ?></div>
            </div>
            <div class="row mt-5 justify-content-center">
              <i id="np-purge-cache-loading"class="fa fa-refresh fa-spin" style="margin:5px;font-size:48px;display:none;"></i>
              <i id="np-purge-cache-success" class="fa fa-check-circle" style="margin:5px;font-size:48px;display:none;"></i>
              <i id="np-purge-cache-error" class="fa fa-times-circle" style="margin:5px;font-size:48px;display:none;"></i>
              <button id="optimizations-purge-cache" class="btn btn-light btn-outline-secondary btn-widget-optimizations"><?php esc_html_e( 'Purge Cache', 'nitropack' ); ?></button>
            </div>
          </div>
        </div>
      </div>
    </div>


    <div class="col-md-6 mb-3">
      <div class="card-overlay-blurrable np-widget rounded">
        <div class="card card-d-item">
          <div class="card-body">
            <?php if (empty($oneClickVendorWidget)) { ?>
            <div class="row mt-0" style="line-height: 0.5;">
              <div class="col-10">
                <h5 class="card-title font-weight-bold" style="color: #484848; font-size: 17px"><?php esc_html_e( 'What is NitroPack OneClick?', 'nitropack' ); ?></h5>
              </div>

              <div class="col-2 text-right">
               <span style="font-size: 44px; color:#d9d9d9;"> ⓘ </span>
              </div>
            </div>

            <div class="row mt-4">
              <div class="col-9">
                <p clas="default-fonts" style="font-size: 16.4px; color:#484848">NitroPack OneClick is technically a one-click version of NitroPack preconfigured with essential features for immediate use. Activate is effortlessly and enjoy an instant boost in page speed. </p>
              </div>
            </div>

            <div class="row">
              <button class="btn btn-outline-secondary btn-widget-optimizations"><?php esc_html_e( 'Learn More', 'nitropack' ); ?></button>
            </div>
            <?php } else { ?>
              <?php echo $oneClickVendorWidget; ?>
            <?php } ?>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<script>
    

  ($ => {
    var getOptimizationsTimeout = null;
    let isClearing = false;

    $(window).on("load",function() {
      $("#optimizations-widget").cardOverlay("loading", {message: "Loading optimizations data"});
      $(function () { $('[data-toggle="tooltip"]').tooltip()});
      getOptimizations();
    });

    $(document).on('click', '[data-hideable]', function(e) {
      e.preventDefault();

      $('[data-hideable]').removeClass('optimizations-hidden');

      $(this).addClass('optimizations-hidden');
    });

    $(document).on('click', '#optimizations-invalidate-cache', function(e) {
      e.preventDefault();
      //Overlay.loading("Invalidating cache...");

      invalidateEvent = new Event("cache.invalidate.request");
      window.dispatchEvent(invalidateEvent);
    });

    $(document).on('click', '#optimizations-purge-cache', function(e) {
      e.preventDefault();
      //Overlay.loading("Purging cache...");
      purgeCache();
    });

    let purgeCache = () => {
        let purgeEvent = new Event("cache.purge.request");
        window.dispatchEvent(purgeEvent);
    }

    $(document).on('click', '[nitropack-rc-data]', function(e) {
      e.preventDefault();
      if (isClearing) return;
        let currentButton = $(this);
        $.ajax({
          url: ajaxurl,
          type: "POST",
          dataType: "text",
          data: {
            action: 'nitropack_clear_residual_cache',
            gde: currentButton.attr('nitropack-rc-data'),
            nonce: nitroNonce
          },
          beforeSend: function () {
            currentButton.parent(".alert-warning").hide();
            isClearing = true;
          },
          success: function(resp) {
            result = JSON.parse(resp);
            Notification[result.type](result.message);
          },
          error: function(resp) {
            result = JSON.parse(resp);
            Notification[result.type](result.message);
          },
          complete: function() {
            isClearing = false;
            setTimeout(function(){location.reload();}, 3000);
          }
        });
    });

    var getOptimizations = _ => {
      var url = '<?php echo $optimizationDetailsUrl; ?>';
      ((s, e, f) => {
        if (window.fetch) {
          fetch(url)
          .then(resp => resp.json())
          .then(s)
          .catch(e)
          .finally(f);
        } else {
          $.ajax({
            url: url,
            type: 'GET',
            dataType: 'json',
            success: s,
            error: e,
            complete: f
          })
        }
      })(data => {
        $('[data-last-cache-purge]').text(data.last_cache_purge.timeAgo);
        if (data.last_cache_purge.reason) {
            $('[data-purge-reason]').text(data.last_cache_purge.reason);
            $('#last-cache-purge-reason').show();
          } else {
              $('#last-cache-purge-reason').hide();
            }

        if (data.pending_count) {
            $("#pending-optimizations-count").text(data.pending_count);
            $("#pending-optimizations-section").show();
          } else {
              $("#pending-optimizations-section").hide();
            }

        $('[data-optimized-pages-desktop]').text(data.optimized_pages.desktop);
        $('[data-optimized-pages-mobile]').text(data.optimized_pages.mobile);
        $('[data-optimized-pages-tablet]').text(data.optimized_pages.tablet);
        $('[data-optimized-pages-total]').text(data.optimized_pages.total);

        $("#optimizations-widget").cardOverlay("clear");
      }, __ => {
        $("#optimizations-widget").cardOverlay("error", {message: "<?php esc_html_e( 'Error while fetching optimizations data', 'nitropack' ); ?>"});
      }, __ => {
        if (!getOptimizationsTimeout) {
          getOptimizationsTimeout = setTimeout(function() {getOptimizationsTimeout = null; getOptimizations();}, 60000);
        }
      });
    }

    var loadSafemodeStatus = function() {
      $.ajax({
        url: ajaxurl,
        type: "POST",
        data: {
          action: "nitropack_safemode_status",
          nonce: nitroNonce
        },
        dataType: "json",
        success: function(resp) {
          if (resp.type == "success") {
            $("#nitropack-smenabled-notice").length && !!resp.isEnabled ? $("#nitropack-smenabled-notice").parent().show() : $("#nitropack-smenabled-notice").parent().hide();
          } else {
            setTimeout(loadSafemodeStatus, 500);
          }
        }
      });
    }
    loadSafemodeStatus();

    window.addEventListener("cache.invalidate.success", getOptimizations);
    if ($('#np-onstate-cache-purge').length) {
      window.addEventListener("cache.purge.success", function(){setTimeout(function(){document.cookie = "nitropack_apwarning=1; expires=Thu, 01 Jan 1970 00:00:01 GMT; path=<?php echo nitropack_cookiepath(); ?>"; window.location.reload()}, 1500)});
    } else {
      window.addEventListener("cache.purge.success", getOptimizations);
    }

    window.closeModalOverlay = function() {
      $("#settings-widget").cardOverlay("clear");
    }

    window.performCachePurge = () => {
        purgeCache();
        closeModalOverlay();
    }
  })(jQuery);
</script>
