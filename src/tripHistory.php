<?php
namespace SapiStudio\AudiMMI;
use \SapiStudio\FileDatabase\Handler as Database;
use \Sapistudio\SapiMaps\Handler as MapsHandler;
class tripHistory
{
    private $handler;
    
    /**
     * tripHistory::query()
     * 
     * @return
     */
    public static function query(){
        return self::getDb()->query();
    }
    
    /**
     * tripHistory::getDb()
     * 
     * @return
     */
    public static function getDb(){
        $configFile = __dir__.DIRECTORY_SEPARATOR.'configs'.DIRECTORY_SEPARATOR.DatabaseConfig::HISTORY_DATABASE.'.db.php';
        if(!file_exists($configFile))
            return false;
        $databaseConfig         = require $configFile;
        $configOptions = ['dir' => realpath(__DIR__).DIRECTORY_SEPARATOR.'database'.DIRECTORY_SEPARATOR,'fields' => $databaseConfig];
        return Database::load(DatabaseConfig::HISTORY_DATABASE,$configOptions);
    }
    
    /**
     * tripHistory::__construct()
     * 
     * @param mixed $handler
     * @return
     */
    public function __construct(Handler $handler = null){
        $this->handler = $handler;
    }
    
    /**
     * tripHistory::checkingStatus()
     * 
     * @return
     */
    public function checkingStatus(){
        $position = $this->handler->loadPosition();
        if(!$position['findCarResponse'])
            return false;
        
        $carResponse    = $position['findCarResponse'];
        $database       = self::getDb();
        $lastEntry      = $database->findAll()->last();
        
        $parkingTime    = date("Y-m-d H:i", strtotime($carResponse->parkingTimeUTC));
        $locationEntry  = ['dateupdated'   => date("Y-m-d H:i")];
        $locationEntry['parkingelapsed'] = date_diff(date_create($parkingTime), date_create($locationEntry['dateupdated']))->format('%H:%I hrs');
        $status         = VehicleStatus::initStatus($this->handler->status());
        if(isset($lastEntry->parkingtime) && $lastEntry->parkingtime == $parkingTime){
            $locationEntry[DatabaseConfig::UNIQUE_IDENTIFIER] = $lastEntry->{DatabaseConfig::UNIQUE_IDENTIFIER};
        }else{
            $locationEntry['endingLatLng']      = self::coordinateConverter($carResponse->Position->carCoordinate->latitude).','.self::coordinateConverter($carResponse->Position->carCoordinate->longitude);
            $locationEntry['startingLatLng']    = (isset($lastEntry->endingLatLng)) ? $lastEntry->endingLatLng.','.$lastEntry->endinglon : $locationEntry['endingLatLng'];
            $locationEntry['parkingtime']       = $parkingTime;
            $locationEntry['fuelprocent']       = $status->getFieldData('0x030103000A');
            $locationEntry['currentmilleage']   = $status->getFieldData('0x0101010002');
            $locationEntry['remainingmilleage'] = $status->getFieldData('0x0301030005');
            $locationEntry['tripmilleage']      = (isset($lastEntry->currentmilleage)) ? ($locationEntry['currentmilleage'] - $lastEntry->currentmilleage) : ($locationEntry['currentmilleage'] - $locationEntry['currentmilleage']);

            $map = MapsHandler::load('directions')->setApiKey($this->handler->getCredential('googleApiKey'))->setParam(['origin'=>$locationEntry['startingLatLng'],'destination'=>$locationEntry['endingLatLng']])->query();
            
            $locationEntry['startingAddress']   = $map->getStartAddress();
            $locationEntry['endingAddress']     = $map->getEndAddress();
            $locationEntry['staticMap']         = $map->getStaticMap();
            $locationEntry['hasRefuel']         = ($lastEntry && ($lastEntry->fuelprocent < $locationEntry['fuelprocent'])) ? 1 : 0;
            /** pit stop to gas station*/
            if($locationEntry['hasRefuel'] == 1){
                try {
                    $locationEntry['refuelData'] = MapsHandler::load('nearbysearch')->setApiKey($this->handler->getCredential('googleApiKey'))->setParam(['location'=>$locationEntry['endingLatLng'],'type'=>'gas_station','radius'=>50])->query()->getFirst();
                }catch (\Exception $e) {
                    
                }
            }
        }
        return $database->addEntry($locationEntry);
    }
    
    /**
     * tripHistory::coordinateConverter()
     * 
     * @param mixed $coordinateNumber
     * @return
     */
    public static function coordinateConverter($coordinateNumber = null){
        $dot = strlen($coordinateNumber)-6;
        return substr($coordinateNumber,0,$dot).'.'.substr($coordinateNumber, $dot);
    }
}
