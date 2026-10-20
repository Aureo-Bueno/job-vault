<?php

namespace App\Application\Commands\Vacancies;

use App\Application\Abstractions\Command;

/**
 * Publishes a new vacancy.
 */
final class CreateVacancyCommand implements Command
{
  /**
   * @param string|list<string> $topics
   */
  public function __construct(
    public readonly string $title,
    public readonly string $description,
    public readonly bool $active = true,
    public readonly string $area = 'backend',
    public readonly string $seniority = 'pleno',
    public readonly string $workMode = 'remoto',
    public readonly string|array $topics = [],
    public readonly ?string $companyId = null
  ) {
  }
}
