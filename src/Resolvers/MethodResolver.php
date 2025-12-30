<?php

namespace Abstract\Resolvers;

use ReflectionMethod;

/**
 * Method Resolver
 */
class MethodResolver {

  /** @var ReflectionFunction */
  public $method;

  /** @var array */
  public $parameters = [];

  /** @var string */
  private $name;

  /** @var string|object|Resource */
  private $class;

  /**
   * @param string|object|Resource $class
   * @param string $name
   */
  public function __construct($class, $name) {
    // Initializes
    $this->name = $name;
    $this->class = $class;
    $this->method = new ReflectionMethod($class, $name);
    $this->parameters = (empty($this->method)
      || !count($this->method->getParameters())
    )
      ? []
      : $this->method->getParameters();
  }

  /**
   * @param array|null $arguments
   * @return mixed
   */
  public function resolve(?array $arguments = []): mixed {
    // Calls method
    return call_user_func_array(
      array($this->class, $this->name),
      ArgumentResolver::resolve($this->parameters, $arguments)
    );
  }

}