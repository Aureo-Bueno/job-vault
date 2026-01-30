<?php

namespace App\Application\Service;

use App\Domain\Repository\ApplicationRepositoryInterface;

class ApplicationService
{
  private ApplicationRepositoryInterface $applicationRepository;

  public function __construct(ApplicationRepositoryInterface $applicationRepository)
  {
    $this->applicationRepository = $applicationRepository;
  }

  public function apply(string $userId, string $vacancyId): string
  {
    if ($this->applicationRepository->hasApplied($userId, $vacancyId)) {
      return 'exists';
    }

    return $this->applicationRepository->create($userId, $vacancyId) ? 'success' : 'error';
  }

  public function hasApplied(string $userId, string $vacancyId): bool
  {
    return $this->applicationRepository->hasApplied($userId, $vacancyId);
  }

  /** @return string[] */
  public function getAppliedVacancyIdsByUser(string $userId): array
  {
    return $this->applicationRepository->getAppliedVacancyIdsByUser($userId);
  }
}
