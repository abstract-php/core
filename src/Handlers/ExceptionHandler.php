<?php

namespace Abstract\Handlers;

use Abstract\Annotations\Message;

use Throwable;
use Exception;

/**
 * Exceptions handler
 */
class ExceptionHandler {

  /**
   * Throw exception
   *
   * @param  string  $message
   * @param  ?int  $code
   * @return void
   *
   * @throws Throwable
   */
  public static function throw(string $message, ?int $code): void {

    if (empty($message)) {
      $message = Message::$errors[0];
    }

    if (class_exists('Abstract\\Translation')) {
      
    } else {
      if (empty($code)) {
        throw new Exception($message);
      } else {
        throw new Exception($message, $code);
      }
    }
  }
}
