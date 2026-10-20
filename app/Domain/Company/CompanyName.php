<?php

namespace App\Domain\Company;

use App\Domain\Shared\Guard;

final class CompanyName implements \Stringable
{
  private const MAX_LENGTH = 150;

  private function __construct(private string $value)
  {
  }

  public static function fromString(string $value): self
  {
    return new self(Guard::requiredText(
      $value,
      self::MAX_LENGTH,
      'O nome da empresa é obrigatório.',
      'O nome da empresa deve ter no máximo ' . self::MAX_LENGTH . ' caracteres.'
    ));
  }

  public function equals(self $other): bool
  {
    return mb_strtolower($this->value, 'UTF-8') === mb_strtolower($other->value, 'UTF-8');
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
