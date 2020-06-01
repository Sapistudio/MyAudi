<?php 
return [
    'AUTHORIZATION' => [
        'BEARER_HEADER' => 'Bearer ',
    ],
    'SCOPES' => [
        'AUDI' => 'openid profile email mbb offline_access mbbuserid myaudi selfservice:read selfservice:write',
        'VW' => 'sc2:fal',
        'R_TOKEN' => 'refresh_token',
    ],
    'ENDPOINTS' => [
        'AUDI_API' => 'https://msg.audi.de',
        'REGISTER_APP' => 'https://mbboauth-1d.prd.ece.vwg-connect.com/mbbcoauth/mobile/register/v1',
        'AUDI_TOKEN' => 'https://id.audi.com/v1/token',
        'VW_TOKEN' => 'https://mbboauth-1d.prd.ece.vwg-connect.com/mbbcoauth/mobile/oauth2/v1/token',
    ],
    'HERE_API_KEY' => '',
    'TOKENS_REFRESH_PERIOD' => 600,
    'X_APP_ID' => 'de.myaudi.mobile.assistant',
    'X_APP_NAME' => 'myAudi',
    'X_APP_VERSION' => '3.9.1',
    'X_APP_BRAND' => 'Audi',
    'X_APP_USER_AGENT' => 'okhttp/3.11.0',
    'X_APP_COUNTRY' => 'DE',
    'X_MARKET' => 'en_RO',
    'X_APP_LANGUAGE' => 'en_RO',
    'CLIENT_ID' => 'mmiconnect_android',
    'CLIENT_NAME' => 'Android SDK built for x86_64',
    'CLIENT_PLATFORM' => 'google',
]
;
