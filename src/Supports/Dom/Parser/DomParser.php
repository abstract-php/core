<?php

namespace Abstract\Supports\Dom\Parser;

use Abstract\Supports\Dom\DomAbstract;

use Abstract\Abstraction;
use Abstract\Supports\Dom\DomAttributeAbstract;
use Abstract\Supports\Dom\DomAttributeListAbstract;
use Abstract\Transformer\Parser;

use Abstract\Common\Convertor\StringCase;

/**
 * Class DomParser
 *
 * This class extends the Parser class and is part of the Dom Transformer component
 * within the Supports module of the application.
 *
 * @package Supports\Dom\Transformer
 */
class DomParser extends Parser {

  public static array $tags = [
    'text' => 'text'
  ];

  public static function strategicAlias(string $alias): string {
    return 'DOM' . StringCase::toPascalCase($alias);
  }

  /**
   * Parses a DOMDocument and returns a Dom object.
   *
   * @param \DOMDocument $document The source DOMDocument to be parsed.
   * @param bool $associative Optional. Whether to return the result as an associative array. Default is true.
   * @return Abstraction The parsed Abstraction or Dom object.
   */
  public function document(
    \DOMDocument $source,
    bool $associative = true
  ): Abstraction {
    // Encapsulate comment nodes
    self::encapsulateComment($source);
    // Encapsulate text nodes
    self::encapsulateString($source);
    // Parse the document element of the source
    return $this->node($source, $source->documentElement, $associative);
  }

  /**
   * Parses a DOMNode and returns a Abstraction or Dom object.
   *
   * @param \DOMDocument $document The source DOMDocument to be parsed.
   * @param \DOMNode $source The source DOMNode to be parsed.
   * @param bool $associative Optional. Whether to return the result as an associative array. Default is true.
   * @return Abstraction The parsed result as a Abstraction or Dom object.
   */
  public function node(
    \DOMDocument $document,
    \DOMNode $source,
    bool $associative = true
  ): Abstraction {

    // Get the node name as a string
    $name = $source->nodeName;

    switch ($name) {
      case DomAbstract::getIndicatedOf(DomAbstract::$textTagName):
        // If the node name is the text tag name, parse the text node.
        $nodeValue = trim($source->nodeValue);
        return (new DomAbstract)->withArgument($nodeValue);
      default:
        // Otherwise, parse the node as a abstraction.
        $abstraction = isset($this->factory->functions[$name])
          ? $this->factory->functions[$name]()
          : (new DomAbstract)->withName($name);
        // Parse the children of the node.
        $children = $this->children($document, $source->childNodes, $associative);
        // Assign the associative flag to safeAssociative to prevent overwriting.
        $safeAssociative = $associative;
        $attributes = null;
        if ($source->attributes) {
          // Parse the attributes of the node.
          $attributes = $this->attributes($document, $source->attributes);
          // Check if there is an associative flag in the attributes.
          $asAssociative = static::asAssociative($attributes);
          if (!is_null($asAssociative)) {
            $safeAssociative = $asAssociative;
          }
          // Check if there is a list flag in the attributes.
          $asList = static::asList($attributes);
          if (!is_null($asList)) {
            // If the list flag is set, set the safeAssociative flag 
            // to the opposite of the list flag.
            $safeAssociative = !$asList;
          }
        }
        if ($children->getName()) {
          // If the children have a name, attach the children to the abstraction.
          $abstraction->attach($associative && $safeAssociative, $children);
        } else {
          // Otherwise, set the children as the abstraction.
          if ($children->hasArgument()) {
            // If the children have an argument, set the argument of the abstraction.
            $abstraction->setReferenceValue($children->getArgument());
          } else {
            // Otherwise, set the children as the abstraction.
            $abstraction = $children;
            // Set the associative flag of the abstraction.
            $abstraction->setAssociative($associative && $safeAssociative);
            // Set the name of the abstraction.
            $abstraction->setName($name);
          }
        }

        // Prepend the attributes to the abstraction if they exist.
        if (!is_null($attributes) && !empty($attributes->list())) {
          $abstraction->prepend($attributes);
        }

        return $abstraction;
    }
  }

  /**
   * Parses the children of a DOM node list.
   *
   * @param \DOMDocument $document The source DOMDocument to be parsed.
   * @param \DOMNodeList $source The source DOM node list to parse.
   * @param bool $associative Optional. Whether to return the result as an associative array. Default is false.
   * @return Abstraction The parsed Abstraction or Dom object.
   */
  public function children(
    \DOMDocument $document,
    \DOMNodeList $source,
    bool $associative = false
  ): Abstraction {
    // Reduce the children of the node list to a single abstraction.
    $children = array_reduce(
      // Convert the node list to an array
      iterator_to_array($source),
      fn($_children, \DOMNode $_childNode) => [
        ...$_children,
        ...(
          $_childNode->nodeType !== 3
          // If the node type is not a text node, attach the child node.
          ? [$this->node($document, $_childNode, $associative)]
          // Otherwise, ignore attaching the child node.
          : []
        )
      ],
      []
    );
    if (count($children) === 1) {
      // If there is only one child, return the child.
      return current($children);
    } elseif ($associative) {
      // If the associative flag is set, return a new Dom object with
      return new DomAbstract(...$children);
    } else {
      // Otherwise, return a new Dom container object with the children.
      return DomAbstract::container(...$children);
    }
  }

  /**
   * Extracts attributes from a DOMNamedNodeMap and returns them as an Attributes object.
   *
   * @param \DOMNamedNodeMap $source The source DOMNamedNodeMap containing the attributes.
   * @return Abstraction The extracted attributes as a Abstraction or Dom object.
   */
  public function attributes(
    \DOMDocument $document,
    \DOMNamedNodeMap $source
  ): Abstraction {
    return (
      new DomAttributeListAbstract(
        $document,
        ...array_reduce(
          iterator_to_array($source),
          function ($_attributes, $_attribute) use ($document) {
            // Decrypt the tag name
            $name = $_attribute->name;
            $parts = explode('.', $name);
            $name = current($parts);
            $argument = $_attribute->value === '' ? true : $_attribute->value;
            $abstraction = isset($this->factory->functions[$name])
              ? $this->factory->functions[$name]($argument)
              : (new DomAttributeAbstract($document))->withName($name)->withArgument($argument);
            $extensions = [];
            if (count($parts) > 1) {
              array_shift($parts);
              $extensions = $parts;
            }
            $_attributes[] = $this->extractExtendedAttributes($abstraction, $extensions);
            return $_attributes;
          },
          []
        )
      )
    );
  }

  /**
   * Checks if the attributes contain an associative flag.
   *
   * @param Abstraction $attributes The abstraction containing attributes to be converted.
   * @param string $indicatedName Optional. The indicated name of the associative flag.
   * @return bool|null Returns true if the conversion is successful, false if not, or null if the input is invalid.
   */
  protected static function asAssociative(
    Abstraction $attributes,
    ?string $indicatedName = null
  ): ?bool {
    // Use the indicated name of the associative flag
    $indicatedName = $indicatedName ?? DomAbstract::getIndicatedOf('associative');
    // Get the attribute with the indicated name
    $attribute = $attributes->get($indicatedName);
    if (!is_null($attribute)) {
      return $attribute->getArgument() === 'false' ? false : true;
    } else {
      return null;
    }
  }

  /**
   * Checks if the attributes contain a list flag.
   *
   * @param Abstraction $attributes The abstraction containing attributes to be converted.
   * @param string $indicatedName Optional. The indicated name of the list flag.
   * @return bool|null Returns true if the conversion is successful, false if not, or null if the input is invalid.
   */
  protected static function asList(
    Abstraction $attributes,
    ?string $indicatedName = null
  ): ?bool {
    // Use the indicated name of the list flag
    $indicatedName = $indicatedName ?? DomAbstract::getIndicatedOf('list');
    // Get the attribute with the indicated name
    $attribute = $attributes->get($indicatedName);
    if (!is_null($attribute)) {
      return $attribute->getArgument() === 'true' ? true : false;
    } else {
      return null;
    }
  }

  /**
   * Extracts extended attributes from the given extensions array and applies them to the abstraction.
   *
   * @param Abstraction $abstraction The abstraction to which the extended attributes will be applied.
   * @param array $extensions An array of extensions containing the extended attributes.
   * @return Abstraction The abstraction with the extended attributes applied.
   */
  protected function extractExtendedAttributes(
    Abstraction $abstraction,
    array $extensions
  ): Abstraction {
    // If there are extensions, extract the extended attributes.
    if (count($extensions)) {
      // Get the last extension
      $extenstion = end($extensions);
      // Get the children of the abstraction
      $children = $abstraction->list();
      // Create the extended abstraction
      $extendedAbstraction = isset($this->factory->functions[$extenstion])
        // If the extension is a factory function, create the abstraction with the factory function
        ? ($this->factory->functions[$extenstion]())->withChildren(...$children)
        // Otherwise, create the abstraction with the extension name
        : (new Abstraction(...$children))->withName($extenstion);
      // If the abstraction has an argument, set the argument of the extended abstraction
      if ($abstraction->hasArgument()) {
        $extendedAbstraction->setReferenceValue($abstraction->getArgument());
        $extendedAbstraction->setAssociative($abstraction->isAssociative());
        $abstraction->unsetReferenceValue();
      }
      // Attach the extended abstraction to the abstraction
      $abstraction->attach($abstraction->isAssociative(), $extendedAbstraction);
      // Remove the last extension
      array_pop($extensions);
      // Recursively call the extractExtendedAttributes method 
      // to extract the extended attributes
      return $this->extractExtendedAttributes($abstraction, $extensions);
    } else {
      // Return the abstraction if there are no more extensions
      return $abstraction;
    }
  }

  /**
   * Encapsulates a string within a DOMDocument.
   *
   * This method takes a DOMDocument object and performs encapsulation
   * of a string within it. The exact encapsulation logic is defined
   * within the method.
   *
   * @param \DOMDocument $dom The DOMDocument object to encapsulate the string in.
   * @param string $indicatedName Optional. The indicated name of the text tag.
   *
   * @return void
   */
  protected static function encapsulateString(
    \DOMDocument $dom,
    ?string $indicatedName = null
  ): void {
    // Use the indicated name of the text tag
    $indicatedName = $indicatedName ?? DomAbstract::getIndicatedOf(DomAbstract::$textTagName);
    // Create a new DOMXPath instance for querying
    $xpath = new \DOMXPath($dom);
    // Use X-Path to query all text node
    $texts = $xpath->query("//text()");
    if ($texts !== false) {
      array_map(
        function ($text) use ($dom, $indicatedName) {
          $textContent = $text->textContent;
          if (!empty(trim($textContent))) {
            // Create a new DOM element for the text node
            // with the indicated name for texts.
            $element = $dom->createElement($indicatedName, $textContent);
            // Replace the text node with the new element
            $text->parentNode->replaceChild($element, $text);
          }
        },
        // Convert the texts to an array
        iterator_to_array($texts)
      );
    }
  }

  /**
   * Encapsulates comments within the provided DOMDocument.
   *
   * This method processes the given DOMDocument and ensures that all comments
   * are properly encapsulated.
   *
   * @param \DOMDocument $dom The DOMDocument instance to process.
   * @param string $indicatedName Optional. The indicated name of the comment tag.
   *
   * @return void
   */
  protected static function encapsulateComment(
    \DOMDocument $dom,
    ?string $indicatedName = null
  ): void {
    // Use the indicated name of the comment tag
    $indicatedName = $indicatedName
      ?? DomAbstract::getIndicatedOf(DomAbstract::$commentTagName);
    // Create a new DOMXPath instance for querying
    $xpath = new \DOMXPath($dom);
    // Use X-Path to query all comment node
    $comments = $xpath->query("//comment()");
    if ($comments !== false) {
      array_map(
        function ($comment) use ($dom, $indicatedName) {
          $value = $comment->nodeValue;
          if (!empty(trim($value))) {
            // Create a new DOM element for the comment node
            // with the indicated name for comments.
            $element = $dom->createElement($indicatedName, $value);
            // Replace the comment node with the new element
            $comment->parentNode->replaceChild($element, $comment);
          }
        },
        // Convert the comments to an array
        iterator_to_array($comments)
      );
    }
  }
}
