<?php

namespace App\Application\Queries\Vacancies;

use App\Application\Abstractions\Query;

final class ListOpenVacanciesQuery implements Query
{
  public function __construct(
    public readonly ?string $search = null,
    public readonly ?string $area = null,
    public readonly ?string $workMode = null,
    public readonly string $sort = 'match'
  ) {
  }
}
