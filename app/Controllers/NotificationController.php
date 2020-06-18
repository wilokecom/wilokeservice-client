<?php

namespace WilokeServiceClient\Controllers;

use WilokeServiceClient\Helpers\RestApi;

/**
 * Class NotificationController
 * @package WilokeServiceClient\Controllers
 */
class NotificationController extends Controller
{
    public function __construct()
    {
        add_action('wilokeservice_hourly_event', [$this, 'fetchNotifications']);
        add_action('admin_init', [$this, 'updateReadStatus']);
        add_action('admin_head', [$this, 'addColorToNotificationIcon']);
        add_action('wilokeservice-clients/theme-updates', [$this, 'renderNotifications']);
        add_action('admin_init', [$this, 'focusFetchNotifications']);
    }
    
    public function addColorToNotificationIcon()
    {
        ?>
        <style>
            #toplevel_page_wilokeservice .dashicons-megaphone:before {
                color: red !important;
            }
        </style>
        <?php
    }
    
    /**
     * @return bool
     */
    public function updateReadStatus()
    {
        if (!$this->isWilcityServiceArea()) {
            return false;
        }
        
        if (get_option('wilokeservice_unread_notifications') !== 'yes') {
            return false;
        }
        
        if (!current_user_can('administrator')) {
            return false;
        }
        
        delete_option('wilokeservice_unread_notifications');
    }
    
    /**
     * @param $aNotification
     *
     * @return bool
     */
    private function renderNotification($aNotification)
    {
        if (!isset($aNotification['toggle']) || $aNotification['toggle'] === 'disable' || !isset($aNotification['content'])) {
            return false;
        }
        
        $status = isset($aNotification['status']) ? $aNotification['status'] : '';
        ?>
        <div class="ui message <?php echo esc_attr($status); ?>">
            <?php
            if (isset($aNotification['title']) && !empty($aNotification['title'])) :
                ?>
                <h3 class="ui heading"><?php echo $aNotification['title']; ?></h3>
            <?php endif; ?>
            <p><?php echo do_shortcode($aNotification['content']); ?></p>
        </div>
        <?php
    }
    
    /**
     * @return bool
     */
    public function renderNotifications()
    {
        if (!$this->isWilcityServiceArea()) {
            return false;
        }
        
        $aNotifications = get_option('wilokeservice_notifications');

        if (empty($aNotifications)) {
            return false;
        }
        
        if (isset($aNotifications['title'])) {
            $this->renderNotification($aNotifications);
        } else {
            foreach ($aNotifications as $aNotification) {
                $this->renderNotification($aNotification);
            }
        }
    }
    
    /**
     * @return bool
     */
    public function focusFetchNotifications()
    {
        if (!current_user_can('administrator') || !$this->isWilcityServiceArea()) {
            return false;
        }
        
        if (isset($_REQUEST['is-refresh-update']) && $_REQUEST['is-refresh-update'] === 'yes') {
            $this->fetchNotifications();
        }
    }
    
    /**
     * @return bool
     */
    public function fetchNotifications()
    {
        $aResponse = RestApi::get('notifications');
       
        if ($aResponse['status'] === '404' ||
            $aResponse['status'] === 'error' ||
            !isset($aResponse['data']) ||
            !isset($aResponse['data']['toggle']) ||
            $aResponse['data']['toggle'] === 'disable') {
            return false;
        }
        
        $aOldNotification = get_option('wilokeservice_notifications');
  
        update_option('wilokeservice_notifications', $aResponse['data']);
        if (empty($aOldNotification) ||
            $aOldNotification['saved_at'] != $aResponse['data']['saved_at']) {
            update_option('wilokeservice_unread_notifications', 'yes');
        }
    }
}
