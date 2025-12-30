<?php

namespace Abstract;

/**
 * Class Reference
 *
 * Represents a reference with a key, value, id, and timestamp.
 *
 * @package Abstract\Registry
 */
class Reference {

  /**
   * @var string $id The unique identifier of the reference.
   */
  public readonly string $id;

  /**
   * @var ?string $indicator Indicator of the abstraction.
   */
  public ?string $indicator = null;

  /**
   * @var ?string $key The key of the reference, can be null.
   */
  public ?string $key;

  /**
   * @var mixed $value The value of the reference, can be null.
   */
  public mixed $value;

  /**
   * @var \DateTime $timestamp The timestamp when the reference was created.
   */
  public readonly \DateTime $timestamp;

  /**
   * @var string $depth The unique identifier of the reference.
   */
  public int $depth;

  /**
   * @var bool $associative Indicates if the reference is associative.
   */
  public bool $associative = true;
  /**
   * @var bool $indicated Use indicator of the abstraction.
   */
  public bool $indicated = false;

  /**
   * Reference constructor.
   *
   * @param ?string $key The key of the reference, can be null.
   * @param mixed $value The value of the reference, can be null.
   */
  public function __construct(
    ?string $key = null,
    mixed $value = null,
    bool $associative = true,
    bool $indicated = false
  ) {
    $this->id = uniqid();
    $this->timestamp = new \DateTime;
    $this->depth = 0;
    $this->key = $key ?? null;
    $this->value = $value ?? null;
    $this->associative = $associative;
    $this->indicated = $indicated;
  }

  /**
   * Checks if the reference has a key.
   *
   * @return bool True if the reference has a key, false otherwise.
   */
  public function hasValue(): bool {
    return isset($this->value);
  }

  /**
   * Gets the value of the reference.
   *
   * @return mixed The value, can be null.
   */
  public function getValue(): mixed {
    if ($this->hasValue()) {
      return $this->value;
    } else {
      return null;
    }
  }

  /**
   * Sets the value of the reference.
   *
   * @param mixed $value The value to set.
   * @return void
   */
  public function setValue(mixed $value): void {
    $this->value = $value;
  }

  /**
   * Unsets the value of the reference.
   *
   * @return void
   */
  public function unsetValue(): void {
    unset($this->value);
  }

  /**
   * Gets the key of the reference.
   *
   * @return bool The key, can be null.
   */
  public function hasKey(): bool {
    return !is_null($this->key);
  }

  /**
   * Gets the key of the reference.
   *
   * @return ?string The key, can be null.
   */
  public function getKey(): ?string {
    $indicator = $this->hasIndicator() ? $this->indicator : '';
    return $indicator . $this->key;
  }

  /**
   * Sets the key of the reference.
   *
   * @param string $key The key to set.
   * @return void
   */
  public function setKey(string $key, ?string $indicator = null): void {
    $this->key = $key;
    if ($indicator) {
      $this->setIndicator($indicator);
    }
  }

  /**
   * Unsets the key of the reference.
   *
   * @return void
   */
  public function unsetKey(): void {
    $this->key = null;
    $this->unsetIndicator();
  }

  /**
   * Gets the timestamp when the reference was created.
   *
   * @return \DateTime The timestamp.
   */
  public function getTimestamp(): \DateTime {
    return $this->timestamp;
  }

  /**
   * Checks if the reference has an indicator.
   *
   * @return bool True if the reference has an indicator, false otherwise.
   */
  public function hasIndicator(): bool {
    return !is_null($this->indicator);
  }

  /**
   * Sets the indicator of the reference.
   *
   * @param string $indicator The indicator to set.
   * @return void
   */
  public function setIndicator(string $indicator = null): void {
    $this->indicator = $indicator;
  }

  /**
   * Unsets the indicator of the reference.
   *
   * @return void
   */
  public function unsetIndicator(): void {
    $this->indicator = null;
  }

}
