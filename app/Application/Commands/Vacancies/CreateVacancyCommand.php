<?php

namespace App\Application\Commands\Vacancies;

use App\Application\Abstractions\Command;

/**
 * Creates a vacancy, published right away (`aberta`) or saved as a draft (`rascunho`).
 *
 * Salary in whole reais per month (null = not informed; both null = "A combinar").
 * City/UF are required for on-site and hybrid vacancies. `closesAt` is the last day
 * to apply (Y-m-d) or null for no deadline.
 */
final class CreateVacancyCommand implements Command
{
  /**
   * @param string|list<string> $topics
   * @param string|list<string> $benefits
   */
  public function __construct(
    public readonly string $title,
    public readonly string $description,
    public readonly string $status = 'aberta',
    public readonly string $area = 'backend',
    public readonly string $seniority = 'pleno',
    public readonly string $workMode = 'remoto',
    public readonly string|array $topics = [],
    public readonly ?string $companyId = null,
    public readonly int $positions = 1,
    public readonly string $contractType = 'clt',
    public readonly ?int $salaryMin = null,
    public readonly ?int $salaryMax = null,
    public readonly ?string $city = null,
    public readonly ?string $state = null,
    public readonly string|array $benefits = [],
    public readonly ?string $closesAt = null
  ) {
  }
}
