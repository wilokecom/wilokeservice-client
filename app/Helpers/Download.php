<?php

namespace WilokeServiceClient\Helpers;

/**
 * Class Download
 * @package WilokeServiceClient\Helpers
 */
final class Download
{
    /**
     * @param $pluginPath
     *
     * @return mixed
     */
    public static function parsePluginName($pluginPath)
    {
        $aParsed = explode('/', $pluginPath);
        
        return $aParsed[0];
    }
    
    /**
     * @param string $dirPath
     *
     * @return bool
     */
    public static function removeDir($dirPath = '')
    {
        
        if (empty($dirPath)) {
            return false;
        }
        
        $dirPath = untrailingslashit($dirPath).WILOKESERVICE_DS;
        
        if ($dirPath == ABSPATH) {
            return false;
        }
        
        if (!is_dir($dirPath)) {
            return false;
        }
        
        $files = scandir($dirPath, 1);
        
        foreach ($files as $file) {
            if ($file != '.' && $file != '..') {
                if (is_dir($dirPath.$file)) {
                    self::removeDir($dirPath.$file);
                } else {
                    unlink($dirPath.$file);
                }
            }
        }
        
        if (is_file($dirPath.'.DS_Store')) {
            unlink($dirPath.'.DS_Store');
        }
        
        return rmdir($dirPath);
        
    }
    
    /**
     * @param $path
     * @param $type
     *
     * @return string
     */
    private static function downloadUrl($path, $type)
    {
        return add_query_arg(
            [
                'domain'   => home_url('/'),
                'date'     => time(),
                'type'     => $type,
                'path'     => $path,
                'token'    => GetSettings::getOptionField('secret_token'),
                'download' => self::parsePluginName($path)
            ],
            wilokeServiceGetConfigFile('app')['serviceURL']
        );
    }
    
    /**
     * @return string
     */
    public static function pluginsDirPath()
    {
        return dirname(WILOKESERVICE_CLIENT_DIR).WILOKESERVICE_DS;
    }
    
    /**
     * @param $path
     *
     * @return string
     */
    public static function downloadPluginUrl($path)
    {
        return self::downloadUrl($path, 'plugin');
    }
    
    /**
     * @param $path
     *
     * @return string
     */
    public static function createPluginZipPlaceholder($path)
    {
        return self::pluginsDirPath().self::parsePluginName($path).'.zip';
    }
}
