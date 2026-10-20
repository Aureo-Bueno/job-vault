<?php

namespace App\Domain\Role;

use App\Domain\Shared\Guard;

final class RoleName implements \Stringable
{
  public const ADMIN = 'admin';
  public const DEFAULT_USER = 'usuario';
  private const MAX_LENGTH = 100;

  private function __construct(private string $value)
  {
  }

  public static function fromString(string $value): self
  {
    return new self(Guard::requiredText(
      $value,
      self::MAX_LENGTH,
      'O nome da role é obrigatório.',
      'O nome da role deve ter no máximo ' . self::MAX_LENGTH . ' caracteres.'
    ));
  }

  public static function admin(): self
  {
    return new self(self::ADMIN);
  }

  /**
   * Role given to self-registered users when none is chosen.
   */
  public static function defaultUser(): self
  {
    return new self(self::DEFAULT_USER);
  }

  public function isAdmin(): bool
  {
    return $this->value === self::ADMIN;
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
