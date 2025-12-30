<?php

namespace Abstract\Supports\Markup;

use Abstract\Abstraction;

class MarkupCommentAbstract extends MarkupAbstract {

  /** @var string $name represents constant name of the derived abstraction */
  public static ?string $name = 'comment';

  /**
   * Constructor for the MarkupCommentAbstract class.
   *
   * @param string $value The text value to be used by the MarkupCommentAbstract class.
   */
  public function __construct(string $value = '') {
    // Call the parent constructor with the given abstractions.
    parent::__construct();
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
    $space = $prettyPrint ? ' ' : '';
    // Generate the markup comment
    $head = $tab . '<!--' . $space;
    $body = (string)$abstraction->getArgument();
    $foot = $space . '-->';
    return $head . $body . $foot;
  }

  /**
   * Converts the current object to a comment string.
   *
   * @param bool $prettyPrint Whether to format the comment with indentation and line breaks for readability.
   * @return string The comment string representation of the object.
   */
  public function toComment(bool $prettyPrint = false): string {
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
    return $this->toComment(true);
  }
}
