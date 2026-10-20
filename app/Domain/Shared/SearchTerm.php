<?php

namespace App\Domain\Shared;

/**
 * Free-text search term. Empty input means "no search".
 */
final class SearchTerm implements \Stringable
{
  private function __construct(private string $value)
  {
  }

  public static function tryFrom(?string $value): ?self
  {
    $normalized = trim((string) $value);
    return $normalized === '' ? null : new self($normalized);
  }

  /**
   * Splits the term into words, useful for "all words must match" searches.
   *
   * @return string[]
   */
  public function words(): array
  {
    return preg_split('/\s+/', $this->value) ?: [];
  }

  public function matches(string ...$haystacks): bool
  {
    $text = mb_strtolower(implode(' ', $haystacks));
    return str_contains($text, mb_strtolower($this->value));
  }

  public function value(): string
  {
    return $this->value;
  }

  public function __toString(): string
  {
    return $this->value;
  }
}
