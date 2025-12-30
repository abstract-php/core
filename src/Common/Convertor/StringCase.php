<?php

namespace Abstract\Common\Convertor;

class StringCase {
  
  public static function toCamelCase(string $string): string {
    return lcfirst(self::toPascalCase($string));
  }

  public static function toPascalCase(string $string): string {
    return str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $string)));
  }

  public static function toSnakeCase(
    string $string, 
    bool $reserveUpperCase = false
  ): string {
    $snakeCase = preg_replace('/(?<!^)[A-Z]/', '_$0', $string);
    return $reserveUpperCase ? $snakeCase : strtolower($snakeCase);
  }

  public static function toKebabCase(
    string $string, 
    bool $reserveUpperCase = false
  ): string {
    return str_replace('_', '-', self::toSnakeCase($string, $reserveUpperCase));
  }

  public static function toSpaceCase(
    string $string, 
    bool $reserveUpperCase = true
  ): string {
    return str_replace('_', ' ', self::toSnakeCase($string, $reserveUpperCase));
  }

  public static function toDotCase(
    string $string, 
    bool $reserveUpperCase = true
  ): string {
    return str_replace('_', '.', self::toSnakeCase($string, $reserveUpperCase));
  }

  public static function toSlashCase(
    string $string, 
    bool $reserveUpperCase = true
  ): string {
    return str_replace('_', '/', self::toSnakeCase($string, $reserveUpperCase));
  }

  public static function toBackslashCase(
    string $string, 
    bool $reserveUpperCase = true
  ): string {
    return str_replace('_', '\\', self::toSnakeCase($string, $reserveUpperCase));
  }

  public static function toColonCase(
    string $string, 
    bool $reserveUpperCase = true
  ): string {
    return str_replace('_', ':', self::toSnakeCase($string, $reserveUpperCase));
  }

  public static function toCommaCase(
    string $string, 
    bool $reserveUpperCase = true
  ): string {
    return str_replace('_', ',', self::toSnakeCase($string, $reserveUpperCase));
  }

  public static function toSemiColonCase(
    string $string, 
    bool $reserveUpperCase = true
  ): string {
    return str_replace('_', ';', self::toSnakeCase($string, $reserveUpperCase));
  }
  
}