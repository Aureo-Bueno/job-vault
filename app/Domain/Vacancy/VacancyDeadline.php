<?php

namespace App\Domain\Vacancy;

use App\Domain\Exception\InvalidValueException;
use DateTimeImmutable;
use DateTimeZone;

/**
 * Last day to apply. Applications are accepted until the end of that day,
 * Brasília time, regardless of the server timezone.
 */
final class VacancyDeadline
{
  public const TIMEZONE = 'America/Sao_Paulo';

  private function __construct(private string $date)
  {
  }

  public static function fromString(string $value): self
  {
    $value = trim($value);
    $parsed = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
    if ($parsed === false || $parsed->format('Y-m-d') !== $value) {
      throw new InvalidValueException('Informe o prazo de inscrição como uma data válida.');
    }

    return new self($value);
  }

  /**
   * Empty input means "no deadline".
   */
  public static function fromInput(?string $value): ?self
  {
    return trim((string) $value) === '' ? null : self::fromString((string) $value);
  }

  public static function fromStored(mixed $value): ?self
  {
    if (!is_string($value) || trim($value) === '') {
      return null;
    }

    try {
      return self::fromString(substr(trim($value), 0, 10));
    } catch (InvalidValueException) {
      return null;
    }
  }

  /**
   * Today's date (Y-m-d) in the deadline timezone.
   */
  public static function today(?DateTimeImmutable $now = null): string
  {
    return ($now ?? new DateTimeImmutable())->setTimezone(new DateTimeZone(self::TIMEZONE))->format('Y-m-d');
  }

  public function hasPassed(?DateTimeImmutable $now = null): bool
  {
    return self::today($now) > $this->date;
  }

  public function value(): string
  {
    return $this->date;
  }

  public function label(): string
  {
    return substr($this->date, 8, 2) . '/' . substr($this->date, 5, 2) . '/' . substr($this->date, 0, 4);
  }

  public function equals(?self $other): bool
  {
    return $other !== null && $other->date === $this->date;
  }
}
