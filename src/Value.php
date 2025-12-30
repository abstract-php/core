<?php

namespace Abstract;

class Value {

  public Reference $reference;

  /**
   * @var Value[] $resource The resource of the value.
   */
  public array $resource;

  /**
   * @var Value[] $resource The resource of the value.
   */
  public array $resolved;

  public function __construct(
    Reference $reference,
    Value ...$resource
  ) {
    $this->setReference($reference);
    $this->setResource(...$resource);
  }


  public function setReference(Reference $reference): void {
    $this->reference = $reference;
  }

  public function getResource(?bool $associative = null): mixed {
    if ($this->reference->hasValue()) {
      return $this->build($this->reference->getValue());
    } else {
      return $this->normalize($associative);
    }
  }

  public function setResource(Value ...$resource): void {
    $this->resource = $resource;
  }

  public function normalize(
    ?bool $associative = null,
    // ?bool $useResolved = false
  ): mixed {
    switch (true) {
      case $associative === true:
        return $this->build(
          // Reduce the list of associated children with normalized abstractions
          array_reduce(
            $this->resource,
            function ($_normalization, $_value) use ($associative) {
              // Get the key of the abstraction
              $key = $_value->reference->getKey();
              // Normalize the abstraction
              $normalized = $_value->getResource($associative);
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
                    $this->resource,
                    function (
                      $_similar,
                      $__value
                    ) use ($key, $associative) {
                      if ($__value->reference->getKey() === $key) {
                        $__normalized = $__value->getResource($associative);
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
        return $this->build(
          array_map(
            fn($_value) => $_value->getResource($associative),
            $this->resource
          )
        );
      default:
        return $this->build(
          // Reduce the list of associated children with normalized abstractions
          array_reduce(
            $this->resource,
            function ($_normalization, $_value) use ($associative) {
              // Get the key of the abstraction
              $key = $_value->reference->getKey();
              // Normalize the abstraction
              $normalized = $_value->getResource($associative);
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
              $_normalization->previousResource = $_value;
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

  private function build(mixed $result): mixed {
    $key = $this->reference->getKey();
    // var_dump($key);
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
  }
}
