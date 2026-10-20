<?php

namespace App\Domain\Vacancy;

use App\Domain\Shared\PageRequest;

interface VacancyRepositoryInterface
{
  public function get(VacancyId $id): ?Vacancy;

  /**
   * Vacancies matching the filter: most relevant first when the filter has a text search,
   * otherwise newest first.
   *
   * @return Vacancy[]
   */
  public function matching(VacancyFilter $filter, PageRequest $page): array;

  public function countMatching(VacancyFilter $filter): int;

  /**
   * Open vacancies whose deadline has passed at the given moment.
   *
   * @return Vacancy[]
   */
  public function openPastDeadline(\DateTimeImmutable $now): array;

  public function save(Vacancy $vacancy): void;

  public function remove(VacancyId $id): void;
}
