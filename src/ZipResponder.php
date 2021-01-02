<?php

namespace Selective\Http\Zip;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;

/**
 * A HTTP ZIP responder.
 */
final class ZipResponder
{
    /**
     * @var StreamFactoryInterface
     */
    private $streamFactory;

    /**
     * The constructor.
     *
     * @param StreamFactoryInterface $streamFactory The stream factory
     */
    public function __construct(StreamFactoryInterface $streamFactory)
    {
        $this->streamFactory = $streamFactory;
    }

    /**
     * Add ZIP file to response.
     *
     * @param ResponseInterface $response The response
     * @param string $filename The source ZIP file
     * @param string $outputName The output name
     * @param string $disposition the content disposition: 'attachment' or 'inline'
     *
     * @return ResponseInterface The response
     */
    public function zipFile(
        ResponseInterface $response,
        string $filename,
        string $outputName,
        string $disposition = 'attachment'
    ): ResponseInterface {
        $response = $this->withHttpHeaders($response, $outputName, $disposition);
        $response = $response->withBody($this->streamFactory->createStreamFromFile($filename));

        return $response;
    }

    /**
     * Add ZIP file to response.
     *
     * @param ResponseInterface $response The response
     * @param resource $stream The source ZIP stream
     * @param string $outputName The output name
     * @param string $disposition the content disposition: 'attachment' or 'inline'
     *
     * @return ResponseInterface The response
     */
    public function zipStream(
        ResponseInterface $response,
        $stream,
        string $outputName,
        string $disposition = 'attachment'
    ): ResponseInterface {
        $response = $this->withHttpHeaders($response, $outputName, $disposition);
        $response = $response->withBody($this->streamFactory->createStreamFromResource($stream));

        return $response;
    }

    /**
     * Add HTTP headers.
     *
     * @param ResponseInterface $response The response
     * @param string $outputName The output ZIP filename
     * @param string $contentDisposition the content disposition
     *
     * @return ResponseInterface The response
     */
    private function withHttpHeaders(
        ResponseInterface $response,
        string $outputName,
        string $contentDisposition
    ): ResponseInterface {
        if ($outputName) {
            // Various different browsers dislike various characters here. Strip them all for safety.
            $safeOutput = trim(str_replace(['"', "'", '\\', ';', "\n", "\r"], '', $outputName));

            // Check if we need to UTF-8 encode the filename
            $urlencoded = rawurlencode($safeOutput);
            $contentDisposition .= "; filename*=UTF-8''{$urlencoded}";
        }

        $headers = [
            'Content-Type' => 'application/zip',
            'Content-Disposition' => $contentDisposition,
            'Pragma' => 'public',
            'Cache-Control' => 'public, must-revalidate',
            'Content-Transfer-Encoding' => 'binary',
        ];

        foreach ($headers as $key => $val) {
            $response = $response->withHeader($key, $val);
        }

        return $response;
    }
}
