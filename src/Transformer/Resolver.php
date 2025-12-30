<?php

namespace Abstract\Transformer;

use Abstract\Abstraction;

/**
 * Class Resolver
 *
 * The Resolver class is responsible for normalizing data by applying various transformation functions.
 * It provides methods to archive and aggregate transformation functions from multiple Resolver instances.
 *
 * @package Abstract\Transformer
 */
class Resolver {

  /** @var callable[] */
  public array $functions;

  /** @var string[] */
  public static array $ignores = [
    '__construct',
    '__destruct',
    'adaptive',
    'apply',
    'build',
  ];

  /**
   * Constructor for the Resolver class.
   *
   * Initializes a new instance of the Resolver class, optionally accepting a Resolver instance for
   * object creation and an array of additional Resolver instances to extend parsing capabilities.
   *
   * @param Resolver ...$additionalResolvers An optional array of additional Resolver instances.
   */
  public function __construct(
    Resolver ...$additionalResolvers
  ) {
    // Archive the functions of current resolver or additional Resolver instances into the $functions property.
    // The archive method aggregates the functions of the additional resolvers into the current instance,
    // effectively extending its parsing capabilities.
    $this->functions = $this->archive(...$additionalResolvers);
  }

  /**
   * Archives current resolver or additional resolvers into the current Resolver instance.
   *
   * This method aggregates the functions of additional Resolver instances into the current instance,
   * effectively extending its parsing capabilities.
   *
   * @param Resolver ...$resolvers An array of Resolver instances to be archived.
   * @return array An array of callable functions aggregated from the provided Resolver instances.
   */
  protected function archive(
    Resolver ...$resolvers
  ): array {
    return count($resolvers)
      // Reduce the functions of additional resolver into the archive array.
      ? array_reduce(
        $resolvers,
        fn($_archive, $_resolver) => [
          ...$_archive,
          ...array_reduce(
            array_keys($_resolver->functions),
            function ($__functions, $__key) use ($_resolver) {
              $__functions[$__key] = fn(
                Abstraction $abstraction
              ) => $_resolver->functions[$__key]($abstraction);
              return $__functions;
            },
            []
          )
        ],
        []
      )
      // Reduce the functions of current resolver into the archive array.
      : array_reduce(
        (new \ReflectionClass($this))->getMethods(\ReflectionMethod::IS_PUBLIC),
        function ($_functions, $_method) {
          if (!in_array($_method->name, static::$ignores)) {
            $function = fn(Abstraction $abstraction) => [$this, $_method->name]($abstraction);
            $name = Abstraction::getIndicatedOf($_method->name, true);
            $_functions[$name] = $function;
          }
          return $_functions;
        },
        []
      );
  }

  public function apply(Abstraction $abstraction, mixed $value = null): void {
    $abstraction->detach();
    $abstraction->setReferenceValue($value);
    $abstraction->unsetName();
  }

  public function build(Abstraction $abstraction): mixed {
    if (!$abstraction->hasArgument()) {
      $associative = $abstraction->isAssociative();
      $children = array_map(
        fn($_abstraction) => $this->adaptive($_abstraction),
        $abstraction->list()
      );
      // $abstraction->attach(
      //   $associative,
      //   ...$children
      // );
      if (count($children) === 1) {
        $child = current($children);
        if ($associative && !$child->getName() && $child->hasArgument()) {
          $abstraction->detach();
          $abstraction->setReferenceValue($child->getArgument());
          $this->adaptive($abstraction);
        }
      }
    }
    return current((array)$abstraction->getValue());
  }

  /**
   * Adapts the abstraction to the appropriate type based on its key.
   *
   * @param Abstraction $abstraction The abstraction to be adapted.
   * @return mixed The adapted value.
   */
  public function adaptive(Abstraction $abstraction): Abstraction {
    if (isset($this->functions[$abstraction->getName()])) {
      return $this->functions[$abstraction->getName()]($abstraction);
    } else {
      $this->build($abstraction);
      return $abstraction;
    }
  }

  /**
   * Converts the abstraction to null or an object.
   *
   * @param Abstraction $abstraction The abstraction to be converted.
   * @return null The converted value.
   */
  public function null(Abstraction $abstraction): Abstraction {
    $this->apply($abstraction, true, null);
    return $abstraction;
  }

  /**
   * Converts the abstraction to an integer or an object.
   *
   * @param Abstraction $abstraction The abstraction to be converted.
   * @return int The converted value.
   */
  public function integer(Abstraction $abstraction): Abstraction {
    $value = $this->build($abstraction);
    $validated = filter_var($value, FILTER_VALIDATE_INT) !== false;
    $this->apply($abstraction, $validated ? (int)$value : 0);
    return $abstraction;
  }

  /**
   * Converts the abstraction to a double or an object.
   *
   * @param Abstraction $abstraction The abstraction to be converted.
   * @return float The converted value.
   */
  public function double(Abstraction $abstraction): Abstraction {
    $value = $this->build($abstraction);
    $validated = filter_var($value, FILTER_VALIDATE_FLOAT) !== false;
    $this->apply($abstraction, $validated ? (float)$value : doubleval(0));
    return $abstraction;
  }

  /**
   * Converts the abstraction to a float or an object.
   *
   * @param Abstraction $abstraction The abstraction to be converted.
   * @return float The converted value.
   */
  public function float(Abstraction $abstraction): Abstraction {
    $value = $this->build($abstraction);
    $validated = filter_var($value, FILTER_VALIDATE_FLOAT) !== false;
    $this->apply($abstraction, $validated ? (float)$value : floatval(0));
    return $abstraction;
  }

  /**
   * Converts the abstraction to a string or an object.
   *
   * @param Abstraction $abstraction The abstraction to be converted.
   * @return string The converted value.
   */
  public function string(Abstraction $abstraction): Abstraction {
    // $value = $this->build($abstraction);
    // $validated = !(is_object($value) || is_array($value));
    $children = $abstraction->list();
    if (count($children) > 1) {
      $cloned = $abstraction->clone();
      $cloned->unsetName();
      // var_dump($cloned);
      $this->apply($abstraction, (string)$cloned);
    } else {
      $this->apply($abstraction, (string)current($children));
    }
    // $this->apply($abstraction, $validated ? (string)$value : '');
    return $abstraction;
  }

  /**
   * Converts the abstraction to a boolean or an object.
   *
   * @param Abstraction $abstraction The abstraction to be converted.
   * @return bool|object The converted value.
   */
  public function boolean(Abstraction $abstraction): Abstraction {
    $value = $this->build($abstraction);
    $validated = filter_var($value, FILTER_VALIDATE_BOOLEAN) !== false;
    $this->apply($abstraction, $validated ? (bool)$value : false);
    return $abstraction;
  }

  /**
   * Converts the abstraction to an array.
   *
   * @param Abstraction $abstraction The abstraction to be converted.
   * @return array The converted value.
   */
  public function array(Abstraction $abstraction) {
    $value = $this->build($abstraction);
    $validated = is_array($value) && array_is_list($value);
    $this->apply($abstraction, $validated ? $value : []);
    return $abstraction;
  }

  /**
   * Converts the abstraction to an object or other mixed type.
   *
   * @param Abstraction $abstraction The abstraction to be converted.
   * @return mixed The converted value.
   */
  public function object(Abstraction $abstraction): mixed {
    $value = $this->build($abstraction);
    $validated = is_object($value)
      || (is_array($value) && !array_is_list($value));
    $this->apply($abstraction, $validated ? (object)$value : (object)[]);
    return $abstraction;
  }
}
