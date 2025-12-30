<?php

namespace Abstract\Transformer;

use Abstract\Common\Convertor\StringCase;

/**
 * Class Factory
 * 
 * This class represents a factory for creating abstractions with different object keys.
 *
 * @package Abstract\Transformer
 */
class Factory {

  /**
   * @var array
   * An array of transformation aliases.
   */
  public array $aliases = [];

  /** 
   * @var callable[] 
   * Archived functions from current factory or additional factories
   */
  public array $functions = [];

  /**
   * Constructor for the Factory class.
   *
   * Initializes a new instance of the Factory class, optionally accepting a Factory instance for
   * object creation and an array of additional Factory instances to extend parsing capabilities.
   *
   * @param bool|null $xmlTagNameSupport An optional XML tag name support for aliases creation
   * @param Factory ...$additionalFactories An optional array of additional Factory instances.
   */
  public function __construct(
    ?bool $xmlTagNameSupport = true,
    Factory ...$additionalFactories
  ) {
    // Archive the functions of current factory or additional Factory instances into the $functions property.
    // The archive method aggregates the functions of the additional factories into the current instance,
    // effectively extending its parsing capabilities.
    $this->archive($xmlTagNameSupport, ...$additionalFactories);
  }

  /**
   * Archives current factory or additional factories into the current Factory instance.
   *
   * This method aggregates the functions of additional Factory instances into the current instance,
   * effectively extending its parsing capabilities.
   *
   * @param bool|null $xmlTagNameSupport An optional XML tag name support for aliases creation
   * @param Factory ...$factories An array of Factory instances to be archived.
   * @return array An array of callable functions aggregated from the provided Factory instances.
   */
  protected function archive(
    ?bool $xmlTagNameSupport = true,
    Factory ...$factories
  ): void {
    $this->aliases = [
      ...$this->aliases,
      ...array_map(fn($_factory) => $_factory->aliases, $factories)
    ];
    $functions = count($factories)
      // Reduce the functions of additional factory into the archive array.
      ? array_reduce(
        $factories,
        fn($_archive, $_factory) => [
          ...$_archive,
          ...array_reduce(
            array_keys($_factory->functions),
            function ($__functions, $__key) use ($_factory) {
              $__functions[$__key] = fn(
                mixed $value
              ) => $_factory->functions[$__key](
                $value
              );
              return $__functions;
            },
            []
          )
        ],
        []
      )
      // Reduce the functions of current factory into the archive array.
      : array_reduce(
        (new \ReflectionClass($this))->getMethods(\ReflectionMethod::IS_PUBLIC),
        function ($_functions, $_method) use ($xmlTagNameSupport) {
          if ($_method->name !== '__construct' && $_method->name !== '__destruct') {
            $function = fn(mixed $value) => [self::class, $_method->name](
              $value
            );
            $_functions[$_method->name] = $function;
            if ($xmlTagNameSupport) {
              $xmlTagName = StringCase::toKebabCase($_method->name);
              if ($xmlTagName !== $_method->name) {
                $_functions[$xmlTagName] = $function;
              }
            }
          }
          return $_functions;
        },
        []
      );
    $this->functions = [
      ...$functions,
      ...array_reduce(
        array_keys($this->aliases),
        function (array $_functions, string $_alias) {
          $function = fn(mixed $value) => [
            self::class,
            $this->aliases[$_alias]
          ]($value);
          $_functions[$_alias] = $function;
          return $_functions;
        },
        []
      )
    ];
  }
}
