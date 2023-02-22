<?php

use Drewlabs\AuthHttpGuard\ReadWriter;
use PHPUnit\Framework\TestCase;

class ReadWriterTest extends TestCase
{

    public function test_open_missing_file()
    {
        $this->expectException(\RuntimeException::class);
        ReadWriter::open(__DIR__ . '/app.log');
        $this->assertTrue(true);

    }

    public function test_read_bytes_from_file_resource()
    {
        ReadWriter::open(__DIR__ . '/output/app.log', 'w')->write('');
        $reader = ReadWriter::open(__DIR__ . '/output/app.log');
        $this->assertEquals('', $reader->read());
    }

    public function test_read_0_bytes_from_file_resource()
    {
        ReadWriter::open(__DIR__ . '/output/app.log', 'w')->write('');
        $reader = ReadWriter::open(__DIR__ . '/output/app.log');
        $this->assertEquals('', $reader->read(0));
    }

    public function test_write_file_resource()
    {
        $writer = ReadWriter::open(__DIR__ . '/output/app.log', 'w');
        $this->assertEquals(strlen('Hello World!'), $writer->write('Hello World!'));
    }
}