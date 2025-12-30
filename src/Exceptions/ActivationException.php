<?php

namespace Abstract\Exceptions;

use Exception;
use Throwable;

class ActivationException extends Exception {

  public function __construct(
    $subcode = 0,
    array $phrases = [],
    Throwable $previous = null
  ) {

    $code = 409;
    $message = '';
    switch ($subcode) {
      case 1:
        $message = 'Invalid activator';
      case 2:
        $message = 'Invalid node activation'
          . (!empty($phrases) ? ': "' . implode('", "', $phrases) . '" not supported' : '');
      default:
        $message = 'Unknown activation error';
    }

    // Call the parent constructor
    parent::__construct($message, $code, $previous);
  }

  // You can add custom methods or override existing methods as needed
}
