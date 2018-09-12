<?php
namespace SapiStudio\AudiMMI;
use \Lazer\Classes\Database;
use \Lazer\Classes\Helpers\Validate;
use \Lazer\Classes\LazerException as FileException;

class FileBase
{
    /**
     * FileBase::loadDatabase()
     * 
     * @param mixed $databaseName
     * @return
     */
    public static function loadDatabase($databaseName = null){
        try{
            Validate::table($databaseName)->exists();
        }catch(FileException $e){
            try{
                $databaseConfig = require __dir__.DIRECTORY_SEPARATOR.'configs'.DIRECTORY_SEPARATOR.$databaseName.'.config.php';
                if(!$databaseConfig)
                    return false;
                Database::create($databaseName, $databaseConfig);
            }catch(FileException $e){
                return false;
            }
        }
        return Database::table($databaseName);
    }
    
    /**
     * FileBase::setDatabasePath()
     * 
     * @return void
     */
    public static function setDatabasePath(){
        if (!defined('LAZER_DATA_PATH')) {
            define('LAZER_DATA_PATH', realpath(__DIR__).DIRECTORY_SEPARATOR.'database'.DIRECTORY_SEPARATOR);//file system database path
        }
    }
}
