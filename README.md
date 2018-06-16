# AudiMMI

This library provides access to the Audi Connect API.



```php
use SapiStudio\AudiMMI\Handler;

$configure = [
    'username' => 'user',
    'password' => 'pass'
];
Handler::configure($configure)->loadPosition();
```
