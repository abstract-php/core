<?php

namespace Abstract\Supports\Markup\Parser;

use Abstract\Abstraction;
use Abstract\Transformer\Factory;
use Abstract\Transformer\Parser;
// use Abstract\Supports\Markup\Abstraction;
// use Abstract\Supports\Markup\MarkupAttributeListAbstract;
// use Abstract\Supports\Markup\MarkupAttributeAbstract;
// use Abstract\Supports\Markup\MarkupCommentAbstract;
// use Abstract\Supports\Markup\MarkupRootAbstract;
// use Abstract\Supports\Markup\MarkupTextAbstract;

use Abstract\Supports\Dom\Parser\DomParser;
use Abstract\Common\Convertor\Unicode;
use Abstract\Reference;

class MarkupParser extends DomParser
{

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
   * Parses a DOMDocument and returns a Dom object.
   *
   * @param \DOMDocument $source The source DOMDocument to be parsed.
   * @param bool $associative Optional. Whether to return the result as an associative array. Default is true.
   * @return Abstraction The parsed Abstraction or Dom object.
   */
  public function domDocument(
    \DOMDocument $source,
    bool $associative = true
  ): Abstraction {
    // Encapsulate comment nodes
    parent::encapsulateComment(
      $source,
      Abstraction::getIndicator(true, true) . 'comment'
    );
    // Encapsulate text nodes
    parent::encapsulateString(
      $source,
      Abstraction::getIndicator(true, true) . 'text'
    );
    // Parse the document element of the source
    return $this->domNode($source, $source->documentElement, $associative);
  }

  /**
   * Parses a DOMNode and returns a Abstraction or Dom object.
   *
   * @param \DOMDocument $document The source DOMDocument to be parsed.
   * @param \DOMNode $source The source DOMNode to be parsed.
   * @param bool $associative Optional. Whether to return the result as an associative array. Default is true.
   * @return Abstraction The parsed result as a Abstraction or Dom object.
   */
  public function domNode(
    \DOMDocument $document,
    \DOMNode $source,
    bool $associative = true
  ): Abstraction {
    // Get the node name as a string
    $name = Unicode::toString($source->nodeName);

    switch ($name) {
      case Abstraction::getIndicator(true, true) . 'root':
        // If the node name is the indicator, parse the children of the node.
        return $this->domChildren($document, $source->childNodes, $associative);
      case Abstraction::getIndicator(true, true) . 'text':
        // If the node name is the text tag name, parse the text node.
        // return (new Abstraction)->withArgument(
        //   Unicode::toString(trim($source->nodeValue))
        // );
        return (
          new Abstraction(
            new Reference(
              'string',
              Unicode::toString(trim($source->nodeValue)),
            )
          )
        )->withName('text');
      case Abstraction::getIndicator(true, true) . 'comment':
        // If the node name is the text tag name, parse the text node.
        return (
          new Abstraction(
            new Reference(
              'string',
              Unicode::toString(trim($source->nodeValue)),
            )
          )
        )->withName('comment');
      default:
        // Otherwise, parse the node as a abstraction.
        $abstraction = isset($this->factory->functions[$name])
          ? $this->factory->functions[$name]()
          : (new Abstraction)->withName($name);
        // Parse the children of the node.
        $children = $this->domChildren($document, $source->childNodes, $associative);
        // Assign the associative flag to safeAssociative to prevent overwriting.
        $safeAssociative = $associative;
        $attributes = null;
        if ($source->attributes) {
          // Parse the attributes of the node.
          $attributes = $this->domAttributes($document, $source->attributes);
          // Check if there is an associative flag in the attributes.
          $asAssociative = static::asAssociative(
            $attributes,
            Abstraction::getIndicatedOf('associative')
          );
          if (!is_null($asAssociative)) {
            $safeAssociative = $asAssociative;
          }
          // Check if there is a list flag in the attributes.
          $asList = static::asList(
            $attributes,
            Abstraction::getIndicatedOf('list')
          );
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
  public function domChildren(
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
          ? [$this->domNode($document, $_childNode, $associative)]
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
      return new Abstraction(...$children);
    } else {
      // Otherwise, return a new Dom container object with the children.
      return Abstraction::container(...$children);
    }
  }

  /**
   * Extracts attributes from a DOMNamedNodeMap and returns them as an Abstraction object.
   *
   * @param \DOMDocument $document The source DOMDocument to be parsed.
   * @param \DOMNamedNodeMap $source The source DOMNamedNodeMap containing the attributes.
   * @return Abstraction The extracted attributes as an Abstraction object.
   */
  public function domAttributes(
    \DOMDocument $document,
    \DOMNamedNodeMap $source
  ): Abstraction {
    return (
      new Abstraction(
        ...array_reduce(
          iterator_to_array($source),
          function ($_attributes, $_attribute) {
            // Decrypt the tag name
            $name = Unicode::toString($_attribute->name);
            $parts = explode('.', $name);
            $name = current($parts);
            $argument = $_attribute->value === '' ? true : $_attribute->value;
            $abstraction = isset($this->factory->functions[$name])
              ? $this->factory->functions[$name]($argument)
              : (new Abstraction)->withName($name)->withArgument($argument);
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
    )->withName('attributes');
  }

  /**
   * Parses the given markup source.
   *
   * @param string $source The markup source to be parsed.
   * @param bool $associative Optional. Whether to return the result as an associative array. Default is true.
   * @return Abstraction The parsed markup object.
   */
  public static function string(
    string $source,
    bool $associative = true,
    ?Factory $factory = null,
    Parser ...$additionalParsers
  ): Abstraction {
    // Check if the source is empty
    if (empty($source)) {
      // Return an empty markup object
      return $associative ? new Abstraction : Abstraction::container();
    } else {

      // Create a new DOMDocument
      $document = new \DOMDocument('1.0', 'UTF-8');

      // Preserve whitespace and format
      $document->preserveWhiteSpace = true;
      $document->formatOutput = true;

      // Use the wrapped source if the source is empty
      $useWrappedSource = empty($source);

      // Options for loading the HTML
      $options =
        // Suppress warning reports
        LIBXML_NOWARNING
        // Suppress error reports
        | LIBXML_NOERROR
        // Remove redundant namespace declarations
        | LIBXML_NSCLEAN
        // Merge CDATA as text nodes
        | LIBXML_NOCDATA
        // Drop the XML declaration when saving a document
        | LIBXML_NOXMLDECL
        // Expand empty tags (e.g. <br/> to <br></br>)
        | LIBXML_NOEMPTYTAG;

      // Load the HTML into the document without the wrapped source
      $result = $document->loadHTML(static::preserve($source), $options);

      // Get the document element
      $documentElement = $document->documentElement;
      // Check if the document element is not an HTML element
      if ($result && ($documentElement && $documentElement->nodeName !== 'html')) {
        // Reload the HTML into the document with the wrapped source
        $result = $document->loadHTML(static::wrap($source), $options);
        $useWrappedSource = true;
      }

      // Create a new markup object
      $markup = $associative ? new Abstraction : Abstraction::container();
      if ($result) {
        // Parse the document using the DOM parser
        $markupParser = new static($factory, ...$additionalParsers);
        $dom = $markupParser->domDocument($document, $associative);
        $children = $dom->list();
        $markup = $dom->isAssociative()
          ? new Abstraction(...$children)
          : Abstraction::container(...$children);
        // Set the argument of the markup object
        if ($dom->hasArgument()) {
          $markup->setReferenceValue($dom->getArgument());
        };
        $name = $dom->getName();
        // Set the name of the markup object
        if ($name && !$useWrappedSource) {
          $markup->setName($dom->getName());
        }
      }

      return $markup;
    }
  }

  /**
   * Parses a markup file and returns a Markup object.
   *
   * @param string $source The path to the source file to be parsed.
   * @param bool $associative Optional. Whether to return the result as an associative array. Default is true.
   * @return Abstraction The parsed markup object.
   */
  public static function file(
    string $source,
    bool $associative = true
  ): Abstraction {
    return static::string(file_get_contents($source), $associative);
  }

  /**
   * Preserves the given source string.
   *
   * This method takes a source string and performs operations to preserve its content.
   *
   * @param string $source The source string to be preserved.
   * @return string The preserved source string.
   */
  private static function preserve(string $source): string
  {
    // Characters to exclude from encoding
    // to reduce the size of the encoded string
    $exclude = [
      ...(str_split(' /-._=')),
      ...(str_split('0123456789')),
      ...(str_split('abcdefghijklmnopqrstuvwxyz')),
      '\'',
      "\"",
      "\\",
      "\t",
      "\n",
      "\r",
      "\f",
      "\v",
      "\0"
    ];
    // Pattern to match tags
    $pattern = '/<(\/?)([\w\p{L}\-\:\.]*)(?:\s+([^>]*))?(\/)?>/u';
    // Replace tag names and details with unicode
    // also encode the tag names
    return mb_encode_numericentity(
      preg_replace_callback(
        $pattern,
        function ($matches) use ($exclude) {
          // Get the tag name
          $tag = $matches[2];
          // Encode the tag name
          $encodedTag = Unicode::fromString($tag, $exclude);
          // Check if the tag is a closing tag
          if (!empty($matches[1])) {
            // Return the encoded closing tag
            return '</' . $encodedTag . '>';
          } else {
            // Get the tag details
            $details = isset($matches[3]) ? $matches[3] : '';
            // Encode the tag details
            $encodedDetails = $details;
            if (!empty($details)) {
              // Replace attribute names with unicode
              $encodedDetails = preg_replace_callback(
                '/([\w\p{L}\-\:\.]*+)\s*=\s*(["\'])((?:(?!\2).)*)\2/u',
                function ($matches) use ($exclude) {
                  return Unicode::fromString($matches[1], $exclude)
                    . (!empty($matches[3]) ? '="' . $matches[3] . '"' : '');
                },
                $details
              );
            }
            // Check if the tag is self-closing
            $isSelfClosing = (
              isset($matches[3])
              && strlen($matches[3])
              && $matches[3][strlen($matches[3]) - 1] === '/'
            ) || in_array(strtolower($tag), static::$selfClosingTags);
            return '<'
              . $encodedTag
              . (!empty($encodedDetails) ? ' ' . $encodedDetails : '')
              . ($isSelfClosing ? ' />' : '>');
          }
        },
        $source
      ),
      // Encode all characters
      [0x80, 0x10FFFF, 0, ~0],
      // Use UTF-8 encoding
      'UTF-8'
    );
  }

  /**
   * Wraps the given source string with necessary formatting or tags.
   *
   * @param string $source The source string to be wrapped.
   * @return string The wrapped string.
   */
  private static function wrap(string $source): string
  {
    $indication = Abstraction::getIndicatedName(true);
    // Wrap the source with the indicator
    return static::preserve('<' . $indication . '>' . $source . '</' . $indication . '>');
  }
}
