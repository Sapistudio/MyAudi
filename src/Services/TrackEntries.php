<?php
namespace SapiStudio\MyAudi\Services;
use SapiStudio\MyAudi\ApiServices;

class TrackEntries
{
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
    protected $entries          = [];
    protected $attributeTypes   = ['doubleValue','stringValue','dateValue'];
    
    /** TrackEntries::__construct()*/
    public function __construct($entries = null){
        if (!$entries)
            throw new \Exception('No car data provided');
        if($entries[ApiServices::getElement('trackEntries')]){
            foreach($entries[ApiServices::getElement('trackEntries')] as $indexEntry => $entryData){
                $attributes = [];
                foreach($this->attributeTypes as $attributeType)
                    $attributes = array_merge($attributes,array_column($entryData['attributes']['attribute'],$attributeType,'name'));
                $this->entries[$entryData['id']]  = $attributes;
                $this->entries[$entryData['id']]['deleted']     = $entryData['deleted'];
                $this->entries[$entryData['id']]['type']        = $entryData['type'];
            }
        }
    }
    
    /** TrackEntries::getEntries()*/
    public function getEntries(){
        array_walk($this->entries,function(&$value, &$key) {
            foreach(['ts_end','ts_start','date'] as $dateKeys){
                if(isset($value[$dateKeys]))
                    $value[$dateKeys] = date('Y-m-d H:i',strtotime($value[$dateKeys]));
            }
            if(isset($value['km_at_end']) && isset($value['km_at_start']))
                $value['distance'] = $value['km_at_end'] - $value['km_at_start'].self::JOURNEY_UNIT_DIST;
            if(isset($value['ts_end']) && isset($value['ts_start'])){
                $datetime1 = new \DateTime($value['ts_end']);
                $datetime2 = new \DateTime($value['ts_start']);
                $interval = $datetime1->diff($datetime2);
                $value['journeyTime'] = $interval->format('%H').":".$interval->format('%I')." hours";
            }
        });
        return $this->entries;
    }
}
