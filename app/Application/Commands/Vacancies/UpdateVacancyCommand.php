<?php

namespace App\Application\Commands\Vacancies;

use App\Application\Abstractions\Command;

/**
 * Revises a vacancy and sets its status.
 */
final class UpdateVacancyCommand implements Command
{
  public function __construct(
    public readonly string $vacancyId,
    public readonly string $title,
    public readonly string $description,
    public readonly bool $active = true
  ) {
  }
}
