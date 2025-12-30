<?php

namespace Abstract\Handlers\Exceptions;

use Exception;
use Throwable;

class ElementException extends Exception {
  // Additional properties or methods can be added here

  public $test = 500;

  public function __construct($code = 0, array $phrases = [], Throwable $previous = null) {
    // Additional properties or methods can be added here

    $message = '';
    switch ($code) {
      case 1:
        $message = 'The parameter "properties" must not be list array';
      default:
        $message = 'Unknown element error';
    }

    // Call the parent constructor
    parent::__construct($message, $code, $previous);
  }

  // You can add custom methods or override existing methods as needed
}

class DOMException extends Exception {
  // Additional properties or methods can be added here

  public $test = 800;

  public function __construct($code = 0, array $phrases = [], Throwable $previous = null) {

    $message = '';
    switch ($code) {
      case 1:
        $message = 'Unsupported XML data';
      case 2:
        $message = '"' . $phrases[0] . '" is not support';
      default:
        $message = 'Unknown DOM error';
    }

    // Call the parent constructor
    parent::__construct($message, $code, $previous);
  }

  // You can add custom methods or override existing methods as needed
}
