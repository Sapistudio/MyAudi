<?php
namespace SapiStudio\AudiMMI;
use \SapiStudio\FileDatabase\Handler as Database;

class FileBase extends Database
{
    /**
     * FileBase::loadDatabase()
     * 
     * @param mixed $databaseName
     * @return
     */
    public static function loadDatabase($databaseName = null){
        if(!self::dbExists($databaseName)){
            try{
                $configFile = __dir__.DIRECTORY_SEPARATOR.'configs'.DIRECTORY_SEPARATOR.$databaseName.'.db.php';
                if(!file_exists($configFile))
                    return false;
                $databaseConfig         = require $configFile;
                self::createDatabase($databaseName, $databaseConfig);
            }catch(\Exception $e){
                return false;
            }
        }
        return self::load($databaseName);
    }
}