<?php

/**
 * Error
 *
 * Handle system and application errors.
 *
 * @package core
 * @deprecated Error is private in PHP7, use Errors instead
 * @author Stefano Azzolini <lastguest@gmail.com>
 * @copyright Coesion - 2026
 */

if (!class_exists('Errors', false)) {
  include_once __DIR__ . '/Errors.php';
}
if (!class_exists('Error', false)) {
  class_alias('Errors', 'Error', true);
}
