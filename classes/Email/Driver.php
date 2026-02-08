<?php

/**
 * Email\Driver
 *
 * Email services common interface.
 *
 * @package core
 * @author Stefano Azzolini <lastguest@gmail.com>
 * @copyright Coesion - 2026
 */

namespace Email;

interface Driver {
  public function onInit($options);
  public function onSend(Envelope $envelope);
}
