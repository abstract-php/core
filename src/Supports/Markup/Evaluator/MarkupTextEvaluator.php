<?php

namespace Abstract\Supports\Markup;

use Abstract\Abstraction;

class MarkupTextEvaluator extends MarkupAbstract {

  /** @var string $name represents constant name of the derived abstraction */
  public static ?string $name = 'text';

  // public function getName(bool $asString = false): ?string {
  //   return null;
  // }

  /**
   * Constructor for the MarkupTextEvaluator class.
   *
   * @param string $value The text value to be used by the MarkupTextEvaluator class.
   */
  public function __construct(string $value = '') {
    // Call the parent constructor with the given abstractions.
    parent::__construct(...[]);
    // Set the argument value
    $this->setReferenceValue($value);
  }

  /**
   * Parses the given abstraction into a string representation.
   *
   * @param Abstraction $abstraction The abstraction to be parsed.
   * @param bool $prettyPrint Optional. Whether to pretty print the output. Default is false.
   * @param int $depth Optional. The depth level for pretty printing. Default is 0.
   * @return string The parsed string representation of the abstraction.
   */
  public static function parse(
    Abstraction $abstraction,
    bool $prettyPrint = false,
    int $depth = 0
  ): string {
    // Handle pretty printing
    $indents = array_fill(0, $depth, "\t");
    $tab = $prettyPrint ? join('', $indents) : '';
    // Generate the markup text
    $cloned = $abstraction->clone(true);
    $cloned->unsetName();
    $result = (string)$cloned;
    return empty($result) ? '' : $tab . $result;
    // return '';
  }

  /**
   * Converts the current object to a text string.
   *
   * @param bool $prettyPrint Whether to format the text with indentation and line breaks for readability.
   * @return string The text string representation of the object.
   */
  public function toText(bool $prettyPrint = false): string {
    return self::parse($this, $prettyPrint);
  }

  /**
   * Convert the object to its string representation.
   *
   * This method is called when the object is treated as a string.
   *
   * @return string The string representation of the object.
   */
  public function __toString(): string {
    return $this->toText(true);
  }
}
