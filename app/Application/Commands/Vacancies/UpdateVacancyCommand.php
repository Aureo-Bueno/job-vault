<?php

namespace App\Application\Commands\Vacancies;

use App\Application\Abstractions\Command;

/**
 * Revises a vacancy and, when `status` is given, moves it (publish, close or reopen).
 *
 * Employment terms (contract, salary, city/UF, benefits) are replaced together when
 * `contractType` is given and kept otherwise. `closesAt`: null keeps the deadline,
 * '' removes it, 'Y-m-d' sets it.
 */
final class UpdateVacancyCommand implements Command
{
  /**
   * @param string|list<string>|null $topics
   * @param string|list<string> $benefits
   */
  public function __construct(
    public readonly string $vacancyId,
    public readonly string $title,
    public readonly string $description,
    public readonly ?string $status = null,
    public readonly ?string $area = null,
    public readonly ?string $seniority = null,
    public readonly ?string $workMode = null,
    public readonly string|array|null $topics = null,
    public readonly ?string $companyId = null,
    public readonly bool $changeCompany = false,
    public readonly ?int $positions = null,
    public readonly ?string $contractType = null,
    public readonly ?int $salaryMin = null,
    public readonly ?int $salaryMax = null,
    public readonly ?string $city = null,
    public readonly ?string $state = null,
    public readonly string|array $benefits = [],
    public readonly ?string $closesAt = null
  ) {
  }
}
