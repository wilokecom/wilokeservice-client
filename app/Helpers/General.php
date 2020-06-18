<?php
namespace WilokeServiceClient\Helpers;

use function Sodium\compare;

/**
 * Class General
 * @package WilokeServiceClient\Helpers
 */
class General
{
    /**
     * @return bool
     */
    public static function isWilcityServicePage()
    {
        if (!is_admin() || !isset($_GET['page']) ||
            $_GET['page'] !== wilokeServiceGetConfigFile('app')['updateSlug']) {
            return false;
        }
        
        return true;
    }
    
    /**
     * @param $newVersion
     * @param $currentVersion
     *
     * @return mixed
     */
    public static function isNewVersion($newVersion, $currentVersion)
    {
        return version_compare($newVersion, $currentVersion, '>');
    }
    
    /**
     * @param      $content
     * @param bool $isReturn
     *
     * @return string
     */
    public static function ksesHtml($content, $isReturn = false)
    {
        $allowed_html = [
            'a'      => [
                'href'     => [],
                'style'    => [
                    'color' => []
                ],
                'title'    => [],
                'target'   => [],
                'class'    => [],
                'data-msg' => []
            ],
            'div'    => ['class' => []],
            'h1'     => ['class' => []],
            'h2'     => ['class' => []],
            'h3'     => ['class' => []],
            'h4'     => ['class' => []],
            'h5'     => ['class' => []],
            'h6'     => ['class' => []],
            'br'     => ['class' => []],
            'p'      => ['class' => [], 'style' => []],
            'em'     => ['class' => []],
            'strong' => ['class' => []],
            'span'   => ['data-typer-targets' => [], 'class' => []],
            'i'      => ['class' => []],
            'ul'     => ['class' => []],
            'ol'     => ['class' => []],
            'li'     => ['class' => []],
            'code'   => ['class' => []],
            'pre'    => ['class' => []],
            'iframe' => ['src' => [], 'width' => [], 'height' => [], 'class' => ['embed-responsive-item']],
            'img'    => ['src' => [], 'width' => [], 'height' => [], 'class' => [], 'alt' => []],
            'embed'  => ['src' => [], 'width' => [], 'height' => [], 'class' => []],
        ];
        
        if (!$isReturn) {
            echo wp_kses(wp_unslash($content), $allowed_html);
        } else {
            return wp_kses(wp_unslash($content), $allowed_html);
        }
    }
}
