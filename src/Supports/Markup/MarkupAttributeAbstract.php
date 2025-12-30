<?php

namespace Abstract\Supports\Markup;

use Abstract\Abstraction;

use Abstract\Supports\Dom\DomAttributeAbstract;

/**
 * Class Attribute
 *
 * This class represents an attribute component in the Markup support system.
 * It extends the base Abstraction class.
 *
 * @package Supports\Markup\Components
 */
class MarkupAttributeAbstract extends MarkupAbstract {

  /**
   * @var array $splitValuesNames
   * 
   * This static property holds an array of names that are space-separated values.
   * It is used to manage and identify attributes that can contain multiple values separated by spaces.
   */
  public static array $splitValuesNames = [
    'class'
  ];

  /**
   * Sets the argument with the given value.
   *
   * @param mixed $value The value to set for the argument.
   * @return static Returns the current instance for method chaining.
   */
  public function withArgument(mixed $value): static {
    if (in_array($this->getName(), static::$splitValuesNames)) {
      $this->attach(
        true,
        ...array_map(
          fn($value) => (new Abstraction)->withArgument($value),
          explode(' ', $value)
        )
      );
      return $this;
    } else {
      return parent::withArgument($value);
    }
  }

  /**
   * Parses the given abstraction into a string representation.
   *
   * @param Abstraction $abstraction The abstraction to be parsed.
   * @param bool $prettyPrint Optional. Whether to pretty print the output. Default is false.
   * @param int $depth Optional. The depth level for nested structures. Default is 0.
   * @return string The parsed string representation of the abstraction.
   */
  public static function parse(
    Abstraction $abstraction,
    bool $prettyPrint = false,
    int $depth = 0
  ): string {
    $name = $abstraction->getName();
    if ($name) {
      $extraction = self::extract(
        $name,
        current((array)$abstraction->getValue())
      );
      $key = current($extraction);
      $value = end($extraction);
      if (is_string($value)) {
        return $key . '="' . addslashes($value) . '"';
      } else {
        return $key;
      }
    } else {
      return null;
    }
  }

  public static function extract(string $name, mixed $value): array {
    if (is_array($value) || is_object($value)) {
      // If the value is an array or an object
      $values = is_object($value) ? (array)$value : $value;
      if (array_is_list($values)) {
        // If array is list, consider if all values should be converted to JSON.
        // Return the name of the abstraction with the appropriate suffix.
        // and the values in the appropriate format.
        $asJson = array_reduce(
          $values,
          // Check if all values should be converted to JSON
          // when some of the values are not strings.
          fn($asJson, $_value) => $asJson && !is_string($_value),
          true
        );
        // Check if the values should use split by extension 
        // when name is not in the space separated values names.
        $splitExtenstion = !in_array(
          $name, 
          DomAttributeAbstract::$splitValuesAttributeNames
        );
        return [
          $name
            // Return the name of the abstraction with the appropriate suffix.
            . (
              $asJson || $splitExtenstion ?
              ('.' . Abstraction::getIndicatedOf($asJson ? 'json' : 'split')) : ''
            ),
          // Return the values in the appropriate format.
          $asJson ? json_encode($values) : join(',', $values)
        ];
      } else {
        // If array is associative
        if (count($values) === 1) {
          // If there is only one value, extract the attributes from the value.
          return static::extract(
            $name . '.' . key($values),
            value(current($values))
          );
        } else {
          // If there are multiple values, extract the attributes as JSON.
          return [
            $name . '.' . Abstraction::getIndicatedOf('json'),
            json_encode($values)
          ];
        }
      }
    } else {
      // If the value is a string or a scalar value,
      // return the name and the value.
      return [$name, $value];
    }
  }

  /**
   * Converts the current instance to an attribute string.
   *
   * @return string The attribute string representation of the current instance.
   */
  public function toAttribute(): string {
    return self::parse($this);
  }

  /**
   * Convert the object to its string representation.
   *
   * This method is called when the object is treated as a string.
   *
   * @return string The string representation of the object.
   */
  public function __toString(): string {
    return $this->toAttribute();
  }
}
