<?php

namespace App\Domain\Vacancy;

use App\Domain\Company\CompanyId;
use App\Domain\Shared\BrazilianState;
use App\Domain\Shared\SearchTerm;
use App\Domain\Shared\WorkArea;
use App\Domain\Shared\WorkMode;
use DateTimeImmutable;

/**
 * Search criteria for vacancies (no SQL knowledge).
 *
 * - text: full-text search over title, description, topics and benefits. Every word is
 *   required (as a prefix, accent-insensitive) and results come ranked by relevance;
 * - minSalary: the top of the salary range reaches the amount ("A combinar" is excluded);
 * - acceptingApplicationsAt: the deadline, if any, has not passed at that moment.
 */
final class VacancyFilter
{
  public function __construct(
    public readonly ?SearchTerm $text = null,
    public readonly ?VacancyStatus $status = null,
    public readonly ?CompanyId $companyId = null,
    public readonly ?WorkArea $area = null,
    public readonly ?WorkMode $workMode = null,
    public readonly ?int $minSalary = null,
    public readonly ?BrazilianState $state = null,
    public readonly ?ContractType $contractType = null,
    public readonly ?DateTimeImmutable $acceptingApplicationsAt = null
  ) {
  }

  /**
   * What candidates see: open vacancies still within the deadline.
   */
  public static function openOnly(
    ?SearchTerm $text = null,
    ?WorkArea $area = null,
    ?WorkMode $workMode = null,
    ?int $minSalary = null,
    ?BrazilianState $state = null,
    ?ContractType $contractType = null,
    ?DateTimeImmutable $now = null
  ): self {
    return new self(
      $text,
      VacancyStatus::Open,
      null,
      $area,
      $workMode,
      $minSalary,
      $state,
      $contractType,
      $now ?? new DateTimeImmutable()
    );
  }
}
