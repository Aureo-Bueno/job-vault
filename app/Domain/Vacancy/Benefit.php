<?php

namespace App\Domain\Vacancy;

use App\Domain\Exception\InvalidValueException;
use App\Domain\Shared\Slug;

/**
 * Free-form benefit tag ("Plano de saúde", "Vale-refeição", ...), deduplicated by slug.
 */
final class Benefit implements \Stringable
{
  public const MAX_LENGTH = 60;
  public const MAX_PER_VACANCY = 20;

  private function __construct(
    private string $slug,
    private string $label
  ) {
  }

  public static function fromLabel(string $label): self
  {
    $clean = preg_replace('/\s+/u', ' ', trim($label)) ?? '';
    if ($clean === '') {
      throw new InvalidValueException('O benefício não pode ser vazio.');
    }

    if (mb_strlen($clean) > self::MAX_LENGTH) {
      throw new InvalidValueException('Cada benefício deve ter no máximo ' . self::MAX_LENGTH . ' caracteres.');
    }

    $slug = Slug::of($clean);
    if ($slug === '') {
      throw new InvalidValueException('Benefício inválido: ' . $clean);
    }

    return new self($slug, $clean);
  }

  public static function reconstitute(string $slug, string $label): self
  {
    $label = trim($label);
    $slug = trim($slug);

    return new self($slug !== '' ? $slug : Slug::of($label), $label);
  }

  /**
   * Parses a comma/semicolon/newline separated string or a list of strings/Benefits.
   *
   * @param string|iterable<mixed> $raw
   * @return list<self>
   */
  public static function listFromInput(string|iterable $raw): array
  {
    $parts = [];
    foreach (is_string($raw) ? [$raw] : $raw as $item) {
      if ($item instanceof self) {
        $parts[] = $item->label;
      } elseif (is_string($item)) {
        array_push($parts, ...(preg_split('/[,;\n\r]+/u', $item) ?: []));
      }
    }

    $bySlug = [];
    foreach ($parts as $part) {
      if (trim($part) === '') {
        continue;
      }

      $benefit = self::fromLabel($part);
      $bySlug[$benefit->slug] ??= $benefit;
    }

    if (count($bySlug) > self::MAX_PER_VACANCY) {
      throw new InvalidValueException('Informe no máximo ' . self::MAX_PER_VACANCY . ' benefícios.');
    }

    return array_values($bySlug);
  }

  public function slug(): string
  {
    return $this->slug;
  }

  public function label(): string
  {
    return $this->label;
  }

  public function __toString(): string
  {
    return $this->label;
  }
}
