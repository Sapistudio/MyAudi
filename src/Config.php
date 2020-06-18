<?php
namespace SapiStudio\MyAudi;
use Illuminate\Config\Repository;

class Config
{
    
    private static $instance            = null;
    private static $configStoragePath   = null;
    private static $mainConfigName      = 'myaudi';
    private static $mainConfigFolder    = null;
    private static $mainCacheFolder     = null;
    private static $configExtension     = '.config.php';
    private static $configsPaths        = [];
    private static $foldersPath         = ['cache','configs'];
    
    /** Config::__callStatic() */
    public static function __callStatic($name, $args) {
        return self::getter(implode('.',array_filter([$name,implode('.',$args)])));
    }
    
    /** Config::initiate() */
    public static function initiate($dirPath = null){
        self::checkFolderPaths();
        if(is_null(self::$instance)){
            register_shutdown_function([__NAMESPACE__.'\Config','saveConfigs']);
            self::$instance             = new Repository();
            self::$configStoragePath    = static::$mainConfigFolder.static::$mainConfigName.static::$configExtension;
            self::loadConfigFiles();
        }
        return self::$instance;
    }
    
    /** Config::checkFolderPaths() */
    private static function checkFolderPaths($workingDirectory = null){
        $workingDirectory = (!$workingDirectory) ? sys_get_temp_dir().DIRECTORY_SEPARATOR.'myAudiCache' : $workingDirectory;
        if (!is_dir($workingDirectory)) {
            @mkdir($workingDirectory, 0777, true);
            foreach(self::$foldersPath as $folder)
                @mkdir($workingDirectory.DIRECTORY_SEPARATOR.$folder, 0777, true);
            \SapiStudio\FileSystem\Handler::copyDirectory(realpath(__DIR__).DIRECTORY_SEPARATOR.'configs',$workingDirectory.DIRECTORY_SEPARATOR.'configs');
        }
        $workingDirectory           = (!$workingDirectory) ? realpath(__DIR__) : $workingDirectory;
        static::$mainConfigFolder   = $workingDirectory.DIRECTORY_SEPARATOR.'configs'.DIRECTORY_SEPARATOR;
        static::$mainCacheFolder    = $workingDirectory.DIRECTORY_SEPARATOR.'cache'.DIRECTORY_SEPARATOR;
    }
    
    /** Config::getCachePath() */
    public static function getCachePath(){
        return self::$mainCacheFolder;
    }
    
    /** Config::loadConfigFiles() */
    private static function loadConfigFiles(){
        if(!is_dir(static::$mainConfigFolder))
            return false;
        $files      = [];
        $phpFiles   = \SapiStudio\FileSystem\Handler::getFinder()->files()->name('*'.self::$configExtension)->in(static::$mainConfigFolder)->depth(0);
        if($phpFiles){
            foreach($phpFiles as $file) {
                $indexName  = basename($file->getRealPath(),self::$configExtension);
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
                $dumpText = (is_array($configVariables[$configIndex])) ? array_filter($configVariables[$configIndex]) : $configVariables[$configIndex];
                \SapiStudio\FileSystem\Handler::dumpToConfig($configPath,$dumpText);
                unset($configVariables[$configIndex]);
            }  
        }
        /** after this,save the main config file*/
        if(is_file(self::$configStoragePath))
            \SapiStudio\FileSystem\Handler::dumpToConfig(self::$configStoragePath,array_filter($configVariables));
        return;
    }
    
    /** Config::setter() */
    public static function setter($setData = [){
        self::$instance->offsetSet($setData);
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
