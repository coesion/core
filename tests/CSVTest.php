<?php

use PHPUnit\Framework\TestCase;

class CSVTest extends TestCase {
  public function testWriteAndReadCsv(): void {
    $file = tempnam(sys_get_temp_dir(), 'csv_test_');
    $csv = CSV::create($file, CSV::STANDARD);
    $csv->write(['name' => 'Alice', 'age' => 30]);
    $csv->write(['name' => 'Bob', 'age' => 40]);
    $csv->flush();

    $reader = CSV::open($file, CSV::STANDARD);
    $rows = $reader->each();

    $this->assertCount(2, $rows);
    $this->assertSame('Alice', $rows[0]['name']);
    $this->assertSame('40', $rows[1]['age']);

    @unlink($file);
  }
}
