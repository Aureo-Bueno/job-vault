<?php

namespace App\Domain\Vacancy;

use App\Domain\Shared\Guard;

final class VacancyDescription implements \Stringable
{
  /** MySQL TEXT column limit */
  private const MAX_LENGTH = 65535;

  private function __construct(private string $value)
  {
  }

  public static function fromString(string $value): self
  {
    return new self(Guard::requiredText(
      $value,
      self::MAX_LENGTH,
      'A descrição da vaga é obrigatória.',
      'A descrição da vaga é muito longa.'
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
