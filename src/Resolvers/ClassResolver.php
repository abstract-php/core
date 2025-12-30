<?php

namespace Abstract\Resolvers;

use ReflectionClass;

/**
 * Argument Resolver
 */
class ClassResolver {

  /** @var ReflectionClass */
  public $class;

  /** @var array */
  public $parameters = [];

  /**
   * @param string|object|Resource $name
   */
  public function __construct($name) {
    // Initializes
    $this->class = new ReflectionClass($name);
    $constructor = $this->class->getConstructor();
    $this->parameters = empty($constructor)
      ? []
      : $constructor->getParameters();
  }

  /**
   * @param array|null $arguments
   * @return mixed
   */
  public function resolve(?array $arguments = []): mixed {
    return $this->class->newInstanceArgs(
      ArgumentResolver::resolve($this->parameters, $arguments)
    );
  }
}
