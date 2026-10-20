<?php

namespace App\Application\Queries\Vacancies;

use App\Application\Abstractions\Query;

/**
 * Paged vacancy search by title and status (null = any status).
 */
final class SearchVacanciesQuery implements Query
{
  public function __construct(
    public readonly ?string $search = null,
    public readonly ?bool $active = null,
    public readonly int $page = 1,
    public readonly int $perPage = 10
  ) {
  }
}
