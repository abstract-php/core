<?php

namespace Abstract;

/**
 * Class Association
 *
 * This class represents a association that can store references either in an associative manner.
 * It provides methods to list all stored references and retrieve references by key.
 *
 * @package Abstract\Registry
 */
class Association {

  /** @var array */
  public array $items = [];

  public bool $lock = false;

  /**
   * Map constructor.
   *
   * @param Reference ...$references A variable number of Reference objects to initialize the association with.
   */
  public function __construct(Reference ...$references) {
    // Build the association items based on the provided references.
    $this->items = static::build(...$references);
  }

  /**
   * Builds the association items based on the provided references.
   *
   * @param Reference ...$references A variable number of Reference objects to build the association with.
   * @return array The built association items.
   */
  public static function build(Reference ...$references): array {
    /** @var array|null An array of keys of references. */
    $referenceKeys = array_map(
      fn(Reference $_reference) => $_reference->getKey(),
      $references
    );
    // Check if all references have a key.
    if (in_array(null, $referenceKeys)) {
      // Throw an exception if any reference does not have a key.
      throw new \InvalidArgumentException(
        'All references must have a key to be stored in the association.'
      );
    }
    return array_reduce(
      $references,
      // Merge the references into an associative array based on their keys.
      fn($_items, $_reference) => [
        ...$_items,
        ...[$_reference->getKey() => $_reference]
      ],
      []
    );
  }

  /**
   * Lists all stored references.
   *
   * @param bool $associative 
   * @return array|Reference[] The list of stored references.
   */
  public function list(bool $associative = true): array {
    if ($associative) {
      // Return the associative array of references.
      return $this->items;
    } else {
      // Return the list of references.
      return array_map(fn($_item) => $_item, $this->items);
    }
  }

  /**
   * Retrieves references by key.
   *
   * @param string $key The key to retrieve references by.
   * @return Reference|null The references associated with the given key.
   */
  public function get(string $key): ?Reference {
    // Return the references associated with the given key.
    return isset($this->items[$key]) ? $this->items[$key] : null;
  }

  /**
   * Sets references for a given key.
   *
   * @param string $key The key to set the references for.
   * @param Reference $reference The references to set.
   * @return void
   */
  public function set(string $key, Reference $reference): void {
    // Set the key of the reference with new key
    $reference->setKey($key);
    // Set the reference for the given key.
    $this->items[$key] = $reference;
  }

  /**
   * Unsets references for a given key.
   *
   * @param string $key The key to unset the references for.
   * @return void
   */
  public function unset(string $key): void {
    if (isset($this->items[$key])) {
      unset($this->items[$key]);
    }
  }

  /**
   * Appends references to the association.
   *
   * @param Reference ...$references The references to append.
   * @return void
   */
  public function append(Reference ...$references): void {
    $this->items = static::build(
      ...$this->list(false),
      ...$references
    );
  }

  /**
   * Prepends references to the association.
   *
   * @param Reference ...$references The references to prepend.
   * @return void
   */
  public function prepend(Reference ...$references): void {
    $this->items = static::build(
      ...$references,
      ...$this->list(false)
    );
  }

  /**
   * Replaces a target reference with another reference.
   *
   * @param Reference $target The reference to be replaced.
   * @param Reference $replaceWith The reference to replace with.
   * @return void
   */
  public function replace(
    Reference $target,
    Reference $replaceWith
  ): void {
    // Check if the replacement reference has a key.
    // if (!isset($replaceWith->getKey())) {
    //   // Throw an exception if the target reference does not have a key.
    //   throw new \InvalidArgumentException(
    //     'The target reference must have a key to be replaced.'
    //   );
    // }
    // Replace the target reference with the replacement reference.
    $this->items = array_reduce(
      array_keys($this->items),
      function ($_items, $_key) use ($target, $replaceWith) {
        // Check if the target reference is found.
        if ($this->items[$_key]->id === $target->id) {
          // Replace the target reference with the replacement reference.
          // at the same position in the array.
          $_items[$replaceWith->getKey()] = $replaceWith;
        } else {
          $_items[$_key] = $this->items[$_key];
        }
        return $_items;
      },
      []
    );
  }

  /**
   * Removes specified references from the association.
   *
   * @param Reference ...$references The references to be removed.
   * @return void
   */
  public function remove(Reference ...$references): void {
    /** @var array An array of reference identifiers. */
    $referenceIds = array_map(
      fn(Reference $_reference) => $_reference->id,
      $references
    );
    $this->items = array_reduce(
      array_keys($this->items),
      function ($_items, $_key) use ($referenceIds) {
        // Check if the reference is not in the list of references to be removed.
        if (!in_array($this->items[$_key]->id, $referenceIds)) {
          // Remove the references from the association.
          $_items[$_key] = $this->items[$_key];
        }
        return $_items;
      },
      []
    );
  }

  /**
   * Clears all references from the association.
   *
   * @return void
   */
  public function clear(): void {
    $this->items = [];
  }

  /**
   * Magic method to get references by key.
   *
   * @param string $key The key to retrieve references by.
   * @return Reference The references associated with the given key.
   */
  public function __get(string $key): Reference {
    return $this->get($key);
  }

  /**
   * Magic method to set a reference by key.
   *
   * @param string $key The key to set the reference for.
   * @param Reference $reference The reference to set.
   * @return void
   */
  public function __set(string $key, Reference $reference) {
    return $this->set($key, $reference);
  }
}
