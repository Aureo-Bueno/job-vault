<?php

namespace App\Domain\User;

use App\Domain\Exception\InvalidValueException;

/**
 * Password typed by a user, validated against the password policy.
 * Never persisted: it is only used to build a PasswordHash.
 */
final class PlainPassword
{
  public const MIN_LENGTH = 6;
  /** bcrypt silently truncates anything above 72 bytes */
  public const MAX_LENGTH = 72;

  private function __construct(private string $value)
  {
  }

  public static function fromString(string $value): self
  {
    if (strlen($value) < self::MIN_LENGTH) {
      throw new InvalidValueException('A senha deve ter no mínimo ' . self::MIN_LENGTH . ' caracteres.');
    }

    if (strlen($value) > self::MAX_LENGTH) {
      throw new InvalidValueException('A senha deve ter no máximo ' . self::MAX_LENGTH . ' caracteres.');
    }

    return new self($value);
  }

  public function value(): string
  {
    return $this->value;
  }

  public function __toString(): string
  {
    return '********';
  }

  public function __debugInfo(): array
  {
    return ['value' => '********'];
  }
}
