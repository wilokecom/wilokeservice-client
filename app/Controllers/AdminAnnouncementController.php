<?php


namespace WilokeServiceClient\Controllers;


use WilokeServiceClient\Models\AnnouncementModel;

class AdminAnnouncementController
{
	public function __construct()
	{
		add_action('admin_notices', [$this, 'maybeNotices']);
	}

	public function maybeNotices()
	{
		$aAnnouncements = AnnouncementModel::getAll();
		if (!empty($aAnnouncements)) {
			foreach ($aAnnouncements as $category => $aMessages) {
				if (!is_array($aMessages) || empty($aMessages)) {
					continue;
				}
				?>
                <div class="notice notice-<?php echo esc_attr($category); ?> is-dismissible">
                    <ol>
						<?php foreach ($aMessages as $msg): ?>
                            <li><?php echo $msg; ?></li>
						<?php endforeach; ?>
                    </ol>
                </div>
				<?php
			}
		}
	}
}