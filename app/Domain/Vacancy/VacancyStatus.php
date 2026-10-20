<?php

namespace App\Domain\Vacancy;

enum VacancyStatus: string
{
  case Active = 'active';
  case Inactive = 'inactive';

  public static function fromBool(bool $active): self
  {
    return $active ? self::Active : self::Inactive;
  }

  public function isActive(): bool
  {
    return $this === self::Active;
  }
}
