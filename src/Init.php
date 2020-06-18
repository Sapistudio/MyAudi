<?php
namespace SapiStudio\MyAudi;
use \SapiStudio\MyAudi\Services\Entries;

class Init extends ApiConnect
{
    /**
    |--------------------------------------------------------------------------
    | HELPERS INITITAOTRS
    |--------------------------------------------------------------------------
    */
    /** Init::Entries()*/
    public function Entries(){
        return new Services\Entries($this);
    }
    
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
    
    /** Init::trackLocation() */
    public function trackLocation(){
        if(!self::$mapsHandler)
            return false;        
        $currentPosition = $this->getPosition();
        /** no response , probably in movement*/
        if(!$currentPosition)
            return $this;
        /** if we have a response ,save a history or update car position*/
        Config::setter(['positionHistory.hashes.'.$currentPosition[Entries::ENTRY_HASH_KEY] => $currentPosition]);
        /** create journey data*/
        $history    = Config::getter('positionHistory.hashes');
        for($start  = 0;$start < count($history);$start++){
            $journey    =  array_slice($history, $start, 2, true);
            if(count($journey) ==  2)
                $journeys[] = $this->Entries()->saveLocalJourney($journey);
        }
        return $this->Entries()->syncJourneys();
    }
    
    /** Init::getPosition() */
    public function getPosition(){
        return $this->buildVehicleReponseFormat($this->setAuthorizationVwBearerHeader()->cachedGetRequest(self::$cacheHashes['POSITION'],ApiServices::getUrl('position'))[ApiServices::getElement('position')]);
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
            $partnerCode = $this->setAuthorizationAudiBearerHeader()->cachedGetRequest(str_replace(array_keys($partnerCode),array_values($partnerCode),ApiServices::getDetailUrl('partnership')))[ApiServices::getElement('partnership')];
        }
        return $partnerCode;
    }
    
    /** Init::rolesRights()*/
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
    
    /** Init::buildVehicleReponseFormat()*/
    protected function buildVehicleReponseFormat($vehicleResponseData = []){
        if(!$vehicleResponseData && !isset($vehicleResponseData['Position']))
            return false;
        $positionFormat = [
            Entries::ENTRY_HASH_KEY    => md5(implode('',$vehicleResponseData['Position']['carCoordinate']).$vehicleResponseData['Position']['parkingTimeUTC']),
            'coordinates'           => array_map('self::coordinateConverter', $vehicleResponseData['Position']['carCoordinate']),
            'parkingTime'           => date("Y-m-d H:i:s", strtotime($vehicleResponseData['parkingTimeUTC'])),
            'milleage'              => $this->getVehicle()->UTC_TIME_AND_KILOMETER_STATUS,
            'updateTime'            => date("Y-m-d H:i:s"),
        ];
        return $positionFormat;
    }
}
