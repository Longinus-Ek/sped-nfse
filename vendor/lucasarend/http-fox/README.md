# foxy-http

## About Package
Hey,
This is my first package, I'm still learning. Feel free to suggest improvements

## Installation

Router is available via Composer:

```bash
"lucasarend/http-fox": "^1.0"
```
or run
```bash
composer require lucasarend/http-fox
```

## Documentation
### MultCrawler
If you intend to use it for multiple crawlers or simultaneous requests that require cookies, set this option in your .env file.
```php
HTTP_MULTI_CRAWLER=true
```

### Create Class
```php
<?php

use LucasArend\HttpFox\HttpFox;

$http = new HttpFox();
```

### Simple Get Page
```php
$http->getURL('https://www.blogger.com/about/?hl=pt-br');
//Write Page Return
echo $http->response;
```

### Simple Post
```php
$postData = 'name=Lucas';
$http->sendPost('https://www.blogger.com/about/?hl=pt-br',$postData);
//Write Page Return
echo $http->response;
```

### Proxy And Debug
#### Set Proxy
You only need to do this once and then you can run multiple requests.
```
$http->setProxy('Host',Port,'User','Password');
```
### Debug
#### Debug Request
To debug the requests you will need a program to intersperse these requests as a proxy server, I like to use [Fiddler]('https://www.telerik.com/fiddler').
<br />Example of a simple debug routine
```php
use LucasArend\HttpFox\HttpFox;

$http = new HttpFox();

$http->setProxy();//setProxy use default fiddler config

$http->getURL('https://www.blogger.com/about/?hl=pt-br');

//Write Page Return
echo $http->response;
```

#### Response headers
```php
$httpFox = new HttpFox();
$httpFox->enableResponseHeader(); //Enable response headers

$httpFox->enableResponseHeader(false); // Disable response headers
```

## License

The MIT License (MIT). Please see [License File](https://github.com/LucsaArend/foxy-http/blob/main/LICENSE) for more information.