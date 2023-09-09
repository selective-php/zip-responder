<?php

namespace Selective\Http\Zip\Stream;

use Psr\Http\Message\StreamInterface;
use RuntimeException;

/**
 * Callback-based stream implementation.
 *
 * Wraps a callback, and invokes it in order to stream it.
 *
 * Only one invocation is allowed; multiple invocations will return an empty
 * string for the second and subsequent calls.
 */
final class CallbackStream implements StreamInterface
{
    /**
     * @var callable|null
     */
    private $callback;

    /**
     * Whether the callback has been previously invoked.
     *
     * @var bool
     */
    private $called = false;

    /**
     * @param callable $callback The callback function that echos the body content
     */
    public function __construct(callable $callback)
    {
        $this->callback = $callback;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return '';
    }

    /**
     * Closes the stream and any underlying resources.
     *
     * @return void
     */
    public function close()
    {
    }

    /**
     * Separates any underlying resources from the stream.
     *
     * After the stream has been detached, the stream is in an unusable state.
     *
     * @return resource|null Underlying PHP stream, if any
     */
    public function detach()
    {
        $this->callback = null;

        return null;
    }

    /**
     * Get the size of the stream if known.
     *
     * @return int|null returns the size in bytes if known, or null if unknown
     */
    public function getSize()
    {
        return null;
    }

    /**
     * Returns the current position of the file read/write pointer.
     *
     * @throws RuntimeException on error
     *
     * @return int Position of the file pointer
     */
    public function tell()
    {
        return 0;
    }

    /**
     * Returns true if the stream is at the end of the stream.
     *
     * @return bool
     */
    public function eof()
    {
        return $this->called;
    }

    /**
     * Returns whether the stream is seekable.
     *
     * @return bool
     */
    public function isSeekable()
    {
        return false;
    }

    /**
     * Seek to a position in the stream.
     *
     * @see http://www.php.net/manual/en/function.fseek.php
     *
     * @param int $offset Stream offset
     * @param int $whence Specifies how the cursor position will be calculated
     *     based on the seek offset. Valid values are identical to the built-in
     *     PHP $whence values for `fseek()`.  SEEK_SET: Set position equal to
     *     offset bytes SEEK_CUR: Set position to current location plus offset
     *     SEEK_END: Set position to end-of-stream plus offset.
     *
     * @throws RuntimeException on failure
     *
     * @return void
     */
    public function seek($offset, $whence = SEEK_SET)
    {
    }

    /**
     * Seek to the beginning of the stream.
     *
     * If the stream is not seekable, this method will raise an exception;
     * otherwise, it will perform a seek(0).
     *
     * @throws RuntimeException on failure
     *
     * @return bool
     *
     * @see seek()
     * @see http://www.php.net/manual/en/function.fseek.php
     */
    public function rewind()
    {
        return false;
    }

    /**
     * Returns whether the stream is writable.
     *
     * @return bool
     */
    public function isWritable()
    {
        return false;
    }

    /**
     * Write data to the stream.
     *
     * @param string $string The string that is to be written
     *
     * @throws RuntimeException on failure
     *
     * @return int Returns the number of bytes written to the stream
     */
    public function write($string)
    {
        return 0;
    }

    /**
     * Returns whether the stream is readable.
     *
     * @return bool
     */
    public function isReadable()
    {
        return true;
    }

    /**
     * Read data from the stream.
     *
     * @param int $length Read up to $length bytes from the object and return them.
     *  Fewer than $length bytes may be returned if underlying stream call returns fewer bytes.
     *
     * @throws RuntimeException if an error occurs
     *
     * @return string returns the data read from the stream, or an empty string if no bytes are available
     */
    public function read($length)
    {
        if ($this->called || !$this->callback) {
            return '';
        }

        $this->called = true;

        // Execute the callback with output buffering.
        call_user_func($this->callback);

        return '';
    }

    /**
     * Returns the remaining contents in a string.
     *
     * @throws RuntimeException if unable to read or an error occurs while reading
     *
     * @return string
     */
    public function getContents()
    {
        return '';
    }

    /**
     * Get stream metadata as an associative array or retrieve a specific key.
     *
     * The keys returned are identical to the keys returned from PHP's
     * stream_get_meta_data() function.
     *
     * @see http://php.net/manual/en/function.stream-get-meta-data.php
     *
     * @param string $key The specific metadata to retrieve
     *
     * @return array|mixed|null Returns an associative array if no key is
     * provided. Returns a specific key value if a key is provided and the
     * value is found, or null if the key is not found.
     */
    public function getMetadata($key = null)
    {
        if ($key === null) {
            return [];
        }

        return null;
    }
}
