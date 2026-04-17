<?php

namespace App\Domain\ValueObject;

final class PlainPassword
{
  private const MIN_LENGTH = 6;

  private string $value;

  private function __construct(string $value)
  {
    $this->value = $value;
  }

  public static function fromString(string $value): ?self
  {
    if (strlen($value) < self::MIN_LENGTH) {
      return null;
    }

    return new self($value);
  }

  public static function minimumLength(): int
  {
    return self::MIN_LENGTH;
  }

  public function hash(): string
  {
    return password_hash($this->value, PASSWORD_DEFAULT);
  }

  public function matchesHash(string $hash): bool
  {
    return password_verify($this->value, $hash);
  }
}
