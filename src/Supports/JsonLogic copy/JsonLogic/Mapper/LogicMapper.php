<?php

namespace Abstract\Supports\JsonLogic\Mapper;

use Abstract\Mapper;
use Abstract\Common\Convertor\StringCase;
use Abstract\Abstractions\Arguments;

use Abstract\Abstraction;
use Abstract\Supports\JsonLogic\Components\Logic;

use ReflectionClass;
use ReflectionMethod;

use Throwable;
use Exception;
use Abstract\Mapper\FallbackFactory;
use Abstract\MapperArgument;

class LogicMapper extends Mapper {

  public static array $names = [
    'var' => ':var',
    'missing' => ':missing',
    'missing_some' => ':missing_some',
    '==' => ':equal',
    '===' => ':strictly-equal',
    '!=' => ':not-equal',
    '!==' => ':strictly-not-equal',
    '!' => ':not',
    '!!' => ':coercion',
    'or' => ':or',
    'and' => ':and',
    '>' => ':much-than',
    '>=' => ':much-or-equal-than',
    '<' => ':less-than',
    '<=' => ':less-or-equal-than',
    'max' => ':max',
    'min' => ':min',
    '+' => ':plus',
    '-' => ':minus',
    '*' => ':multiply-by',
    '/' => ':divide-by',
    '%' => ':modulus',
    'map' => ':map',
    'reduce' => ':reduce',
    'filter' => ':filter',
    'all' => ':all',
    'none' => ':none',
    'some' => ':some',
    'merge' => ':merge',
    'in' => ':in',
    'cat' => ':cat',
    'substr' => ':substr',
    'log' => ':log'
  ];

  private static $aliasRuleCallback; 

  public function __construct() {
    static::$aliasRuleCallback = fn ($alias): string => $alias;
    parent::__construct();
    var_dump(9999999);
  }

  public static function var(): FallbackFactory {
    return FallbackFactory::withAliasRule(
      StringCase::toKebabCase(__FUNCTION__), 
      Logic::var(),
      static::$aliasRuleCallback
    );
  }

  public static function missing(): FallbackFactory {
    return FallbackFactory::withAliasRule(
      StringCase::toKebabCase(__FUNCTION__), 
      Logic::missing(),
      static::$aliasRuleCallback
    );
  }

  public static function if(
    MapperArgument $argument
  ): FallbackFactory {
    return FallbackFactory::withAliasRule(
      StringCase::toKebabCase(__FUNCTION__), 
      Logic::if(
        $argument[0],
        $argument[1], 
        $argument[2]
      ),
      static::$aliasRuleCallback
    );
  }

  public static function equal(
    MapperArgument $argument
  ): FallbackFactory {
    return FallbackFactory::withAliasRule(
      StringCase::toKebabCase(__FUNCTION__), 
      Logic::equal(),
      static::$aliasRuleCallback
    );
  }

  public static function strictlyEqual(
    MapperArgument $argument
  ): FallbackFactory {
    return FallbackFactory::withAliasRule(
      StringCase::toKebabCase(__FUNCTION__), 
      Logic::strictlyEqual(),
      static::$aliasRuleCallback
    );
  }

  public static function notEqual(
    MapperArgument $argument
  ): FallbackFactory {
    return FallbackFactory::withAliasRule(
      StringCase::toKebabCase(__FUNCTION__), 
      Logic::notEqual(),
      static::$aliasRuleCallback
    );
  }

  public static function not(
    MapperArgument $argument
  ): FallbackFactory {
    return FallbackFactory::withAliasRule(
      StringCase::toKebabCase(__FUNCTION__), 
      Logic::not(),
      static::$aliasRuleCallback
    );
  }

  public static function coercion(
    MapperArgument $argument
  ): FallbackFactory {
    return FallbackFactory::withAliasRule(
      StringCase::toKebabCase(__FUNCTION__), 
      Logic::coercion(),
      static::$aliasRuleCallback
    );
  }

  public static function or(
    MapperArgument $argument
  ): FallbackFactory {
    return FallbackFactory::withAliasRule(
      StringCase::toKebabCase(__FUNCTION__), 
      Logic::or(),
      static::$aliasRuleCallback
    );
  }

  public static function and(
    MapperArgument $argument
  ): FallbackFactory {
    return FallbackFactory::withAliasRule(
      StringCase::toKebabCase(__FUNCTION__), 
      Logic::and(),
      static::$aliasRuleCallback
    );
  }

  public static function much(
    MapperArgument $argument
  ): FallbackFactory {
    return FallbackFactory::withAliasRule(
      StringCase::toKebabCase(__FUNCTION__), 
      Logic::var(),
      static::$aliasRuleCallback
    );
  }

  public static function less(
    MapperArgument $argument
  ): FallbackFactory {
    return FallbackFactory::withAliasRule(
      StringCase::toKebabCase(__FUNCTION__), 
      Logic::var(),
      static::$aliasRuleCallback
    );
  }

  public static function max(
    MapperArgument $argument
  ): FallbackFactory {
    return FallbackFactory::withAliasRule(
      StringCase::toKebabCase(__FUNCTION__), 
      Logic::max(),
      static::$aliasRuleCallback
    );
  }

  public static function min(
    MapperArgument $argument
  ): FallbackFactory {
    return FallbackFactory::withAliasRule(
      StringCase::toKebabCase(__FUNCTION__), 
      Logic::min(),
      static::$aliasRuleCallback
    );
  }

  public static function plus(
    MapperArgument $argument
  ): FallbackFactory {
    return FallbackFactory::withAliasRule(
      StringCase::toKebabCase(__FUNCTION__), 
      Logic::plus(),
      static::$aliasRuleCallback
    );
  }

  public static function minus(
    MapperArgument $argument
  ): FallbackFactory {
    return FallbackFactory::withAliasRule(
      StringCase::toKebabCase(__FUNCTION__), 
      Logic::minus(),
      static::$aliasRuleCallback
    );
  }

  public static function multiply(
    MapperArgument $argument
  ): FallbackFactory {
    return FallbackFactory::withAliasRule(
      StringCase::toKebabCase(__FUNCTION__), 
      Logic::var(),
      static::$aliasRuleCallback
    );
  }

  public static function divide(
    MapperArgument $argument
  ): FallbackFactory {
    return FallbackFactory::withAliasRule(
      StringCase::toKebabCase(__FUNCTION__), 
      Logic::var(),
      static::$aliasRuleCallback
    );
  }

  public static function modulas(
    MapperArgument $argument
  ): FallbackFactory {
    return FallbackFactory::withAliasRule(
      StringCase::toKebabCase(__FUNCTION__), 
      Logic::modulas(),
      static::$aliasRuleCallback
    );
  }

  public static function map(
    MapperArgument $argument
  ): FallbackFactory {
    return FallbackFactory::withAliasRule(
      StringCase::toKebabCase(__FUNCTION__), 
      Logic::map(),
      static::$aliasRuleCallback
    );
  }

  public static function reduce(
    MapperArgument $argument
  ): FallbackFactory {
    return FallbackFactory::withAliasRule(
      StringCase::toKebabCase(__FUNCTION__), 
      Logic::reduce(),
      static::$aliasRuleCallback
    );
  }

  public static function filter(
    MapperArgument $argument
  ): FallbackFactory {
    return FallbackFactory::withAliasRule(
      StringCase::toKebabCase(__FUNCTION__), 
      Logic::filter(),
      static::$aliasRuleCallback
    );
  }

  public static function all(
    MapperArgument $argument
  ): FallbackFactory {
    return FallbackFactory::withAliasRule(
      StringCase::toKebabCase(__FUNCTION__), 
      Logic::all(),
      static::$aliasRuleCallback
    );
  }

  public static function none(
    MapperArgument $argument
  ): FallbackFactory {
    return FallbackFactory::withAliasRule(
      StringCase::toKebabCase(__FUNCTION__), 
      Logic::none(),
      static::$aliasRuleCallback
    );
  }

  public static function some(
    MapperArgument $argument
  ): FallbackFactory {
    return FallbackFactory::withAliasRule(
      StringCase::toKebabCase(__FUNCTION__), 
      Logic::some(),
      static::$aliasRuleCallback
    );
  }

  public static function merge(
    MapperArgument $argument
  ): FallbackFactory {
    return FallbackFactory::withAliasRule(
      StringCase::toKebabCase(__FUNCTION__), 
      Logic::merge(),
      static::$aliasRuleCallback
    );
  }

  public static function in(
    MapperArgument $argument
  ): FallbackFactory {
    return FallbackFactory::withAliasRule(
      StringCase::toKebabCase(__FUNCTION__), 
      Logic::in(),
      static::$aliasRuleCallback
    );
  }

  public static function cat(
    MapperArgument $argument
  ): FallbackFactory {
    return FallbackFactory::withAliasRule(
      StringCase::toKebabCase(__FUNCTION__), 
      Logic::cat(),
      static::$aliasRuleCallback
    );
  }

  public static function substr(
    MapperArgument $argument
  ): FallbackFactory {
    return FallbackFactory::withAliasRule(
      StringCase::toKebabCase(__FUNCTION__), 
      Logic::substr(),
      static::$aliasRuleCallback
    );
  }

  public static function log(
    MapperArgument $argument
  ): FallbackFactory {
    return FallbackFactory::withAliasRule(
      StringCase::toKebabCase(__FUNCTION__), 
      Logic::log(),
      static::$aliasRuleCallback
    );
  }

}
