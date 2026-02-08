<?php

/**
 * Email\Native
 *
 * Email\Native PHP mail() driver.
 *
 * @package core
 * @author Stefano Azzolini <lastguest@gmail.com>
 * @copyright Coesion - 2026
 */

namespace Email;

class Native implements Driver {
  
  public function onInit($options){}

  public function onSend(Envelope $envelope){
    $results 		= [];
    $recipients 	= $envelope->to();
    $envelope->to(false);
    foreach ($recipients as $to) {
      $results[$to] = mail($to,$envelope->subject(),$envelope->body(),$envelope->head());
    }
    return $results;
  }

}

