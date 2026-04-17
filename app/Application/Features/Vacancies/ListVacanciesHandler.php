<?php

namespace App\Application\Features\Vacancies;

use App\Application\Abstractions\Query;
use App\Application\Abstractions\QueryHandlerInterface;
use App\Application\Mappings\VacancyMapping;
use App\Application\Queries\Vacancies\ListVacanciesQuery;
use App\Application\Service\VacancyService;
use InvalidArgumentException;

/**
 * Handles vacancy list query.
 */
final class ListVacanciesHandler implements QueryHandlerInterface
{
  public function __construct(private VacancyService $vacancyService)
  {
  }

  public function queryClass(): string
  {
    return ListVacanciesQuery::class;
  }

  public function handle(Query $query): array
  {
    if (!$query instanceof ListVacanciesQuery) {
      throw new InvalidArgumentException('Invalid query for ListVacanciesHandler.');
    }

    $vacancies = $this->vacancyService->list(
      $query->where,
      $query->order,
      $query->limit,
      $query->params
    );

    return array_map([VacancyMapping::class, 'toDto'], $vacancies);
  }
}
