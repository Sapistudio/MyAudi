<?php
namespace SapiStudio\AudiMMI;
use \Illuminate\Support\Collection;

class VehicleStatus
{
    private $responseFieldsNames    = [];
    private $responseData           = [];
    private $parsedResponse         = [];
    
    /**
     * VehicleStatus::initStatus()
     * 
     * @param mixed $vehicleResponse
     * @return
     */
    public static function initStatus($vehicleResponse = null){
        return new static($vehicleResponse);
    }
    
    /**
     * VehicleStatus::__construct()
     * 
     * @param mixed $vehicleResponse
     * @return
     */
    protected function __construct($vehicleResponse = null){
        if(!isset($vehicleResponse['StoredVehicleDataResponse']->vehicleData->data))
            return false;
        $this->responseFieldsNames  = require __dir__.DIRECTORY_SEPARATOR.'configs'.DIRECTORY_SEPARATOR.'fieldsResponse.config.php';
        $this->responseData         = $vehicleResponse['StoredVehicleDataResponse']->vehicleData->data;
        $this->parseResponse();
    }
    
    /**
     * VehicleStatus::getFieldData()
     * 
     * @param mixed $fieldName
     * @return
     */
    public function getFieldData($fieldName = null){
        if(!$fieldName)
            return false;
        if(!is_array($fieldName))
            return $this->displayField($fieldName);
    }
    
    /**
     * VehicleStatus::parseResponse()
     * 
     * @return
     */
    protected function parseResponse(){
        foreach((new Collection(array_map(function($element){return $element->field;},$this->responseData)))->flatten()->toArray() as $index => $responseEntry){
            $fieldName = (isset($this->responseFieldsNames[$responseEntry->id])) ? $this->responseFieldsNames[$responseEntry->id] : $responseEntry->textId;
            $this->parsedResponse[$responseEntry->id] = ['name' => $fieldName,'unit' => $responseEntry->unit,'value' => $responseEntry->value,'measure_time' => $responseEntry->tsCarCaptured,'send_time' => $responseEntry->tsCarSent,'measure_mileage' => $responseEntry->milCarCaptured,'send_mileage' => $responseEntry->milCarSent];
        }
    }
    
    /**
     * VehicleStatus::displayField()
     * 
     * @param mixed $fieldId
     * @return
     */
    protected function displayField($fieldId = null){
        return (!isset($this->parsedResponse[$fieldId])) ? false : $this->parsedResponse[$fieldId]['value'];
    }
}
