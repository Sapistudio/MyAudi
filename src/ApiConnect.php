<?php
namespace SapiStudio\MyAudi;
use \SapiStudio\RestApi\AbstractHttpClient;

class ApiConnect extends AbstractHttpClient
{
    protected $responseFormat       = 'json';
    private $lastRequestCall        = null;
    private $lastRequestArgs        = [];
    private $cacheParams            = [];
    private $blockLatRequestCall    = false;
    protected static $cacheHashes   = [];
    protected static $mapsHandler   = null;
    private static $revokeTokens    = false;
    private static                  $credentials;
    private static                  $vehiclesData;
    private static                  $userInfo;
    protected static                $apiInstance;
    
    /**
    |--------------------------------------------------------------------------
    | Initiators
    |--------------------------------------------------------------------------
    */       
    /** ApiConnect::configure() */
    public static function configure($credentials = [])
    {
        /** check credentials*/
        if(empty($credentials['username']) || empty($credentials['password']))
            throw new \Exception('Require in array [username], [password]');
        self::$credentials = $credentials;
        return self::make()->isAppConfigured();
    }
    
    /** ApiConnect::logout() */
    public static function logout()
    {
        self::$revokeTokens = true;
        return self::make();
    }
    
    /** ApiConnect::make() */
    public static function make()
    {
        if (null === static::$apiInstance)
            static::$apiInstance = new static($options);
        return static::$apiInstance;
    }
    
    /**
    |--------------------------------------------------------------------------
    | api helpers
    |--------------------------------------------------------------------------
    */
    /** Init::getVehicle() */
    public function getVehicle(){
        return self::$vehiclesData;
    }
    
    /** Init::getUserInfo() */
    public function getUserInfo(){
        return self::$userInfo;
    }
    
    /** Init::coordinateConverter()*/
    public static function coordinateConverter($coordinateNumber = null){
        $dot = strlen($coordinateNumber)-6;
        return substr($coordinateNumber,0,$dot).'.'.substr($coordinateNumber, $dot);
    }
    
    /** Init::getMapHandler()*/
    public function getMapHandler(){
        return self::$mapsHandler;
    }
    /**
    |--------------------------------------------------------------------------
    | APP INITIATOR AND TOKENS GENERATOR
    |--------------------------------------------------------------------------
    */
    /** ApiConnect::__construct()*/
    public function __construct(){
        parent::__construct();
        /** LOAD CONFIG*/
        Config::initiate();
        self::$mapsHandler = \Sapistudio\SapiMaps\Handler::Here(Config::HERE_API_KEY());
        /** build hashes for cached requests*/
        if($this->isAppConfigured()){
            $cacheHash          = md5(realpath(dirname(__FILE__)).Config::X_CLIENT_ID());
            self::$cacheHashes  = [
                'TOKEN_AUDI'    => $cacheHash.'aTokens',
                'TOKEN_VW'      => $cacheHash.'vwTokens',
                'REFRESH'       => $cacheHash.'refreshTokens',
                'VEHICLE'       => $cacheHash.'vehiclesList',
                'USER'          => $cacheHash.'userInfo',
                'POSITION'      => $cacheHash.'carFinder',
                'CARSTATUS'     => $cacheHash.'carStatus'
            ];
        }
        /** set defaults http client options*/
        $this->setOption('http_errors',false)->setOption('verify',false)->setHeaders(
            [
                'Accept' => 'application/json',
                'X-App-Name' => Config::X_APP_NAME(),
                'X-App-Version' => Config::X_APP_VERSION(),
                'User-Agent' => Config::X_APP_USER_AGENT()
            ]
        )->initCacheRequests();
        /** register and init the api. if all is fine , after this you can make audi api calls :)*/
        $this->initAudiApp();
    }
    
    /** ApiConnect::isAppConfigured() */
    protected function isAppConfigured(){
        return (!Config::X_CLIENT_ID() || !Config::TOKENS()) ? false : true;
    }
    
    /** ApiConnect::initAudiApp() */
    private function initAudiApp(){
        if(self::$credentials['username'] && self::$credentials['password'])
            return $this->registerApp();
        if(!$this->isAppConfigured())
            throw new \Exception('Cant start app...not configured properly');
        if(self::$revokeTokens)
            return $this->revokeTokens();
        if(!Config::TOKENS()['REFRESH_TIMESTAMP'] || Config::TOKENS()['NEXT_REFRESH'] <= strtotime("now"))
            $this->refreshTokens();
        $this->setHeaders(
            [
                'X-App-ID'      => Config::X_APP_ID(),
                'X-Brand'       => Config::X_APP_BRAND(),
                'X-Country-Id'  => Config::X_APP_COUNTRY(),
                'X-Language-Id' => Config::X_APP_LANGUAGE(),
                'X-Market'      => Config::X_MARKET()
            ]
        );
        /** load main car and user details */
        self::$vehiclesData = new Services\VehiclesStatus($this->setAuthorizationAudiBearerHeader()->cachedGetRequest(self::$cacheHashes['VEHICLE'],ApiServices::getUrl('vehicleList'))[ApiServices::getElement('vehicleList')]);
        self::$vehiclesData->setCarStatus(
            $this->setAuthorizationVwBearerHeader()->cachedGetRequest(self::$cacheHashes['CARSTATUS'],ApiServices::getUrl('carStatus'))[ApiServices::getElement('carStatus')]
        );
        self::$userInfo     = $this->setAuthorizationAudiBearerHeader()->cachedGetRequest(self::$cacheHashes['USER'],ApiServices::getUrl('userInfo'));
        return $this;
    }
    
    /** ApiConnect::registerApp() */
    private function registerApp()
    {
        if($this->isAppConfigured())
            throw new \Exception('App is already configured..please logout first');
        if(!Config::X_CLIENT_ID()){
            $request = [
                "appId"         => Config::X_APP_ID(),
                "appName"       => Config::X_APP_NAME(),
                "appVersion"    => Config::X_APP_VERSION(),
                "client_brand"  => Config::X_APP_BRAND(),
                "client_name"   => Config::CLIENT_NAME(),
                "platform"      => Config::CLIENT_PLATFORM()
            ];
            $registerApp        = $this->postJson(Config::ENDPOINTS()['REGISTER_APP'],$request);
            Config::setter(['X_CLIENT_ID' => $registerApp['client_id']]);
        }        
        if(!Config::TOKENS()){
            $this->deleteCachedRequests();
            $tokens['AUDI'] = $this->cachedPostRequest(self::$cacheHashes['TOKEN_AUDI'],Config::ENDPOINTS()['AUDI_TOKEN'],[
                'form_params'   => [
                    'client_id'     => Config::CLIENT_ID(),
                    'scope'         => Config::SCOPES()['AUDI'],
                    'grant_type'    => 'password',
                    'username'      => self::$credentials['username'],
                    'password'      => self::$credentials['password']
                ]
            ]);
            /** clear credentials*/
            unset(self::$credentials['username'],self::$credentials['password']);
            $tokens['VW'] = $this->cachedPostRequest(self::$cacheHashes['TOKEN_VW'],Config::ENDPOINTS()['VW_TOKEN'], [
                'headers'       => ["X-Client-Id" => Config::X_CLIENT_ID()],
                'form_params'   => [
                    'scope'         => Config::SCOPES()['VW'],
                    'grant_type'    => 'id_token',
                    'token'         => $tokens['AUDI']['id_token']
                ]
            ]);
            /** append all any other configs bypassed*/
            if(self::$credentials){
                foreach(self::$credentials as $configName => $configValue)
                    Config::setter([$configName => $configValue]);
            }
            Config::setter(['TOKENS' => $tokens]);
        }
        /** fallback for any other error from now,we keep all the configuration*/
        Config::saveConfigs();
        return $this;
    }
    
    /** ApiConnect::refreshTokens() */
    private function refreshTokens(){
        if(!Config::TOKENS() || (Config::TOKENS()['REFRESH_TIMESTAMP'] && !Config::MAIN_VIN()))
            throw new \Exception('Can not refresh tokens. No tokens or vin found');
        $this->deleteCachedRequests();
        $tokens['AUDI'] = $this->cachedPostRequest(self::$cacheHashes['TOKEN_AUDI'],Config::ENDPOINTS()['AUDI_TOKEN'],[
            'form_params'   => [
                'client_id'     => Config::CLIENT_ID(),
                'grant_type'    => Config::SCOPES()['R_TOKEN'],
                'response_type' => 'token id_token',
                'refresh_token' => Config::TOKENS()['AUDI']['refresh_token']
            ]
        ]);
        $tokens['VW'] = $this->cachedPostRequest(self::$cacheHashes['TOKEN_VW'],Config::ENDPOINTS()['VW_TOKEN'], [
            'headers'       => ["X-Client-Id" => Config::X_CLIENT_ID()],
            'form_params'   => ['scope' => Config::SCOPES()['VW'],'grant_type' => Config::SCOPES()['R_TOKEN'],'vin' => Config::MAIN_VIN(),'token' => Config::TOKENS()['VW']['refresh_token']]
        ]);
        $tokens['VW']                   = array_merge(Config::TOKENS()['VW'],$tokens['VW']);
        $tokens['REFRESH_TIMESTAMP']    = strtotime("now");
        $tokens['NEXT_REFRESH']         = strtotime("now") + Config::TOKENS_REFRESH_PERIOD();
        Config::setter(['TOKENS' => $tokens]);
        return $this;
    }
    
    /** ApiConnect::revokeTokens() */
    private function revokeTokens(){
        if(!Config::TOKENS())
            throw new \Exception('Can not revoke tokens. No tokens found');
        $this->deleteCachedRequests();
        $this->cachedPostRequest('revokeAudi',Config::ENDPOINTS()['AUDI_REVOKE'],[
            'form_params'   => [
                'client_id'         => Config::CLIENT_ID(),
                'token_type_hint'   => Config::SCOPES()['R_TOKEN'],
                'token'             => Config::TOKENS()['AUDI']['refresh_token']
            ]
        ]);
         $this->cachedPostRequest('revokeVw',Config::ENDPOINTS()['VW_REVOKE'], [
            'headers'       => ["X-Client-Id" => Config::X_CLIENT_ID()],
            'form_params'   => ['token_type_hint' => Config::SCOPES()['R_TOKEN'],'token' => Config::TOKENS()['VW']['refresh_token']]
        ]);
        Config::unsetter(['TOKENS','MAIN_VIN','LAST_KNOWN_POSITION','CAR_TRACKING_ADDED']);
        return $this;
    }
    
    /**
    |--------------------------------------------------------------------------
    | AUTHORIZATION headers
    |--------------------------------------------------------------------------
    */
    /** ApiConnect::setAuthorizationAudiBearerHeader()*/
    public function setAuthorizationAudiBearerHeader(){
        $this->addHeader('Authorization',Config::AUTHORIZATION('BEARER_HEADER').Config::TOKENS('AUDI')['access_token']);
        return $this;
    }
    
    /** ApiConnect::setAuthorizationVwBearerHeader()*/
    public function setAuthorizationVwBearerHeader(){
        $this->addHeader('Authorization',Config::AUTHORIZATION('BEARER_HEADER').Config::TOKENS('VW')['access_token']);
        return $this;
    }
    
    /**
    |--------------------------------------------------------------------------
    | RESTAPI methods & HTTP HELPERS
    |--------------------------------------------------------------------------
    */
    /** ApiConnect::initCacheRequests() */
    public function initCacheRequests(){
        $this->cacheParams = ['cacheDir' => Config::getCachePath(),'cacheVal' => 3600];
        $this->getHttpClient()->setCacheParams($this->cacheParams);
        return $this;
    }
    
    /** ApiConnect::deleteCachedRequests() */
    public function deleteCachedRequests(){
        return ($this->cacheParams['cacheDir']) ? \SapiStudio\FileSystem\Handler::deleteDirectory($this->cacheParams['cacheDir'],true) : false;
    }
    
    /** ApiConnect::reDueLastCall() */
    protected function reDueLastCall(){
        //echo 'Redoing '.$this->lastRequestCall. ' with '.implode(' / ',$this->lastRequestArgs);
        //return $this->{$this->lastRequestCall}(...$this->lastRequestArgs);
    }
    
    /** ApiConnect::saveLastCall() */
    protected function saveLastCall(){
        if(!$this->blockLatRequestCall){
            $lastCall               = debug_backtrace()[2];
            $this->lastRequestCall  = $lastCall['function'];
            $this->lastRequestArgs  = $lastCall['args'];
        }
        $this->blockLatRequestCall = true;
    }
    
    /** ApiConnect::validateApiResponse()*/
    /** this is where we check errors in api responses*/
    protected function validateApiResponse($response = null){
        $response = json_decode(json_encode($response),true);
        if ($response['error']){
            $error = ($response['error_description']) ? $response['error_description'] : $response['error']['description'];
            throw new \Exception('Invalid response: '.$error);
        }
        return $response;
    }
    
    /** ApiConnect::buildRequestUri() */
    /** this is where we replace request vars in url*/
    protected function buildRequestUri($baseUri,$path=false)
    {
        $url        = (substr($path, 0, 4) === 'http') ? $path : rtrim(Config::ENDPOINTS()['AUDI_API'],'/').'/'.ltrim($path,'/');
        if(self::$vehiclesData)
            $url        = str_replace(['{csid}','{vin}','{brandType}','{country}'],[self::$vehiclesData->getCarCsid(),self::$vehiclesData->getCarVin(),Config::X_APP_BRAND(),Config::X_APP_COUNTRY()],$url);
        return $url;
    }
}
