<?php

namespace App\Domain\ValueObject;

final class SearchTerm
{
  private string $value;

  private function __construct(string $value)
  {
    $this->value = $value;
  }

  public static function fromString(string $value): ?self
  {
    $normalizedValue = trim($value);
    if ($normalizedValue === '') {
      return null;
    }

    return new self($normalizedValue);
  }

  public function toSqlLikePattern(): string
  {
    return '%' . str_replace(' ', '%', $this->value) . '%';
  }

  public function __toString(): string
  {
    return $this->value;
  }
}
