<?php

namespace Abstract\Supports\Dom;

use Abstract\Abstraction;

class DomAttributeListAbstract extends DomAbstract {

  /** @var string $name represents constant name of the derived abstraction */
  public static ?string $name = 'attributes';

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
   * Constructor for the DomAttributeListAbstract class.
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

  public function parse(Abstraction $abstraction): array {
    $children = $abstraction->list();
    return array_reduce(
      $children,
      function ($attributes, $child) {
        $attribute = (new DomAttributeAbstract($this->document))->parse($child);
        if ($attribute !== false) {
          $attributes[] = $attribute;
        }
        return $attributes;
      },
      []
    );
  }

}
