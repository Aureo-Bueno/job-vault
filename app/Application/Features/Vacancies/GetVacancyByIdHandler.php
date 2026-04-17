<?php

namespace App\Application\Features\Vacancies;

use App\Application\Abstractions\Query;
use App\Application\Abstractions\QueryHandlerInterface;
use App\Application\DTOs\VacancyDto;
use App\Application\Mappings\VacancyMapping;
use App\Application\Queries\Vacancies\GetVacancyByIdQuery;
use App\Application\Service\VacancyService;
use InvalidArgumentException;

/**
 * Handles vacancy retrieval by identifier query.
 */
final class GetVacancyByIdHandler implements QueryHandlerInterface
{
  public function __construct(private VacancyService $vacancyService)
  {
  }

  public function queryClass(): string
  {
    return GetVacancyByIdQuery::class;
  }

  public function handle(Query $query): ?VacancyDto
  {
    if (!$query instanceof GetVacancyByIdQuery) {
      throw new InvalidArgumentException('Invalid query for GetVacancyByIdHandler.');
    }

    $vacancy = $this->vacancyService->getById($query->vacancyId);

    return $vacancy ? VacancyMapping::toDto($vacancy) : null;
  }
}
