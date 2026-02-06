<?php

use PHPUnit\Framework\TestCase;

class ResponseTest extends TestCase {

	public function testGet() {
		Response::clean();
		Response::text('alpha', 'beta');
		$this->assertEquals('alphabeta', Response::body());

		Response::clean();
		Response::json(['x' => 1]);
		$this->assertEquals('{"x":1}', Response::body());
	}

	public function testResetClearsState() {
		Response::text('payload');
		Response::header('X-Test', '1');
		Response::status(201, 'Created');
		Response::download('file.txt');

		Response::reset();

		$this->assertSame('', Response::body());
		$this->assertFalse(Response::sent());
		$headers = Response::headers();
		$this->assertArrayHasKey('Content-Type', $headers);
		$contentType = $headers['Content-Type'][0];
		$this->assertSame('text/html; charset=utf-8', $contentType);
	}

}
