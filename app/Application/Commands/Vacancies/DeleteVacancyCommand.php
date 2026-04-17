<?php

namespace App\Application\Commands\Vacancies;

use App\Application\Abstractions\Command;

/**
 * Command to delete a vacancy.
 */
final class DeleteVacancyCommand implements Command
{
  public function __construct(public string $vacancyId)
  {
  }
}
