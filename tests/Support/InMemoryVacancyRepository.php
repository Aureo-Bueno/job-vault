<?php

namespace Tests\Support;

use App\Domain\Shared\PageRequest;
use App\Domain\Vacancy\Vacancy;
use App\Domain\Vacancy\VacancyFilter;
use App\Domain\Vacancy\VacancyId;
use App\Domain\Vacancy\VacancyRepositoryInterface;

final class InMemoryVacancyRepository implements VacancyRepositoryInterface
{
  /** @var array<string,Vacancy> */
  private array $vacancies = [];

  public function get(VacancyId $id): ?Vacancy
  {
    return isset($this->vacancies[$id->value()]) ? clone $this->vacancies[$id->value()] : null;
  }

  public function matching(VacancyFilter $filter, PageRequest $page): array
  {
    $matches = array_values(array_filter($this->vacancies, static function (Vacancy $vacancy) use ($filter): bool {
      if ($filter->title !== null && !$filter->title->matches($vacancy->title()->value())) {
        return false;
      }

      return $filter->status === null || $vacancy->status() === $filter->status;
    }));

    usort($matches, static fn (Vacancy $a, Vacancy $b): int => $b->publishedAt() <=> $a->publishedAt());

    return array_map(static fn (Vacancy $v): Vacancy => clone $v, array_slice($matches, $page->offset(), $page->size));
  }

  public function countMatching(VacancyFilter $filter): int
  {
    return count($this->matching($filter, PageRequest::all()));
  }

  public function save(Vacancy $vacancy): void
  {
    $this->vacancies[$vacancy->id()->value()] = clone $vacancy;
  }

  public function remove(VacancyId $id): void
  {
    unset($this->vacancies[$id->value()]);
  }
}
