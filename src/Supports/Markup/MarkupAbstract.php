<?php

namespace Abstract\Supports\Markup;

use Abstract\Abstraction;

class MarkupAbstract extends Abstraction {

  /** @var ?string $indicator is prefix to the name of the abstraction */
  public static ?string $indicator = 'markup';

  /**
   * @var array $selfClosingTags
   * 
   * An array of HTML self-closing tags.
   * These tags do not require a closing tag and are used in the markup generation process.
   */
  public static array $selfClosingTags = [
    'area',
    'base',
    'br',
    'col',
    'embed',
    'hr',
    'img',
    'input',
    'link',
    'meta',
    'param',
    'source',
    'track',
    'wbr'
  ];

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

    // Get the name of the abstraction
    $name = $abstraction->getName();

    // Switch based on the name of the abstraction
    switch ($name) {
      case MarkupAbstract::getIndicator(true) ?? '':
        var_dump(5555);
        // If the name is indicated, return the markup for the child abstractions
        return join(
          "\n",
          array_map(
            fn($abstraction) => self::parse($abstraction, $prettyPrint, $depth),
            $abstraction->list()
          )
        );
      case MarkupTextAbstract::getIndicatedName(true) ?? '':
        // If the name is a comment, return the markup for the comment abstraction
        return MarkupTextAbstract::parse($abstraction, $prettyPrint, $depth);
      case MarkupCommentAbstract::getIndicatedName(true) ?? '':
        // If the name is a comment, return the markup for the comment abstraction
        return MarkupCommentAbstract::parse($abstraction, $prettyPrint, $depth);
      default:

        // Clone the abstraction
        $cloned = $abstraction->clone();

        // Get the name of the cloned abstraction
        $name = $cloned->getName();

        // Check if the cloned abstraction is valuable
        $hasArgument = $cloned->hasArgument();

        // Check if the cloned abstraction is a list
        $isList = $name && !$hasArgument && !$abstraction->isAssociative();
        // Unset the name of the cloned abstraction if it is a list
        // because the name will be set in the child abstractions
        if ($isList) {
          $cloned->unsetName();
        }

        // Get the attributes of the cloned abstraction
        $attributes = MarkupAttributeListAbstract::extract($cloned);
        // Remove the attributes from the cloned abstraction to prevent duplication
        $cloned->removeByName(MarkupAttributeListAbstract::getIndicatedName(true) ?? '');

        // Generate the result based on whether the cloned abstraction is valuable or a list
        $result = $hasArgument
          // If the cloned abstraction is valuable, return the value as a string
          ? (string)$cloned->getArgument()
          // If the cloned abstraction is a list, generate the markup for the child abstractions
          : array_reduce(
            // Recursively call the markup method to generate the markup for the child abstractions
            $cloned->list(),
            function (
              array $_markups,
              Abstraction $_abstraction
            ) use ($isList, $cloned, $name, $prettyPrint, $depth) {
              // Clone the child abstraction if it is a list
              $childAbstraction = $_abstraction;
              // Set the name of the child abstraction if it is a list
              if ($isList) {
                $childAbstraction = $_abstraction->clone();
                $childAbstraction->setName($name);
              }
              // Calculate the new depth based on the cloned abstraction's associativity and name
              $newDepth = $depth + ($cloned->isAssociative() && $name ? 1 : 0);
              // Recursively call the markup method to generate the markup for the child abstraction
              return [...$_markups, self::parse($childAbstraction, $prettyPrint, $newDepth)];
            },
            []
          );

        // var_dump($result);

        // Generate the final markup using the cloned abstraction, result, attributes, format, and depth
        $newline = $prettyPrint ? "\n" : '';
        $indents = array_fill(0, $depth, "\t");
        $tab = $prettyPrint ? join('', $indents) : '';
        $indentsInside = array_fill(0, $depth + 1, "\t");
        $tabInside = $prettyPrint ? join('', $indentsInside) : '';
        $body = is_string($result)
          ? (empty($result) ? '' : ($name ? $tabInside : $tab)) . $result
          : (
            is_array($result) && array_is_list($result)
            ? join($newline, $result)
            : ''
          );
        $line = (empty($body) ? '' : $newline);
        if (in_array($name, static::$selfClosingTags) && empty(trim($body))) {
          return $name
            ? $tab . '<' . $name . '' . (
              !empty($attributes) ? ' ' . $attributes : ''
            ) . '/>' . $line
            : '';
        } else {
          $head = $name
            ? $tab . '<' . $name . '' . (
              !empty($attributes) ? ' ' . $attributes : ''
            ) . '>' . $line
            : '';
          $foot = $name ? $line . (empty($body) ? '' : $tab) . '</' . $name . '>' : '';
          return $head . $body . $foot;
        }
    }
  }

  /**
   * Converts the current object to a markup string.
   *
   * @param bool $prettyPrint Whether to format the output with indentation and line breaks.
   * @param bool $asXML Whether to output the markup as XML.
   * @return string The generated markup string.
   */
  public function toMarkup(bool $prettyPrint = false, bool $asXML = false): string {
    return self::parse($this, $prettyPrint, $asXML);
  }

  /**
   * Converts the markup to an HTML string.
   *
   * @param bool $prettyPrint Whether to format the HTML output for readability.
   * @return string The HTML representation of the markup.
   */
  public function toHTML(bool $prettyPrint = false): string {
    return $this->toMarkup($prettyPrint);
  }

  /**
   * Converts the current object to an XML string.
   *
   * @param bool $prettyPrint Optional. Whether to format the XML output with indentation and line breaks. Default is false.
   * @return string The XML representation of the current object.
   */
  public function toXML(bool $prettyPrint = false): string {
    return $this->toMarkup($prettyPrint, true);
  }

  /**
   * Convert the Markup object to its string representation.
   *
   * This method is called when the object is treated as a string.
   *
   * @return string The string representation of the Markup object.
   */
  public function __toString(): string {
    return $this->toHTML(true);
  }
}
