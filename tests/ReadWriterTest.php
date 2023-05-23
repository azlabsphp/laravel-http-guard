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

use Drewlabs\HttpGuard\ReadWriter;
use PHPUnit\Framework\TestCase;

class ReadWriterTest extends TestCase
{
    public function test_open_missing_file()
    {
        $this->expectException(\RuntimeException::class);
        ReadWriter::open(__DIR__.'/app.log');
        $this->assertTrue(true);
    }

    public function test_read_bytes_from_file_resource()
    {
        ReadWriter::open(__DIR__.'/output/app.log', 'w')->write('');
        $reader = ReadWriter::open(__DIR__.'/output/app.log');
        $this->assertSame('', $reader->read());
    }

    public function test_read_0_bytes_from_file_resource()
    {
        ReadWriter::open(__DIR__.'/output/app.log', 'w')->write('');
        $reader = ReadWriter::open(__DIR__.'/output/app.log');
        $this->assertSame('', $reader->read(0));
    }

    public function test_write_file_resource()
    {
        $writer = ReadWriter::open(__DIR__.'/output/app.log', 'w');
        $this->assertSame(strlen('Hello World!'), $writer->write('Hello World!'));
    }

    public function test_read_from_closed_resource_return_false()
    {
        $reader = ReadWriter::open(__DIR__.'/output/app.log', 'r');
        // Close the io reader
        $reader->close();
        $this->assertEquals(false, $reader->read());
    }

    public function test_write_to_closed_resource_return_false()
    {
        $writer = ReadWriter::open(__DIR__.'/output/app.log', 'r');
        // Close the io reader
        $writer->close();
        $this->assertEquals(false, $writer->write('My Text content...'));
    }
}
