<?php

namespace App\Domain\ValueObject;

final class EmailAddress
{
  private string $value;

  private function __construct(string $value)
  {
    $this->value = $value;
  }

  public static function fromString(string $value): ?self
  {
    $normalizedValue = strtolower(trim($value));
    if (!filter_var($normalizedValue, FILTER_VALIDATE_EMAIL)) {
      return null;
    }

    return new self($normalizedValue);
  }

  public function equals(self $other): bool
  {
    return $this->value === (string) $other;
  }

  public function __toString(): string
  {
    return $this->value;
  }
}
