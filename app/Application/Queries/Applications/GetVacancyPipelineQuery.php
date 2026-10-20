<?php

namespace App\Application\Queries\Applications;

use App\Application\Abstractions\Query;

/**
 * Applications of a vacancy grouped by pipeline stage (board columns).
 */
final class GetVacancyPipelineQuery implements Query
{
  public function __construct(public readonly string $vacancyId)
  {
  }
}
