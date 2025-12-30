<?php

namespace Abstract\Basic;

use Abstract\Abstraction;
use Abstract\Reference;


class Evaluator {

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

  /** @var Reference */
  public Reference $reference;

  /**
   * Constructor for the Evaluator class.
   *
   * Initializes a new instance of the Evaluator class, optionally accepting a Evaluator instance for
   * object creation and an array of additional Evaluator instances to extend parsing capabilities.
   *
   * @param Evaluator ...$additionalEvaluators An optional array of additional Evaluator instances.
   */
  public function __construct(
    Evaluator ...$additionalEvaluators
  ) {
    // Archive the functions of current Evaluator or additional Evaluator instances into the $functions property.
    // The archive method aggregates the functions of the additional Evaluators into the current instance,
    // effectively extending its parsing capabilities.
    $this->functions = $this->archive(...$additionalEvaluators);
  }

  /**
   * Archives current Evaluator or additional Evaluators into the current Evaluator instance.
   *
   * This method aggregates the functions of additional Evaluator instances into the current instance,
   * effectively extending its parsing capabilities.
   *
   * @param Evaluator ...$Evaluators An array of Evaluator instances to be archived.
   * @return array An array of callable functions aggregated from the provided Evaluator instances.
   */
  protected function archive(
    Evaluator ...$Evaluators
  ): array {
    return count($Evaluators)
      // Reduce the functions of additional Evaluator into the archive array.
      ? array_reduce(
        $Evaluators,
        fn($_archive, $_Evaluator) => [
          ...$_archive,
          ...array_reduce(
            array_keys($_Evaluator->functions),
            function ($__functions, $__key) use ($_Evaluator) {
              $__functions[$__key] = fn(
                Abstraction $abstraction
              ) => $_Evaluator->functions[$__key]($abstraction);
              return $__functions;
            },
            []
          )
        ],
        []
      )
      // Reduce the functions of current Evaluator into the archive array.
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

  public function resolve(string $name, mixed $value) {
    if (isset($this->functions[$name])) {
      return $this->functions[$name]($value);
    }
  }

  // public function apply(Abstraction $abstraction, mixed $value = null): void {
  //   $abstraction->detach();
  //   $abstraction->setReferenceValue($value);
  //   $abstraction->unsetName();
  // }

  // public function build(Abstraction $abstraction): mixed {
  //   if (!$abstraction->hasArgument()) {
  //     $associative = $abstraction->isAssociative();
  //     $children = array_map(
  //       fn($_abstraction) => $this->adaptive($_abstraction),
  //       $abstraction->list()
  //     );
  //     // $abstraction->attach(
  //     //   $associative,
  //     //   ...$children
  //     // );
  //     if (count($children) === 1) {
  //       $child = current($children);
  //       if ($associative && !$child->getName() && $child->hasArgument()) {
  //         $abstraction->detach();
  //         $abstraction->setReferenceValue($child->getArgument());
  //         $this->adaptive($abstraction);
  //       }
  //     }
  //   }
  //   return current((array)$abstraction->getValue());
  // }

  public function adaptive(string $name, mixed $value): mixed {
    if (isset($this->functions[$name])) {
      return $this->functions[$name]($value);
    } else {
      return $value;
    }
  }

  public function null(mixed $value): null {
    return is_null($value) ? $value : null;
  }

  public function integer(mixed $value): int {
    $validated = filter_var($value, FILTER_VALIDATE_INT) !== false;
    return $validated ? (int)$value : 0;
  }

  public function double(mixed $value): float {
    $validated = filter_var($value, FILTER_VALIDATE_FLOAT) !== false;
    return $validated ? (float)$value : doubleval(0);
  }

  public function float(mixed $value): float {
    $validated = filter_var($value, FILTER_VALIDATE_FLOAT) !== false;
    return $validated ? (float)$value : floatval(0);
  }

  public function string(mixed $value): string {
    $validated = !(is_object($value) || is_array($value));
    return $validated ? (string)$value : '';
  }

  public function boolean(mixed $value): bool {
    $validated = filter_var($value, FILTER_VALIDATE_BOOLEAN) !== false;
    return $validated ? (bool)$value : false;
  }

  public function array(mixed $value) {
    $validated = is_array($value) && array_is_list($value);
    // $this->apply($abstraction, $validated ? $value : []);
    return $value;
  }

  public function object(mixed $value): mixed {
    $validated = is_object($value)
      || (is_array($value) && !array_is_list($value));
    // $this->apply($abstraction, $validated ? (object)$value : (object)[]);
    return $value;
  }
}
