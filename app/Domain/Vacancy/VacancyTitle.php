<?php

namespace App\Domain\Vacancy;

use App\Domain\Shared\Guard;

final class VacancyTitle implements \Stringable
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
      'O título da vaga é obrigatório.',
      'O título da vaga deve ter no máximo ' . self::MAX_LENGTH . ' caracteres.'
    ));
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
