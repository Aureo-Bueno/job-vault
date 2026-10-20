<?php

namespace App\Domain\Shared;

use App\Domain\Exception\InvalidValueException;

enum WorkMode: string
{
  case Remoto = 'remoto';
  case Hibrido = 'hibrido';
  case Presencial = 'presencial';

  public static function fromString(string $value): self
  {
    $normalized = strtolower(trim($value));
    $mode = self::tryFrom($normalized);
    if ($mode === null) {
      throw new InvalidValueException('Modalidade de trabalho inválida.');
    }

    return $mode;
  }

  public static function fromStored(?string $value): self
  {
    if ($value === null || trim($value) === '') {
      return self::Remoto;
    }

    return self::tryFrom(strtolower(trim($value))) ?? self::Remoto;
  }

  public function label(): string
  {
    return match ($this) {
      self::Remoto => 'Remoto',
      self::Hibrido => 'Híbrido',
      self::Presencial => 'Presencial',
    };
  }

  /**
   * Affinity ratio (0.0 to 1.0) between candidate preference ($this) and vacancy mode ($vacancyMode).
   */
  public function affinityWith(self $vacancyMode): float
  {
    if ($this === $vacancyMode) {
      return 1.0;
    }

    if ($this === self::Hibrido || $vacancyMode === self::Remoto) {
      return 0.7;
    }

    return 0.25;
  }

  /**
   * @return array<string,string>
   */
  public static function options(): array
  {
    $options = [];
    foreach (self::cases() as $case) {
      $options[$case->value] = $case->label();
    }

    return $options;
  }
}
