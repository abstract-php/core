<?php

namespace Abstract\Common\Convertor;

class Unicode {

  public static function fromCharacter($char) {
    // Get the Unicode code point of the character
    $codepoint = mb_ord($char, 'UTF-8');

    // Convert the code point to a 4-digit hexadecimal string, padded with zeros
    return 'u' . strtoupper(str_pad(dechex($codepoint), 4, '0', STR_PAD_LEFT));
  }

  public static function fromString(string $string, array $exclude = []): string {
    return array_reduce(
      mb_str_split($string, 1, 'UTF-8'),
      function ($result, $char) use ($exclude) {
        return $result . (in_array($char, $exclude) ? $char : self::fromCharacter($char));
      },
      ''
    );
  }

  public static function toCharacter($unicodeEscape) {
    // Remove the leading 'u' and convert the remaining hexadecimal part to decimal
    $codepoint = hexdec(substr($unicodeEscape, 1));

    // Convert the decimal code point back to a character
    return mb_chr($codepoint, 'UTF-8');
  }

  public static function toString($unicodeString) {
    // Match all 'uXXXX' patterns using regex
    return preg_replace_callback('/u[0-9A-Fa-f]{4}/', function ($matches) {
      // Convert each matched 'uXXXX' back to a character
      return self::toCharacter($matches[0]);
    }, $unicodeString);
  }
}
