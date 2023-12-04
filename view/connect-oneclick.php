<div id="nitropack-container" class="wrap" style="visibility: hidden">
    <div class="row">
        <div class="col-md-12">
            <div id="login-container">
                <h3><?php esc_html_e('Welcome to NitroPack OneClick for WordPress!', 'nitropack'); ?></h3>
                <p><?php esc_html_e('Your license is managed by your hosting provider.', 'nitropack'); ?></p>
                <img src="<?= esc_url(plugin_dir_url(__FILE__)) ?>/images/nitropackwp.jpg" alt="NitroPack" />
                <hr />
                <h3><?php esc_html_e('Let\'s Get Started!', 'nitropack'); ?></h3>
                <p><?php esc_html_e('In order to connect NitroPack OneClick with WordPress you need to visit your hosting provider page', 'nitropack'); ?></p>
                <?php if ($oneClickConnectUrl) : ?>
                    <a class="btn btn-primary white" href="<?php esc_attr_e($oneClickConnectUrl); ?>" target="_blank">
                        <span id="connect-text"><?php esc_html_e('Connect to NitroPack OneClick', 'nitropack'); ?></span>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>