<?php

namespace App\Domain\Vacancy;

use App\Domain\Shared\SearchTerm;

/**
 * Search criteria for vacancies (no SQL knowledge).
 */
final class VacancyFilter
{
  public function __construct(
    public readonly ?SearchTerm $title = null,
    public readonly ?VacancyStatus $status = null
  ) {
  }

  public static function openOnly(): self
  {
    return new self(null, VacancyStatus::Active);
  }
}
