<div>
  <div class="row">
    <div class="col-md-6 mb-3">
      <div class="card-overlay-blurrable np-widget" id="diagnostic-widget">
        <div class="card card-d-item">
          <div class="card-body">
            <h5 class="card-title"><?php esc_html_e( 'Diagnostics report', 'nitropack' ); ?></h5>
            <ul class="list-group list-group-flush">                        
              <li class="list-group-item px-0 d-flex justify-content-between align-items-center">
                <span id="loading-general-info">
                  <?php esc_html_e( 'Include NitroPack info(version, methods, environment)', 'nitropack' ); ?>
                </span>
                <span id="general-info-toggle">
                  <label id="general-info-slider" class="switch">
                    <input type="checkbox" class="diagnostic-option" id="general-info-status" checked>
                    <span class="slider"></span>
                  </label>
                </span>
              </li>
              <li class="list-group-item px-0 d-flex justify-content-between align-items-center">
                <span id="loading-plugins-status">
                  <?php esc_html_e( 'Include active plugins list', 'nitropack' ); ?>
                </span>
                <span id="active-plugins-toggle">
                  <label id="active-plugins-slider" class="switch">
                    <input type="checkbox" class="diagnostic-option" id="active-plugins-status" checked>
                    <span class="slider"></span>
                  </label>
                </span>
              </li>
              <li class="list-group-item px-0 d-flex justify-content-between align-items-center">
                <span id="conflicting-plugins-info">
                  <?php esc_html_e( 'Include conflicting plugins list', 'nitropack' ); ?>
                </span>
                <span id="conflicting-plugins-toggle">
                  <label id="conflicting-plugins-slider" class="switch">
                    <input type="checkbox" class="diagnostic-option" id="conflicting-plugins-status" checked>
                    <span class="slider"></span>
                  </label>
                </span>
              </li>
              <li class="list-group-item px-0 d-flex justify-content-between align-items-center">
                <span id="loading-user-config">
                  <?php esc_html_e( 'Include user config', 'nitropack' ); ?>
                </span>
                <span id="user-config-toggle">
                  <label id="user-config-slider" class="switch">
                    <input type="checkbox" class="diagnostic-option" id="user-config-status" checked>
                    <span class="slider"></span>
                  </label>
                </span>
              </li>
              <li class="list-group-item px-0 d-flex justify-content-between align-items-center">
                <span id="loading-dir-info">
                  <?php esc_html_e( 'Include directory info(structure,permissions)', 'nitropack' ); ?>
                </span>
                <span id="dir-info-toggle">
                  <label id="dir-info-slider" class="switch">
                    <input type="checkbox" class="diagnostic-option" id="dir-info-status" checked>
                    <span class="slider"></span>
                  </label>
                </span>
              </li>
              <li class="list-group-item px-0 d-flex justify-content-between align-items-center">
                <span>
                  <a id="gen-report-btn" href="javascript:void(0);" class="btn btn-light btn-outline-secondary"><i class="fa fa-refresh fa-spin" style="display:none" id="diagnostics-loader"></i>&nbsp;&nbsp;<?php esc_html_e( 'Generate&nbsp;Report', 'nitropack' ); ?></a>
                </span>
              </li>
            </ul>
            <p class="mb-0 mt-2"><i class="fa fa-info-circle text-primary" aria-hidden="true"></i> <?php esc_html_e( 'The generated report will be saved to your computer and can later be attached to your ticket.', 'nitropack' ); ?></p>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<script>

($ => {
  let isReportGenerating = false;

  $("#gen-report-btn").on("click", function(e) {
    if (isReportGenerating) return;

    $.ajax({
      url: ajaxurl,
      type: "POST",
      dataType: "text",
      data: {
        action: 'nitropack_generate_report',
        nonce: nitroNonce,
        toggled: {
          "general-info-status": $("#general-info-status:checked").length,
          "active-plugins-status": $("#active-plugins-status:checked").length,
          "conflicting-plugins-status": $("#conflicting-plugins-status:checked").length,
          "user-config-status": $("#user-config-status:checked").length,
          "dir-info-status": $("#dir-info-status:checked").length
        }
      },
      beforeSend: function (xhr,sett) {
        if ($(".diagnostic-option:checked").length > 0) {
          $("#diagnostics-loader").show();
          isReportGenerating = true;
          return true;
        } else {
          alert("<?php esc_html_e( 'Please select at least one of the report options', 'nitropack' ); ?>");
          return false;
        }
      },
      success: function(response, status, xhr) {
        if (response.length > 1) {
          var filename = "";
          var disposition = xhr.getResponseHeader('Content-Disposition');
          if (disposition && disposition.indexOf('attachment') !== -1) {
            var filenameRegex = /filename[^;=\n]*=((['"]).*?\2|[^;\n]*)/;
            var matches = filenameRegex.exec(disposition);
            if (matches != null && matches[1]) filename = matches[1].replace(/['"]/g, '');
          }

          var type = xhr.getResponseHeader('Content-Type');
          var blob = new Blob([response], { type: type });

          if (typeof window.navigator.msSaveBlob !== 'undefined') {
            // IE workaround for "HTML7007: One or more blob URLs were revoked by closing the blob for which they were created. These URLs will no longer resolve as the data backing the URL has been freed."
            window.navigator.msSaveBlob(blob, filename);
          } else {
            var URL = window.URL || window.webkitURL;
            var downloadUrl = URL.createObjectURL(blob);

            if (filename) {
              // use HTML5 a[download] attribute to specify filename
              var a = document.createElement("a");
              // safari doesn't support this yet
              if (typeof a.download === 'undefined') {
                window.location.href = downloadUrl;
              } else {
                a.href = downloadUrl;
                a.download = filename;
                document.body.appendChild(a);
                a.click();
              }
            } else {
              window.location.href = downloadUrl;
            }

            setTimeout(function () { URL.revokeObjectURL(downloadUrl); }, 100);
          }
          Notification.success("<?php esc_html_e( 'Report generated successfully.', 'nitropack' ); ?>");
        } else {
          Notification.error("<?php esc_html_e( 'Response is empty. Report generation failed.', 'nitropack' ); ?>");
        }
      },
      error: function() {
        Notification.error("<?php esc_html_e( 'There was an error while generating the report.', 'nitropack' ); ?>");
      },
      complete: function() {
        $("#diagnostics-loader").hide();
        isReportGenerating = false;
      }
    });
  });
})(jQuery);
</script>
