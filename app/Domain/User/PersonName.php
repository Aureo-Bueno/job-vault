<?php

namespace App\Domain\User;

use App\Domain\Shared\Guard;

final class PersonName implements \Stringable
{
  private const MAX_LENGTH = 255;

  private function __construct(private string $value)
  {
  }

  public static function fromString(string $value): self
  {
    return new self(Guard::requiredText(
      $value,
      self::MAX_LENGTH,
      'Nome é obrigatório.',
      'Nome deve ter no máximo ' . self::MAX_LENGTH . ' caracteres.'
    ));
  }

  public function initial(): string
  {
    return mb_strtoupper(mb_substr($this->value, 0, 1));
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
