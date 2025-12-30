<?php

namespace Abstract;

use Abstract\Common\Taxonomy\Type;
use \stdClass;

class Abstraction {

  /** @var string $delimiter is string between the indicator 
   * and the name of the abstraction
   */
  public static ?string $delimiter = ':';

  /** @var ?string $indicator is prefix to the name of the abstraction */
  public static ?string $indicator = null;

  /** @var ?string $name represents constant name of the abstraction */
  public static ?string $name = null;

  /** @var Reference */
  public Reference $reference;

  /** @var Value */
  public Value $value;

  /** @var Abstraction[] */
  public array $children = [];

  /** @var ?\Abstract\Transformer\Factory $factory */
  public ?\Abstract\Transformer\Factory $factory;

  /** @var ?\Abstract\Transformer\Observer $parser */
  public ?\Abstract\Transformer\Observer $observer;

  public Evaluation $evaluation;

  // public function __construct($name, $value, $association) {
  // $value in the constructor is the value that shouldn't be parse as abstraction
  public function __construct(
    ?Reference $reference = null,
    ?Evaluation $evaluation = null
  ) {
    $this->reference = $reference ?? new Reference;
    $this->evaluation = $evaluation ?? new Evaluation;
    $this->observer = new \Abstract\Transformer\Observer;
    $this->factory = new \Abstract\Transformer\Factory;
    if (static::$name) {
      $this->setName(static::$name, true);
    }
    // $this->value = new Value(
    //   $this->reference,
    //   ...array_map(
    //     fn($_abstraction) => $_abstraction->value,
    //     $this->list()
    //   )
    // );
    // if (!empty($abstractions)) {
    //   $this->attach(true, ...$abstractions);
    // }
  }

  public function __destruct() {
    // $this->unsetReferenceValue();
    // $this->clear();
  }

  public static function container(Abstraction ...$abstractions): static {
    $abstraction = new static;
    $abstraction->attach(false, ...$abstractions);
    return $abstraction;
  }

  public static function getIndicator(
    bool $asString = false,
    bool $includeEmpty = false
  ): ?string {
    return static::$indicator
      ? static::$indicator . static::$delimiter
      : ($includeEmpty ? static::$delimiter : ($asString ? '' : null));
  }

  public static function getIndicatedName(
    bool $asString = false,
    bool $includeEmpty = false
  ): ?string {
    return static::$name
      ? static::getIndicator(true, $includeEmpty) . static::$name
      : ($asString ? '' : null);
  }

  public static function getIndicatedOf(
    string $name,
    bool $includeEmpty = false
  ): string {
    return static::getIndicator(true, $includeEmpty) . $name;
  }

  public function withName(string $name, bool $indicative = false): static {
    $this->setName($name, $indicative);
    return $this;
  }

  public function withArgument(mixed $value): static {
    $this->setReferenceValue($value);
    $this->setAssociative(true);
    return $this;
  }

  public function parse(
    mixed $source,
    ?\Abstract\Transformer\Factory $factory = null
  ): Abstraction {

    // Use the factory that is passed as argument or the default factory
    $factory = $factory ?? $this->factory;

    // build function that should be used when the source is an object
    $build = function (
      ?string $key = null,
      mixed $value = null
    ) use ($factory): Reference {
      if (isset($factory->functions[$key])) {
        return $factory->functions[$key]($value);
      } else {
        return new Reference($key, $this->parse($value, $factory));
      }
    };

    // Normalize the source when it is an object (Only for PHP)
    $iterable = $source instanceof stdClass ? (array)$source : $source;

    switch (Type::of($source)) {
      case 'object':
        // When the source is an object
        if (!array_is_list($iterable)) {
          if (count($iterable) === 1) {
            // When the source is an object with only one key
            return new Abstraction(
              $build(key($iterable), current($iterable))
            );
          } else {
            // When the source is an object with multiple keys
            return new Abstraction(
              new Reference(
                null,
                array_map(
                  function ($_key) use ($iterable, $build) {
                    return new Abstraction(
                      $build($_key, $iterable[$_key])
                    );
                  },
                  array_keys($iterable)
                )
              )
            );
          }
        }
      case 'array':
        // When the source is an array
        if (array_is_list($iterable)) {
          return new Abstraction(
            new Reference(
              null,
              array_map(
                fn($_value) => $this->parse($_value, $factory),
                $source
              ),
              false
            )
          );
        }
      default:
        // When the source is a scalar
        return new Abstraction(new Reference(null, $source));
    }
  }

  /** @var Abstraction[] */
  public function list(): array {
    return $this->children;
  }

  /** @var self|self[]|static|static[]|null */
  public function get(
    string $name,
    bool $asArray = false,
    bool $recursive = false
  ): self|static|array|null {
    $children = array_filter(
      $this->list(),
      fn($_child) => $_child->getName() === $name
        || ($recursive && $_child->get($name))
    );
    if (count($children) > 1 || $asArray) {
      return $children;
    } elseif (empty($children)) {
      return null;
    } else {
      return current($children);
    }
  }

  public function set(
    string $name,
    Abstraction ...$abstractions
  ): void {
    $this->children = array_map(
      fn($_child) => $_child->getName() === $name
        ? [
          $name,
          count($abstractions) > 1
            ? (
              $_child->isAssociative()
              ? (new static(...$abstractions))->withName($name)
              : static::container(...$abstractions)->withName($name)
            )
            : current($abstractions)
        ]
        : $_child,
      $this->list()
    );
    $this->applyResource();
  }

  public function unset(string $name): void {
    $this->children = array_filter(
      $this->list(),
      fn($_child) => $_child->reference->getKey() !== $name,
    );
    $this->applyResource();
  }

  public function attach(
    ?bool $associative = null,
    Abstraction ...$children
  ): void {
    $associative = is_null($associative) ? $this->isAssociative() : $associative;
    // Attach the abstractions to the children
    $this->children = array_map(
      function ($_child) {
        $_child->setDepth($this->reference->depth + 1);
        return $_child;
      },
      $children
    );
    $this->applyResource();
    // Set the associative flag
    $this->setAssociative($associative);
    // Set the association
    // $this->setAssociation(...$children);
  }

  public function detach(?bool $associative = null): void {
    $associative = is_null($associative) ? $this->isAssociative() : $associative;
    // Detach the abstractions to the children
    $this->children = [];
    $this->applyResource();
    // Set the associative flag
    $this->setAssociative($associative);
    // Set the association
    // $this->setAssociation(...$children);
  }

  public function append(Abstraction ...$children): void {
    // Append the abstractions to the children
    $this->children = [
      ...$this->list(),
      ...array_map(
        function ($_child) {
          $_child->setDepth($this->reference->depth + 1);
          return $_child;
        },
        $children
      )
    ];
    $this->applyResource();
    // Set the association
    // $this->setAssociation(...$abstractions);
  }

  public function prepend(Abstraction ...$children): void {
    // Prepend the abstractions to the children
    $this->children = [
      ...array_map(
        function ($_child) {
          $_child->setDepth($this->reference->depth + 1);
          return $_child;
        },
        $children
      ),
      ...$this->list()
    ];
    $this->applyResource();
  }

  public function replace(
    Abstraction $target,
    Abstraction $replaceWith
  ): void {
    $this->children = array_map(
      fn($_child) => $_child->reference->id === $target->reference->id
        ? $replaceWith
        : $_child,
      $this->list()
    );
    $this->applyResource();
  }

  public function remove(Abstraction ...$abstractions): void {
    $this->children = array_filter(
      $this->list(),
      fn($_child) => !in_array(
        $_child->reference->id,
        array_map(
          fn($__abstraction) => $__abstraction->reference->id,
          $abstractions
        )
      )
    );
    $this->applyResource();
  }

  public function removeByName(string $name): void {
    $this->children = array_filter(
      $this->list(),
      fn($_child) => $_child->getName() !== $name,
    );
    $this->applyResource();
  }

  public function clear(): void {
    $this->children = [];
    $this->applyResource();
  }

  public function clone(bool $demote = false): self|static {
    $children = $this->list();
    $abstraction = $demote ? new self : new static;
    $abstraction->attach($this->isAssociative(), ...$children);
    if ($this->hasArgument()) {
      $abstraction->setReferenceValue($this->getArgument());
    };
    $name = $this->getName();
    if ($name) {
      $abstraction->setName($this->getName());
    }
    return $abstraction;
  }

  /** @var self|self[]|static|static[]|null */
  public function indicate(?string $name = ''): self|static|array|null {
    $indicator = $this->reference->indicator;
    return $this->get(($indicator ? $indicator . ':' : '') . $name);
  }

  /** @var self|static|null */
  public function refer(
    Reference $reference,
    bool $recursive = false
  ): self|static|null {
    return current(
      array_filter(
        $this->list(),
        fn($_child) => $_child->reference->id === $reference->id
          || ($recursive && $_child->refer($reference))
      )
    ) ?? null;
  }

  public function getName(bool $asString = false): ?string {
    return $this->reference->hasKey()
      ? $this->reference->getKey()
      : ($asString ? '' : null);
  }

  public function setName(string $name, bool $indicative = false): void {
    $indicator = static::getIndicator(true);
    if (strpos($name, $indicator) === 0) {
      $indicative = true;
      $name = mb_substr($name, strlen($indicator));
    }
    $this->reference->setKey($name, $indicative ? $indicator : null);
  }

  public function unsetName(): void {
    $this->reference->unsetKey();
  }

  public function getArgument(): mixed {
    return $this->reference->getValue();
  }

  public function hasArgument(): bool {
    return $this->reference->hasValue();
  }

  public function setReferenceValue(mixed $argument): void {
    $this->reference->setValue($argument);
  }

  public function unsetReferenceValue(): void {
    $this->reference->unsetValue();
  }

  public function getId(): string {
    return $this->reference->id;
  }

  public function getDepth(): int {
    return $this->reference->depth;
  }

  public function setDepth(int $depth): void {
    $this->reference->depth = $depth;
    $this->children = array_map(
      function ($_child) use ($depth) {
        $_child->setDepth($depth + 1);
        return $_child;
      },
      $this->children
    );
  }

  public function unsetDepth(): void {
    $this->setDepth(0);
  }

  public function isAssociative(): bool {
    return $this->reference->associative;
  }

  public function setAssociative(bool $associative): void {
    $this->reference->associative = $associative;
  }

  public function getValue(?bool $associative = null): mixed {
    return $this->value->getResource($associative);
  }

  public function observe(
    ?bool $associative = null,
    ?\Abstract\Transformer\Observer $observer = null
  ): mixed {
    $observer = $observer ?? $this->observer;
    $referenceValue = $this->reference->getValue();
    $value = $this->observer->adaptive($this->getName(), $referenceValue);
    // $this->setReferenceValue($value);
    // $this->unsetName();
    // return $this->value->getResource($associative);
    return null;
  }

  public function applyResource(): void {
    $this->value->setResource(
      ...array_map(fn($_child) => $_child->value, $this->list())
    );
  }

  public function normalize(
    ?bool $associative = null
  ): mixed {

    $build = function (mixed $result): mixed {
      $key = $this->reference->getKey();
      $value = is_array($result) && !array_is_list($result)
        ? (object)$result
        : $result;
      // Check if the key exists
      if ($key) {
        // Create an object with the key-value pair
        return (object)[$key => $value];
      } else {
        // Return the value
        return $value;
      }
    };

    $referenceValue = $this->reference->getValue();
    if ($referenceValue instanceof Abstraction) {
      return $build($referenceValue->normalize($associative));
    } else if (is_scalar($referenceValue) || is_null($referenceValue) || is_object($referenceValue)) {
      return $build($referenceValue);
    } else {
      switch (true) {  
        case $associative === true:
          return $build(
            // Reduce the list of associated children with normalized abstractions
            array_reduce(
              $referenceValue,
              function ($_normalization, $abstraction) use ($referenceValue, $associative) {
                // Get the key of the abstraction
                $key = $abstraction->reference->getKey();
                // Normalize the abstraction
                $normalized = $abstraction->normalize($associative);
                // Extract value of the normalized abstraction
                $value = is_object($normalized) ? current((array)$normalized) : $normalized;
                // Set default normalized result
                $result = $_normalization->result;
                // Use associative strategy when the abstraction is associable
                if (is_object($normalized) && $this->reference->associative) {
                  // Ignore similar keys
                  if (!in_array($key, $_normalization->similarKeysAssigned)) {
                    // Collect similar key abstractions
                    $similarKeyValues = array_reduce(
                      $referenceValue,
                      function (
                        $_similar,
                        $__value
                      ) use ($key, $associative) {
                        if ($__value->reference->getKey() === $key) {
                          $__normalized = $__value->normalize($associative);
                          $_similar[] = is_object($__normalized)
                            ? current((array)$__normalized)
                            : $__normalized;
                        }
                        return $_similar;
                      },
                      []
                    );
                    // Map the similar key abstractions to normalized values as array
                    if (count($similarKeyValues) > 1) {
                      // Merge the similar key values
                      $value = $similarKeyValues;
                      // Add the key to the list of attached similar keys 
                      // to be ignored in the next iteration
                      $_normalization->similarKeysAssigned[] = $key;
                    }
                    if (is_object($result)) {
                      // Simply convert result to array when normalized and result 
                      // are still objects and the abstraction is associative
                      $result = (array)$result;
                    }
                    // Merge the result with the normalized value
                    $_normalization->result = $_normalization->isAssociable
                      // Merge the key-value pair with the result when the abstraction is associable
                      // as the result is an object
                      ? (object)[...$result, ...[$key => $value]]
                      // Merge key-value pair when the abstraction is not support associable
                      : [...$result, ...[$key ? [$key => $value] : $value]];
                  }
                } else {
                  // Use list strategy when the abstraction is not support associable
                  if (is_object($result)) {
                    // Convert to array with key-value pair when normalized is not an object
                    // or the abstraction is not associative
                    $result = array_map(
                      fn($__key, $__value) => [$__key => $__value],
                      array_keys((array)$result),
                      (array)$result
                    );
                  }
                  // Simply merge the result with the normalized value
                  $_normalization->result = [...$result, $normalized];
                  // Set the not support flag for the next iteration
                  $_normalization->isAssociable = false;
                }
                return $_normalization;
              },
              // Initialize the normalization object
              (object)['isAssociable' => true, 'similarKeysAssigned' => [], 'result' => []]
            )->result
          );
        case $associative === false:
          return $build(
            array_map(
              fn($abstraction) => $abstraction->normalize($associative),
              $referenceValue
            )
          );
        default:
          return $build(
            // Reduce the list of associated children with normalized abstractions
            array_reduce(
              $referenceValue,
              function ($_normalization, $abstraction) use ($associative) {
                // Get the key of the abstraction
                $key = $abstraction->reference->getKey();
                // Normalize the abstraction
                $normalized = $abstraction->normalize($associative);
                // Extract value of the normalized abstraction
                $value = is_object($normalized) ? current((array)$normalized) : $normalized;
                // Set default normalized result
                $result = $_normalization->result;
                // Use associative strategy when the abstraction is associable
                if (is_object($normalized) && $this->reference->associative) {
                  // Optimize strategy
                  if (is_object($result)) {
                    // Simply convert result to array when normalized and result 
                    // are still objects and the abstraction is associative
                    $result = (array)$result;
                  }
                  $previousResource = $_normalization->previousResource;
                  if (
                    $previousResource
                    && $previousResource->reference->getKey() === $key
                  ) {
                    if ($_normalization->isAssociable) {
                      if ($_normalization->isOptimizing) {
                        $result[$key] = [...$result[$key], $value];
                      } else {
                        $result[$key] = [$result[$key], $value];
                        $_normalization->isOptimizing = true;
                      }
                      $_normalization->result = (object)$result;
                    } else {
                      $previousIndex = count($result) - 1;
                      $resultList = $result[$previousIndex];
                      if (is_object($resultList)) {
                        // Simply convert result to array when normalized and result 
                        // are still objects and the abstraction is associative
                        $resultList = (array)$resultList;
                      }
                      if ($_normalization->isOptimizing) {
                        $resultList[$key] = [...$resultList[$key], $value];
                      } else {
                        $resultList[$key] = [$resultList[$key], $value];
                        $_normalization->isOptimizing = true;
                      }
                      $_normalization->result[$previousIndex] = $resultList;
                    }
                  } else {
                    if (isset($result[$key])) {
                      $_normalization->isAssociable = false;
                      // Use list strategy when the abstraction is not support associable
                      if (is_object($_normalization->result)) {
                        // Convert to array with key-value pair when normalized is not an object
                        // or the abstraction is not associative
                        $result = array_map(
                          fn($__key, $__value) => [$__key => $__value],
                          array_keys($result),
                          $result
                        );
                      }
                    }
                    $_normalization->isOptimizing = false;
                    // Merge the result with the normalized value
                    $_normalization->result = $_normalization->isAssociable
                      // Merge the key-value pair with the result when the abstraction is associable
                      // as the result is an object
                      ? (object)[...$result, ...[$key => $value]]
                      // Merge key-value pair when the abstraction is not support associable
                      : [...$result, ...[$key ? [$key => $value] : $value]];
                  }
                } else {
                  // Use list strategy when the abstraction is not support associable
                  if (is_object($result)) {
                    // Convert to array with key-value pair when normalized is not an object
                    // or the abstraction is not associative
                    $result = array_map(
                      fn($__key, $__value) => [$__key => $__value],
                      array_keys((array)$result),
                      (array)$result
                    );
                  }
                  // Simply merge the result with the normalized value
                  $_normalization->result = [...$result, $normalized];
                  // Set the not support flag for the next iteration
                  $_normalization->isAssociable = false;
                }
                $_normalization->previousResource = $abstraction;
                return $_normalization;
              },
              // Initialize the normalization object
              (object)[
                'isAssociable' => true,
                'isOptimizing' => false,
                'previousResource' => null,
                'result' => []
              ]
            )->result
          );
      }
    }

  }

  public function toJson(
    ?bool $associative = null,
    bool $prettyPrint = false
  ): string {
    $normalized = $this->normalize($associative);
    if (is_object($normalized) || is_array($normalized)) {
      $options = $prettyPrint
        ? JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
        : JSON_UNESCAPED_UNICODE;
      return json_encode($normalized, $options);
    } else {
      return (string)$normalized ?? '';
    }
  }

  public function __toString(): string {
    return $this->toJson(true, true);
  }
}
