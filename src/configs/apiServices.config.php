<?php 
return [
    'userInfo' => [
        'url' => 'https://id.audi.com/v1/userinfo',
    ],
    'userProfile' => [
        'url' => 'myaudi/rolesrights/v1/user/profile',
    ],
    'vehicleList' => [
        'url' => 'myaudi/vehicle-management/v1/vehicles',
        'element' => 'vehicles',
    ],
    'position' => [
        'url' => 'fs-car/bs/cf/v1/{brandType}/{country}/vehicles/{vin}/position',
        'element' => 'findCarResponse',
    ],
    'trackEntries' => [
        'url' => 'myaudi/profileservice/v1/entries',
        'element' => 'entry',
    ],
    'auxiliarStatus' => [
        'url' => 'fs-car/bs/rs/v1/{brandType}/{country}/vehicles/{vin}/status',
        'element' => 'statusResponse',
    ],
    'destinations' => [
        'url' => 'fs-car/destinationfeedservice/mydestinations/v1/{brandType}/{country}/vehicles/{vin}/destinations',
        'element' => 'destinations',
    ],
    'carStatus' => [
        'url' => 'fs-car/bs/vsr/v1/{brandType}/{country}/vehicles/{vin}/status',
        'element' => 'StoredVehicleDataResponse',
    ],
    'historyActions' => [
        'url' => 'fs-car/bs/rlu/v1/{brandType}/{country}/vehicles/{vin}/actions',
        'element' => 'actionsResponse',
    ],
    'partnership' => [
        'url' => 'myaudi/partnership/v1/favorite-partner',
        'detailUrl' => 'https://cache-dealersearch.audi.com/api/json/v2/audi-cbs/id?q=kvpsid&countryCode=tenant&language=en',
        'element' => 'partners',
    ],
    'verification' => [
        'url' => 'myaudi/rolesrights/v1/management/verification/v2/{vin}',
        'element' => 'verificationState',
    ],
    'appointment' => [
        'url' => 'fs-car/bs/otv/v1/{brandType}/{country}/vehicles/{vin}',
    ],
    'serviceBook' => [
        'url' => 'myaudi/service-book/v1/vehicles/{vin}/service-book',
    ],
    'historyAlert' => [
        'url' => 'fs-car/bs/dwap/v1/{brandType}/{country}/vehicles/{vin}/history',
        'element' => 'dwaPushHistory',
    ],
];
