<div id="nitropack-safemode-popup">
    <i id="np-safemode-loader-loading" class="fa fa-refresh fa-spin np-safemode-loader-icon" style="display:none;"></i>
    <i id="np-safemode-loader-success" class="fa fa-check-circle np-safemode-loader-icon" style="display:none;"></i>
    <i id="np-safemode-loader-error" class="fa fa-times-circle np-safemode-loader-icon" style="display:none;"></i>
    <div id="np-safemode-content">    
        <div class="np-safemode-title">
            <?php esc_html_e( 'Did you know...', 'nitropack' ); ?>
        </div>
        <div class="np-safemode-msg">
            <?php esc_html_e( 'It is not necessary to deactivate NitroPack for troubleshooting.', 'nitropack' ); ?>
        <?php esc_html_e( 'You can use our', 'nitropack' ); ?> <a href="https://support.nitropack.io/hc/en-us/articles/360060910574-Safe-Mode" target="blank"><?php esc_html_e( 'Test Mode', 'nitropack' ); ?></a> <?php esc_html_e( 'instead.', 'nitropack' ); ?> <?php esc_html_e( 'Do you still want to deactivate?', 'nitropack' ); ?>
        </div>
        <div class="np-safemode-controls">
            <a id="np-safemode-nogo" class="btn btn-main" type="submit" name="np-safemode-nogo" ><?php esc_html_e( 'Yes, deactivate', 'nitropack' ); ?></a>&nbsp;
            <a id="np-safemode-go" class="btn btn-sec" type="submit" name="np-safemode-go" ><?php esc_html_e( 'No, enable "Test Mode"', 'nitropack' ); ?></a>
        </div>
    </div>
</div>
<div id="nitropack-safemode-veil"></div>