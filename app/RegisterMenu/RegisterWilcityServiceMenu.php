<?php

namespace WilokeServiceClient\RegisterMenu;

use WilokeServiceClient\Helpers\SemanticUi;

/**
 * Class RegisterWilcityServiceMenu
 * @package WilokeServiceClient\RegisterMenu
 */
class RegisterWilcityServiceMenu
{
    public static $optionKey = 'wilokeservice_client';
    
    public function __construct()
    {
        add_action('admin_menu', [$this, 'registerMenu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueScripts'], 999);
    }
    
    /**
     * @return bool
     */
    private function isWilcityServiceArea()
    {
        if (is_admin() && isset($_GET['page']) && $_GET['page'] == wilokeServiceGetConfigFile('app')['updateSlug']) {
            return true;
        }
        
        return false;
    }
    
    /**
     * @return bool
     */
    public function enqueueScripts()
    {
        if (!$this->isWilcityServiceArea()) {
            return false;
        }
        
        wp_register_style('semantic-ui', WILOKESERVICE_CLIENT_ASSSETS.'semantic-ui/form.min.css');
        wp_enqueue_style('semantic-ui');
        wp_register_script('semantic-ui', WILOKESERVICE_CLIENT_ASSSETS.'semantic-ui/semantic.min.js', ['jquery'], null,
            true);
        wp_enqueue_script('semantic-ui');
        
        wp_enqueue_script('wilokeservice-service', WILOKESERVICE_CLIENT_SOURCE.'script.js', ['jquery'],
            WILOKESERVICE_VERSION);
    }
    
    public function registerMenu()
    {
        $icon = get_option(
                    wilokeServiceGetConfigFile('app')['unreadOption']
                ) === 'yes' ? 'dashicons-megaphone' : 'dashicons-share-alt';
        
        add_menu_page(
            wilokeServiceGetConfigFile('app')['menu']['title'],
            wilokeServiceGetConfigFile('app')['menu']['title'],
            wilokeServiceGetConfigFile('app')['menu']['roles'],
            wilokeServiceGetConfigFile('app')['menu']['slug'],
            [$this, 'settings'],
            $icon
        );
    }
    
    /**
     * @return bool
     */
    private function saveConfiguration()
    {
        if (!current_user_can('administrator')) {
            return false;
        }
        
        if ((isset($_POST['wilokeservice_client']) && !empty($_POST['wilokeservice_client'])) &&
            isset($_POST['wilokeservice_client_nonce_field']) && !empty($_POST['wilokeservice_client_nonce_field']) &&
            wp_verify_nonce($_POST['wilokeservice_client_nonce_field'], 'wilokeservice_client_nonce_action')) {
            $aOptions = $_POST['wilokeservice_client'];
            
            foreach ($aOptions as $key => $val) {
                $aOptions[$key] = sanitize_text_field($val);
            }
            
            update_option(self::$optionKey, $aOptions);
        }
    }
    
    private function fsMethodNotification()
    {
        if (defined('FS_METHOD') && FS_METHOD !== 'direct') {
            SemanticUi::renderDescField(
                [
                    'desc'        => 'Please access to your hosting  by using cPanel or FileZilla -> Open wp-config.php -> Put define("FS_METHOD", "direct"); to this file',
                    'desc_status' => 'red'
                ]
            );
        }
    }
    
    public function settings()
    {
        $this->fsMethodNotification();
        $this->saveConfiguration();
        $aConfiguration = wilokeServiceGetConfigFile('settings');
        do_action('wilokeservice-clients/theme-updates');
        $aValues  = get_option(self::$optionKey);
        $aValues  = maybe_unserialize($aValues);
        $adminUrl = add_query_arg(
            [
                'page'              => wilokeServiceGetConfigFile('app')['updateSlug'],
                'is-refresh-update' => 'yes'
            ], admin_url('admin.php'));
        ?>
        <form action="<?php echo esc_url($adminUrl); ?>" method="POST"
              class="form ui" style="margin-top: 20px;">
            <?php
            wp_nonce_field('wilokeservice_client_nonce_action', 'wilokeservice_client_nonce_field');
            
            foreach ($aConfiguration['fields'] as $aField) :
                if (!in_array($aField['type'], ['open_segment', 'close_segment', 'submit'])) {
                    $aField['value'] = isset($aValues[$aField['id']]) ? $aValues[$aField['id']] : '';
                }
                
                switch ($aField['type']) {
                    case 'open_segment';
                        SemanticUi::renderOpenSegment($aField);
                        break;
                    case 'open_accordion';
                        SemanticUi::renderOpenAccordion($aField);
                        break;
                    case 'open_fields_group';
                        SemanticUi::renderOpenFieldGroup($aField);
                        break;
                    case 'close';
                        SemanticUi::renderClose();
                        break;
                    case 'close_segment';
                        SemanticUi::renderCloseSegment();
                        break;
                    case 'password':
                        SemanticUi::renderPasswordField($aField);
                        break;
                    case 'text';
                        SemanticUi::renderTextField($aField);
                        break;
                    case 'select_post';
                    case 'select_ui';
                        SemanticUi::renderSelectUiField($aField);
                        break;
                    case 'select':
                        SemanticUi::renderSelectField($aField);
                        break;
                    case 'textarea':
                        SemanticUi::renderTextareaField($aField);
                        break;
                    case 'submit':
                        SemanticUi::renderSubmitBtn($aField);
                        break;
                    case 'header':
                        SemanticUi::renderHeader($aField);
                        break;
                    case 'desc';
                        SemanticUi::renderDescField($aField);
                        break;
                }
            endforeach;
            ?>
        </form>
        <?php
    }
}
