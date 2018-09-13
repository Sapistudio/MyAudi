<?php
namespace SapiStudio\AudiMMI;

class tripHistory
{
    private $handler;
    
    /**
     * tripHistory::__construct()
     * 
     * @param mixed $handler
     * @return
     */
    public function __construct(Handler $handler){
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
        $database       = FileBase::loadDatabase(DatabaseConfig::HISTORY_DATABASE);
        $lastEntry      = $database->getEntry($database->lastId());
        $parkingTime    = date("Y-m-d H:i", strtotime($carResponse->parkingTimeUTC));
        $locationEntry  = ['dateupdated'   => date("Y-m-d H:i")];
        $status         = VehicleStatus::initStatus($this->handler->status());
        if(isset($lastEntry->parkingtime) && $lastEntry->parkingtime == $parkingTime){
            $locationEntry[DatabaseConfig::UNIQUE_IDENTIFIER] = $lastEntry->{DatabaseConfig::UNIQUE_IDENTIFIER};
        }else{
            if(isset($lastEntry->endinglat)){
                $locationEntry['startinglat'] = $lastEntry->endinglat;
                $locationEntry['startinglon'] = $lastEntry->endinglon;
            }
            $locationEntry['endinglat'] = self::coordinateConverter($carResponse->Position->carCoordinate->latitude);
            $locationEntry['endinglon'] = self::coordinateConverter($carResponse->Position->carCoordinate->longitude);
            $locationEntry['parkingtime'] = $parkingTime;
            $locationEntry['fuelprocent'] = $status->getFieldData('0x030103000A');
            $locationEntry['currentmilleage'] = $status->getFieldData('0x0101010002');
            $locationEntry['remainingmilleage'] = $status->getFieldData('0x0301030005');
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
