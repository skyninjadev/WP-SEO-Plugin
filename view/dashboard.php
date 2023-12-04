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
</style>

<div>
  <div class="row">
    <div class="col-md-12">
      <?php nitropack_display_admin_notices(); ?>
    </div>
  </div>
  <?php if (count(get_nitropack()->Notifications->get('system')) > 0) { ?>
  <div class="row">
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
      <div class="card-overlay-blurrable np-widget" id="optimizations-widget">
        <div class="card card-d-item">
          <div class="card-body">
            <h5 class="card-title"><?php esc_html_e( 'Optimized Pages', 'nitropack' ); ?>
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
      <div class="card-overlay-blurrable np-widget" id="plan-details-widget">
        <div class="card card-d-item">
          <div class="card-body">
            <h5 class="card-title"><?php esc_html_e( 'Plan', 'nitropack' ); ?></h5>
            <div class="mt-3">
              <h5 class="font-weight-lighter"><span data-plan-title>Unknown</span> <a target="_blank" href="https://nitropack.io/user/billing" class="btn btn-primary btn-sm ml-3"><?php esc_html_e( 'Manage plan', 'nitropack' ); ?></a></h5>
            </div>
            <ul class="list-group list-group-flush" id="plan-quotas">
              <li class="list-group-item px-0 d-flex justify-content-between align-items-center"><?php esc_html_e( 'Next Reset', 'nitropack' ); ?> <span data-next-reset><?php esc_html_e( 'No ETA', 'nitropack' ); ?></span></li>
              <li class="list-group-item px-0 d-flex justify-content-between align-items-center"><?php esc_html_e( 'Next Billing', 'nitropack' ); ?> <span data-next-billing><?php esc_html_e( 'No ETA', 'nitropack' ); ?></span></li>
            </ul>
            <p class="mb-0 mt-2"><i class="fa fa-info-circle text-primary" aria-hidden="true"></i> <?php esc_html_e( 'You will be notified if you approach the plan resource limit', 'nitropack' ); ?></p>
          </div>
        </div>
      </div>
    </div>
    <div class="col-md-6 mb-3">
      <div class="card-overlay-blurrable np-widget" id="quicksetup-widget">
        <div class="card card-d-item">
          <div class="card-body">
            <h5 class="card-title"><?php esc_html_e( 'Optimization Mode', 'nitropack' ); ?></h5>
            <p><small><?php esc_html_e( 'Slide to change your settings. This will update the level of optimization.', 'nitropack' ); ?></small></p>

            <div id="range-container">
              <div id="labels"></div>
              <div id="range-element">
                <div id="divisors"></div>
                <input id="range" type="range" min="1" max="5" step="1" value="0" />
              </div>
            </div>

            <div id="description">
              <div class="text dummy">
                <h6 class="text-success"><?php esc_html_e( 'Dummy', 'nitropack' ); ?></h6>
                <p><small></small></p>
              </div>
              <div class="text standard">
                <h6 class="text-info"><?php esc_html_e( 'Standard', 'nitropack' ); ?></h6>
                <p><small><?php esc_html_e( 'A basic set of optimizations enough to get you up and running. Includes CDN and lossless image optimization.', 'nitropack' ); ?></small></p>
              </div>
              <div class="text medium">
                <h6 class="text-success"><?php esc_html_e( 'Medium', 'nitropack' ); ?></h6>
                <p><small><?php esc_html_e( 'Well-balanced and suitable for many cases.', 'nitropack' ); ?></small></p>
              </div>
              <div class="text strong">
                <h6 class="nitropack_success_text"><?php esc_html_e( 'Strong', 'nitropack' ); ?></h6>
                <p><small><?php esc_html_e( 'Very stable. Includes advanced features like automatic image lazy loading and font definition modification. This is the recommended optimization mode.', 'nitropack' ); ?></small></p>
              </div>
              <div class="text ludicrous">
                <h6 class="text-danger"><?php esc_html_e( 'Ludicrous', 'nitropack' ); ?></h6>
                <p><small><?php esc_html_e( 'A pre-defined configuration aiming to achieve the fastest possible speed. Prioritizes rendering.', 'nitropack' ); ?></small></p>
              </div>
              <div class="text custom">
                <h6><?php esc_html_e( 'Manual', 'nitropack' ); ?></h6>
                <p><small><?php esc_html_e( 'Use your own settings.', 'nitropack' ); ?> <a id="manual-settings-url" href="javascript:void(0);" target="_blank"><?php esc_html_e( 'Click here', 'nitropack' ); ?></a><?php  esc_html_e( 'to configure them.', 'nitropack' ); ?></small></p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-md-6 mb-3">
      <div class="card-overlay-blurrable np-widget" id="settings-widget">
        <div class="card card-d-item">
          <div class="card-body">
            <h5 class="card-title"><?php esc_html_e( 'Settings', 'nitropack' ); ?></h5>
            <ul class="list-group list-group-flush">
              <li class="list-group-item px-0 d-flex justify-content-between align-items-center">
                <span><?php esc_html_e( 'Cache Warmup', 'nitropack' ); ?>
                        <?php
                        $sitemap = get_option('np_warmup_sitemap', false);
                        $toolTipDisplayState = $sitemap ? '' : 'd-none';
                        ?>
                        <i class="fa fa-info-circle text-primary warmup-tooltip <?php echo $toolTipDisplayState; ?>" data-toggle="tooltip" data-placement="top" title="<?php echo esc_html_e($sitemap); ?>" aria-hidden="true"></i>
                    </br>
                  <small><?php esc_html_e( 'Learn more about this feature', 'nitropack' ); ?> <a href="https://support.nitropack.io/hc/en-us/articles/1500002555901-Cache-Warmup-WordPress-" target="_blank" rel="noreferrer noopener"><?php esc_html_e( 'here', 'nitropack' ); ?></a></small>
                </span>
                <span id="loading-warmup-status">
                  <?php esc_html_e( 'Loading cache warmup status', 'nitropack' ); ?> <i class="fa fa-refresh fa-spin" style="color: var(--blue);"></i>
                </span>
                <span id="warmup-toggle" style="display: none;">
                  <label id="warmup-status-slider" class="switch">
                    <input type="checkbox" id="warmup-status">
                    <span class="slider"></span>
                  </label>
                </span>
              </li>
              <li class="list-group-item px-0 d-flex justify-content-between align-items-center">
                <span><?php esc_html_e( 'Test Mode', 'nitropack' ); ?></br>
                  <small><?php esc_html_e( 'Learn more about this feature', 'nitropack' ); ?> <a href="https://support.nitropack.io/hc/en-us/articles/360060910574-Safe-Mode" target="_blank" rel="noreferrer noopener"><?php esc_html_e( 'here', 'nitropack' ); ?></a></small>
                </span>
                <span id="loading-safemode-status">
                  <?php esc_html_e( 'Loading test mode status', 'nitropack' ); ?> <i class="fa fa-refresh fa-spin" style="color: var(--blue);"></i>
                </span>
                <span id="safemode-toggle" style="display: none;">
                  <label id="safemode-status-slider" class="switch">
                    <input type="checkbox" id="safemode-status">
                    <span class="slider"></span>
                  </label>
                </span>
              </li>
              <li class="list-group-item px-0 d-flex justify-content-between align-items-center">
                <span id="detecting-compression" style="display: none;"><?php esc_html_e( 'Testing current compression status', 'nitropack' ); ?> <a href="javascript:void(0);"><i class="fa fa-refresh fa-spin" style="color: var(--blue);"></i></a></span>
                <span id="detected-compression"><?php esc_html_e( 'HTML Compression', 'nitropack' ); ?> <a href="javascript:void(0);" id="compression-test-btn" data-toggle="tooltip" data-placement="top" title="<?php esc_html_e( 'Automatically detect whether compression is needed', 'nitropack' ); ?>"><i class="fa fa-refresh" style="color: var(--blue);"></i></a></span>
                <span>
                  <label class="switch">
                    <input type="checkbox" id="compression-status" <?php echo (int)$enableCompression === 1 ? "checked" : ""; ?>>
                    <span class="slider"></span>
                  </label>
                </span>
              </li>
              <?php if (nitropack_render_woocommerce_cart_cache_option()) { ?>
                <li class="list-group-item px-0 d-flex justify-content-between align-items-center">
                  <span><?php esc_html_e( 'Ecommerce Cart Cache', 'nitropack' ); ?> <span class="badge badge-info"><?php echo nitropack_is_cart_cache_available() ? __( 'New', 'nitropack' ) : '<a href="https://' . NITROPACKIO_HOST . '/pricing" target="_blank" rel="noopener noreferrer" class="text-white">'.__( 'Requires a paid subscription', 'nitropack' ).'</a>'; ?></span></br>
                      <small><?php esc_html_e( 'Your visitors will enjoy full site speed while browsing with items in cart. Fully optimized page cache will be served.', 'nitropack' ); ?></small>
                  </span>
                  <span id="cart-cache-toggle">
                    <label class="switch" id="cart-cache-status-slider">
                    <input type="checkbox" id="cart-cache-status" <?php if (nitropack_is_cart_cache_active()) echo "checked"; ?> <?php if (!nitropack_is_cart_cache_available()) echo "disabled"; ?>>
                      <span class="slider"></span>
                    </label>
                  </span>
                </li>
              <?php } ?>
              <li class="list-group-item px-0 d-flex justify-content-between align-items-center">
                <span>
                  <a href="javascript:void(0);" class="btn btn-danger text-white" id="disconnect-btn"><i class="fa fa-power-off text-white"></i>&nbsp;&nbsp;<?php esc_html_e( 'Disconnect', 'nitropack' ); ?></a>
                </span>
              </li>
            </ul>
            <p class="mb-0 mt-2"><i class="fa fa-info-circle text-primary" aria-hidden="true"></i> <?php esc_html_e( 'You can further configure how NitroPack\'s optimization behaves through your account at', 'nitropack' ); ?> <a href="https://<?php echo NITROPACKIO_HOST; ?>/" target="_blank">https://<?php echo NITROPACKIO_HOST; ?>/&nbsp;&nbsp;<i class="fa fa-external-link"></i></a>.</p>
          </div>
        </div>
      </div>
    </div>
    <div class="col-md-6 mb-3">
      <div class="card-overlay-blurrable np-widget" id="automations-widget">
        <div class="card card-d-item">
          <div class="card-body">
            <h5 class="card-title"><?php esc_html_e( 'Automated Behavior', 'nitropack' ); ?></h5>
            <ul class="list-group list-group-flush">
              <li class="list-group-item px-0 d-flex justify-content-between align-items-center">
                <span><?php esc_html_e( 'Purge affected cache when content is updated or published', 'nitropack' ); ?></span>
                <span id="auto-purge-toggle">
                  <label id="auto-purge-status-slider" class="switch">
                    <input type="checkbox" id="auto-purge-status" <?php if ($autoCachePurge) echo "checked"; ?>>
                    <span class="slider"></span>
                  </label>
                </span>
              </li>
              <li class="list-group-item px-0 d-flex justify-content-between align-items-center">
                <span>Use legacy purge<br/>
                  <small>Selecting this forces NitroPack to use the old cache invalidation algorithm</small>
                </span>
                <span id="legacy-purge-toggle">
                  <label id="legacy-purge-status-slider" class="switch">
                    <input type="checkbox" id="legacy-purge-status" <?php if ($legacyPurge) echo "checked"; ?>>
                    <span class="slider"></span>
                  </label>
                </span>
              </li>
              <?php if (\NitroPack\Integration\Plugin\BeaverBuilder::isActive()) { ?>
              <li class="list-group-item px-0 d-flex justify-content-between align-items-center">
                <span><?php esc_html_e( 'Purge NitroPack cache when Beaver Builder cache is purged', 'nitropack' ); ?><br/>
                  <small><?php esc_html_e( 'Warning: This will perform a full NitroPack cache purge', 'nitropack' ); ?></small>
                </span>
                <span id="bb-purge-toggle">
                  <label id="bb-purge-status-slider" class="switch">
                    <input type="checkbox" id="bb-purge-status" <?php if ($bbCacheSyncPurge) echo "checked"; ?>>
                    <span class="slider"></span>
                  </label>
                </span>
              </li>
              <?php } ?>
              <li class="list-group-item px-0 d-flex justify-content-between align-items-center">
                <span><?php esc_html_e( 'Select what post/page types get optimized', 'nitropack' ); ?></span>
                <span id="cacheable-post-types-btn">
                  <a href="javascript:void(0);" class="btn btn-light btn-outline-secondary" data-toggle="modal" data-target="#cacheable-post-types-modal"><i class="fa fa-cog"></i></a>
                </span>
              </li>
            </ul>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Post Types Modal -->
<div class="modal" id="cacheable-post-types-modal" tabindex="-1" role="dialog" aria-labelledby="cacheable-post-types-title" aria-hidden="true" data-backdrop="false">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="cacheable-post-types-title"><?php esc_html_e( 'Configure page types that can be optimized', 'nitropack' ); ?></h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body nitropack-scrollable-modal-body">
        <ul class="list-group list-group-flush">
          <?php foreach ($objectTypes as $objectType) {?>
          <li class="list-group-item px-0 d-flex justify-content-between align-items-center border-0">
            <span><?php echo $objectType->label; ?></span>
            <span id="post-type-<?php echo $objectType->name; ?>-toggle">
              <label id="post-type-<?php echo $objectType->name; ?>-status-slider" class="switch">
                <input class="cacheable-post-type" name="<?php echo $objectType->name; ?>" type="checkbox" id="post-type-post-status" <?php if (in_array($objectType->name, $cacheableObjectTypes)) echo 'checked'; ?>>
                <span class="slider"></span>
              </label>
            </span>
          </li>
          <?php if (!empty($objectType->taxonomies)) {?>
            <?php foreach ($objectType->taxonomies as $taxonomyType) {?>
            <li class="list-group-item px-0 d-flex justify-content-between align-items-center list-group-item-indented border-0">
              <span><?php echo $taxonomyType->label; ?></span>
              <span id="post-type-<?php echo $taxonomyType->name; ?>-toggle">
                <label id="post-type-<?php echo $taxonomyType->name; ?>-status-slider" class="switch">
                  <input class="cacheable-post-type" name="<?php echo $taxonomyType->name; ?>" type="checkbox" id="post-type-post-status" <?php if (in_array($taxonomyType->name, $cacheableObjectTypes)) echo 'checked'; ?>>
                  <span class="slider"></span>
                </label>
              </span>
            </li>
            <?php }?>
          <?php }?>
          <?php }?>
        </ul>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal"><?php esc_html_e( 'Close', 'nitropack' ); ?></button>
        <button type="button" class="btn btn-primary" id="save-cacheable-post-types"><?php esc_html_e( 'Save changes', 'nitropack' ); ?>&nbsp;&nbsp;<i class="fa fa-spinner fa-spin d-none"></i></button>
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
      $("#plan-details-widget").cardOverlay("loading", {message: "Loading plan data"});
      $("#quicksetup-widget").cardOverlay("loading", {message: "Loading settings"});
      $(function () { $('[data-toggle="tooltip"]').tooltip()});
      getOptimizations();
      getPlan();
      getQuickSetup();

      
      <?php if ($checkedCompression != 1) { ?>
        autoDetectCompression();
      <?php } ?>
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

    $("#btn-run-warmup").on("click", function(e) {
      runWarmup();
    })

    $("#btn-stop-warmup").on("click", function(e) {
      disableWarmup();
    })

    var estimateWarmup = (id, retry) => {
      id = id || null;
      retry = retry || 0;
      if (!id) {
        $("#settings-widget").cardOverlay("loading", {message: "<?php esc_html_e( 'Estimating optimizations usage', 'nitropack' ); ?>"});
        //$("#estimation-spinner").show();
        //$("#warmup-status-slider").hide();

        $.post(ajaxurl, {
          action: 'nitropack_estimate_warmup',
          nonce: nitroNonce
        }, function(response) {
          var resp = JSON.parse(response);
          if (resp.type == "success") {
            setTimeout( (function(id){
              estimateWarmup(id);
            })(resp.res), 1000 );
          } else {
            $("#settings-widget").cardOverlay("error", {message: "<?php esc_html_e( 'Warmup estimation failed', 'nitropack' ); ?>", timeout: 3000});
          }
        });
      } else {
        $.post(ajaxurl, {
          action: 'nitropack_estimate_warmup',
          estId: id,
          nonce: nitroNonce
        }, function(response) {
          var resp = JSON.parse(response);
          if (resp.type == "success") {
            if (isNaN(resp.res) || resp.res == -1) { // Still calculating
              if (retry >= 10) {
                $("#settings-widget").cardOverlay("error", {message: "<?php esc_html_e( 'Warmup estimation failed. Please try again or contact support if the issue persists.', 'nitropack' ); ?>", dismissable: true});
              } else {
                setTimeout( (function(id, retry){
                  estimateWarmup(id, retry);
                })(id, retry+1), 1000 );
              }
            } else {
              if (resp.res == 0) {
                $("#settings-widget").cardOverlay("notify", {message: "<?php esc_html_e( 'We could not find any links for warming up on your home page', 'nitropack' ); ?>", timeout: 3000});
              } else {
                var confirmHtml = '<p>Enabling cache warmup will optimize ' + resp.res + ' pages. Would you like to continue?</p>';
                confirmHtml += '<p><a href="javascript:void(0);" onclick="rejectWarmup()" class="btn btn-default btn-sm"><?php esc_html_e( 'No', 'nitropack' ); ?></a>&nbsp;&nbsp;<a href="javascript:void(0);" onclick="confirmWarmup()" class="btn btn-success btn-sm"><?php esc_html_e( 'Yes', 'nitropack' ); ?></p></a>';
                $("#settings-widget").cardOverlay("notify", {message: confirmHtml});

                if (resp.sitemap_indication) {
                    jQuery('.warmup-tooltip').removeClass('d-none');
                    jQuery('.warmup-tooltip').attr('title', resp.sitemap_indication);
                }
              }
            }
          } else {
            $("#settings-widget").cardOverlay("error", {message: "<?php esc_html_e( 'Warmup estimation failed', 'nitropack' ); ?>", timeout: 3000});
          }
        });
      }
    }

    window.confirmWarmup = function() {
      $("#settings-widget").cardOverlay("loading", {message: "<?php esc_html_e( 'Enabling warmup', 'nitropack' ); ?>"});
      enableWarmup();
    }

    window.rejectWarmup = function() {
      $("#settings-widget").cardOverlay("clear");
    }

    var enableWarmup = () => {
      jQuery.post(ajaxurl, {
        action: 'nitropack_enable_warmup',
        nonce: nitroNonce
      }, function(response) {
        var resp = JSON.parse(response);
        if (resp.type == "success") {
          $("#settings-widget").cardOverlay("clear");
          $("#warmup-status").attr("checked", true);
        } else {
          setTimeout(enableWarmup, 1000);
        }
      });
    }

    var disableWarmup = () => {
      jQuery.post(ajaxurl, {
        action: 'nitropack_disable_warmup',
        nonce: nitroNonce
      }, function(response) {
        var resp = JSON.parse(response);
        if (resp.type == "success") {
          // Success notification
            jQuery('.warmup-tooltip').addClass('d-none');
            jQuery('.warmup-tooltip').attr('title', '');
        } else {
          // Error notification
        }
      });
    }

    var runWarmup = () => {
      jQuery.post(ajaxurl, {
        action: 'nitropack_run_warmup',
        nonce: nitroNonce
      }, function(response) {
        var resp = JSON.parse(response);
        if (resp.type == "success") {
          // Success notification
        } else {
          // Error notification
        }
      });
    }

    var enableSafemode = () => {
      jQuery.post(ajaxurl, {
        action: 'nitropack_enable_safemode',
        nonce: nitroNonce
      }, function(response) {
        var resp = JSON.parse(response);
        if (resp.type == "success") {
            $("#safemode-status").attr("checked", true);
            $("#nitropack-smenabled-notice").parent().show();
          // Success notification
        } else {
            $("#safemode-status").attr("checked", false);
          // Error notification
        }
      });
    }

    var disableSafemode = () => {
      jQuery.post(ajaxurl, {
        action: 'nitropack_disable_safemode',
        nonce: nitroNonce
      }, function(response) {
        var resp = JSON.parse(response);
        if (resp.type == "success") {
          // Success notification
            $("#nitropack-smenabled-notice").parent().hide();
        } else {
          // Error notification
        }
      });
    }

   

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

    var getPlan = _ => {
      var url = '<?php echo $planDetailsUrl; ?>';
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
        $('[data-plan-title]').text(data.plan_title);
        $('[data-next-billing]').text(data.next_billing ? data.next_billing : 'N/A');
        $('[data-next-reset]').text(data.next_reset ? data.next_reset : 'N/A');

        for (prop in data) {
            if (prop.indexOf("show_") === 0) continue;
            if (prop.indexOf("label_") === 0) continue;
            if (prop.indexOf("max_") === 0) continue;
            if (
                typeof data["show_" + prop] != "undefined" &&
                data["show_" + prop] &&
                typeof data["label_" + prop] != "undefined" &&
                typeof data["max_" + prop] != "undefined"
            ) {
                let propertyLabel = data["label_" + prop];
                let propertyValue = data[prop];
                let propertyLimit = data["max_" + prop];
                $("#plan-quotas").append('<li class="list-group-item px-0 d-flex justify-content-between align-items-center">' + propertyLabel + ' <span><span data-optimizations>' + propertyValue + '</span> out of <span data-max-optimizations>' + propertyLimit + '</span></span></li>');
            }
        }

        $("#plan-details-widget").cardOverlay("clear");
      }, __ => {
        $("#plan-details-widget").cardOverlay("error", {message: "<?php esc_html_e( 'Error while fetching plan data', 'nitropack' ); ?>"});
      }, __ => {
      });
    }

    var getQuickSetup = _ => {
      var url = '<?php echo $quickSetupUrl; ?>';
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
        $('#range').val(data.optimization_level);
        $('#manual-settings-url').attr('href', data.manual_settings_url);

        document.getElementById('range').oninput(false);
        $("#quicksetup-widget").cardOverlay("clear");
      }, __ => {
        $("#quicksetup-widget").cardOverlay("error", {message: "<?php esc_html_e( 'Error while fetching the optimization level settings', 'nitropack' ); ?>"});
      }, __ => {
      });
    }

    window.addEventListener("cache.invalidate.success", getOptimizations);
    if ($('#np-onstate-cache-purge').length) {
      window.addEventListener("cache.purge.success", function(){setTimeout(function(){document.cookie = "nitropack_apwarning=1; expires=Thu, 01 Jan 1970 00:00:01 GMT; path=<?php echo nitropack_cookiepath(); ?>"; window.location.reload()}, 1500)});
    } else {
      window.addEventListener("cache.purge.success", getOptimizations);
    }


    var autoDetectCompression = function() {
      $("#settings-widget").cardOverlay("loading", {message: "<?php esc_html_e( 'Testing current compression status', 'nitropack' ); ?>"});

      $.post(ajaxurl, {
        action: 'nitropack_test_compression_ajax',
        nonce: nitroNonce
      }, function(response) {
        var resp = JSON.parse(response);
        if (resp.status == "success") {
          if (resp.hasCompression) { // compression already enabled
            $("#compression-status").attr("checked", false);
            $("#settings-widget").cardOverlay("success", {message: "<?php esc_html_e( 'Compression is already enabled on your server! There is no need to enable it in NitroPack.', 'nitropack' ); ?>", timeout: 3000});
          } else { // no compression - enable ours
            $("#compression-status").attr("checked", true);
            $("#settings-widget").cardOverlay("success", {message: "<?php esc_html_e( 'No compression was detected! We will now enable it in NitroPack.', 'nitropack' ); ?>", timeout: 3000});
          }
          Notification.success('Compression settings saved');
        } else {
          $("#settings-widget").cardOverlay("error", {message: "<?php esc_html_e( 'Could not determine compression status automatically. Please configure it manually.', 'nitropack' ); ?>", timeout: 3000});
        }
      });
    }

    $("#compression-status").on("click", function(e) {
      $.post(ajaxurl, {
        action: 'nitropack_set_compression_ajax',
        nonce: nitroNonce,
        data: {
          compressionStatus: $(this).is(":checked") ? 1 : 0
        }
      }, function(response) {
        Notification.success("<?php esc_html_e( 'Compression settings saved', 'nitropack' ); ?>");
      });
    });

    $("#auto-purge-status").on("click", function(e) {
      $.post(ajaxurl, {
        action: 'nitropack_set_auto_cache_purge_ajax',
        nonce: nitroNonce,
        autoCachePurgeStatus: $(this).is(":checked") ? 1 : 0
      }, function(response) {
        Notification.success("<?php esc_html_e( 'Automatic cache purge settings saved', 'nitropack' ); ?>");
      });
    });

    $("#cart-cache-status").on("click", function(e) {
      $.post(ajaxurl, {
        action: 'nitropack_set_cart_cache_ajax',
        nonce: nitroNonce,
        cartCacheStatus: $(this).is(":checked") ? 1 : 0
      }, function(response) {
        var resp = JSON.parse(response);
        if (resp.type == "success") {
          Notification.success(resp.message);
        } else {
          Notification.error(resp.message);
        }
      });
    });

    $("#bb-purge-status").on("click", function(e) {
      $.post(ajaxurl, {
        action: 'nitropack_set_bb_cache_purge_sync_ajax',
        nonce: nitroNonce,
        bbCachePurgeSyncStatus: $(this).is(":checked") ? 1 : 0
      }, function(response) {
        Notification.success("<?php esc_html_e( 'Beaver Builder cache purge sync settings are saved.', 'nitropack' ); ?>");
      });
    });

    $("#legacy-purge-status").on("click", function(e) {
      $.post(ajaxurl, {
        action: 'nitropack_set_legacy_purge_ajax',
        nonce: nitroNonce,
        legacyPurgeStatus: $(this).is(":checked") ? 1 : 0
      }, function(response) {
        Notification.success('Legacy cache purge settings are saved.');
      });
    });

    $("#save-cacheable-post-types").on("click", function(e) {
      $(this).find("i").removeClass("d-none");
      $.ajax({
        url: ajaxurl,
        type: "POST",
        data: {
          action: 'nitropack_set_cacheable_post_types',
          nonce: nitroNonce,
          cacheableObjectTypes: $('.cacheable-post-type:checked').map(function(i, el){ return el.name; }).toArray()
        },
        success: function() {
          Notification.success("<?php esc_html_e( 'Changes saved', 'nitropack' ); ?>");
        },
        error: function() {
          Notification.error("<?php esc_html_e( 'There was an error while saving the changes. Please try again.', 'nitropack' ); ?>");
        },
        complete: function() {
          $("#save-cacheable-post-types i").addClass("d-none");
          $("#cacheable-post-types-modal").modal("hide");
        }
      });
    });

    $(document).on('click', "#compression-test-btn", e => {
      e.preventDefault();

      autoDetectCompression();
    });

    window.confirmDisconnect = function() {
      $("#settings-widget").cardOverlay("loading", {message: "<?php esc_html_e( 'Disconnecting...', 'nitropack' ); ?>"});
      $.ajax({
        url: ajaxurl,
        type: "POST",
        data: {
          action: "nitropack_disconnect",
          nonce: nitroNonce
        },
        complete: function() {
          location.reload();
        }
      });
    }

    window.closeModalOverlay = function() {
      $("#settings-widget").cardOverlay("clear");
    }

    window.performCachePurge = () => {
        purgeCache();
        closeModalOverlay();
    }

    $(document).on('click', "#disconnect-btn", e => {
      e.preventDefault();

      var confirmHtml = "<p><?php esc_html_e( 'Are you sure that you wish to disconnect?', 'nitropack' ); ?></p>";
      confirmHtml += '<p id="disconnectConfirmBtns"><a href="javascript:void(0);" onclick="closeModalOverlay()" class="btn btn-default btn-sm">No</a>&nbsp;&nbsp;<a href="javascript:void(0);" onclick="confirmDisconnect()" class="btn btn-info btn-sm">Disconnect</a></p>';
      $("#settings-widget").cardOverlay("notify", {message: confirmHtml});
    });

    $("#warmup-status-slider").on("click", function(e) {
      e.preventDefault();
      var isEnabled = $("#warmup-status").is(":checked");
      if (isEnabled) {
        disableWarmup();
        $("#warmup-status").attr("checked", false);
      } else {
        estimateWarmup();
      }
    });

    var loadWarmupStatus = function() {
      $.ajax({
        url: ajaxurl,
        type: "POST",
        data: {
          action: "nitropack_warmup_stats",
          nonce: nitroNonce
        },
        dataType: "json",
        success: function(resp) {
          if (resp.type == "success") {
            $("#warmup-status").attr("checked", !!resp.stats.status);
            $("#loading-warmup-status").hide();
            $("#warmup-toggle").show();
          } else {
            setTimeout(loadWarmupStatus, 500);
          }
        }
      });
    }
    loadWarmupStatus();

      $("#safemode-status-slider").on("click", function (e) {
          e.preventDefault();
          let isEnabled = $("#safemode-status").is(":checked");
          if (isEnabled) {
              let confirmHtml = '<p><strong>Purge Cache?</strong></p>' +
                  '<p>It is recommended to perform a full cache purge so all changes are correctly applied.</p>' +
                  '<p>Would you like to purge the cache now?</p>' +
                  '<p><a href="javascript:void(0);" onclick="closeModalOverlay()" class="btn btn-default btn-sm">No</a>&nbsp;&nbsp;<a href="javascript:void(0);" onclick="performCachePurge()" class="btn btn-info btn-sm">Yes (Recommended)</a></p>';
              $("#settings-widget").cardOverlay("notify", {message: confirmHtml, dismissable: false});
              disableSafemode();
              $("#safemode-status").attr("checked", false);
          } else {
              enableSafemode();
              $("#safemode-status").attr("checked", true);
          }
      });

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
            $("#safemode-status").attr("checked", !!resp.isEnabled);
            $("#nitropack-smenabled-notice").length && !!resp.isEnabled ? $("#nitropack-smenabled-notice").parent().show() : $("#nitropack-smenabled-notice").parent().hide();
            $("#loading-safemode-status").hide();
            $("#safemode-toggle").show();
          } else {
            setTimeout(loadSafemodeStatus, 500);
          }
        }
      });
    }
    loadSafemodeStatus();
  })(jQuery);

  (_ => {
    const classIndex = {
        1: 'range-success',
        2: 'range-warning',
        3: 'range-danger',
        4: 'range-ludicrous',
        5: 'range-manual',
    };

    let rangeInputElement = document.getElementById('range');
        
    let className = document.getElementsByClassName("label");
    
    let min = parseInt(rangeInputElement.min);
    let max = parseInt(rangeInputElement.max);

    const atTimeout = (_ => {
      var timeout;

      return (callback, time) => {
        clearTimeout(timeout);
        timeout = setTimeout(callback, time)
      };
    })();

    const saveSetting = function(value) {
      return new Promise((resolve, reject) => {
        var xhr = new XMLHttpRequest();

        xhr.open("POST", '<?php echo $quickSetupSaveUrl; ?>', true);

        //Send the proper header information along with the request
        xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

        xhr.onreadystatechange = function() { // Call a function when the state changes.
            if (this.readyState === XMLHttpRequest.DONE && this.status === 200) {
                resolve();
            }
        }

        xhr.send("setting=" + value);
      });
    }

    let changeMode = function(do_save) {
     
      let children = document.getElementById('description').children;
      let shown;

      if(this.getAttribute("value")) {
        shown = parseInt(this.getAttribute("value"));
      }
      if(this.value) {
        shown = parseInt(this.value);
      }
      
      for (var i = 0; i < children.length; i++) {
        children.item(i).classList.toggle('hidden', i != shown);
      }

      document.getElementById('range').classList.remove('range-success', 'range-warning', 'range-danger', 'range-ludicrous', 'range-manual');
      
      rangeInputElement.value = shown;
 
      if (classIndex[shown]) {
        document.getElementById('range').classList.add(classIndex[shown]);
      }

      if (do_save) {
          atTimeout(async function() {
              jQuery("#quicksetup-widget").cardOverlay("loading", {message: "Saving..."});
              await saveSetting(shown);
              jQuery("#quicksetup-widget").cardOverlay("clear");
          }, 0);
      }
    };

    rangeInputElement.oninput = changeMode;
    //rangeInputElement.oninput(false);

    for (var i = min; i <= max; i++) {
      let divisor = document.createElement('div');
      let textDescription = document.getElementById('description').children.item(i).getElementsByTagName('p').item(0).textContent;
      
      let label = document.createElement('div');

      divisor.classList.add("divisor");
      document.getElementById('divisors').appendChild(divisor);

      label.setAttribute('data-toggle', 'tooltip');
      label.setAttribute('data-placement', 'top');
      label.setAttribute('title', textDescription);
      label.setAttribute('value', i);
      label.textContent = document.getElementById('description').children.item(i).getElementsByTagName('h6').item(0).textContent;
      label.classList.add("label");
      document.getElementById('labels').appendChild(label);
    }


    for (var i = 0; i < className.length; i++) {
        className[i].addEventListener('click', changeMode, false);
    }
  })();
</script>
