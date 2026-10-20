<?php

namespace App\Domain\Vacancy;

use App\Domain\Exception\InvalidValueException;

/**
 * Hiring regime offered by a vacancy.
 */
enum ContractType: string
{
  case Clt = 'clt';
  case Pj = 'pj';
  case Internship = 'estagio';
  case Temporary = 'temporario';
  case Freelancer = 'freelancer';

  public static function fromString(string $value): self
  {
    return self::tryFrom(strtolower(trim($value)))
      ?? throw new InvalidValueException('Tipo de contrato inválido.');
  }

  public static function fromStored(?string $value): self
  {
    return self::tryFrom(strtolower(trim((string) $value))) ?? self::Clt;
  }

  public function label(): string
  {
    return match ($this) {
      self::Clt => 'CLT',
      self::Pj => 'PJ',
      self::Internship => 'Estágio',
      self::Temporary => 'Temporário',
      self::Freelancer => 'Freelancer',
    };
  }

  /**
   * @return array<string,string> value => label
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
