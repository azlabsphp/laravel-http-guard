<?php

declare(strict_types=1);

/*
 * This file is part of the drewlabs namespace.
 *
 * (c) Sidoine Azandrew <azandrewdevelopper@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Drewlabs\HttpGuard;

class ReadWriter
{
    /**
     * @var int|resource
     */
    private $descriptor;

    /**
     * Creates a read writer instance.
     *
     * @param mixed $descriptor
     *
     * @return void
     */
    private function __construct($descriptor)
    {
        $this->descriptor = $descriptor;
    }

    public function __destruct()
    {
        $this->close();
    }

    public static function open(string $path, $mode = 'r', $include_path = false, $context = null)
    {
        $fd = @fopen($path, $mode, $include_path, $context);
        if (false === $fd) {
            throw new \RuntimeException(sprintf('Error opening stream at path: %s. %s', $path, error_get_last()['message'] ?? ''));
        }

        return new static($fd);
    }

    /**
     * Read data from the open file resource.
     *
     * **Note** Method returns false if was unable to read from
     * file resource because the resource was close or a read error
     * occurs
     *
     * @return string|false
     */
    public function read(?int $length = null)
    {
        // Case the read writer is not a resource, we simply return false
        if (!\is_resource($this->descriptor)) {
            return false;
        }

        if (null === $length) {
            $length = \is_array($stats = @fstat($this->descriptor)) ? $stats['size'] : 0;
        }

        return 0 === $length ? '' : $this->readBytes($length);
    }

    /**
     * Write a total bytes length to the opened file resource.
     *
     * **Note** Method returns false if was unable to write to
     * file resource because the resource was close or a write error
     * occurs
     *
     * @return int|false
     */
    public function write(string $data, ?int $length = null)
    {
        // Case the read writer is not a resource, we simply return false
        if (!\is_resource($this->descriptor)) {
            return false;
        }
        $bytes = false;
        if ($this->descriptor && @flock($this->descriptor, \LOCK_EX | \LOCK_NB)) {
            $bytes = @fwrite($this->descriptor, $data, $length);
            @flock($this->descriptor, \LOCK_UN);
        }

        return $bytes;
    }

    /**
     * Closes the readable resource.
     *
     * @return void
     */
    public function close()
    {
        if (null !== $this->descriptor && \is_resource($this->descriptor)) {
            fclose($this->descriptor);
            $this->descriptor = null;
        }
    }

    /**
     * Read a total of bytes from file descriptor.
     *
     * @return string|false
     */
    private function readBytes(int $length)
    {
        $contents = false;
        if ($this->descriptor && @flock($this->descriptor, \LOCK_EX | \LOCK_NB)) {
            $contents = @fread($this->descriptor, $length);
            @flock($this->descriptor, \LOCK_UN);
        }

        return $contents;
    }
}
