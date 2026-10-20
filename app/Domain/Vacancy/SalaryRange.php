<?php

namespace App\Domain\Vacancy;

use App\Domain\Exception\InvalidValueException;

/**
 * Monthly salary offered by a vacancy, in whole reais. Both ends are optional:
 * no values means "A combinar".
 */
final class SalaryRange
{
  public const MAX = 1_000_000;

  private function __construct(
    private ?int $min,
    private ?int $max
  ) {
  }

  public static function negotiable(): self
  {
    return new self(null, null);
  }

  public static function of(?int $min, ?int $max): self
  {
    foreach ([$min, $max] as $amount) {
      if ($amount !== null && ($amount < 1 || $amount > self::MAX)) {
        throw new InvalidValueException('O salário deve ser um valor mensal entre R$ 1 e R$ ' . self::format(self::MAX) . '.');
      }
    }

    if ($min !== null && $max !== null && $max < $min) {
      throw new InvalidValueException('O salário máximo não pode ser menor que o mínimo.');
    }

    return new self($min, $max);
  }

  /**
   * Lenient: invalid stored values become "A combinar".
   */
  public static function fromStored(mixed $min, mixed $max): self
  {
    $toAmount = static fn (mixed $v): ?int => is_numeric($v) && (int) $v >= 1 && (int) $v <= self::MAX ? (int) $v : null;
    $min = $toAmount($min);
    $max = $toAmount($max);
    if ($min !== null && $max !== null && $max < $min) {
      [$min, $max] = [$max, $min];
    }

    return new self($min, $max);
  }

  /**
   * Parses form input such as "8000", "8.000", "R$ 8.000,00" or "8000.50" (cents are dropped).
   * Empty input means "not informed".
   */
  public static function parseAmount(?string $input): ?int
  {
    $clean = preg_replace('/[^\d.,]/', '', (string) $input) ?? '';
    if ($clean === '') {
      return null;
    }

    // A trailing ",dd" or ".dd" is the cents part; every other separator groups thousands.
    $clean = preg_replace('/[.,]\d{1,2}$/', '', $clean) ?? $clean;
    $digits = preg_replace('/\D/', '', $clean) ?? '';
    if ($digits === '' || strlen($digits) > 9) {
      throw new InvalidValueException('Informe o salário em reais, por exemplo 8.000.');
    }

    return (int) $digits;
  }

  public function min(): ?int
  {
    return $this->min;
  }

  public function max(): ?int
  {
    return $this->max;
  }

  public function isNegotiable(): bool
  {
    return $this->min === null && $this->max === null;
  }

  /**
   * Whether the vacancy may pay at least the given amount (uses the top of the range).
   * "A combinar" never matches a salary filter.
   */
  public function reaches(int $amount): bool
  {
    $top = $this->max ?? $this->min;
    return $top !== null && $top >= $amount;
  }

  public function label(): string
  {
    return match (true) {
      $this->min !== null && $this->max !== null && $this->min === $this->max => 'R$ ' . self::format($this->min),
      $this->min !== null && $this->max !== null => 'R$ ' . self::format($this->min) . ' – R$ ' . self::format($this->max),
      $this->min !== null => 'A partir de R$ ' . self::format($this->min),
      $this->max !== null => 'Até R$ ' . self::format($this->max),
      default => 'A combinar',
    };
  }

  public function equals(self $other): bool
  {
    return $this->min === $other->min && $this->max === $other->max;
  }

  private static function format(int $amount): string
  {
    return number_format($amount, 0, ',', '.');
  }
}
