<?php

namespace App\Application\Commands\Vacancies;

use App\Application\Abstractions\Command;

/**
 * Removes a vacancy.
 */
final class DeleteVacancyCommand implements Command
{
  public function __construct(public readonly string $vacancyId)
  {
  }
}
