<?php

namespace App\Domain\Vacancy;

/**
 * Why a vacancy stopped receiving applications.
 */
enum VacancyCloseReason: string
{
  case Manual = 'manual';
  case Deadline = 'prazo';
  case Filled = 'preenchida';

  public static function fromStored(?string $value): ?self
  {
    return $value === null ? null : self::tryFrom($value);
  }

  public function label(): string
  {
    return match ($this) {
      self::Manual => 'Encerrada pela empresa',
      self::Deadline => 'Prazo encerrado',
      self::Filled => 'Posições preenchidas',
    };
  }
}
