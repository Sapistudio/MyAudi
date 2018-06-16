<?php
namespace SapiStudio\AudiMMI;

class Handler
{
    const BASE_URL  = 'https://msg.audi.de/';
    const CAR_URL   = 'https://msg.audi.de/fs-car';
    const COMPANY   = 'Audi';
    const COUNTRY   = 'DE';
    protected static $username;
    protected static $password;
    private $authToken;
    private static $instance;
    private static $cacheHash;
    protected $primaryVin;
    protected $primaryCsid;
    
    
    /**
     * Handler::getVehicles()
     * 
     * @return
     */
    public function getVehicles(){
        return $this->requestResource("/myaudi/carservice/v2/".self::COMPANY."/".self::COUNTRY."/vehicles");
    }
    
    /**
     * Handler::getVehicleData()
     * 
     * @return
     */
    public function getVehicleData(){
        return $this->requestResource("/myaudi/carservice/v2/".self::COMPANY."/".self::COUNTRY."/vehicle/{csid}");
    }
    
    /**
     * Handler::getActions()
     * 
     * @return
     */
    public function getActions(){
        return $this->requestResource('/bs/rlu/v1/".self::COMPANY."/".self::COUNTRY."/vehicles/{vin}/actions');
    }
    
    /**
     * Handler::loadPosition()
     * 
     * @return
     */
    public function loadPosition(){
        return $this->requestResource("/bs/cf/v1/".self::COMPANY."/".self::COUNTRY."/vehicles/{vin}/position");
    }
    
    /**
     * Handler::vehicleMgmt()
     * 
     * @return
     */
    public function vehicleMgmt(){
        return $this->requestResource("/vehicleMgmt/vehicledata/v2/".self::COMPANY."/".self::COUNTRY."/vehicles/{vin}");
    }
    
    /**
     * Handler::pairing()
     * 
     * @return
     */
    public function pairing(){
        return $this->requestResource("/usermanagement/users/v1/".self::COMPANY."/".self::COUNTRY."/vehicles/{vin}/pairing");
    }
    
    /**
     * Handler::operations()
     * 
     * @return
     */
    public function operations(){
        return $this->requestResource("/rolesrights/operationlist/v2/".self::COMPANY."/".self::COUNTRY."/vehicles/{vin}/operations");
    }
    
    /**
     * Handler::status()
     * 
     * @return
     */
    public function status(){
        return $this->requestResource("/bs/vsr/v1/".self::COMPANY."/".self::COUNTRY."/vehicles/{vin}/status");
    }
    
    /**
     * Handler::pois()
     * 
     * @return
     */
    public function pois(){
        return $this->requestResource(self::BASE_URL.'/audi/b2c/poinav/v1/vehicles/{vin}/pois');
    }
    
    
    /**
     * Handler::configure()
     * 
     * @return
     */
    public static function configure($options = [])
    {
        if (null === static::$instance)
            static::$instance = new static($options);
        return static::$instance;
    }
    
    /**
     * Handler::__construct()
     * 
     * @return
     */
    public function __construct($credentials){
        self::$cacheHash = md5(realpath(dirname(__FILE__)));
        $this->setHttpClient();
        $this->setCredentials($credentials);
    }
    
    /**
     * Handler::requestResource()
     * 
     * @return
     */
    public function requestResource($url, $params = [], $method = 'GET')
    {
        if (!is_array($params))
            throw new \InvalidArgumentException('Params should be an associative array.');
        $url        = (substr($source, 0, 4) === 'http') ? $url : self::CAR_URL.$url;
        $url        = str_replace(['{csid}','{vin}'],[$this->primaryCsid,$this->primaryVin],$url);
        $response   = false;
        switch ($method){
            case 'GET':
                $response = $this->httpClient->get($url,$this->buildHttpOptions(['query' => $params]));
                break;
            case 'POST':
                $response = $this->httpClient->post($url,$this->buildHttpOptions(['form_params' => $params]));
                break;
            case 'PUT':
                $response = $this->httpClient->put($url,$this->buildHttpOptions(['form_params' => $params]));
                break;
            case 'DELETE':
                $response = $this->httpClient->delete($url,$this->buildHttpOptions(['form_params' => $params]));
                break;
            default:
                throw new \Exception("HTTP Request method {$method} not allowed.");
        }
        if(!$response)
            throw new \Exception("Invalid resources response");
        return $this->processHttpResponse($response);
    }
    
    /**
     * Handler::buildHttpOptions()
     * 
     * @return
     */
    private function buildHttpOptions($options = []){
        return array_merge_recursive(
        [
            'http_errors' => false,
            'headers' => [
                'Accept'        =>'application/json',
                'X-App-ID'      =>'de.audi.mmiapp',
                'X-App-Name'    =>'MMIconnect',
                'X-App-Version' =>'3.4.0',
                'X-Brand'       =>'audi',
                'X-Country-Id'  =>'DE',
                'X-Language-Id' =>'de',
                'X-Platform'    =>'google',
                'User-Agent'    =>'okhttp/3',
                'ADRUM_1'       =>'isModule:true',
                'ADRUM'         =>'isAray:true',
                'Authorization' =>'AudiAuth 1 '.$this->authToken]
            ],
            $options);
    }
   
    /**
     * Handler::processHttpResponse()
     * 
     * @return
     */
    private function processHttpResponse($response)
    {
        return json_decode($response->getBody()->getContents());
    }

    /**
     * Handler::setHttpClient()
     * 
     * @return
     */
    public function setHttpClient()
    {
        $this->httpClient = \SapiStudio\Http\Browser\CurlClient::make();
    }

    /**
     * Handler::login()
     * 
     * @return
     */
    public function login()
    {
        $response = $this->httpClient->cacheRequest(self::$cacheHash)->post(self::CAR_URL.'/core/auth/v1/'.self::COMPANY.'/'.self::COUNTRY.'/token', [
                'verify'        => false,
                'form_params'   => [
                    'grant_type'    =>'password',
                    'username'      =>self::$username,
                    'password'      =>self::$password
                ],
            ]);
        $response = json_decode($response);
        if (!$response->access_token)
            throw new \Exception('Can not login using credentials: ' . sprintf('[Username: %s@%s].',self::$username,self::$password));
        return $response->access_token;
    }

    /**
     * Handler::setCredentials()
     * 
     * @return
     */
    public function setCredentials($credentials)
    {
        self::$username = !empty($credentials['username'])  ? $credentials['username']  : $check = true;
        self::$password = !empty($credentials['password'])  ? $credentials['password']  : $check = true;
        if ($check){
            throw new \Exception('Require in array [username], [password]');
        }
        $this->authToken    = $this->login();
        $vehicles           = $this->getVehicles();
        if($vehicles){
            $this->primaryVin   = $vehicles->getUserVINsResponse->CSIDVins[0]->VIN;
            $this->primaryCsid  = $vehicles->getUserVINsResponse->CSIDVins[0]->CSID;
        }
    }
}
