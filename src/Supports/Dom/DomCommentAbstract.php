<?php

namespace Abstract\Supports\Dom;

use Abstract\Abstraction;

class DomCommentAbstract extends DomAbstract {

  /** @var string $name represents constant name of the derived abstraction */
  public static ?string $name = 'comment';

  /**
   * @var \DOMDocument
   * An instance of the DOMDocument class 
   * representing the entire HTML or XML document.
   */
  public \DOMDocument $document;

  /**
   * Constructor for the DomCommentAbstract class.
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

  public function parse(Abstraction $abstraction): \DOMComment|false {
    $cloned = $abstraction->clone();
    $cloned->unsetName();
    $comment = $this->document->createComment((string)$cloned);
    return $comment;
  }
  
}
