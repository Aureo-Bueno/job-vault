<?php

namespace App\Application\Features\Vacancies;

use App\Application\Abstractions\Query;
use App\Application\Abstractions\QueryHandlerInterface;
use App\Application\DTOs\VacancyDto;
use App\Application\Queries\Vacancies\ListOpenVacanciesQuery;
use App\Domain\Shared\PageRequest;
use App\Domain\Vacancy\VacancyFilter;
use App\Domain\Vacancy\VacancyRepositoryInterface;
use InvalidArgumentException;

final class ListOpenVacanciesHandler implements QueryHandlerInterface
{
  public function __construct(private VacancyRepositoryInterface $vacancies)
  {
  }

  public function queryClass(): string
  {
    return ListOpenVacanciesQuery::class;
  }

  /** @return VacancyDto[] */
  public function handle(Query $query): array
  {
    if (!$query instanceof ListOpenVacanciesQuery) {
      throw new InvalidArgumentException('Invalid query for ' . self::class);
    }

    $vacancies = $this->vacancies->matching(VacancyFilter::openOnly(), PageRequest::all());
    return array_map([VacancyDto::class, 'fromVacancy'], $vacancies);
  }
}
