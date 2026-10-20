<?php

namespace App\Domain\Vacancy;

use App\Domain\Shared\PageRequest;

interface VacancyRepositoryInterface
{
  public function get(VacancyId $id): ?Vacancy;

  /**
   * Vacancies matching the filter, newest first.
   *
   * @return Vacancy[]
   */
  public function matching(VacancyFilter $filter, PageRequest $page): array;

  public function countMatching(VacancyFilter $filter): int;

  public function save(Vacancy $vacancy): void;

  public function remove(VacancyId $id): void;
}
