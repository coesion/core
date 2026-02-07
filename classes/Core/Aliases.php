<?php

namespace Core;

class Aliases {
  public static function register(): void {
    $map = [
      'Core' => '\\Core',
      'Cache' => '\\Cache',
      'Check' => '\\Check',
      'CLI' => '\\CLI',
      'CSV' => '\\CSV',
      'Deferred' => '\\Deferred',
      'Dictionary' => '\\Dictionary',
      'Email' => '\\Email',
      'Errors' => '\\Errors',
      'Event' => '\\Event',
      'File' => '\\File',
      'Filter' => '\\Filter',
      'Hash' => '\\Hash',
      'HTTP' => '\\HTTP',
      'Job' => '\\Job',
      'Loader' => '\\Loader',
      'Map' => '\\Map',
      'Message' => '\\Message',
      'Model' => '\\Model',
      'Module' => '\\Module',
      'Negotiation' => '\\Negotiation',
      'Options' => '\\Options',
      'Password' => '\\Password',
      'Persistence' => '\\Persistence',
      'Redirect' => '\\Redirect',
      'Relation' => '\\Relation',
      'Request' => '\\Request',
      'Response' => '\\Response',
      'Route' => '\\Route',
      'Service' => '\\Service',
      'Session' => '\\Session',
      'Shell' => '\\Shell',
      'SQL' => '\\SQL',
      'Structure' => '\\Structure',
      'Text' => '\\Text',
      'Token' => '\\Token',
      'URL' => '\\URL',
      'View' => '\\View',
      'Work' => '\\Work',
      'ZIP' => '\\ZIP',
    ];

    spl_autoload_register(callback: function($class) use ($map) {
      if (strpos($class, 'Core\\') !== 0) return false;
      $alias = substr($class, 5);
      if (!isset($map[$alias])) return false;
      $original = $map[$alias];
      if (class_exists($original)) {
        if (!class_exists($class, false)) {
          class_alias($original, $class);
        }
        return true;
      }
      return false;
    }, throw: true, prepend: true);
  }
}
