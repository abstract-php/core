<?php

namespace Abstract\Exceptions;

use Exception;
use Throwable;

class DocumentException extends Exception {

  public function __construct($subcode = 0, array $phrases = [], Throwable $previous = null) {

    $code = 409;
    $message = '';
    switch ($subcode) {
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
