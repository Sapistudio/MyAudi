# MyAudi rest api
# stil in tests
Inspired by https://github.com/davidgiga1993/AudiAPI
This library provides access to the MyAudi api.

First we must configure the app.credentials are not stored anywhere,just used for receiving tokens.
This is just one times use command.It will fall , unless a proper logout is made.

For auto tracking of journeys you will need an here api key
get your here api:https://developer.here.com/

```php
use SapiStudio\MyAudi\Init;

$configure = [
    'username' => 'user',
    'password' => 'pass'
    'HERE_API_KEY'=>'apivalue'
];
$myAudiHandler = Init::configure($configure);
```
Now we can make api calls
Initiate the app
```php
use SapiStudio\MyAudi\Init;

$myAudiHandler = Init::make();
```

Get car reported position
```php
$myAudiHandler->getPosition();
```
Get the car service plan
```php
$myAudiHandler->getServicePlan();
```
Get cost tracker entries
```php
$myAudiHandler->loadCostEntries();
```
Get journal entries
```php
$myAudiHandler->loadDriversEntries();
```
Check auxiliar clima status
```php
$myAudiHandler->auxiliarClimaStatus();
```
Get favourite audi partner
```php
$myAudiHandler->getFavoritePartner();
```
Get favourite audi partner
```php
$myAudiHandler->getFavoritePartner();
```
   
Setup a cron at minimum 5 minutes and run(this is the period that requests are cached.below this you are runing same request )
```php
$myAudiHandler->trackLocation();
```
when it sees a change in your position,automitcally will add a journey entry to MyAudi,and a here static map with the route
