<?php

namespace Abstract\Transformer;

use Abstract\Abstraction;
use Abstract\Reference;
use Abstract\Common\Taxonomy\Type;

/**
 * Parser class
 *
 * This class is designed to parse data abstractions. It allows for the extension of its parsing capabilities
 * by incorporating additional Parser instances. It leverages a Factory object for creating instances
 * as needed.
 *
 * @package Abstract\Transformer
 */
class Parser {

  /** @var ?string $indicator is prefix to the name of the parser */
  public static ?string $indicator = null;

  /** 
   * @var callable[] 
   * Archived functions from current parser or additional parsers
   */
  public array $functions;

  /** @var Factory */
  protected Factory $factory;

  /**
   * Constructor for the Parser class.
   *
   * Initializes a new instance of the Parser class, optionally accepting a Factory instance for
   * object creation and an array of additional Parser instances to extend parsing capabilities.
   *
   * @param Factory|null $factory An optional Factory instance for object creation.
   * @param Parser ...$additionalParsers An optional array of additional Parser instances.
   */
  public function __construct(
    mixed $source,
    ?Factory $factory = null,
    Parser ...$additionalParsers
  ) {
    // Set the factory property to the provided $factory instance if it is not null,
    // otherwise create a new instance of the Factory class.
    $this->factory = $factory ?? new Factory;

    // Archive the functions of current parser or additional Parser instances into the $functions property.
    // The archive method aggregates the functions of the additional parsers into the current instance,
    // effectively extending its parsing capabilities.
    $this->functions = $this->archive(...$additionalParsers);
  }

  public static function strategicAlias(string $alias): string {
    return $alias;
  }

  /**
   * Archives current parser or additional parsers into the current Parser instance.
   *
   * This method aggregates the functions of additional Parser instances into the current instance,
   * effectively extending its parsing capabilities.
   *
   * @param Parser ...$parsers An array of Parser instances to be archived.
   * @return array An array of callable functions aggregated from the provided Parser instances.
   */
  protected function archive(
    Parser ...$parsers
  ): array {
    return count($parsers)
      // Reduce the functions of additional parser into the archive array.
      ? array_reduce(
        $parsers,
        fn($_functions, $parser) => [
          ...$_functions,
          ...array_reduce(
            array_keys($parser->functions),
            function ($__functions, $__key) use ($parser) {
              $__key = $parser::strategicAlias($__key);
              $__functions[$__key] = fn(
                mixed $source
              ) => $parser->functions[$__key](
                $source
              );
              return $__functions;
            },
            []
          )
        ],
        []
      )
      // Reduce the functions of current parser into the archive array.
      : array_reduce(
        array_filter(
          (new \ReflectionClass($this))->getMethods(\ReflectionMethod::IS_PUBLIC),
          fn($method) => !$method->isStatic()
        ),
        function ($_functions, $_method) {
          if ($_method->name !== '__construct' && $_method->name !== '__destruct') {
            $_functions[$_method->name] = fn(mixed $source) => [
              $this,
              $_method->name
            ]($source);
          }
          return $_functions;
        },
        []
      );
  }

  /**
   * Transform the source into a Abstraction using adaptive transformation.
   *
   * @param mixed $source
   * @return Abstraction
   */
  public function adaptive(mixed $source): Abstraction {
    $type = Type::of($source);
    if (isset($this->functions[$type])) {
      return $this->functions[$type]($source);
    } else {
      $sourceIsList = is_array($source) && array_is_list($source);
      $abstraction = $sourceIsList ? Abstraction::container() : new Abstraction;
      $abstraction->setReferenceValue($source);
      return $abstraction;
    }
  }

  public function build(?string $key = null, mixed $value = null): Reference {
    if (isset($this->factory->functions[$key])) {
      return $this->factory->functions[$key]($value);
    } else {
      return new Reference($key, $value);
    }
  }

  /**
   * Transform the array source into a Abstraction.
   *
   * @param array $source
   * @return Abstraction
   */
  public function array(array $source): Abstraction {
    if (array_is_list($source)) {
      $reference = new Reference(
        null,
        array_map(
          fn($_value) => $this->adaptive($_value),
          $source
        ),
        false
      );
      return new Abstraction($reference);
    } else {
      return $this->object((object)$source);
    }
  }

  /**
   * Transform the object source into a Abstraction.
   *
   * @param object $source
   * @return Abstraction
   */
  public function object(object $source): mixed {
    $array = (array)$source;
    // If the source is a list, transform it into a Abstraction with a key of 'array'.
    if (array_is_list($array)) {
      return $this->array($array);
    } else {
      $reference = new Reference();
      if (count($array) === 1) {
        $key = key($array);
        $reference = $this->build($key, $array[$key]);
      } else {
        $reference = new Reference(
          null,
          array_map(
            function ($_key) use ($array) {
              $_reference = $this->build($_key, $array[$_key]);
              return new Abstraction($_reference);
            },
            array_keys($array)
          )
        );
        return new Abstraction($reference);
      }
    }
  }

  public function abstraction(Abstraction $source): Abstraction {
    return $source;
  }
}
