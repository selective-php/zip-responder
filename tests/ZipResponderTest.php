<?php

namespace Selective\Http\Zip\Test;

use PHPUnit\Framework\TestCase;
use PhpZip\ZipFile;
use Selective\Http\Zip\ZipResponder;
use Slim\Psr7\Factory\StreamFactory;
use Slim\Psr7\Response;
use ZipArchive;
use ZipStream\Option\Archive;
use ZipStream\ZipStream;

/**
 * Test.
 */
class ZipResponderTest extends TestCase
{
    /**
     * Create instance.
     *
     * @return ZipResponder The responder
     */
    protected function createZipResponder(): ZipResponder
    {
        return new ZipResponder(new StreamFactory());
    }

    /**
     * Test.
     *
     * @return void
     */
    public function testZipFile(): void
    {
        $responder = $this->createZipResponder();
        $response = $responder->withZipFile(new Response(), __DIR__ . '/test.zip', 'download.zip');

        $this->assertSame(298, $response->getBody()->getSize());
        $this->assertStringContainsString('file1.txt', (string)$response->getBody());
        $this->assertSame('application/zip', $response->getHeaderLine('Content-Type'));
        $this->assertSame("attachment; filename*=UTF-8''download.zip", $response->getHeaderLine('Content-Disposition'));
        $this->assertSame(200, $response->getStatusCode());
    }

    /**
     * Test.
     *
     * @return void
     */
    public function testZipStream(): void
    {
        $stream = fopen(__DIR__ . '/test.zip', 'r');

        $responder = $this->createZipResponder();
        $response = $responder->withZipStream(new Response(), $stream, 'download.zip');

        $this->assertSame(298, $response->getBody()->getSize());
        $this->assertStringContainsString('file1.txt', (string)$response->getBody());
        $this->assertSame('application/zip', $response->getHeaderLine('Content-Type'));
        $this->assertSame("attachment; filename*=UTF-8''download.zip", $response->getHeaderLine('Content-Disposition'));
        $this->assertSame(200, $response->getStatusCode());
    }

    /**
     * Test.
     *
     * @return void
     */
    public function testZipStreamPhp(): void
    {
        $zipStreamArchive = new Archive();
        $zipStreamArchive->setOutputStream(fopen('php://memory', 'r+'));

        $zip = new ZipStream(null, $zipStreamArchive);
        $zip->addFile('test.txt', 'content');
        $zip->finish();

        $responder = $this->createZipResponder();
        $response = $responder->withZipStream(new Response(), $zipStreamArchive->getOutputStream(), 'download.zip');

        $this->assertSame(123, $response->getBody()->getSize());
        $this->assertStringContainsString('test.txt', (string)$response->getBody());
        $this->assertSame('application/zip', $response->getHeaderLine('Content-Type'));
        $this->assertSame("attachment; filename*=UTF-8''download.zip", $response->getHeaderLine('Content-Disposition'));
        $this->assertSame(200, $response->getStatusCode());
    }

    /**
     * Test.
     *
     * @return void
     */
    public function testNelexaZip(): void
    {
        $zipFile = new ZipFile();
        $zipFile->addFromString('test.txt', 'File content');

        $responder = $this->createZipResponder();
        $response = $responder->withZipString(new Response(), $zipFile->outputAsString(), 'download.zip');

        $this->assertSame(126, $response->getBody()->getSize());
        $this->assertStringContainsString('test.txt', (string)$response->getBody());
        $this->assertSame('application/zip', $response->getHeaderLine('Content-Type'));
        $this->assertSame("attachment; filename*=UTF-8''download.zip", $response->getHeaderLine('Content-Disposition'));
        $this->assertSame(200, $response->getStatusCode());
    }

    /**
     * Test.
     *
     * @return void
     */
    public function testZipArchive(): void
    {
        $filename = __DIR__ . '/temp.zip';

        $zip = new ZipArchive();
        $zip->open($filename, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $zip->addFromString('test.txt', 'my content');
        $zip->close();

        $responder = $this->createZipResponder();
        $response = $responder->withZipStream(new Response(), fopen($filename, 'r'), 'download.zip');

        $this->assertSame(124, $response->getBody()->getSize());
        $this->assertStringContainsString('test.txt', (string)$response->getBody());
        $this->assertSame('application/zip', $response->getHeaderLine('Content-Type'));
        $this->assertSame("attachment; filename*=UTF-8''download.zip", $response->getHeaderLine('Content-Disposition'));
        $this->assertSame(200, $response->getStatusCode());

        if (file_exists($filename)) {
            unlink($filename);
        }
    }
}
