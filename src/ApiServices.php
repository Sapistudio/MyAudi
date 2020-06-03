<?php
namespace SapiStudio\MyAudi;

class ApiServices
{
    protected static $servicesTypes = [];
    
    /** ApiServices::__callStatic() */
    public static function __callStatic($serviceName, $args){
        $apiCall = $args[0];
        if(!self::$servicesTypes)
            self::$servicesTypes = Config::apiServices();
        if(!isset(self::$servicesTypes[$apiCall]))
            return false;
        switch($serviceName){
            case "getElement":
                $fieldName = 'element';
                break;
            case "getDetailUrl":
                $fieldName = 'detailUrl';
                break;
            case "getUrl":
                $fieldName = 'url';
                break;
            default:
                $fieldName = null;
                break;
        };
        return (isset(self::$servicesTypes[$apiCall][$fieldName])) ? self::$servicesTypes[$apiCall][$fieldName] : false;
    }
}
