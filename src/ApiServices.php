<?php
namespace SapiStudio\MyAudi;

class ApiServices
{
    protected static $servicesTypes = [
        "userInfo"      => [
            "url"           => "https://id.audi.com/v1/userinfo",
        ],
        "userProfile"      => [
            "url"           => "myaudi/rolesrights/v1/user/profile",
        ],
        "vehicleList"   => [
            "url"           => "myaudi/vehicle-management/v1/vehicles",
            "element"       => "vehicles",
        ],
        "position" => [
            "url"       => "fs-car/bs/cf/v1/{brandType}/{country}/vehicles/{vin}/position",
            "element"   => "findCarResponse"
        ],
        "trackEntries"   => [
            "url"           => "myaudi/profileservice/v1/entries",
            "element"       => "entry"
        ],
        "auxiliarStatus" => [
            "url"       => "fs-car/bs/rs/v1/{brandType}/{country}/vehicles/{vin}/status",
            "element"   => "statusResponse"
        ],
        
        "destinations"   => [
            "url"           => "fs-car/destinationfeedservice/mydestinations/v1/{brandType}/{country}/vehicles/{vin}/destinations",
            "element"       => "destinations"
        ],
        "carStatus"   => [
            "url"           => "fs-car/bs/vsr/v1/{brandType}/{country}/vehicles/{vin}/status",
            "element"       => "StoredVehicleDataResponse"
        ],
        "historyActions"   => [
            "url"           => "fs-car/bs/rlu/v1/{brandType}/{country}/vehicles/{vin}/actions",
            "element"       => "actionsResponse"
        ],
        "partnership"   => [
            "url"           => "myaudi/partnership/v1/favorite-partner",
            "detailUrl"     => "https://cache-dealersearch.audi.com/api/json/v2/audi-cbs/id?q=kvpsid&countryCode=tenant&language=en",
            "element"       => "partners"
        ],
        "verification"   => [
            "url"           => "myaudi/rolesrights/v1/management/verification/v2/{vin}",
            "element"       => "verificationState"
        ],
        "appointment"   => [
            "url"           => "fs-car/bs/otv/v1/{brandType}/{country}/vehicles/{vin}",
        ],
        "serviceBook"   => [
            "url"           => "myaudi/service-book/v1/vehicles/{vin}/service-book",
        ],
        "historyAlert" => [
            "url"       => "fs-car/bs/dwap/v1/{brandType}/{country}/vehicles/{vin}/history",
            "element"   => "dwaPushHistory"
        ],
        /** TO DO
        "rolesrights"   => [
            //"url"           => "fs-car/rolesrights/operationlist/v2/{brandType}/{country}/vehicles/{vin}/operations",
            "url"             => "myaudi/rolesrights/v1/vehicles/{vin}"
        ],
        "newsFeeds"   => [
            "url"           => "fs-car/news/myfeeds/v1{brandType}/{country}/newsFeeds",
        ],
        "requests" => [
            "url"       => "fs-car/bs/vsr/v1/{brandType}/{country}/vehicles/{vin}/requests",
            "element"   => ""
        ],
        "socialTwitter"   => [
            "url"           => "fs-car/social/twitter/v1/{brandType}/{country}/vehicles/{vin}/credentials",
        ],
        */
    ];
    
    /** ApiServices::getUrl() */
    public static function getUrl($serviceName = null){
        return (isset(self::$servicesTypes[$serviceName])) ? self::$servicesTypes[$serviceName]['url'] : false;
    }
    
    /** ApiServices::getDetailUrl() */
    public static function getDetailUrl($serviceName = null){
        return (isset(self::$servicesTypes[$serviceName])) ? self::$servicesTypes[$serviceName]['detailUrl'] : false;
    }
    
    /** ApiServices::getElement() */
    public static function getElement($serviceName = null){
        return (isset(self::$servicesTypes[$serviceName])) ? self::$servicesTypes[$serviceName]['element'] : false;
    }
}