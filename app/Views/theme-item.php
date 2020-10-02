<?php

use WilokeServiceClient\Helpers\General;

?>
<div class="wil-theme-item-wrapper wil-item-wrapper card <?php echo $this->isCurrentTheme($aTheme) ? 'active' : ''; ?>">
    <div class="wil-top" style="padding: 10px; color: red; font-weight: 800">
		<?php if (!empty($aTheme['documentationUrl'])) : ?>
            <span class="product-type">
                <a target="_blank"
                   href="<?php echo esc_url($aTheme['documentationUrl']); ?>"
                   style="color:green">Documentation</a></span>
		<?php endif; ?>
    </div>

    <div class="content" style="padding: 1.3em 1.2em;">
        <img class="right floated mini ui image" style="width: 60px"
             src="<?php echo esc_url($aTheme['thumbnail']); ?>">
        <div class="header"
             style="font-size: 1.1em; margin-bottom: 7px">
            <a target="_blank"
               href="<?php echo esc_url($aTheme['preview']) ?>"><?php echo esc_html($aTheme['name']); ?></a></div>
        <div class="meta" style="font-size: 13px">
                                <span class="version" style=" display: block; margin-bottom: 2px; color: #222">
                                    You are using: <span class="wil-current-version">
                                        <?php echo esc_html($this->getCurrentTheme()->get('Version')); ?></span>
                                </span>
            <span class="version" style=" display: block; margin-bottom: 2px; color: #222">
                                    New Version: <span
                        class="wil-new-version"><?php echo esc_html($aTheme['version']); ?></span>
                                </span>
            <span class="updated_at"
                  style=" display: block; color: #222">Updated Date: <?php echo date_i18n(get_option('date_format'),
					$aTheme['updatedAt']); ?></span>
        </div>
        <div class="description" style="font-size: 13px">
			<?php echo $aTheme['description']; ?>
        </div>
    </div>
    <div class="extra content">
		<?php wp_nonce_field('wiloke-service-nonce', 'wiloke-service-nonce-value'); ?>
        <div class="ui two buttons wil-button-wrapper" data-item-slug="<?php echo esc_attr($aTheme['slug']); ?>"
             data-item-type="theme">
			<?php if ($this->isCurrentTheme($aTheme)) : ?>
				<?php if ((General::isNewVersion($aTheme['version'], $this->oCurrentThemeVersion->get('Version')))): ?>
                    <a class="ui basic green button wil-update-theme">Update</a>
				<?php endif; ?>
                <a class="ui basic red button wil-btn-action" target="_blank" href="<?php echo esc_url
				($this->aTheme['preview']); ?>">Changelog</a>
			<?php else: ?>
				<?php if (!$this->isInstalledTheme($aTheme['slug'])) : ?>
                    <a class="wwil-install-theme ui basic green button wil-btn-action"
                       href="<?php echo esc_url(admin_url('themes.php')); ?>"
                       data-action="wiloke_download_theme" data-target="<?php echo $aTheme['slug']; ?>"
                       target="_blank">Install</a>
				<?php else: ?>
					<?php if (!defined('WILOKE_THEME')) : ?>
                        <a class="wil-install-theme ui basic green button wil-btn-action" href="#"
                           data-action="wiloke_activate_theme">Active</a>
					<?php endif; ?>
				<?php endif; ?>
			<?php endif; ?>
        </div>
    </div>
</div>