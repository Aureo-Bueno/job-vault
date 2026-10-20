<?php

namespace App\Application\Queries\Vacancies;

use App\Application\Abstractions\Query;

/**
 * Fetches a single vacancy.
 */
final class GetVacancyByIdQuery implements Query
{
  public function __construct(public readonly string $vacancyId)
  {
  }
}
