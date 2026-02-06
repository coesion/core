<?php

use PHPUnit\Framework\TestCase;

class ShellTest extends TestCase {
  public function testCommandCompilation(): void {
    $shell = Shell::execCommand('echo', ['hello']);
    $this->assertStringContainsString('echo hello', $shell->getShellCommand());

    $shell = Shell::execCommand('cmd', [[
      'flag' => true,
      'name' => 'value',
      0 => 'switch'
    ]]);

    $cmd = $shell->getShellCommand();
    $this->assertStringContainsString('--flag', $cmd);
    $expectedName = PHP_OS_FAMILY === 'Windows' ? '--name="value"' : "--name='value'";
    $this->assertStringContainsString($expectedName, $cmd);
    $this->assertStringContainsString('--switch', $cmd);
  }

  public function testAliasReturnsShellInstance(): void {
    Shell::alias('demo', function() {
      return 'echo alias';
    });

    $shell = Shell::demo();
    $this->assertInstanceOf(Shell::class, $shell);
    $this->assertStringContainsString('echo alias', $shell->getShellCommand());
  }
}
