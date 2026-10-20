<?php

namespace App\Application\Features\Vacancies;

use App\Application\Abstractions\Query;
use App\Application\Abstractions\QueryHandlerInterface;
use App\Application\DTOs\PagedResult;
use App\Application\DTOs\VacancyDto;
use App\Application\Queries\Vacancies\SearchVacanciesQuery;
use App\Domain\Shared\PageRequest;
use App\Domain\Shared\SearchTerm;
use App\Domain\Vacancy\VacancyFilter;
use App\Domain\Vacancy\VacancyRepositoryInterface;
use App\Domain\Vacancy\VacancyStatus;
use InvalidArgumentException;

final class SearchVacanciesHandler implements QueryHandlerInterface
{
  public function __construct(private VacancyRepositoryInterface $vacancies)
  {
  }

  public function queryClass(): string
  {
    return SearchVacanciesQuery::class;
  }

  /** @return PagedResult<VacancyDto> */
  public function handle(Query $query): PagedResult
  {
    if (!$query instanceof SearchVacanciesQuery) {
      throw new InvalidArgumentException('Invalid query for ' . self::class);
    }

    $filter = new VacancyFilter(
      SearchTerm::tryFrom($query->search),
      $query->active === null ? null : VacancyStatus::fromBool($query->active)
    );

    $total = $this->vacancies->countMatching($filter);
    $page = (new PageRequest($query->page, $query->perPage))->clampTo($total);
    $items = array_map([VacancyDto::class, 'fromVacancy'], $this->vacancies->matching($filter, $page));

    return new PagedResult($items, $total, $page->number, $page->size);
  }
}
