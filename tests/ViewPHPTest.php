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

  public function testPHPContextAccessorsAndPartial(): void {
    $dir = sys_get_temp_dir() . '/core_view_ctx_' . uniqid();
    @mkdir($dir, 0777, true);
    file_put_contents($dir . '/partial.php', 'Partial: <?= $name ?>');
    file_put_contents($dir . '/main.php', 'Main: <?= $this->partial("partial") ?>');

    View::using(new ViewPHP($dir));

    $context = new View\PHPContext(['name' => 'Rick']);
    $this->assertSame('Rick', $context->name);
    $this->assertSame('', $context->missing);

    $rendered = (string) View::from('main', ['name' => 'Daryl']);
    $this->assertSame('Main: Partial: Daryl', $rendered);

    @unlink($dir . '/partial.php');
    @unlink($dir . '/main.php');
    @rmdir($dir);
  }
}
