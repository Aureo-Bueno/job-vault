<?php

namespace App\Application\Commands\Applications;

use App\Application\Abstractions\Command;

/**
 * Command to apply a user to a vacancy.
 */
final class ApplyToVacancyCommand implements Command
{
  public function __construct(
    public string $userId,
    public string $vacancyId
  ) {
  }
}
