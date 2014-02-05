<?php
final class Rails
{
    static protected $serviceManager;
    
    static public function setServiceManager($serviceManager)
    {
        self::$serviceManager = $serviceManager;
    }
    
    static public function serviceManager()
    {
        return self::$serviceManager;
    }
}
