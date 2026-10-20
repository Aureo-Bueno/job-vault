<?php

namespace App\Domain\Vacancy;

use App\Domain\Exception\InvalidValueException;
use App\Domain\Shared\BrazilianState;

/**
 * City and state where the work happens (required for on-site and hybrid vacancies).
 */
final class VacancyLocation
{
  private const CITY_MAX_LENGTH = 100;

  private function __construct(
    private string $city,
    private BrazilianState $state
  ) {
  }

  public static function of(string $city, BrazilianState $state): self
  {
    $clean = preg_replace('/\s+/u', ' ', trim($city)) ?? '';
    if (mb_strlen($clean) < 2) {
      throw new InvalidValueException('Informe o nome da cidade.');
    }

    if (mb_strlen($clean) > self::CITY_MAX_LENGTH) {
      throw new InvalidValueException('O nome da cidade deve ter no máximo ' . self::CITY_MAX_LENGTH . ' caracteres.');
    }

    return new self($clean, $state);
  }

  /**
   * Both empty: no location. Only one of them: invalid.
   */
  public static function fromInput(?string $city, ?string $state): ?self
  {
    $city = trim((string) $city);
    $state = trim((string) $state);
    if ($city === '' && $state === '') {
      return null;
    }

    if ($city === '' || $state === '') {
      throw new InvalidValueException('Informe a cidade e a UF juntas.');
    }

    return self::of($city, BrazilianState::fromString($state));
  }

  public static function fromStored(?string $city, ?string $state): ?self
  {
    $uf = BrazilianState::fromStored($state);
    $city = trim((string) $city);

    return ($uf === null || $city === '') ? null : new self($city, $uf);
  }

  public function city(): string
  {
    return $this->city;
  }

  public function state(): BrazilianState
  {
    return $this->state;
  }

  public function label(): string
  {
    return $this->city . '/' . $this->state->value;
  }
}
