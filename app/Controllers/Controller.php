<?php

namespace WilokeServiceClient\Controllers;

/**
 * Class Controller
 * @package WilokeServiceClient\Controllers
 */
class Controller
{
    /**
     * @return bool
     */
    protected function isWilcityServiceArea()
    {
        if (!isset($_REQUEST['page']) || $_REQUEST['page'] !== wilokeServiceGetConfigFile('app')['updateSlug']) {
            return false;
        }
        
        return true;
    }
}
