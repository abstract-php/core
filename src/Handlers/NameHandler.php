<?php

namespace Abstract\Handlers;

/**
 * Naming normalizer
 */
class NameHandler {

  /**
   * @param string $key
   * @return string
   */
  public static function class(string $key): string {
    $parts = preg_split('/[\s-]+/', $key);
    $result = '';
    foreach ($parts as $part) {
      $result .= ucfirst($part);
    }
    return $result;
  }

  /**
   * @param string $key
   * @return string
   */
  public static function key($text, $delimiter = '_'): string {
    $words = preg_split('/(?=[A-Z])/', $text);
    $words = array_map('strtolower', $words);
    $words = array_filter($words);
    return implode($delimiter, $words);
  }
}
