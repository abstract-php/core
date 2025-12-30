<?php

namespace Abstract\Exceptions;

use Exception;
use Throwable;

class ElementException extends Exception {

  public function __construct($subcode = 0, array $phrases = [], Throwable $previous = null) {

    $code = 409;
    $message = '';
    switch ($subcode) {
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