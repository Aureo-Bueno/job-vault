<?php

namespace App\Domain\Vacancy;

use App\Domain\Exception\InvalidValueException;

/**
 * How many people the company wants to hire for a vacancy.
 */
final class VacancyPositions
{
  private const MAX = 999;

  private function __construct(private int $value)
  {
  }

  public static function fromInt(int $value): self
  {
    if ($value < 1 || $value > self::MAX) {
      throw new InvalidValueException('O número de posições deve ser entre 1 e ' . self::MAX . '.');
    }

    return new self($value);
  }

  public static function one(): self
  {
    return new self(1);
  }

  public static function fromStored(mixed $value): self
  {
    $int = (int) $value;
    return new self($int >= 1 && $int <= self::MAX ? $int : 1);
  }

  public function value(): int
  {
    return $this->value;
  }

  public function areFilledBy(int $approved): bool
  {
    return $approved >= $this->value;
  }
}
