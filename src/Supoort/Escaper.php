<?php

declare(strict_types=1);

namespace Botforge\Support;

class Escaper
{
  public static function html(string $text): string
  {
    return htmlspecialchars(
      $text,
      ENT_QUOTES | ENT_SUBSTITUTE,
      'UTF-8'
    );
  }

  public static function markdownV2(string $text): string
  {
    $specialCharacters = [
      '\\' => '\\\\',
      '_' => '\\_',
      '*' => '\\*',
      '[' => '\\[',
      ']' => '\\]',
      '(' => '\\(',
      ')' => '\\)',
      '~' => '\\~',
      '`' => '\\`',
      '>' => '\\>',
      '#' => '\\#',
      '+' => '\\+',
      '-' => '\\-',
      '=' => '\\=',
      '|' => '\\|',
      '{' => '\\{',
      '}' => '\\}',
      '.' => '\\.',
      '!' => '\\!',
    ];

    return strtr($text, $specialCharacters);
  }
}
