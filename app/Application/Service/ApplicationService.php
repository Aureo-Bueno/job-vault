<?php

namespace App\Application\Service;

use App\Domain\Repository\ApplicationRepositoryInterface;

/**
 * Coordinates vacancy application use cases.
 */
class ApplicationService
{
  private ApplicationRepositoryInterface $applicationRepository;

  public function __construct(ApplicationRepositoryInterface $applicationRepository)
  {
    $this->applicationRepository = $applicationRepository;
  }

  /**
   * Applies a user to a vacancy and returns an operation status string.
   *
   * Returned statuses:
   * - `exists`: user has already applied.
   * - `success`: application created.
   * - `error`: persistence failed.
   */
  public function apply(string $userId, string $vacancyId): string
  {
    if ($this->applicationRepository->hasApplied($userId, $vacancyId)) {
      return 'exists';
    }

    return $this->applicationRepository->create($userId, $vacancyId) ? 'success' : 'error';
  }

  /**
   * Checks whether a user already applied to a vacancy.
   */
  public function hasApplied(string $userId, string $vacancyId): bool
  {
    return $this->applicationRepository->hasApplied($userId, $vacancyId);
  }

  /**
   * Returns all vacancy IDs the user has already applied to.
   *
   * @return string[]
   */
  public function getAppliedVacancyIdsByUser(string $userId): array
  {
    return $this->applicationRepository->getAppliedVacancyIdsByUser($userId);
  }
}
