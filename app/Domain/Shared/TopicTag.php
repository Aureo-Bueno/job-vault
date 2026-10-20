<?php

namespace App\Domain\Shared;

use App\Domain\Exception\InvalidValueException;

/**
 * Normalized topic/skill tag shared by candidate profiles and job vacancies.
 */
final class TopicTag implements \Stringable
{
  private const MAX_LENGTH = 60;

  private const ALIASES = [
    '.net' => 'dotnet',
    'net' => 'dotnet',
    'node' => 'nodejs',
    'reactjs' => 'react',
    'vue' => 'vuejs',
    'postgres' => 'postgresql',
    'js' => 'javascript',
    'ts' => 'typescript',
    'phpoo' => 'poo',
    'orientacao-a-objetos' => 'poo',
    'rest' => 'api-rest',
    'restful' => 'api-rest',
    'rest-api' => 'api-rest',
  ];

  private function __construct(
    private string $slug,
    private string $label
  ) {
  }

  public static function fromLabel(string $label): self
  {
    $cleanLabel = preg_replace('/\s+/u', ' ', trim($label)) ?? '';
    if ($cleanLabel === '') {
      throw new InvalidValueException('O tópico não pode ser vazio.');
    }

    if (mb_strlen($cleanLabel) > self::MAX_LENGTH) {
      throw new InvalidValueException('Cada tópico deve ter no máximo ' . self::MAX_LENGTH . ' caracteres.');
    }

    $slug = self::normalizeSlug($cleanLabel);
    if ($slug === '') {
      throw new InvalidValueException('Tópico inválido: ' . $cleanLabel);
    }

    return new self($slug, $cleanLabel);
  }

  public static function reconstitute(string $slug, string $label): self
  {
    $cleanLabel = trim($label);
    $cleanSlug = self::normalizeSlug($slug !== '' ? $slug : $cleanLabel);

    return new self($cleanSlug !== '' ? $cleanSlug : strtolower($cleanLabel), $cleanLabel);
  }

  /**
   * Parses a comma/newline separated string or array of strings into deduplicated TopicTags.
   *
   * @param string| iterable<mixed> $raw
   * @return list<self>
   */
  public static function listFromInput(string|iterable $raw): array
  {
    if (is_string($raw)) {
      $parts = preg_split('/[,;\n\r]+/u', $raw) ?: [];
    } else {
      $parts = [];
      foreach ($raw as $item) {
        if ($item instanceof self) {
          $parts[] = $item->label;
        } elseif (is_string($item)) {
          foreach (preg_split('/[,;\n\r]+/u', $item) ?: [] as $sub) {
            $parts[] = $sub;
          }
        }
      }
    }

    $bySlug = [];
    foreach ($parts as $part) {
      $trimmed = trim((string) $part);
      if ($trimmed === '') {
        continue;
      }

      $tag = self::fromLabel($trimmed);
      $bySlug[$tag->slug] ??= $tag;
    }

    return array_values($bySlug);
  }

  public static function normalizeSlug(string $value): string
  {
    $lower = mb_strtolower(trim($value), 'UTF-8');
    $transliterated = strtr($lower, [
      'á' => 'a', 'à' => 'a', 'ã' => 'a', 'â' => 'a', 'ä' => 'a',
      'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e',
      'í' => 'i', 'ì' => 'i', 'î' => 'i', 'ï' => 'i',
      'ó' => 'o', 'ò' => 'o', 'õ' => 'o', 'ô' => 'o', 'ö' => 'o',
      'ú' => 'u', 'ù' => 'u', 'û' => 'u', 'ü' => 'u',
      'ç' => 'c', 'ñ' => 'n',
    ]);

    if (isset(self::ALIASES[$transliterated])) {
      return self::ALIASES[$transliterated];
    }

    $withoutDots = str_replace('.', '', $transliterated);
    $dashed = preg_replace('/[\s\/_]+/', '-', $withoutDots) ?? $withoutDots;
    $cleaned = preg_replace('/[^a-z0-9#+-]/', '', $dashed) ?? '';
    $slug = trim((string) preg_replace('/-+/', '-', $cleaned), '-');

    return self::ALIASES[$slug] ?? $slug;
  }

  public function slug(): string
  {
    return $this->slug;
  }

  public function label(): string
  {
    return $this->label;
  }

  public function matches(self $other): bool
  {
    return $this->slug === $other->slug;
  }

  public function __toString(): string
  {
    return $this->label;
  }
}
