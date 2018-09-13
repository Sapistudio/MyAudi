<?php
namespace SapiStudio\AudiMMI;
use \Lazer\Classes\Database;
use \Lazer\Classes\Helpers\Validate;
use \Lazer\Classes\LazerException;

class FileBase extends Database
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
        }catch(LazerException $e){
            try{
                $configFile = __dir__.DIRECTORY_SEPARATOR.'configs'.DIRECTORY_SEPARATOR.$databaseName.'.db.php';
                if(!file_exists($configFile))
                    return false;
                $databaseConfig         = require $configFile;
                self::create($databaseName, $databaseConfig);
            }catch(LazerException $e){
                return false;
            }
        }
        $self       = new self();
        $self->name = $databaseName;
        $self->setFields();
        $self->setPending();
        return $self;
    }
    
    /**
     * FileBase::setDatabasePath()
     * 
     * @return void
     */
    public static function setDatabasePath(){
        if (!defined('LAZER_DATA_PATH')){
            define('LAZER_DATA_PATH', realpath(__DIR__).DIRECTORY_SEPARATOR.'database'.DIRECTORY_SEPARATOR);//file system database path
        }
    }
    
    /**
     * FileBase::addEntry()
     * 
     * @param mixed $data
     * @return void
     */
    public function addEntry($data = [])
    {
        if(!$data)
            return false;
        if(!isset($data[DatabaseConfig::UNIQUE_IDENTIFIER]))
            $this->clearQuery();
        foreach ($data as $name => $value){
            if (Validate::table($this->name)->field($name) && Validate::table($this->name)->type($name,$value))
                $this->set->{$name} = utf8_encode($value);
        }
        $this->save();
    }
    
    /**
     * FileBase::addEntry()
     * 
     * @param mixed $data
     * @return void
     */
    public function getEntry($entryId = null)
    {
        try{
            $data = (is_int($entryId)) ? (array)$this->find($entryId)->set : $this->asArray();
        }catch(LazerException $e){
            $data = [];
        }
        return json_decode(json_encode($data));
    }
}
