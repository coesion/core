<?php

use PHPUnit\Framework\TestCase;

class CLITest extends TestCase {
  public function testCommandsRegistration(): void {
    CLI::on('hello :name', function($name) {
      return "Hi $name";
    }, 'Greets a user');

    $commands = CLI::commands();
    $this->assertNotEmpty($commands);

    $match = null;
    foreach ($commands as $cmd) {
      if ($cmd['name'] === 'hello') {
        $match = $cmd;
        break;
      }
    }
    $this->assertNotNull($match);
    $this->assertSame('Greets a user', $match['description']);
    $this->assertStringContainsString('[name]', $match['params']);
  }
}
