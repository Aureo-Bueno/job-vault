<?php

namespace App\Domain\Shared;

use App\Domain\Exception\InvalidValueException;

enum Seniority: string
{
  case Estagio = 'estagio';
  case Junior = 'junior';
  case Pleno = 'pleno';
  case Senior = 'senior';
  case Especialista = 'especialista';

  public static function fromString(string $value): self
  {
    $normalized = strtolower(trim($value));
    $seniority = self::tryFrom($normalized);
    if ($seniority === null) {
      throw new InvalidValueException('Nível de senioridade inválido.');
    }

    return $seniority;
  }

  public static function fromStored(?string $value): self
  {
    if ($value === null || trim($value) === '') {
      return self::Pleno;
    }

    return self::tryFrom(strtolower(trim($value))) ?? self::Pleno;
  }

  public function label(): string
  {
    return match ($this) {
      self::Estagio => 'Estágio',
      self::Junior => 'Júnior',
      self::Pleno => 'Pleno',
      self::Senior => 'Sênior',
      self::Especialista => 'Especialista',
    };
  }

  public function level(): int
  {
    return match ($this) {
      self::Estagio => 1,
      self::Junior => 2,
      self::Pleno => 3,
      self::Senior => 4,
      self::Especialista => 5,
    };
  }

  /**
   * Affinity ratio (0.0 to 1.0) between candidate seniority ($this) and required seniority ($required).
   */
  public function affinityWith(self $required): float
  {
    $diff = $this->level() - $required->level();
    if ($diff === 0 || $diff === 1) {
      return 1.0;
    }

    if ($diff === -1 || $diff === 2) {
      return 0.5;
    }

    return 0.15;
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
