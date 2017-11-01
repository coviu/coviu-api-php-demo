This is a rewrite of the python demo in the coviu-api-python-demo repository in PHP.

This demonstration uses a PHP dependency manager called composer: https://github.com/coviu/coviu-api-php-demo

To install all dependencies:
```
composer require coviu/coviu-sdk
```


To run the demo:
```
COVIU_API_KEY=... COVIU_API_KEY_SECRET=... [COVIU_API_ENDPOINT=...] php demo.php
```

Use the API key and API key secret created in your account.
See https://help.coviu.com/api-information/general-questions/how-do-i-create-an-api-key-secret .


For example:
```
COVIU_API_KEY=d0ed4c21-0037-4eff-9a48-8cdf9d058294 COVIU_API_KEY_SECRET=bf64e03ebb68da6c573b COVIU_API_ENDPOINT=http://localhost:9400/v1 php demo.php
```
