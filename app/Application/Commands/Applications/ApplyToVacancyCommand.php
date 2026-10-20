<?php

namespace App\Application\Commands\Applications;

use App\Application\Abstractions\Command;

/**
 * The current user applies to a vacancy.
 */
final class ApplyToVacancyCommand implements Command
{
  public function __construct(public readonly string $vacancyId)
  {
  }
}
