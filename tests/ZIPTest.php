<?php

use PHPUnit\Framework\TestCase;

class ZIPTest extends TestCase {
  public function testZipCreateAndWrite(): void {
    if (!class_exists('ZipArchive')) {
      $this->markTestSkipped('ZipArchive extension is not available.');
    }

    $base = sys_get_temp_dir() . '/core_zip_' . uniqid();
    $zip = new ZIP($base);
    $zip->write('file.txt', 'content');
    $zip->close();

    $za = new ZipArchive();
    $this->assertTrue($za->open($zip->path()));
    $this->assertNotFalse($za->locateName('file.txt'));
    $za->close();

    @unlink($zip->path());
  }
}
