<?php

namespace App\Application\Queries\Vacancies;

use App\Application\Abstractions\Query;

/**
 * Query to count vacancies using optional criteria.
 */
final class CountVacanciesQuery implements Query
{
  /**
   * @param array<string,mixed> $params
   */
  public function __construct(
    public ?string $where = null,
    public array $params = []
  ) {
  }
}
