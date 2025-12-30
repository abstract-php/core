<?php

namespace Abstract\Resolvers;

use ReflectionFunction;

/**
 * Function Resolver
 */
class FunctionResolver {

  /** @var ReflectionFunction */
  public $function;

  /** @var array */
  public $parameters = [];

  /** @var string */
  private $name;

  /**
   * @param string $name
   */
  public function __construct(string $name) {
    // Initializes
    $this->name = $name;
    $this->function = new ReflectionFunction($name);
    $this->parameters = (empty($this->function)
      || !count($this->function->getParameters())
    )
      ? []
      : $this->function->getParameters();
  }

  /**
   * @param array|null $arguments
   * @return mixed
   */
  public function resolve(?array $arguments = []): mixed {
    // Calls function
    return call_user_func_array(
      $this->name,
      ArgumentResolver::resolve($this->parameters, $arguments)
    );
  }
}
