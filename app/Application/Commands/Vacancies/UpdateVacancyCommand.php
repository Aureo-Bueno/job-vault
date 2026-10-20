<?php

namespace App\Application\Commands\Vacancies;

use App\Application\Abstractions\Command;

/**
 * Revises a vacancy and sets its status.
 */
final class UpdateVacancyCommand implements Command
{
  /**
   * @param string|list<string>|null $topics
   */
  public function __construct(
    public readonly string $vacancyId,
    public readonly string $title,
    public readonly string $description,
    public readonly bool $active = true,
    public readonly ?string $area = null,
    public readonly ?string $seniority = null,
    public readonly ?string $workMode = null,
    public readonly string|array|null $topics = null,
    public readonly ?string $companyId = null,
    public readonly bool $changeCompany = false
  ) {
  }
}
