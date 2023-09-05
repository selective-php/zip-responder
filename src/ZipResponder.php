<?php

namespace Selective\Http\Zip;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;

/**
 * A HTTP ZIP response provider.
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
     * @param bool $attachment The Content-Disposition header
     *
     * @return ResponseInterface The response
     */
    public function withZipFile(
        ResponseInterface $response,
        string $filename,
        string $outputName,
        bool $attachment = true
    ): ResponseInterface {
        $response = $this->withZipHeaders($response, $outputName, $attachment);

        $response = $response->withBody($this->streamFactory->createStreamFromFile($filename));

        return $this->withContentLengthHeader($response);
    }

    /**
     * Add ZIP file to response.
     *
     * @param ResponseInterface $response The response
     * @param string $content The source ZIP file content
     * @param string $outputName The output name
     * @param bool $attachment The Content-Disposition header
     *
     * @throws \UnexpectedValueException
     *
     * @return ResponseInterface The response
     */
    public function withZipString(
        ResponseInterface $response,
        string $content,
        string $outputName,
        bool $attachment = true
    ): ResponseInterface {
        $response = $this->withZipHeaders($response, $outputName, $attachment);

        $stream = fopen('php://temp', 'r+');

        if (!$stream) {
            throw new \UnexpectedValueException('Stream could not be created');
        }

        fwrite($stream, $content);

        $response = $response->withBody($this->streamFactory->createStreamFromResource($stream));

        return $this->withContentLengthHeader($response);
    }

    /**
     * Add ZIP stream to response.
     *
     * @param ResponseInterface $response The response
     * @param resource $stream The source ZIP stream
     * @param string $outputName The output name
     * @param bool $attachment The Content-Disposition header
     *
     * @return ResponseInterface The response
     */
    public function withZipStream(
        ResponseInterface $response,
        $stream,
        string $outputName,
        bool $attachment = true
    ): ResponseInterface {
        $response = $this->withZipHeaders($response, $outputName, $attachment);

        $psrStream = $this->streamFactory->createStreamFromResource($stream);
        $response = $response->withBody($psrStream);

        return $this->withContentLengthHeader($response);
    }

    /**
     * Add HTTP headers.
     *
     * @param ResponseInterface $response The response
     * @param string $outputFilename The output filename
     * @param bool $attachment The Content-Disposition Header. If true then attachment otherwise inline
     *
     * @return ResponseInterface The response
     */
    public function withZipHeaders(
        ResponseInterface $response,
        string $outputFilename,
        bool $attachment
    ): ResponseInterface {
        $contentDisposition = ($attachment ? 'attachment' : 'inline');

        if ($outputFilename) {
            // Various different browsers dislike various characters here. Strip them all for safety.
            $outputFilename = trim(str_replace(['"', "'", '\\', ';', "\n", "\r"], '', basename($outputFilename)));

            // Check if we need to UTF-8 encode the filename
            $urlencoded = rawurlencode($outputFilename);
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

    /**
     * Add Content-Length header.
     *
     * @param ResponseInterface $response The response
     *
     * @return ResponseInterface The response
     */
    private function withContentLengthHeader(ResponseInterface $response): ResponseInterface
    {
        // Add Content-Length header if not already added
        $size = $response->getBody()->getSize();

        if ($size !== null && !$response->hasHeader('Content-Length')) {
            $response = $response->withHeader('Content-Length', (string)$size);
        }

        return $response;
    }
}
