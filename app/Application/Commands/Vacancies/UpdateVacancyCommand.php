<?php

namespace App\Application\Commands\Vacancies;

use App\Application\Abstractions\Command;

/**
 * Command to update a vacancy.
 */
final class UpdateVacancyCommand implements Command
{
  public function __construct(
    public string $vacancyId,
    public string $title,
    public string $description,
    public string $isActive = 's'
  ) {
  }
}
