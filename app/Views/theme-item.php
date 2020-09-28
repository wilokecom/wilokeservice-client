<?php

use WilokeServiceClient\Helpers\General;

?>
<div class="wil-theme-item-wrapper card <?php echo $this->isCurrentTheme($aTheme) ? 'active' : ''; ?>">
    <div class="content" style="padding: 1.3em 1.2em;">
        <img class="right floated mini ui image" style="width: 60px"
             src="<?php echo esc_url($aTheme['thumbnail']); ?>">
        <div class="header"
             style="font-size: 1.1em; margin-bottom: 7px"><a target="_blank"
                                                             href="<?php echo esc_url($aTheme['preview']) ?>"><?php
				echo esc_html
				($aTheme['name']); ?></a></div>
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
                  style=" display: block; color: #222">
                                    Updated Date: <?php echo date_i18n(get_option('date_format'),
					$aTheme['updatedAt']); ?></span>
        </div>
        <div class="description" style="font-size: 13px">
			<?php echo $aTheme['description']; ?>
        </div>
    </div>
    <div class="extra content">
        <div class="ui two buttons wil-button-wrapper" data-slug="<?php echo esc_attr($aTheme['slug']); ?>">
			<?php if ($this->isCurrentTheme($aTheme)) : ?>
				<?php if ((General::isNewVersion($aTheme['version'], $this->oCurrentThemeVersion->get('Version')))): ?>
                    <div class="ui basic green button"><a class="wil-update-theme">Update</a></div>
				<?php endif; ?>
                <div class="ui basic red button">
                    <a target="_blank" href="<?php echo esc_url($this->aTheme['preview']); ?>">Changelog</a>
                </div>
			<?php else: ?>
				<?php if (!$this->isInstalledTheme($aTheme)) : ?>
                    <div class="ui basic green button">
                        <a href="<?php echo esc_url(admin_url('themes.php')); ?>" target="_blank">Install</a>
                    </div>
				<?php else: ?>
                    <div class="ui basic green button"><a class="wil-update-theme">Active</a></div>
				<?php endif; ?>
			<?php endif; ?>
        </div>
    </div>
</div>