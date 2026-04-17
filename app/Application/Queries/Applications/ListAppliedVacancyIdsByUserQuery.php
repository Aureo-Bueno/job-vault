<?php

namespace App\Application\Queries\Applications;

use App\Application\Abstractions\Query;

/**
 * Query to list vacancy IDs already applied by a user.
 */
final class ListAppliedVacancyIdsByUserQuery implements Query
{
  public function __construct(public string $userId)
  {
  }
}
