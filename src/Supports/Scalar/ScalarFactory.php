<?php

namespace Abstract\Supports\Scalar;

use Abstract\Transformer\Factory;
use Abstract\Reference;

/**
 * Class Factory
 * 
 * This class represents a factory for creating abstractions with different object keys.
 *
 * @package Abstract\Transformer
 */
class ScalarFactory extends Factory {

  /**
   * @var array
   * An array of transformation aliases.
   */
  public array $aliases = [
    'int' => 'integer',
    'bool' => 'boolean'
  ];

  /**
   * Create a abstraction with null value.
   * 
   * @param mixed $value Value of the reference.
   * @return Reference The Reference with key using function name and value.
   */
  public static function null(mixed $value): Reference {
    return new Reference('null', $value, true, true);
  }

  /**
   * Create a abstraction with integer value.
   * 
   * @param mixed $value Value of the reference.
   * @return Reference The Reference with key using function name and value.
   */
  public static function integer(mixed $value): Reference {
    return new Reference('integer', $value, true, true);
  }

  /**
   * Create a abstraction with double value.
   * 
   * @param mixed $value Value of the reference.
   * @return Reference The Reference with key using function name and value.
   */
  public static function double(mixed $value): Reference {
    return new Reference('double', $value, true, true);
  }

  /**
   * Create a abstraction with float value.
   * 
   * @param mixed $value Value of the reference.
   * @return Reference The Reference with key using function name and value.
   */
  public static function float(mixed $value): Reference {
    return new Reference('float', $value, true, true);
  }

  /**
   * Create a Reference for abstraction with string.
   * 
   * @param mixed $value Value of the reference.
   * @return Reference The Reference with key using function name and value.
   */
  public static function string(mixed $value): Reference {
    return new Reference('string', $value, true, true);
  }

  /**
   * Create a Reference for abstraction with boolean.
   * 
   * @param mixed $value Value of the reference.
   * @return Reference The Reference with key using function name and value.
   */
  public static function boolean(mixed $value): Reference {
    return new Reference('boolean', $value, true, true);
  }

  /**
   * Create a Reference for abstraction with array.
   * 
   * @param mixed $value Value of the reference.
   * @return Reference The Reference with key using function name and value.
   */
  public static function array(mixed $value): Reference {
    return new Reference('array', $value, true, true);
  }

  /**
   * Create a Reference for abstraction with split.
   * 
   * @param mixed $value Value of the reference.
   * @return Reference The Reference with key using function name and value.
   */
  public static function split(mixed $value): Reference {
    return new Reference('split', $value, true, true);
  }
}
