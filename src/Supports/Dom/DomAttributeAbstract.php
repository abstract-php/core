<?php

namespace Abstract\Supports\Dom;

use Abstract\Abstraction;

class DomAttributeAbstract extends DomAbstract {

  /** @var ?string $indicator is prefix to the name of the abstraction */
  public static ?string $indicator = 'dom';

  /** 
   * @var string 
   * A string value representing the indicator for text.
   */
  public static string $textTagName = 'text';

  /** 
   * @var string 
   * A string value representing the indicator for comment.
   */
  public static string $commentTagName = 'comment';

  /** 
   * @var string 
   * A string value representing the indicator for attributes.
   */
  public static string $attributesTagName = 'attributes';

  /**
   * @var array $splitValuesAttributeNames
   * 
   * This static property holds an array of names that are space-separated values.
   * It is used to manage and identify attributes that can contain multiple values separated by spaces.
   */
  public static array $splitValuesAttributeNames = [
    'class'
  ];

  /**
   * @var \DOMDocument
   * An instance of the DOMDocument class 
   * representing the entire HTML or XML document.
   */
  public \DOMDocument $document;

  /**
   * Constructor for the DomAttributeAbstract class.
   *
   * @param \DOMDocument $document The DOMDocument instance, 
   * defaults to a new instance with version '1.0' and encoding 'UTF-8'.
   * @param Abstraction ...$abstractions Variable number of Abstraction instances.
   */
  public function __construct(
    \DOMDocument $document = new \DOMDocument('1.0', 'UTF-8'),
    Abstraction ...$abstractions
  ) {
    $this->document = $document;
    // Call the parent constructor with the given abstractions.
    parent::__construct(...$abstractions);
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
        $splitExtenstion = !in_array($name, static::$splitValuesAttributeNames);
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

  public function parse(Abstraction $abstraction): \DOMAttr|false {
    $name = $abstraction->getName();
    if (!$name) {
      return false;
    }
    $extraction = self::extract($name, current((array)$abstraction->getValue()));
    $key = current($extraction);
    $value = end($extraction);
    $attribute = $this->document->createAttribute($key);
    if (is_string($value)) {
      $attribute->value = $value;
    }
    return $attribute;
  }
}
