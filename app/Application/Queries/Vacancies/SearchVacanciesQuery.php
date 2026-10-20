<?php

namespace App\Application\Queries\Vacancies;

use App\Application\Abstractions\Query;

/**
 * Paged vacancy search by title/topic, status, area and company.
 */
final class SearchVacanciesQuery implements Query
{
  public function __construct(
    public readonly ?string $search = null,
    public readonly ?string $status = null,
    public readonly int $page = 1,
    public readonly int $perPage = 10,
    public readonly ?string $area = null,
    public readonly ?string $companyId = null
  ) {
  }
}
