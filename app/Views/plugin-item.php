<?php

use WilokeServiceClient\Helpers\General;

$aCurrentPluginInfo
	= isset($this->aInstalledPlugins[$this->buildPluginPathInfo($aPlugin)]) ?
	$this->aInstalledPlugins[$this->buildPluginPathInfo($aPlugin)] : false;

if (isset($aPlugin['isRequired']) && $aPlugin['isRequired'] == 'yes') {
	$pluginTypeColor = "red";
	$productType = "Required";
} else {
	$pluginTypeColor = "green";
	$productType = "Optional";
}

?>
<div class="wil-plugin-wrapper wil-item-wrapper card" style="width: 300px;">
    <div class="wil-top" style="padding: 10px; color: red; font-weight: 800">
		<?php if (!empty($aPlugin['productUrl'])) : ?>
            <span class="product-type">
                <a target="_blank"
                   href="<?php echo esc_url($aPlugin['productUrl']); ?>" style="color:<?php echo esc_attr($pluginTypeColor); ?>"><?php echo ucfirst($productType);
                   ?></a></span>
		<?php else : ?>
            <span class="product-type" style="color:<?php echo esc_attr($pluginTypeColor); ?>"><?php echo ucfirst($productType); ?></span>
		<?php endif; ?>
    </div>
    <div class="content" style="padding: 1.3em 1.2em;">
        <img class="right floated mini ui image" style="width: 60px"
             src="<?php echo esc_url($aPlugin['thumbnail']); ?>" alt="Thumbnail"/>
        <div class="header"
             style="font-size: 1.1em; margin-bottom: 7px"><?php echo esc_html($aPlugin['name']); ?></div>
        <div class="meta" style="font-size: 13px">
			<?php if ($aCurrentPluginInfo) : ?>
                <span class="version" style=" display: block; margin-bottom: 2px; color: #222">You are using: <span
                            class="wil-current-version"><?php echo esc_html($aCurrentPluginInfo['Version']); ?></span></span>
			<?php endif; ?>
            <span class="version" style=" display: block; margin-bottom: 2px; color: #222">New Version: <span
                        class="wil-new-version"><?php echo esc_html($aPlugin['version']); ?></span></span>
            <span class="updated_at"
                  style=" display: block; color: #222">Last Update: <?php echo date_i18n(get_option('date_format'),
					$aPlugin['updatedAt']); ?></span>
        </div>
        <div class="description" style="font-size: 13px">
			<?php echo $aPlugin['description']; ?>
        </div>
    </div>

    <div class="extra content">
		<?php wp_nonce_field('wiloke-service-nonce', 'wiloke-service-nonce-value'); ?>
        <div class="ui two buttons wil-button-wrapper"
             data-item-slug="<?php echo esc_attr($aPlugin['slug']); ?>"
             data-item-path="<?php echo esc_attr($this->buildPluginPathInfo($aPlugin)); ?>"
             data-item-type="plugin">
			<?php if (!$aCurrentPluginInfo) : ?>
                <div class="ui basic green button">
                    <a href="#"
                       class="wil-install-plugin wil-btn-action"
                       data-action="wiloke_download_plugin"
                       target="_blank">Install</a>
                </div>
			<?php elseif (General::isNewVersion($aPlugin['version'], $aCurrentPluginInfo['Version'])): ?>
                <div class="ui basic green button">
                    <a class="wil-update-plugin"
                       href="<?php echo esc_url($this->updateChangeLogURL($aPlugin)); ?>">Update</a>
                </div>
			<?php else: ?>
				<?php if (!is_plugin_active($this->buildPluginPathInfo($aPlugin))) : ?>
                    <div class="ui basic green button">
                        <a href="#"
                           class="wil-active-plugin wil-btn-action"
                           data-action="wiloke_activate_plugin"
                           target="_blank">Activate</a>
                    </div>
				<?php else: ?>
                    <div class="ui basic green button">
                        <a href="#"
                           class="wil-deactivate-plugin wil-btn-action"
                           data-action="wiloke_deactivate_plugin"
                           target="_blank">Deactivate</a>
                    </div>
				<?php endif; ?>
                <div class="ui basic red button">
                    <a target="_blank"
                       href="<?php echo esc_url($this->getPreviewURL($aPlugin)); ?>">Changelog</a>
                </div>
			<?php endif; ?>
        </div>
    </div>
</div>