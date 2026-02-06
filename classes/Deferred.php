<?php

/**
 * Deferred
 *
 * Run callback when script execution is stopped.
 *
 * @package core
 * @author Stefano Azzolini <lastguest@gmail.com>
 * @copyright Coesion - 2026
 */

class Deferred {

	protected $callback,
            $enabled = true;

	public function __construct( callable $callback ) {
		$this->callback = $callback;
	}

  public function disarm() {
    $this->enabled = false;
  }

  public function prime() {
    $this->enabled = true;
  }

	public function __destruct() {
		if ( $this->enabled ) call_user_func( $this->callback );
	}

}