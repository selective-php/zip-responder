<?php

namespace Selective\Http\Zip\Test;

use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\Response;
use PHPUnit\Framework\TestCase;
use PhpZip\ZipFile;
use Selective\Http\Zip\Stream\CallbackStream;
use Selective\Http\Zip\ZipResponder;
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
        return new ZipResponder(new Psr17Factory());
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

    /**
     * Test.
     *
     * @return void
     */
    public function testZipArchiveCallbackStream(): void
    {
        $this->expectOutputRegex('/test\.txt/');

        $callbackStream = new CallbackStream(function () {
            $archive = new Archive();

            // Only for testing. Should be true in production.
            $archive->setFlushOutput(false);
            $zip = new ZipStream(null, $archive);

            // Add files to ZIP file and stream it directly
            $zip->addFile('test.txt', 'my file content');
            $zip->addFile('test2.txt', 'my file content 2');
            $zip->addFile('test3.txt', 'my file content 4');
            $zip->finish();
        });

        $responder = $this->createZipResponder();
        $response = $responder->withZipHeaders(new Response(), 'download.zip', true);
        $response = $response->withBody($callbackStream);

        $this->assertSame(null, $response->getBody()->getSize());
        $this->assertSame('application/zip', $response->getHeaderLine('Content-Type'));
        $this->assertSame("attachment; filename*=UTF-8''download.zip", $response->getHeaderLine('Content-Disposition'));
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('', (string)$response->getBody());

        // The read method triggers the callback function
        $this->assertSame('', $response->getBody()->read(4096));

        $content = (string)ob_get_contents();
        $this->assertStringStartsWith('PK', $content);
        $this->assertSame(2, substr_count($content, 'test.txt'));
        $this->assertSame(2, substr_count($content, 'test2.txt'));
        $this->assertSame(2, substr_count($content, 'test3.txt'));
    }
}
