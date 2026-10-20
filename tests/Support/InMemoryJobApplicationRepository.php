<?php

namespace Tests\Support;

use App\Domain\JobApplication\JobApplication;
use App\Domain\JobApplication\JobApplicationRepositoryInterface;
use App\Domain\User\UserId;
use App\Domain\Vacancy\VacancyId;

final class InMemoryJobApplicationRepository implements JobApplicationRepositoryInterface
{
  /** @var JobApplication[] */
  public array $applications = [];

  public function exists(UserId $candidateId, VacancyId $vacancyId): bool
  {
    foreach ($this->applications as $application) {
      if ($application->candidateId()->equals($candidateId) && $application->vacancyId()->equals($vacancyId)) {
        return true;
      }
    }

    return false;
  }

  public function save(JobApplication $application): void
  {
    $this->applications[] = $application;
  }

  public function vacancyIdsAppliedBy(UserId $candidateId): array
  {
    $ids = [];
    foreach ($this->applications as $application) {
      if ($application->candidateId()->equals($candidateId)) {
        $ids[] = $application->vacancyId()->value();
      }
    }

    return $ids;
  }
}
