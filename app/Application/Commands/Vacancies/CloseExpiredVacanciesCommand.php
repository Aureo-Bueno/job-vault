<?php

namespace App\Application\Commands\Vacancies;

use App\Application\Abstractions\Command;
use DateTimeImmutable;

/**
 * System job: closes every open vacancy whose application deadline has passed.
 * Runs on the system bus (no logged-in user).
 */
final class CloseExpiredVacanciesCommand implements Command
{
  public function __construct(public readonly ?DateTimeImmutable $now = null)
  {
  }
}
