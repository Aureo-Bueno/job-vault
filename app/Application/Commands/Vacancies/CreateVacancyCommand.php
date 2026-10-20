<?php

namespace App\Application\Commands\Vacancies;

use App\Application\Abstractions\Command;

/**
 * Publishes a new vacancy.
 */
final class CreateVacancyCommand implements Command
{
  public function __construct(
    public readonly string $title,
    public readonly string $description,
    public readonly bool $active = true
  ) {
  }
}
