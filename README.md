# MyAudi rest api
# stil in tests , used it with caution
Inspired by https://github.com/davidgiga1993/AudiAPI
This library provides access to the MyAudi api.

First we must configure the app.credentials are not stored anywhere,just used for receiving tokens.
This is just one times use command.It will fall on a second run, unless a proper logout is made.

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
$myAudiHandler->Entries()->loadCosts();
```
Get journal entries
```php
$myAudiHandler->Entries()->loadJourneys();
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
when it sees a change in your position,automitcally will add a journey entry to MyAudi,and a HERE static map with the route

Finally, for loggin out , just use
```php

Init::logout();
```
This will clear all your tokens.
For making api calls again,you must start with the configure function
