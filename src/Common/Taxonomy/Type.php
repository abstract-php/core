<?php

namespace Abstract\Common\Taxonomy;

use Abstract\Common\Convertor\StringCase;

class Type {

  public static function of(mixed $value): string {
    return gettype($value) === 'object'
    ? (
      get_class($value) === 'stdClass'
      ? 'object'
      : get_class($value)
    )
    : (
      gettype($value) === 'NULL'
      ? 'null'
      : StringCase::toCamelCase(gettype($value))
    );
  }
}
