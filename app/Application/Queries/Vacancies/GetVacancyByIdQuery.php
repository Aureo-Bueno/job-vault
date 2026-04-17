<?php

namespace App\Application\Queries\Vacancies;

use App\Application\Abstractions\Query;

/**
 * Query to get one vacancy by identifier.
 */
final class GetVacancyByIdQuery implements Query
{
  public function __construct(public string $vacancyId)
  {
  }
}
