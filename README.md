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
  * [Sending a compressed HTTP response](#)
 * [Slim 4 Integration](#slim-4-integration)
    
## Requirements

* PHP 7.3+ or 8.0+
* A PSR-7 StreamFactory implementation

## Installation

```
composer require selective/zip-responder
```

There are multiple PSR-7 StreamFactory implementations available.

* [slim/psr7](https://github.com/slimphp/Slim-Psr7)
* [nyholm/psr7](https://github.com/Nyholm/psr7)
* [guzzlehttp/psr7](https://github.com/guzzle/psr7)
* [laminas/laminas-diactoros](https://github.com/laminas/laminas-diactoros)

To install the `slim/psr7` component, run:

```
composer require slim/psr7
```

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

In reality, it makes sense to use the response object of the action handler:

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
streaming dynamic ZIP files without writing to the disk.
You can send the file directly to the user, which is much faster and improves testability.

**Installation:**

```
composer require maennchen/zipstream-php
```

Creating and sending a ZIP file (only in-memory) to the browser:

```php
use ZipStream\Option\Archive;
use ZipStream\ZipStream;

// ...

// Create ZIP file, only in-memory
$archive = new Archive();
$archive->setOutputStream(fopen('php://memory', 'r+'));

// Add files to ZIP file
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
For this purpose, you can create a temporary file, or you can use an existing file from the filesystem.

```php
use ZipArchive;
// ...

// Create temporary filename
$filename = tempnam(sys_get_temp_dir(), 'zip');

// Add files to temporary ZIP file
$zip = new ZipArchive();
$zip->open($filename, ZipArchive::CREATE | ZipArchive::OVERWRITE);
$zip->addFromString('test.txt', 'my content');
$zip->close();

// Render ZIP file into the response as stream
return $zipResponder->zipStream($response, fopen($filename, 'r+'), 'download.zip');
```

### Sending a compressed HTTP response

Compression is a simple, effective way to save bandwidth and speed up your site.

```php
return $zipResponder->deflateResponse($response);
```

**Verify Your Compression**

To make sure you’re actually serving up compressed content you can:

In your browser: In Chrome or Firefox, open the Developer Toolbar (F12) > Network tab. 
Refresh the page, and click the network line for the page itself. 
The header `Content-encoding: deflate` means that the content was sent compressed.

Use the [online gzip test](http://www.gidnetwork.com/tools/gzip-test.php) to check whether your page is compressed.

**Caveats**

As exciting as it may appear, HTTP Compression isn’t all fun and games. Here’s what to watch out for:

* Older browsers: Yes, some browsers still may have trouble with compressed content 
  (they say they can accept it, but really they can’t). 
  If your site absolutely must work with very old browsers, you may not want to 
  use HTTP compression.

* Already-compressed content: Most images, music and videos are already compressed. 
  Don’t waste time compressing them again. In fact, you probably only need to compress 
  HTML, CSS and Javascript.

* CPU-load: Compressing content on-the-fly uses CPU time and saves bandwidth. 
  Usually this is a great tradeoff given the speed of compression. 
  There are ways to pre-compress static content and send over the compressed versions. 
  This requires more configuration; even if it’s not possible, compressing output may still 
  be a net win. Using CPU cycles for a faster user experience is well worth it, 
  given the short attention spans on the web.

Another way to compress the HTTP content is to use the Apache `mod_deflate` module instead.
You can restrict compression to specific MIME types if needed.

```htaccess
<IfModule mod_deflate.c>
AddOutputFilterByType DEFLATE text/plain
AddOutputFilterByType DEFLATE text/html
AddOutputFilterByType DEFLATE text/xml
AddOutputFilterByType DEFLATE text/shtml
AddOutputFilterByType DEFLATE text/css
AddOutputFilterByType DEFLATE application/xml
AddOutputFilterByType DEFLATE application/xhtml+xml
AddOutputFilterByType DEFLATE application/rss+xml
AddOutputFilterByType DEFLATE application/javascript
AddOutputFilterByType DEFLATE application/x-javascript
</IfModule>
```

## Slim 4 Integration

Insert a DI container definition: `StreamFactoryInterface::class`

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

The responder should only be used within an action handler or middleware.

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
use Selective\Http\Zip\ZipResponder;

final class InflateMiddleware implements MiddlewareInterface
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

        // Restrict compression to specific MIME types
        $contentType = $response->getHeaderLine('Content-Type');

        if (strpos($contentType, 'text/html') !== false) {
            return $this->zipResponder->deflateResponse($response);
        }

        return $response;
    }
}

```

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.
