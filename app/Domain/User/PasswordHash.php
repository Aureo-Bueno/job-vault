<?php

namespace App\Domain\User;

use App\Domain\Exception\InvalidValueException;

/**
 * One-way hash of a user password.
 */
final class PasswordHash
{
  private function __construct(private string $hash)
  {
  }

  public static function fromPlain(PlainPassword $password): self
  {
    return new self(password_hash($password->value(), PASSWORD_DEFAULT));
  }

  public static function fromStored(string $hash): self
  {
    if (trim($hash) === '') {
      throw new InvalidValueException('Hash de senha inválido.');
    }

    return new self($hash);
  }

  public function matches(string $plainPassword): bool
  {
    return password_verify($plainPassword, $this->hash);
  }

  public function value(): string
  {
    return $this->hash;
  }
}
