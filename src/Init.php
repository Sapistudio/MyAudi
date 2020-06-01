<?php
namespace SapiStudio\MyAudi;
use \SapiStudio\RestApi\AbstractHttpClient;

class Init extends ApiConnect
{
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
            return $this->setAuthorizationVwBearerHeader()->cachedGetRequest(self::$cacheHashes['POSITION'],ApiServices::getUrl('position'))[ApiServices::getElement('position')];
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
    
    /** Init::loadCostEntries()*/
    public function loadCostEntries()
    {
        return new Services\TrackEntries($this->setAuthorizationAudiBearerHeader()->get(ApiServices::getUrl('trackEntries').'?type='.Services\TrackEntries::$costType));
    }
    
    /** Init::loadDriversEntries()*/
    public function loadDriversEntries()
    {
        return new Services\TrackEntries($this->setAuthorizationAudiBearerHeader()->get(ApiServices::getUrl('trackEntries').'?type='.Services\TrackEntries::$driveType));
    }
    
    /** Init::trackLocation()*/
    public function trackLocation(){
        /** if we dont have a here api in place,we cant reverse geocode the adress,so we skip it*/
        if(!Config::HERE_API_KEY())
            return false;
        $carFinder = $this->getPosition();
        /** no response , probably in movement*/
        if(!$carFinder){
            if(!Config::CAR_IN_MOVEMENT())
                Config::setter(['CAR_IN_MOVEMENT' => 1]);
        }else{
            $currentPosition = [
                'locHash'       => md5(implode('',$carFinder['Position']['carCoordinate'])),
                'coordinates'   => array_map('self::coordinateConverter', $carFinder['Position']['carCoordinate']),
                'parkingTime'   => date("Y-m-d H:i:s", strtotime($carFinder['parkingTimeUTC'])),
                'milleage'      => $this->getVehicle()->UTC_TIME_AND_KILOMETER_STATUS,
                'updateTime'    => date("Y-m-d H:i:s"),
            ];
            $lastPosition   = Config::CAR_POSITION();
            /** if we have a response and have a locked in movement,check if this was a trip*/
            if(Config::CAR_IN_MOVEMENT() && $lastPosition && $lastPosition['milleage'] < $currentPosition['milleage']){
                $lastAddress    = self::$mapsHandler->revGeocode($lastPosition['coordinates']);
                $currentAddress = self::$mapsHandler->revGeocode($currentPosition['coordinates']);
                $imageRoute     = self::$mapsHandler->mapRoute([$lastPosition['coordinates'],$currentPosition['coordinates']]);
                $updateEntry    = [
                    ['name'  => 'csid','stringValue'            => $this->getVehicle()->getCarCsid()],
                    ['name'  => 'ts_start','dateValue'          => date("Y-m-d\TH:i:sP",strtotime($lastPosition['updateTime']))],
                    ['name'  => 'ts_end','dateValue'            => date("Y-m-d\TH:i:sP",strtotime($currentPosition['updateTime']))],
                    ['name'  => 'km_at_start','doubleValue'     => $lastPosition['milleage']],
                    ['name'  => 'km_at_end','doubleValue'       => $currentPosition['milleage']],
                    ['name'  => 'trip_category','stringValue'   => 'PRIVATE'],
                    ['name'  => 'from_name','stringValue'       => $lastAddress->getAddresLabel()],
                    ['name'  => 'from_street','stringValue'     => $lastAddress->getAddresStreet()],
                    ['name'  => 'from_zip','stringValue'        => $lastAddress->getAddresZipCode()],
                    ['name'  => 'from_city','stringValue'       => $lastAddress->getAddresCity()],
                    ['name'  => 'from_country','stringValue'    => $lastAddress->getAddresCountryName()],
                    ['name'  => 'to_name','stringValue'         => $currentAddress->getAddresLabel()],
                    ['name'  => 'to_street','stringValue'       => $currentAddress->getAddresStreet()],
                    ['name'  => 'to_zip','stringValue'          => $currentAddress->getAddresZipCode()],
                    ['name'  => 'to_city','stringValue'         => $currentAddress->getAddresCity()],
                    ['name'  => 'to_country','stringValue'      => $currentAddress->getAddresCountryName()],
                    ['name'  => 'purpose','stringValue'         => $imageRoute],
                    ['name'  => 'remark','stringValue'          => $imageRoute]
                ];
                $this->saveEntries(Services\TrackEntries::$driveType,$updateEntry);
                Config::setter(['CAR_TRACKING_ADDED' => strtotime("now")]);
            }
            Config::unsetter('CAR_IN_MOVEMENT');
            /** update current position*/
            Config::setter(['CAR_POSITION' => $currentPosition]);
        }
        return $this;
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
