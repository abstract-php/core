<?php

namespace Abstract\Supports\Markup;

use Abstract\Abstraction;

class MarkupAttributeListAbstract extends MarkupAbstract {

  /** @var string $name represents constant name of the derived abstraction */
  public static ?string $name = 'attributes';

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
    return join(
      ' ',
      array_reduce(
        $abstraction->list(),
        function ($_attributes, $_abstraction) {
          $attribute = MarkupAttributeAbstract::parse($_abstraction);
          if ($attribute) {
            $_attributes[] = $attribute;
          }
          return $_attributes;
        },
        []
      )
    );
  }

  /**
   * Extracts a string representation from the given abstraction.
   *
   * @param Abstraction $abstraction The abstraction or markup instance to extract the string from.
   * @return string The extracted string representation.
   */
  public static function extract(Abstraction $abstraction): string {
    $abstractions = $abstraction->get(MarkupAbstract::getIndicatedOf(self::$name));
    if (!is_null($abstractions)) {
      $attributes = is_array($abstractions) ? end($abstractions) : $abstractions;
      return static::parse($attributes);
    } else {
      return '';
    }
  }

  /**
   * Converts the attributes to a string representation.
   *
   * @return string The string representation of the attributes.
   */
  public function toAttributes(): string {
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
    return $this->toAttributes();
  }
}
