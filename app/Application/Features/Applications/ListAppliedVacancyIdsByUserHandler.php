<?php

namespace App\Application\Features\Applications;

use App\Application\Abstractions\Query;
use App\Application\Abstractions\QueryHandlerInterface;
use App\Application\Queries\Applications\ListAppliedVacancyIdsByUserQuery;
use App\Application\Service\ApplicationService;
use InvalidArgumentException;

/**
 * Handles query to list vacancy IDs already applied by a user.
 */
final class ListAppliedVacancyIdsByUserHandler implements QueryHandlerInterface
{
  public function __construct(private ApplicationService $applicationService)
  {
  }

  public function queryClass(): string
  {
    return ListAppliedVacancyIdsByUserQuery::class;
  }

  public function handle(Query $query): array
  {
    if (!$query instanceof ListAppliedVacancyIdsByUserQuery) {
      throw new InvalidArgumentException('Invalid query for ListAppliedVacancyIdsByUserHandler.');
    }

    return $this->applicationService->getAppliedVacancyIdsByUser($query->userId);
  }
}
