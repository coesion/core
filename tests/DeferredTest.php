<?php

use PHPUnit\Framework\TestCase;

class DeferredTest extends TestCase {

	public function testDeferred() {
		$self = $this;
		$flag = false;

		call_user_func(function () use (&$flag, &$self) {
			$_ = new Deferred(function () use (&$flag) {
				$flag = true;
			});
			$self->assertFalse($flag);
		});

		$self->assertTrue($flag);
	}

}
