<?php

namespace Abstract\Supports\Dom;

use Abstract\Abstraction;

class DomAbstract extends Abstraction {

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

  public static string $version = '1.0';
  public static string $encoding = 'UTF-8';

  /**
   * @var \DOMDocument $document An instance of the DOMDocument class representing the entire HTML or XML document.
   */
  public \DOMDocument $document;

  /**
   * Constructor for the Dom class.
   *
   * @param Abstraction ...$abstractions One or more Abstraction instances to be used by the Dom class.
   */
  public function __construct(Abstraction ...$abstractions) {
    // Initialize the DOMDocument instance.
    $this->document = new \DOMDocument(static::$version, static::$encoding);
    // Call the parent constructor with the given abstractions.
    parent::__construct(...$abstractions);
  }

  public function parse(
    Abstraction $abstraction
  ): \DOMDocument|\DOMNode|\DOMCharacterData|array|null|false {

    // Parse the abstraction into the DOMDocument.
    $this->build($abstraction, $this->document);

    return $this->document;
  }

  /**
   * Processes a DOM node with the given abstraction.
   *
   * @param Abstraction $abstraction The abstraction to be used for processing.
   * @param \DOMDocument|\DOMNode $dom The DOM node to be processed.
   * @return void
   */
  public function build(
    Abstraction $abstraction,
    \DOMDocument|\DOMNode $dom
  ): void {
    // Get the name of the abstraction.
    $name = $abstraction->getName();
    // Check if the abstraction has an argument.
    if ($abstraction->hasArgument()) {
      // Create a new DOM element or text node based on the name and argument.
      $element = $name
        ? $this->document->createElement(
          $name,
          $abstraction->getArgument()
        )
        : $this->document->createTextNode($abstraction->getArgument());
      // Append the element to the DOM node.
      $dom->appendChild($element);
    } else {
      switch (true) {
        case DomAbstract::getIndicatedOf(self::$commentTagName):
          (new DomAttributeListAbstract($this->document))->parse($abstraction, $dom);
          break;
        case $abstraction instanceof DomAttributeListAbstract:
          $attributes = (
            new DomAttributeListAbstract($this->document)
          )->parse($abstraction);
          foreach ($attributes as $attribute) {
            $dom->appendChild($attribute);
          }
          break;
        default:
          // Create a new DOM element based on the name.
          $element = $name
            ? $this->document->createElement($name)
            : $dom;
          // var_dump($element);
          foreach ($abstraction->list() as $child) {
            // Process the child abstraction recursively.
            $this->build($child, $element);
          }
          if ($name) {
            // Append the element to the DOM node.
            $dom->appendChild($element);
          }
      }
    }
  }

  public function setFormat(bool $value = true): void {
    // Preserve whitespace and format if required.
    $this->document->preserveWhiteSpace = $value;
    $this->document->formatOutput = $value;
  }

  /**
   * Converts the DOMNode to an XML string.
   *
   * @param \DOMNode|null $node The DOMNode to convert. If null, the current node will be used.
   * @param int $options Optional. Additional options for the conversion.
   * 
   * @return string The XML string representation of the DOMNode.
   */
  public function toXML(
    ?\DOMNode $node = null,
    int $options = 0
  ): string {
    // Set the format for the output.
    $this->setFormat();
    $document = $this->parse($this);
    // Return the XML string representation of the DOMNode.
    return $document->saveXML($node, $options);
  }

  /**
   * Converts a DOMNode to its HTML string representation.
   *
   * @param \DOMNode|null $node The DOMNode to convert. If null, the current node will be used.
   * @return string|false The HTML string representation of the node, or false on failure.
   */
  public function toHTML(
    ?\DOMNode $node = null
  ): string {
    // Set the format for the output.
    $this->setFormat();
    $document = $this->parse($this);
    // Return the HTML string representation of the DOMNode.
    return $document->saveHTML($node);
  }

  /**
   * Convert the object to its string representation.
   *
   * @return string The string representation of the object.
   */
  public function __toString(): string {
    return $this->toHTML();
  }
}
