# MyAudi rest api
Inspired by https://github.com/davidgiga1993/AudiAPI
This library provides access to the MyAudi api.

```php
use SapiStudio\MyAudi\Init;

$configure = [
    'username' => 'user',
    'password' => 'pass'
];
$myAudiHandler = Init::configure(['username' => 'user','password' => 'pass']);
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
