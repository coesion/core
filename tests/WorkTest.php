<?php

use PHPUnit\Framework\TestCase;

class WorkTest extends TestCase {

	protected function setUp(): void {
    parent::setUp();

  }

	public function testAddParallel() {
		$results = [];

		Work::add(function() use (&$results){
			$results[] = 'a';
			yield;
			$results[] = 'b';
		});

		Work::add(function() use (&$results){
			$results[] = 'c';
			yield;
			$results[] = 'd';
		});

		Work::run();
		$this->assertEquals(['a','c','b','d'], $results);
	}

  public function testTaskCoroutineLifecycle(): void {
    $gen = (function () {
      $first = yield 'start';
      yield $first;
    })();

    $task = new TaskCoroutine(1, $gen);
    $this->assertSame('start', $task->run());
    $this->assertFalse($task->complete());

    $task->pass('next');
    $this->assertSame('next', $task->run());
    $this->assertFalse($task->complete());

    $task->run();
    $this->assertTrue($task->complete());
  }

}
