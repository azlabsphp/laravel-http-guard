<?php

declare(strict_types=1);

/*
 * This file is part of the Drewlabs package.
 *
 * (c) Sidoine Azandrew <azandrewdevelopper@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Drewlabs\AuthHttpGuard;


class ReadWriter
{
    /**
     * 
     * @var int|resource
     */
    private $descriptor;

    /**
     * Creates a read writer instance
     * 
     * @param mixed $descriptor 
     * @return void 
     */
    private function __construct($descriptor)
    {
        $this->descriptor = $descriptor;
    }

    public static function open(string $path, $mode = 'r', $include_path = false, $context = null)
    {
        $fd = @fopen($path, $mode, $include_path, $context);
        if (false === $fd) {
            throw new \RuntimeException(sprintf("Error opening stream at path: %s. %s", $path, error_get_last()['message'] ?? ''));
        }
        return new static($fd);
    }

    /**
     * Read data from the open file resource
     * 
     * @param int|null $length 
     * @return string|false 
     */
    public function read(int $length = null)
    {
        if (null === $length) {
            $length = is_array($stats = @fstat($this->descriptor)) ? $stats['size'] : 0;
        }
        return 0 === $length ? '' : $this->readBytes($length);
    }

    /**
     * Write a total bytes length to the opened file resource
     * 
     * @param string $data 
     * @param int|null $length 
     * @return int|false 
     */
    public function write(string $data, int $length = null)
    {
        $bytes = false;
        if ($this->descriptor && @flock($this->descriptor, \LOCK_EX | \LOCK_NB)) {
            $bytes = @fwrite($this->descriptor, $data, $length);
            @flock($this->descriptor, \LOCK_UN);
        }
        return $bytes;
    }

    /**
     * Closes the readable resource
     * 
     * @return void 
     */
    public function close()
    {
        fclose($this->descriptor);
        $this->descriptor = null;
    }

    public function __destruct()
    {
        $this->close();
    }

    /**
     * Read a total of bytes from file descriptor
     * 
     * @param int $length 
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
