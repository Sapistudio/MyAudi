<?php
namespace SapiStudio\MyAudi\Services;
use SapiStudio\MyAudi\ApiServices;
use SapiStudio\MyAudi\Config;

class Entries extends ApiServices
{
    const ENTRY_HASH_KEY        = 'locHash';
    
    const FIELD_CSID            = 'csid';
    const FIELD_TS_START        = 'ts_start';
    const FIELD_TS_END          = 'ts_end';
    const FIELD_KM_AT_START     = 'km_at_start';
    const FIELD_KM_AT_END       = 'km_at_end';
    const FIELD_TRIP            = 'trip_category';
    const FIELD_TRIP_VAL        = 'PRIVATE';
    const FIELD_FROM_NAME       = 'from_name';
    const FIELD_FROM_STREET     = 'from_street';
    const FIELD_FROM_ZIP        = 'from_zip';
    const FIELD_FROM_CITY       = 'from_city';
    const FIELD_FROM_COUNTRY    = 'from_country';
    const FIELD_TO_NAME         = 'to_name';
    const FIELD_TO_STREET       = 'to_street';
    const FIELD_TO_ZIP          = 'to_zip';
    const FIELD_TO_CITY         = 'to_city';
    const FIELD_TO_COUNTRY      = 'from_country';
    const FIELD_PURPOSE         = 'purpose';
    const FIELD_REMARK          = 'remark';
    const JOURNEY_UNIT_DIST     = 'km';
    
    public static $driveType    = 'DriversLogItem';
    public static $costType     = 'CostItem';
    protected $attributeTypes   = ['doubleValue','stringValue','dateValue'];
    
    /** Entries::loadCosts() */
    public function loadCosts($useLocal = true)
    {
        return $this->getEntries(self::$costType,$useLocal);
    }
    
    /** Entries::loadJourneys() */
    public function loadJourneys($useLocal = true)
    {
        return $this->getEntries(self::$driveType,$useLocal);
    }
    
    /** Entries::deleteJourneys() */
    public function deleteJourneys()
    {
        $this->apiHandler->deleteCachedRequests();
        $entries = $this->apiHandler->setAuthorizationAudiBearerHeader()->cachedGetRequest(self::getUrl('trackEntries').'?type='.self::$driveType);
        if($entries){
            foreach($entries[self::getElement('trackEntries')] as $entryData){
                $this->apiHandler->setAuthorizationAudiBearerHeader()->delete(self::getUrl('trackEntries').'/'.$entryData['id'].'?type='.self::$driveType);
            }
        }
    }
    
    /** Entries::saveLocalJourney() */
    public function saveLocalJourney($journeyDetails = null){
        list($lastPosition,$currentPosition) = array_values($journeyDetails);
        if(!$lastPosition['coordinates'] || !$currentPosition['coordinates'])
            return false;
        $journeyId      = 'positionHistory.'.self::$driveType.'.'.self::getElement('trackEntries').'.'.$currentPosition[self::ENTRY_HASH_KEY];
        if(Config::getter($journeyId))
            return true;
        $lastAddress    = self::$mapsHandler->revGeocode($lastPosition['coordinates']);
        $currentAddress = self::$mapsHandler->revGeocode($currentPosition['coordinates']);
        $imageRoute     = self::$mapsHandler->setParams(['poix0' => implode(',',$lastPosition['coordinates']).';5e5656;ffffff;13;'.$lastAddress->getAddresStreet().';','poix1' => implode(',',$currentPosition['coordinates']).';5e5656;ffffff;13;'.$currentAddress->getAddresStreet().';'])->mapRoute([$lastPosition['coordinates'],$currentPosition['coordinates']]);
        $entry = [
            'type'          => self::$driveType,
            'attributes'    => [
                'attribute' => [
                    ['name'  => TkEntries::FIELD_CSID,          'stringValue'   => $this->apiHandler->getVehicle()->getCarCsid()],
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
                ]
            ]
        ];
        if($lastPosition['milleage'] < $currentPosition['milleage'])
            Config::setter([$journeyId => $entry]);
        return true;
    }
    
    /** Entries::syncJourneys() 
    * One direction sync.This will add all the journeys stored locally
    */
    public function syncJourneys(){
        return $this->saveEntries(self::$driveType);
    }
    
    /** Entries::syncCosts() 
    * One direction sync.This will add all the cost entries stored locally
    */
    public function syncCosts($costsAttributes = []){
        return $this->saveEntries(self::$costType);
    }
    
    /** Entries::saveEntries()*/
    protected function saveEntries($entryType = null)
    {
        $entriesHashId  = 'positionHistory.'.$entryType.'.'.self::getElement('trackEntries');
        $entriesData    = Config::getter($entriesHashId);
        if($entriesData){
            foreach($entriesData as $entryIndex => $entryData){
                if(!isset($entryData['id'])){
                    $myAudiId = $this->apiHandler->setAuthorizationAudiBearerHeader()->postJson(self::getUrl('trackEntries'),[self::getElement('trackEntries') => [$entryData]]);
                    if($myAudiId[self::getElement('trackEntries')]['id'])
                        $entriesData[$entryIndex]['id'] = $myAudiId[self::getElement('trackEntries')]['id'];
                }
            }
            Config::setter([$entriesHashId => $entriesData]);
        }
        return $this;
        /**
            [['name'  => 'csid','stringValue' => self::$vehiclesData->getPrimaryCsid()],
            ['name'  => 'date','dateValue' => '2020-06-01T00:17:21+03:00'],
            ['name'  => 'total_price','doubleValue' => '2' ],
            ['name'  => 'km_reading','doubleValue' => '2'],
            ['name'  => 'cost_type','stringValue' => 'CARE'],]
        */
    }
    
    /** Entries::getEntries()*/
    private function getEntries($entryType = null,$useLocal = true){
        $entries = ($useLocal) ? Config::getter('positionHistory.'.$entryType) : $this->apiHandler->setAuthorizationAudiBearerHeader()->cachedGetRequest(self::getUrl('trackEntries').'?type='.$entryType);
        $parsedEntries = [];
        if($entries[self::getElement('trackEntries')]){
            foreach($entries[self::getElement('trackEntries')] as $indexEntry => $entryData){
                $attributes = [];
                foreach($this->attributeTypes as $attributeType)
                    $attributes = array_merge($attributes,array_column($entryData['attributes']['attribute'],$attributeType,'name'));
                $entryHash                              = (isset($entryData['id'])) ? $entryData['id'] : $indexEntry;
                $parsedEntries[$entryHash]              = $attributes;
                $parsedEntries[$entryHash]['deleted']   = $entryData['deleted'];
                $parsedEntries[$entryHash]['type']      = $entryData['type'];
            }
            array_walk($parsedEntries,function(&$value, &$key) {
                foreach(['ts_end','ts_start','date'] as $dateKeys){
                    if(isset($value[$dateKeys]))
                        $value[$dateKeys] = date('Y-m-d H:i',strtotime($value[$dateKeys]));
                }
                $key = (isset($value['ts_start'])) ? strtotime($value['ts_start']) : strtotime($value['date']);
                if(isset($value['km_at_end']) && isset($value['km_at_start']))
                    $value['distance'] = $value['km_at_end'] - $value['km_at_start'].self::JOURNEY_UNIT_DIST;
                if(isset($value['ts_end']) && isset($value['ts_start'])){
                    $datetime1 = new \DateTime($value['ts_end']);
                    $datetime2 = new \DateTime($value['ts_start']);
                    $interval = $datetime1->diff($datetime2);
                    $value['journeyTime'] = $interval->format('%H').":".$interval->format('%I')." hours";
                }
            });
        }
        sort($parsedEntries);
        return $parsedEntries;
    }
}