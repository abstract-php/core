<?php

namespace Abstract\Supports\JsonLogic\Components;

use Abstract\Abstraction;

class Logic extends Abstraction {

  /** @var string */
  public string $name;

  /** @var string */
  public static string $alias = ':';

  public static array $names = [
    '==' => 'equal',
    '===' => 'strictly-equal',
    '!=' => 'not-equal',
    '!==' => 'strictly-not-equal',
    '!' => 'not',
    '!!' => 'coercion',
    '>' => 'much-than',
    '>=' => 'much-or-equal-than',
    '<' => 'less-than',
    '<=' => 'less-or-equal-than',
    '+' => 'plus',
    '-' => 'minus',
    '*' => 'multiply-by',
    '/' => 'divide-by',
    '%' => 'modulus',
  ];


  public function __construct(
    Abstraction ...$children
  ) {
    parent::__construct(...$children);
  }

  public static function var(): Logic {
    return Logic::withName('var');
  }

  public static function missing(): Logic {
    return Logic::withName('var');
  }

  public static function if(
    Abstraction $statement,
    Abstraction $consequent,
    Abstraction $alternative
  ): Logic {
    return Logic::withName(
      'if', 
      $statement,
      $consequent, 
      $alternative
    );
  }

  public static function equal(
    Abstraction ...$values
  ): Logic {
    return Logic::withName(
      'equal', 
      ...$values
    );
  }

  public static function strictlyEqual(
    Abstraction ...$values
  ): Logic {
    return new Logic(
      'strictly-equal', 
      ...$values
    );
  }

  public static function notEqual(
    Abstraction ...$values
  ): Logic {
    return new Logic(
      'if', 
      ...$values
    );
  }

  public static function not(): Logic {
    return Logic::withName('var');
  }

  public static function coercion(): Logic {
    return Logic::withName('var');
  }

  public static function or(): Logic {
    return Logic::withName('var');
  }

  public static function and(): Logic {
    return Logic::withName('var');
  }

  public static function much($than, $equal): Logic {
    return Logic::withName('var');
  }

  public static function less($than, $equal): Logic {
    return Logic::withName('var');
  }

  public static function max(): Logic {
    return Logic::withName('var');
  }

  public static function min(): Logic {
    return Logic::withName('var');
  }

  public static function plus(): Logic {
    return Logic::withName('var');
  }

  public static function minus(): Logic {
    return Logic::withName('var');
  }

  public static function multiply($by): Logic {
    return Logic::withName('var');
  }

  public static function divide($by): Logic {
    return Logic::withName('var');
  }

  public static function modulas(): Logic {
    return Logic::withName('var');
  }

  public static function map(): Logic {
    return Logic::withName('var');
  }

  public static function reduce(): Logic {
    return Logic::withName('var');
  }

  public static function filter(): Logic {
    return Logic::withName('var');
  }

  public static function all(): Logic {
    return Logic::withName('var');
  }

  public static function none(): Logic {
    return Logic::withName('var');
  }

  public static function some(): Logic {
    return Logic::withName('var');
  }

  public static function merge(): Logic {
    return Logic::withName('var');
  }

  public static function in(): Logic {
    return Logic::withName('var');
  }

  public static function cat(): Logic {
    return Logic::withName('var');
  }

  public static function substr(): Logic {
    return Logic::withName('var');
  }

  public static function log(): Logic {
    return Logic::withName('var');
  }
}
