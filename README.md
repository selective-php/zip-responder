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
    * [Sending a ZIP file from a string](#sending-a-zip-file-from-a-string)  
    * [Sending a ZIP stream](#sending-a-zip-stream)
    * [Sending a ZipArchive file](#sending-a-ziparchive-file)
    * [Sending a ZipStream-PHP archive](#sending-a-zipstream-php-archive)
    * [Sending a PhpZip archive](#sending-a-phpzip-archive)
* [Slim 4 Integration](#slim-4-integration)

## Requirements

* PHP 7.3+ or 8.0+
* A PSR-7 StreamFactory implementation, e.g. [nyholm/psr7](https://github.com/Nyholm/psr7)

## Installation

```
composer require selective/zip-responder
```

## Usage

Creating a new responder instance using the `nyholm/psr7` Psr17Factory:

```php
use Selective\Http\Zip\ZipResponder;
use Nyholm\Psr7\Factory\Psr17Factory;

$zipResponder = new ZipResponder(new Psr17Factory());
```

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

return $zipResponder->withZipFile(new Response(), 'source.zip', 'output.zip');
```

In reality, it makes sense to use the response object of the action handler:

```php
return $zipResponder->withZipFile($response, 'source.zip', 'output.zip');
```

### Sending a ZIP file from a string

```php
return $zipResponder->withZipString($response, file_get_contents('example.zip'), 'output.zip');
```

### Sending a ZIP stream

Send ZIP stream to browser, force direct download:

```php
$stream = fopen('test.zip', 'r');
 
return $zipResponder->withZipStream($response, $stream, 'output.zip');
```

### Sending a ZIP stream on the fly

Sending a file directly to the client is not intended according to the PSR-7 specification, 
but can still be realized with the help of a CallbackStream.

```php
use Selective\Http\Zip\Stream\CallbackStream;

$callbackStream = new CallbackStream(function () {
    echo 'my binary zip content';
}

$response = $zipResponder->withZipHeaders($response, $outputName, true);

return $response->withBody($callbackStream);
```

### Sending a ZipArchive file

The ZIP extension enables you to transparently read or write ZIP compressed archives and the files inside them.
A [ZipArchive](https://www.php.net/manual/en/class.ziparchive.php) does not support
"memory mapped files", like PHP streams. You can only access local files with ZipArchive. For this purpose, you can
create a temporary file, or you can use an existing file from the filesystem.

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
return $zipResponder->withZipStream($response, fopen($filename, 'r'), 'download.zip');
```

### Sending a ZipStream-PHP archive

[ZipStream-PHP](https://github.com/maennchen/ZipStream-PHP) is a library for streaming dynamic ZIP files without writing
to the disk. You can send the file directly to the user, which is much faster and improves testability.

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

$response = $zipResponder->withZipStream($response, $archive->getOutputStream(), 'download.zip');
```

Sending a zipstream on the fly:

```php
use Selective\Http\Zip\Stream\CallbackStream;
use ZipStream\Option\Archive;
use ZipStream\ZipStream;
//...

$callbackStream = new CallbackStream(function () {
    $archive = new Archive();

    // Flush ZIP file directly to output stream (php://output)
    $archive->setFlushOutput(true);
    $zip = new ZipStream(null, $archive);

    // Add files to ZIP file and stream it directly
    $zip->addFile('test.txt', 'my file content');
    $zip->addFile('test2.txt', 'my file content 2');
    $zip->addFile('test3.txt', 'my file content 4');
    $zip->finish();
});

$response = $zipResponder->withZipHeaders($response, $outputName, true);

return $response->withBody($callbackStream);
```

### Sending a PhpZip archive

[PhpZip](https://github.com/Ne-Lexa/php-zip) is a library for extended work with ZIP-archives.

**Installation:**

```
composer require nelexa/zip
```

Note, when you use the `nelexa/zip` component, you may not need the `selective/zip-responder` 
component because the `nelexa/zip` already provides its own PSR-7 responder.

**Example**

```php
use PhpZip\ZipFile;

// ...

$zipFile = new ZipFile();
$zipFile->addFromString('test.txt', 'File content');

return $zipFile->outputAsResponse($response, 'download.zip');
```

In case you want to keep your architecture more clean (SRP), 
you may use the `selective/zip-responder` responder to create 
and send a ZIP file to the browser as follows:

```php
use PhpZip\ZipFile;

// ...

// Create new archive
$zipFile = new ZipFile();

// Add entry from string
$zipFile->addFromString('test.txt', 'File content');
     
return $zipResponder->withZipString($response, $zipFile->outputAsString(), 'download.zip');
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

        return $this->zipResponder->withZipFile($response, $filename, 'filename.zip');
    }
}
```

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.
