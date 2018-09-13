# AudiMMI

This library provides access to the Audi Connect API.



```php
use SapiStudio\AudiMMI\Handler;

$configure = [
    'username' => 'user',
    'password' => 'pass'
];
(new SapiStudio\AudiMMI\tripHistory(Handler::configure($configure)))->checkingStatus();
Handler::configure($configure)->loadPosition();
```
