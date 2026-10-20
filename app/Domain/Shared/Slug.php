<?php

namespace App\Domain\Shared;

/**
 * Accent-insensitive, lowercase slugs ("Plano de Saúde" -> "plano-de-saude").
 */
final class Slug
{
  private const TRANSLITERATION = [
    'á' => 'a', 'à' => 'a', 'ã' => 'a', 'â' => 'a', 'ä' => 'a',
    'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e',
    'í' => 'i', 'ì' => 'i', 'î' => 'i', 'ï' => 'i',
    'ó' => 'o', 'ò' => 'o', 'õ' => 'o', 'ô' => 'o', 'ö' => 'o',
    'ú' => 'u', 'ù' => 'u', 'û' => 'u', 'ü' => 'u',
    'ç' => 'c', 'ñ' => 'n',
  ];

  private function __construct()
  {
  }

  /**
   * Lowercase without accents (other characters are kept).
   */
  public static function fold(string $value): string
  {
    return strtr(mb_strtolower(trim($value), 'UTF-8'), self::TRANSLITERATION);
  }

  /**
   * Letters and digits only, words joined by "-".
   */
  public static function of(string $value): string
  {
    $dashed = preg_replace('/[^a-z0-9]+/', '-', self::fold($value)) ?? '';
    return trim($dashed, '-');
  }
}
