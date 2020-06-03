<?php
namespace SapiStudio\MyAudi;
use Illuminate\Config\Repository;

class Config
{
    private static $instance            = null;
    private static $configStoragePath   = null;
    private static $mainConfigName      = 'myaudi';
    private static $mainConfigFolder    = null;
    private static $configExtension     = '.config.php';
    private static $configsPaths        = [];
    
    /** Config::__callStatic() */
    public static function __callStatic($name, $args) {
        return self::getter(implode('.',array_filter([$name,implode('.',$args)])));
    }
    
    /** Config::initiate() */
    public static function initiate($dirPath = null){
        if(is_null(self::$instance)){
            static::$mainConfigFolder   = (!$dirPath) ? realpath(__DIR__).DIRECTORY_SEPARATOR.'configs'.DIRECTORY_SEPARATOR : false;
            self::$instance             = new Repository();
            register_shutdown_function([__NAMESPACE__.'\Config','saveConfigs']);
            self::$configStoragePath    = static::$mainConfigFolder.static::$mainConfigName.static::$configExtension;
            self::loadConfigFiles();
        }
        return self::$instance;
    }
    
    private static function loadConfigFiles(){
        if(!is_dir(static::$mainConfigFolder))
            return false;
        $files      = [];
        $phpFiles   = \SapiStudio\FileSystem\Handler::getFinder()->files()->name('*'.self::$configExtension)->in(static::$mainConfigFolder)->depth(0);
        if($phpFiles){
            foreach($phpFiles as $file) {
                $indexName          = (is_string($folderIndex)) ? $folderIndex.'.'.basename($file->getRealPath(),self::$configExtension) : basename($file->getRealPath(),self::$configExtension);
                if($indexName != static::$mainConfigName){
                    self::$configsPaths[$indexName] = $file->getRealPath();
                    self::$instance->set($indexName, require $file->getRealPath());
                }else
                    self::$instance->set(require $file->getRealPath());
            }
        }
        if(!is_file(self::$configStoragePath))
            throw new \Exception('Can not find main config file');
        return $files;
    }
    
    /** Config::saveConfigs() */
    public static function saveConfigs(){
        /** first save all other configs , except main config,if taht exists*/
        $configVariables = self::$instance->all();
        foreach(self::$configsPaths as $configIndex => $configPath){
            if(isset($configVariables[$configIndex])){
                \SapiStudio\FileSystem\Handler::dumpToConfig($configPath,array_filter($configVariables[$configIndex]));
                unset($configVariables[$configIndex]);
            }  
        }
        /** after this,save the main config file*/
        if(is_file(self::$configStoragePath))
            \SapiStudio\FileSystem\Handler::dumpToConfig(self::$configStoragePath,array_filter($configVariables));
        return;
    }
    
    /** Config::setter() */
    public static function setter($key,$value){
        self::$instance->offsetSet($key,$value);
    }
    
    /** Config::unsetter() */
    public static function unsetter($keys){
        $keys = (is_array($keys)) ? $keys : [$keys];
        foreach($keys as $key)
            self::$instance->offsetUnset($key,$value);
    }
    
    /** Config::getter() */
    public static function getter($key){
        return self::$instance->offsetGet($key);
    }
}
