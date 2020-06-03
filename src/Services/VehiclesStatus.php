<?php
namespace SapiStudio\MyAudi\Services;
use SapiStudio\MyAudi\Config;
use \Illuminate\Support\Collection;

class VehiclesStatus
{
    protected $vehiclesList         = [];
    protected $vehicleDetails       = [];
    protected $vehicleStatus        = [];
    private $responseFieldsNames    = [];
    private $elementStatus          = [1 =>'open',2=>'locked',3=>'closed'];
    private static $vehicleInstance = null;
    
    /** VehiclesStatus::__construct()*/
    public function __construct($vehiclesList = null,$loadCar = 0){
        if(!self::$vehicleInstance){
            if (!$vehiclesList)
                throw new \Exception('No vehicles found');
            $this->vehiclesList     = array_column($vehiclesList,'vin');
            $this->vehicleDetails   = $vehiclesList[$loadCar];
            if($this->getCarVin())
                Config::setter(['MAIN_VIN' => $this->getCarVin()]);
            self::$vehicleInstance = $this;
        }
        return self::$vehicleInstance;
    }
    
    public function __get($name){
        return $this->getFieldStatus($name);
    }
    /** VehiclesStatus::getCarStatus()*/
    public static function getCarStatus(){
        
    }
    
    /** VehiclesStatus::setCarStatus()*/
    public function setCarStatus($vehicleStatus = null){
        $vehicleStatus = json_decode(json_encode($vehicleStatus));
        if(!isset($vehicleStatus->vehicleData->data))
            return false;
        $this->responseFieldsNames  = Config::fieldsResponse();
        foreach((new Collection(array_map(function($element){return $element->field;},$vehicleStatus->vehicleData->data)))->flatten()->toArray() as $index => $responseEntry){
            $fieldName = (isset($this->responseFieldsNames[$responseEntry->id])) ? $this->responseFieldsNames[$responseEntry->id] : $responseEntry->textId;
            $indexName = (isset($this->responseFieldsNames[$responseEntry->id])) ? $this->responseFieldsNames[$responseEntry->id] : $responseEntry->id;
            if(!$fieldName)
                $fieldName = $indexName;
            $fieldValue = (stripos($indexName, 'state') !== false && $this->elementStatus[$responseEntry->value]) ? $this->elementStatus[$responseEntry->value] : $responseEntry->value;
            
            $this->vehicleStatus[$indexName] = [
                'name'              => $fieldName,
                'unit'              => $responseEntry->unit,
                'value'             => $fieldValue,
                'measure_time'      => $responseEntry->tsCarCaptured,
                'send_time'         => $responseEntry->tsCarSent,
                'measure_mileage'   => $responseEntry->milCarCaptured,
                'send_mileage'      => $responseEntry->milCarSent
            ];
        }
    }
    
    /** VehiclesStatus::getFieldStatus()*/
    public function getFieldStatus($fieldName = null){
        if(!$fieldName || is_array($fieldName))
            return false;
        return (!isset($this->vehicleStatus[$fieldName])) ? false : $this->vehicleStatus[$fieldName]['value'].' '.$this->vehicleStatus[$fieldName]['unit'];
    }
    
    /** VehiclesStatus::getFieldFull()*/
    public function getFieldFull($fieldName = null){
        if(!$fieldName || is_array($fieldName))
            return false;
        return (!isset($this->vehicleStatus[$fieldName])) ? false : $this->vehicleStatus[$fieldName];
    }
    
    /** VehiclesStatus::getAllCars() */
    public function getAllCars(){
        return $this->vehiclesList;
    }
    
    /** VehiclesStatus::getCarVin() */
    public function getCarVin(){
        return $this->vehicleDetails['vin'];
    }
    
    /** VehiclesStatus::getCarCsid()*/
    public function getCarCsid(){
        return $this->vehicleDetails['csid'];
    }
    
    /** VehiclesStatus::getModel()*/
    public function getCarModel(){
        return $this->vehicleDetails['model_full'];
    }
    
    /** VehiclesStatus::getModelYear()*/
    public function getCarModelYear(){
        return $this->vehicleDetails['model_year'];
    }
    
    /** VehiclesStatus::getCarImages()*/
    public function getCarImages(){
        return $this->vehicleDetails['imageUrls'];
    }
    
    /** VehiclesStatus::carIsConnected()*/
    public function carIsConnected(){
        return $this->vehicleDetails['connect'];
    }
    
    /** VehiclesStatus::getCarPairingNumber()*/
    public function getCarPairingNumber(){
        return $this->vehicleDetails['pairingNumber'];
    }
}
