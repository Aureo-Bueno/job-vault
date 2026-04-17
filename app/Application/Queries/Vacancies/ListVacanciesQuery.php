<?php

namespace App\Application\Queries\Vacancies;

use App\Application\Abstractions\Query;

/**
 * Query to list vacancies with optional query criteria.
 */
final class ListVacanciesQuery implements Query
{
  /**
   * @param array<string,mixed> $params
   */
  public function __construct(
    public ?string $where = null,
    public ?string $order = null,
    public ?string $limit = null,
    public array $params = []
  ) {
  }
}
