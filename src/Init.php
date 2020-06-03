<?php
namespace SapiStudio\MyAudi;
use SapiStudio\RestApi\AbstractHttpClient;
use SapiStudio\MyAudi\Services\TrackEntries as TkEntries;

class Init extends ApiConnect
{
    const ENTRY_HASH_KEY = 'locHash';
    
    /**
    |--------------------------------------------------------------------------
    | AUDI REQUESTS
    |--------------------------------------------------------------------------
    */
    /** Init::isCarVerified()*/
    public function getServicePlan(){
        $servicePlan = [];        
        $serviceBook = $this->setAuthorizationAudiBearerHeader()->cachedGetRequest(ApiServices::getUrl('serviceBook'));
        if($serviceBook){
            foreach($serviceBook as $serviceEntry)
                $servicePlan[] = $this->setAuthorizationAudiBearerHeader()->cachedGetRequest(ApiServices::getUrl('serviceBook').'/'.$serviceEntry['serviceDocumentId']);
        }
        return $servicePlan;
    }
    
    /** Init::getPosition() */
    public function getPosition(){
        try{
            return $this->buildVehicleReponseFormat($this->setAuthorizationVwBearerHeader()->cachedGetRequest(self::$cacheHashes['POSITION'],ApiServices::getUrl('position'))[ApiServices::getElement('position')]);
        }catch(\Exception $positionException){
            return false;
        }
    }
    
    /** Init::getAppointments()*/
    public function getAppointments(){
        return $this->setAuthorizationAudiBearerHeader()->cachedGetRequest(ApiServices::getUrl('appointment'));
    }
    
    /** Init::isCarVerified()*/
    public function isCarVerified(){
        return $this->setAuthorizationAudiBearerHeader()->get(ApiServices::getUrl('verification'))[ApiServices::getElement('verification')];
    }
    
    /** Init::getFavoritePartner()*/
    public function getFavoritePartner(){
        $partnerCode = $this->setAuthorizationAudiBearerHeader()->cachedGetRequest(ApiServices::getUrl('partnership'));
        if($partnerCode){
            $partnerCode = $this->setAuthorizationAudiBearerHeader()->get(str_replace(array_keys($partnerCode),array_values($partnerCode),ApiServices::getDetailUrl('partnership')))[ApiServices::getElement('partnership')];
        }
        return $partnerCode;
    }
    
    /** Init::destinations()*/
    public function rolesRights()
    {
        return $this->setAuthorizationAudiBearerHeader()->get(ApiServices::getUrl('rolesrights'));
    }
    
    /** Init::destinations()*/
    public function destinations()
    {
        return $this->setAuthorizationVwBearerHeader()->cachedGetRequest(ApiServices::getUrl('destinations'))[ApiServices::getElement('destinations')];
    }
    
    /** Init::auxiliarClimaStatus()*/
    public function auxiliarClimaStatus()
    {
        return $this->setAuthorizationVwBearerHeader()->cachedGetRequest(ApiServices::getUrl('auxiliarStatus'))[ApiServices::getElement('auxiliarStatus')];
    }
    
    /** Init::loadCosts()*/
    public function loadCosts()
    {
        return (new TkEntries($this->setAuthorizationAudiBearerHeader()->get(ApiServices::getUrl('trackEntries').'?type='.TkEntries::$costType)))->getEntries();
    }
    
    /** Init::loadJourneys()*/
    public function loadJourneys()
    {
        return (new TkEntries($this->setAuthorizationAudiBearerHeader()->get(ApiServices::getUrl('trackEntries').'?type='.TkEntries::$driveType)))->getEntries();
    }
    
    /** Init::trackLocation()*/
    public function trackLocation(){
        /** if we dont have a here api in place,we cant reverse geocode the adress,so we skip it*/
        if(!Config::HERE_API_KEY())
            return false;
        $currentPosition = $this->getPosition();
        /** no response , probably in movement*/
        if($currentPosition){
            $lastPosition       = Config::LAST_KNOWN_POSITION();
            if(!$lastPosition)
                $lastPosition   = $currentPosition;
            /** if we have a response ,check if this was a trip*/
            if(!Config::positionHistory($currentPosition[self::ENTRY_HASH_KEY])){
                $lastAddress    = self::$mapsHandler->revGeocode($lastPosition['coordinates']);
                $currentAddress = self::$mapsHandler->revGeocode($currentPosition['coordinates']);
                $imageRoute     = self::$mapsHandler->setParams(['poix0' => implode(',',$lastPosition['coordinates']).';5e5656;ffffff;13;'.$lastAddress->getAddresStreet().';','poix1' => implode(',',$currentPosition['coordinates']).';5e5656;ffffff;13;'.$currentAddress->getAddresStreet().';'])->mapRoute([$lastPosition['coordinates'],$currentPosition['coordinates']]);
                $updateEntry    = [
                    ['name'  => TkEntries::FIELD_CSID,          'stringValue'   => $this->getVehicle()->getCarCsid()],
                    ['name'  => TkEntries::FIELD_TS_START,      'dateValue'     => date("Y-m-d\TH:i:sP",strtotime($lastPosition['updateTime']))],
                    ['name'  => TkEntries::FIELD_TS_END,        'dateValue'     => date("Y-m-d\TH:i:sP",strtotime($currentPosition['updateTime']))],
                    ['name'  => TkEntries::FIELD_KM_AT_START,   'doubleValue'   => $lastPosition['milleage']],
                    ['name'  => TkEntries::FIELD_KM_AT_END,     'doubleValue'   => $currentPosition['milleage']],
                    ['name'  => TkEntries::FIELD_TRIP,          'stringValue'   => TkEntries::FIELD_TRIP_VAL],
                    ['name'  => TkEntries::FIELD_FROM_NAME,     'stringValue'   => $lastAddress->getAddresLabel()],
                    ['name'  => TkEntries::FIELD_FROM_STREET,   'stringValue'   => $lastAddress->getAddresStreet()],
                    ['name'  => TkEntries::FIELD_FROM_ZIP,      'stringValue'   => $lastAddress->getAddresZipCode()],
                    ['name'  => TkEntries::FIELD_FROM_CITY,     'stringValue'   => $lastAddress->getAddresCity()],
                    ['name'  => TkEntries::FIELD_FROM_COUNTRY,  'stringValue'   => $lastAddress->getAddresCountryName()],
                    ['name'  => TkEntries::FIELD_TO_NAME,       'stringValue'   => $currentAddress->getAddresLabel()],
                    ['name'  => TkEntries::FIELD_TO_STREET,     'stringValue'   => $currentAddress->getAddresStreet()],
                    ['name'  => TkEntries::FIELD_TO_ZIP,        'stringValue'   => $currentAddress->getAddresZipCode()],
                    ['name'  => TkEntries::FIELD_TO_CITY,       'stringValue'   => $currentAddress->getAddresCity()],
                    ['name'  => TkEntries::FIELD_TO_COUNTRY,    'stringValue'   => $currentAddress->getAddresCountryName()],
                    ['name'  => TkEntries::FIELD_PURPOSE,       'stringValue'   => $imageRoute],
                    ['name'  => TkEntries::FIELD_REMARK,        'stringValue'   => $imageRoute]
                ];
                if($lastPosition['milleage'] < $currentPosition['milleage']){
                    $this->saveJourney($updateEntry);
                    Config::setter(['CAR_TRACKING_ADDED' => strtotime("now")]);
                }
                /** save a history of car position*/
                Config::setter(['positionHistory.'.$currentPosition[self::ENTRY_HASH_KEY] => $currentPosition]);
            }
            /** update current position*/
            Config::setter(['LAST_KNOWN_POSITION' => $currentPosition]);
        }
        return $this;
    }
    
    /** Init::buildVehicleReponseFormat()*/
    protected function buildVehicleReponseFormat($vehicleResponseData = []){
        if(!$vehicleResponseData && !isset($vehicleResponseData['Position']))
            return false;
        $positionFormat = [
            self::ENTRY_HASH_KEY    => md5(implode('',$vehicleResponseData['Position']['carCoordinate']).$this->getVehicle()->UTC_TIME_AND_KILOMETER_STATUS),
            'coordinates'           => array_map('self::coordinateConverter', $vehicleResponseData['Position']['carCoordinate']),
            'parkingTime'           => date("Y-m-d H:i:s", strtotime($vehicleResponseData['parkingTimeUTC'])),
            'milleage'              => $this->getVehicle()->UTC_TIME_AND_KILOMETER_STATUS,
            'updateTime'            => date("Y-m-d H:i:s"),
        ];
        return $positionFormat;
    }
    
    /** Init::saveJourney()*/
    protected function saveJourney($journeyAttributes = []){
        return $this->saveEntries(Services\TrackEntries::$driveType,$journeyAttributes);
    }
    
    /** Init::saveCosts()*/
    protected function saveCosts($costsAttributes = []){
        return $this->saveEntries(Services\TrackEntries::$costType,$costsAttributes);
    }
    
    /** Init::saveEntries()*/
    protected function saveEntries($entryType = null,$attributes = [])
    {
        $entry[Services\TrackEntries::$costType][ApiServices::getElement('trackEntries')][]     = ['type'   => Services\TrackEntries::$costType];
        $entry[Services\TrackEntries::$driveType][ApiServices::getElement('trackEntries')][]    = ['type'   => Services\TrackEntries::$driveType];
        /**
            [['name'  => 'csid','stringValue' => self::$vehiclesData->getPrimaryCsid()],
            ['name'  => 'date','dateValue' => '2020-06-01T00:17:21+03:00'],
            ['name'  => 'total_price','doubleValue' => '2' ],
            ['name'  => 'km_reading','doubleValue' => '2'],
            ['name'  => 'cost_type','stringValue' => 'CARE'],]
        */
        
        if(array_key_exists($entryType,$entry)){
            $entry[$entryType][ApiServices::getElement('trackEntries')][0]['attributes']['attribute'] = $attributes;
            $this->setAuthorizationAudiBearerHeader()->postJson(ApiServices::getUrl('trackEntries'),$entry[$entryType]);
        }
        return $this;
    }
}
