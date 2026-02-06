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

}
