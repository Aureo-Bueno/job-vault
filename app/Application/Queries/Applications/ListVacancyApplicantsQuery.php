<?php

namespace App\Application\Queries\Applications;

use App\Application\Abstractions\Query;

final class ListVacancyApplicantsQuery implements Query
{
  public function __construct(public readonly string $vacancyId)
  {
  }
}
