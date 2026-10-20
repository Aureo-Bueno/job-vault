<?php

namespace App\Domain\Vacancy;

use App\Domain\Company\CompanyId;
use App\Domain\Shared\SearchTerm;
use App\Domain\Shared\WorkArea;
use App\Domain\Shared\WorkMode;

/**
 * Search criteria for vacancies (no SQL knowledge).
 */
final class VacancyFilter
{
  public function __construct(
    public readonly ?SearchTerm $title = null,
    public readonly ?VacancyStatus $status = null,
    public readonly ?CompanyId $companyId = null,
    public readonly ?WorkArea $area = null,
    public readonly ?WorkMode $workMode = null
  ) {
  }

  public static function openOnly(
    ?SearchTerm $title = null,
    ?WorkArea $area = null,
    ?WorkMode $workMode = null
  ): self {
    return new self($title, VacancyStatus::Active, null, $area, $workMode);
  }
}
