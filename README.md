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
