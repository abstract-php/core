<?php

namespace Abstract\Resolvers;

use ReflectionNamedType;
use ReflectionUnionType;
use ReflectionIntersectionType;

/**
 * Argument Resolver
 */
class ArgumentResolver {

  /**
   * @param array $parameters
   * @param array $arguments
   * @return mixed
   */
  public static function resolve(?array $parameters = [], ?array $arguments = []): mixed {

    $variables = [];
    if (is_array($parameters)) {
      $variables = array_map(
        function ($parameter) use ($arguments) {

          extract($arguments);

          $isOptional = $parameter->isOptional();
          $defaultValue = $isOptional ? $parameter->getDefaultValue() : null;
          $value = isset($arguments[$parameter->getName()])
            ? $arguments[$parameter->getName()]
            : $defaultValue;

          $types = [];
          $type = $parameter->getType();
          if ($type !== null) {
            if ($type instanceof ReflectionNamedType) {
              $types = [$type->getName()];
            } elseif ($type instanceof ReflectionUnionType) {
              foreach ($type->getTypes() as $subType) {
                $types[] = $subType->getName();
              }
            } elseif ($type instanceof ReflectionIntersectionType) {
              foreach ($type->getTypes() as $subType) {
                $types[] = $subType;
              }
            } else {
              $types = [$type];
            }
          }

          // Attach
          if (!$isIntersection) {
            if (in_array('bool', $types) && is_bool($value)) {
              $value = static::bool($value);
            } elseif (in_array('array', $types) && is_array($value)) {
              $value = static::array($value);
            } elseif (in_array('object', $types) && is_object($value)) {
              $value = static::object($value);
            } elseif (in_array('callable', $types) && is_callable($value)) {
            } elseif ($isOptional && (empty($value) || $value === $defaultValue)) {
            }
          }

          return $value;
        },
        $parameters
      );
    }

    return $variables;
  }

  /**
   * @param string|bool|null $value
   * @return bool
   */
  public static function bool($value): bool {
    if (!is_bool($value)) {
      if ($value === 1 || $value === '1' || $value === 'true') {
        $value = true;
      } elseif ($value === 0 || $value === '0' || $value === 'false') {
        $value = false;
      } else {
        $value = false;
      }
    }
    return $value;
  }

  /**
   * @param string|int|bool|array|object|null $value
   * @return array
   */
  public static function array($value): array {
    if (!is_array($value)) {
      $value = (array) $value;
    }
    return $value;
  }

  /**
   * @param string|int|bool|array|object|null $value
   * @return array
   */
  public static function object($value): object {
    if (!is_object($value)) {
      $value = (object) $value;
    }
    return $value;
  }
}
