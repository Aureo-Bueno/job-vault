<?php

namespace App\Domain\User;

use App\Domain\Exception\InvalidValueException;

final class EmailAddress implements \Stringable
{
  private const MAX_LENGTH = 255;

  private function __construct(private string $value)
  {
  }

  public static function fromString(string $value): self
  {
    $normalized = strtolower(trim($value));
    if (
      $normalized === ''
      || strlen($normalized) > self::MAX_LENGTH
      || !filter_var($normalized, FILTER_VALIDATE_EMAIL)
    ) {
      throw new InvalidValueException('E-mail inválido.');
    }

    return new self($normalized);
  }

  public static function tryFrom(string $value): ?self
  {
    try {
      return self::fromString($value);
    } catch (InvalidValueException) {
      return null;
    }
  }

  public function equals(self $other): bool
  {
    return $this->value === $other->value;
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
