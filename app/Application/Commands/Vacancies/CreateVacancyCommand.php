<?php

namespace App\Application\Commands\Vacancies;

use App\Application\Abstractions\Command;

/**
 * Command to create a vacancy.
 */
final class CreateVacancyCommand implements Command
{
  public function __construct(
    public string $title,
    public string $description,
    public string $isActive = 's'
  ) {
  }
}
