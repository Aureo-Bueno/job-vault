<?php

namespace App\Domain\Repository;

use App\Domain\Model\Vacancy;

interface VacancyRepositoryInterface
{
  /** @return Vacancy[] */
  public function findAll(?string $where = null, ?string $order = null, ?string $limit = null): array;

  public function findById(string $id): ?Vacancy;

  public function count(?string $where = null): int;

  public function create(Vacancy $vacancy): Vacancy;

  public function update(Vacancy $vacancy): bool;

  public function delete(string $id): bool;
}
