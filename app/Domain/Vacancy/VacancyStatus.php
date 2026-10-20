<?php

namespace App\Domain\Vacancy;

use App\Domain\Exception\InvalidValueException;

/**
 * Lifecycle of a vacancy:
 *
 *   rascunho ──publicar──► aberta ◄──reabrir── encerrada
 *                            └──────encerrar──────►
 *
 * Only open vacancies are visible to candidates and accept applications.
 * A published vacancy never goes back to draft.
 */
enum VacancyStatus: string
{
  case Draft = 'rascunho';
  case Open = 'aberta';
  case Closed = 'encerrada';

  public static function fromString(string $value): self
  {
    return self::tryFrom(strtolower(trim($value)))
      ?? throw new InvalidValueException('Status da vaga inválido.');
  }

  /**
   * Unknown stored values are treated as closed (hidden from candidates).
   */
  public static function fromStored(?string $value): self
  {
    return self::tryFrom(strtolower(trim((string) $value))) ?? self::Closed;
  }

  public function isOpen(): bool
  {
    return $this === self::Open;
  }

  public function label(): string
  {
    return match ($this) {
      self::Draft => 'Rascunho',
      self::Open => 'Aberta',
      self::Closed => 'Encerrada',
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
