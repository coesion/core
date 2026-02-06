<?php

use PHPUnit\Framework\TestCase;
use View\Adapter as ViewAdapter;
use View\PHP as ViewPHP;

class ViewPHPTest extends TestCase {
  public function testRenderTemplateWithGlobals(): void {
    $dir = sys_get_temp_dir() . '/core_view_' . uniqid();
    @mkdir($dir, 0777, true);
    file_put_contents($dir . '/hello.php', 'Hello <?= $name ?>');

    $view = new ViewPHP($dir);
    $this->assertInstanceOf(ViewAdapter::class, $view);

    ViewPHP::addGlobal('name', 'World');
    $this->assertSame('Hello World', $view->render('hello'));

    @unlink($dir . '/hello.php');
    @rmdir($dir);
  }
}
