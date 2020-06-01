<?php
namespace SapiStudio\MyAudi;
use Illuminate\Config\Repository;

class Config
{
    private static $instance            = null;
    private static $configStoragePath   = null;
    
    /** Config::__callStatic() */
    public static function __callStatic($name, $args) {
        return self::getter(implode('.',array_filter([$name,implode('.',$args)])));
    }
    
    /** Config::initiate() */
    public static function initiate($configName = 'myaudi'){
        if(is_null(self::$instance)){
            register_shutdown_function(['\SapiStudio\MyAudi\Config','saveConfigs']);
            self::$configStoragePath    = realpath(__DIR__).DIRECTORY_SEPARATOR.'configs'.DIRECTORY_SEPARATOR.$configName.'.php';
            self::$instance             = new Repository(require self::$configStoragePath);
        }
        return self::$instance;
    }
    
    /** Config::saveConfigs() */
    public static function saveConfigs(){
        return \SapiStudio\FileSystem\Handler::dumpToConfig(self::$configStoragePath,array_filter(self::$instance->all()));
    }
    
    /** Config::setter() */
    public static function setter($key,$value){
        self::$instance->offsetSet($key,$value);
    }
    
    /** Config::unsetter() */
    public static function unsetter($key){
        self::$instance->offsetUnset($key,$value);
    }
    
    /** Config::getter() */
    public static function getter($key){
        return self::$instance->offsetGet($key);
    }
}