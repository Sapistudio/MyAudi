<?php
namespace SapiStudio\MyAudi\Services;
use SapiStudio\MyAudi\ApiServices;

class TrackEntries
{
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
                $this->entries[$entryData['id']]['deleted']     = $entryData['deleted'];
                $this->entries[$entryData['id']]['type']        = $entryData['type'];
                $this->entries[$entryData['id']]['attributes']  = $attributes;
            }
        }
    }
}