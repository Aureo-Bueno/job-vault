<?php

namespace App\Application\Features\Vacancies;

use App\Application\Abstractions\Query;
use App\Application\Abstractions\QueryHandlerInterface;
use App\Application\Queries\Vacancies\CountVacanciesQuery;
use App\Application\Service\VacancyService;
use InvalidArgumentException;

/**
 * Handles vacancy count query.
 */
final class CountVacanciesHandler implements QueryHandlerInterface
{
  public function __construct(private VacancyService $vacancyService)
  {
  }

  public function queryClass(): string
  {
    return CountVacanciesQuery::class;
  }

  public function handle(Query $query): int
  {
    if (!$query instanceof CountVacanciesQuery) {
      throw new InvalidArgumentException('Invalid query for CountVacanciesHandler.');
    }

    return $this->vacancyService->count($query->where, $query->params);
  }
}
