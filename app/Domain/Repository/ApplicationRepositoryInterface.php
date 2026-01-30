<?php

namespace App\Domain\Repository;

interface ApplicationRepositoryInterface
{
  public function create(string $userId, string $vacancyId): bool;

  public function hasApplied(string $userId, string $vacancyId): bool;

  /** @return string[] */
  public function getAppliedVacancyIdsByUser(string $userId): array;
}
