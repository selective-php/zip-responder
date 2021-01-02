# selective/zip-responder

A ZIP responder (PSR-7).

[![Latest Version on Packagist](https://img.shields.io/github/release/selective-php/zip-responder.svg)](https://packagist.org/packages/selective/zip-responder)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg)](LICENSE)
[![Build Status](https://github.com/selective-php/zip-responder/workflows/build/badge.svg)](https://github.com/selective-php/zip-responder/actions)
[![Coverage Status](https://img.shields.io/scrutinizer/coverage/g/selective-php/zip-responder.svg)](https://scrutinizer-ci.com/g/selective-php/zip-responder/code-structure)
[![Quality Score](https://img.shields.io/scrutinizer/quality/g/selective-php/zip-responder.svg)](https://scrutinizer-ci.com/g/selective-php/zip-responder/?branch=master)
[![Total Downloads](https://img.shields.io/packagist/dt/selective/zip-responder.svg)](https://packagist.org/packages/selective/zip-responder/stats)

## Table of Contents

* [Requirements](#requirements)
* [Installation](#installation)
* [Usage](#usage)
  * [Sending a ZIP file](#sending-a-zip-file)
  * [Sending a ZIP stream](#sending-a-zip-stream)
  * [Sending a ZipStream-PHP archive](#sending-a-zipstream-php-archive)
  * [Sending a ZipArchive file](#sending-a-ziparchive-file)  
  * [Slim 4 Integration](#slim-4-integration)
    
## Requirements

* PHP 7.3+ or 8.0+
* A PSR-7 StreamFactory implementation

## Installation

```
composer require selective/zip-responder
```

There are multiple A PSR-7 StreamFactory implementations available.

* [slim/psr7](https://github.com/slimphp/Slim-Psr7)
* [nyholm/psr7](https://github.com/Nyholm/psr7)

## Usage

Creating a new responder instance using the `slim/psr7` StreamFactory:

```php
use Selective\Http\Zip\ZipResponder;
use Slim\Psr7\Factory\StreamFactory;

$zipResponder = new ZipResponder(new StreamFactory());
```

### Sending a ZIP file

Send ZIP file to browser, force direct download:

```php
use Slim\Psr7\Response;
// ...

return $zipResponder->zipFile(new Response(), 'source.zip', 'output.zip');
```

In reality, you should use the Response object from the action handler:

```php
return $zipResponder->zipFile($response, 'source.zip', 'output.zip');
```

### Sending a ZIP stream

Send ZIP stream to browser, force direct download:

```php
$stream = fopen('test.zip', 'r');
 
return $zipResponder->zipStream($response, $stream, 'output.zip');
```

### Sending a ZipStream-PHP archive

[ZipStream-PHP](https://github.com/maennchen/ZipStream-PHP) is a library for 
dynamically streaming dynamic zip files from PHP without writing to the disk at 
all on the server. You can directly send it to the user, which is much faster.

**Installation:**

```
composer require maennchen/zipstream-php
```

Creating and sending a ZIP file (only in-memory) to the browser:

```php
use ZipStream\Option\Archive;
use ZipStream\ZipStream;

// ...

$archive = new Archive();
$archive->setOutputStream(fopen('php://memory', 'r+'));

$zip = new ZipStream(null, $archive);
$zip->addFile('test.txt', 'my file content');
$zip->finish();

$response = $zipResponder->zipStream($response, $archive->getOutputStream(), 'download.zip');
```

### Sending a ZipArchive file

The ZIP extension enables you to transparently read or write ZIP compressed 
archives and the files inside them.
A [ZipArchive](https://www.php.net/manual/en/class.ziparchive.php) does not support 
"memory mapped files", like PHP streams. You can only access local files with ZipArchive.
For this purpose, you can create a temporary file, or you can use an existing file from your file system.

```php
use ZipArchive;
// ...

$filename = tempnam(sys_get_temp_dir(), 'zip');

$zip = new ZipArchive();
$zip->open($filename, ZipArchive::CREATE | ZipArchive::OVERWRITE);
$zip->addFromString('test.txt', 'my content');
$zip->close();

return $zipResponder->zipStream($response, fopen($filename, 'r+'), 'download.zip');
```

### Slim 4 Integration

Insert a DI container definition for `StreamFactoryInterface::class`:

A `slim/psr7` example:

```php
<?php

use Psr\Http\Message\StreamFactoryInterface;
use Slim\Psr7\Factory\StreamFactory;

return [
    // ...
    StreamFactoryInterface::class => function () {
        return new StreamFactory();
    },
];
```

The responder should only be used within an action handler or a middleware.

**Action class example using dependency injection:**

```php
<?php

namespace App\Action;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Selective\Http\Zip\ZipResponder;
use ZipArchive;

final class ZipDemoAction
{
    /**
     * @var ZipResponder
     */
    private $zipResponder;

    public function __construct(ZipResponder $zipResponder)
    {
        $this->zipResponder = $zipResponder;
    }

    /**
     * Action.
     *
     * @param ServerRequestInterface $request The request
     * @param ResponseInterface $response The response
     *
     * @return ResponseInterface The response
     */
    public function __invoke(
        ServerRequestInterface $request, 
        ResponseInterface $response
    ): ResponseInterface {
        $filename = tempnam(sys_get_temp_dir(), 'zip');

        $zip = new ZipArchive();
        $zip->open($filename, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $zip->addFromString('test.txt', 'my content');
        $zip->close();

        return $this->zipResponder->zipFile($response, $filename, 'filename.zip');
    }
}
```

**Middleware example:**

```php
<?php

namespace App\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use ZipArchive;

final class ZipFileMiddleware implements MiddlewareInterface
{
    /**
     * @var ZipResponder
     */
    private $zipResponder;

    public function __construct(ZipResponder $zipResponder)
    {
        $this->zipResponder = $zipResponder;
    }

    public function process(
        ServerRequestInterface $request, 
        RequestHandlerInterface $handler
    ): ResponseInterface {
        $response = $handler->handle($request);
        
        // Custom logic
        // ...
        
        $filename = tempnam(sys_get_temp_dir(), 'zip');

        $zip = new ZipArchive();
        $zip->open($filename, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $zip->addFromString('test.txt', 'my content');
        $zip->close();

        return $this->zipResponder->zipFile($response, $filename, 'filename.zip');
    }
}
```

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.
