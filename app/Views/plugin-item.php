<?php
$aCurrentPluginInfo
	= isset($this->aInstalledPlugins[$this->buildPluginPathInfo($aPlugin['slug'])]) ?
	$this->aInstalledPlugins[$this->buildPluginPathInfo($aPlugin['slug'])] : false;
?>
<div class="wil-plugin-wrapper card" style="width: 300px;">
	<?php
	$productType = isset($aPlugin['productType']) ? $aPlugin['productType'] : 'Free';
	?>
	<div class="wil-top" style="padding: 10px; color: red; font-weight: 800">
		<?php if (!empty($aPlugin['productUrl'])) : ?>
			<span class="product-type"><a target="_blank"
			                              href="<?php echo esc_url($aPlugin['productUrl']);
			                              ?>"><?php echo ucfirst
					($productType); ?></a></span>
		<?php else : ?>
			<span class="product-type"><?php echo ucfirst($productType); ?></span>
		<?php endif; ?>
		<?php
		if (!$aCurrentPluginInfo && !empty($aPlugin['productUrl'])) :
			?>
			<a class="buy-now" href="<?php echo esc_url($aPlugin['productUrl']); ?>"
			   style="padding: 10px; color: green;
                                    font-weight: 800" target="_blank">Buy now</a>
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
			      style=" display: block; color: #222">Updated at:<?php echo date_i18n(get_option('date_format'),
					$aPlugin['updatedAt']); ?></span>
		</div>
		<div class="description" style="font-size: 13px">
			<?php echo $aPlugin['description']; ?>
		</div>
	</div>
	<?php $this->renderPluginButtons($aPlugin, $aCurrentPluginInfo); ?>
</div>