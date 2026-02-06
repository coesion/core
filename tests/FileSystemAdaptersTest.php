<?php

use PHPUnit\Framework\TestCase;
use FileSystem\Adapter as FSAdapter;
use FileSystem\Native as FSNative;
use FileSystem\ZIP as FSZip;

class FileSystemAdaptersTest extends TestCase {
  public function testNativeFilesystemReadWriteSearch(): void {
    $root = sys_get_temp_dir() . '/core_fs_test_' . uniqid();
    @mkdir($root, 0777, true);
    $fs = new FSNative(['root' => $root]);
    $this->assertInstanceOf(FSAdapter::class, $fs);

    $this->assertFalse($fs->exists('alpha.txt'));
    $fs->write('alpha.txt', 'hello');
    $this->assertTrue($fs->exists('alpha.txt'));
    $this->assertSame('hello', $fs->read('alpha.txt'));

    $fs->append('alpha.txt', ' world');
    $this->assertSame('hello world', $fs->read('alpha.txt'));

    $matches = $fs->search('*.txt');
    $this->assertContains('alpha.txt', $matches);

    $fs->delete('alpha.txt');
    $this->assertFalse($fs->exists('alpha.txt'));

    @rmdir($root);
  }

  public function testZipFilesystemBasicOperations(): void {
    if (!class_exists('ZipArchive')) {
      $this->markTestSkipped('ZipArchive extension is not available.');
    }

    $zipPath = sys_get_temp_dir() . '/core_zip_fs_' . uniqid() . '.zip';
    $fs = new FSZip(['root' => $zipPath]);
    $this->assertInstanceOf(FSAdapter::class, $fs);

    $fs->write('doc.txt', 'data');
    $this->assertTrue($fs->exists('doc.txt'));
    $this->assertSame('data', $fs->read('doc.txt'));

    $matches = $fs->search('*.txt');
    $this->assertContains('doc.txt', $matches);

    @unlink($zipPath);
  }
}
