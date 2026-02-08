<?php

use PHPUnit\Framework\TestCase;

class LoaderTest extends TestCase {
  public function testLoaderAutoloadsClassesFromAddedPath(): void {
    $dir = sys_get_temp_dir() . '/core_loader_test_' . uniqid();
    @mkdir($dir, 0777, true);

    $className = 'TmpAutoloadClass_' . uniqid();
    $file = $dir . '/' . $className . '.php';
    file_put_contents($file, "<?php class $className {}\n");

    Loader::addPath($dir);

    $this->assertTrue(class_exists($className));

    @unlink($file);
    @rmdir($dir);
  }
}
