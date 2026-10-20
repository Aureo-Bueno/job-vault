<?php

namespace Tests\Support;

use App\Domain\JobApplication\ApplicationNote;
use App\Domain\JobApplication\ApplicationStatus;
use App\Domain\JobApplication\JobApplication;
use App\Domain\JobApplication\JobApplicationId;
use App\Domain\JobApplication\JobApplicationRepositoryInterface;
use App\Domain\User\UserId;
use App\Domain\Vacancy\VacancyId;

final class InMemoryJobApplicationRepository implements JobApplicationRepositoryInterface
{
  /** @var array<string,JobApplication> */
  public array $applications = [];

  /** @var list<ApplicationNote> */
  public array $notes = [];

  public function get(JobApplicationId $id): ?JobApplication
  {
    return $this->applications[$id->value()] ?? null;
  }

  public function findFor(UserId $candidateId, VacancyId $vacancyId): ?JobApplication
  {
    foreach ($this->applications as $application) {
      if ($application->candidateId()->equals($candidateId) && $application->vacancyId()->equals($vacancyId)) {
        return $application;
      }
    }

    return null;
  }

  public function exists(UserId $candidateId, VacancyId $vacancyId): bool
  {
    return $this->findFor($candidateId, $vacancyId) !== null;
  }

  public function save(JobApplication $application): void
  {
    $application->releaseNewEvents();
    $this->applications[$application->id()->value()] = $application;
  }

  public function vacancyIdsAppliedBy(UserId $candidateId): array
  {
    $ids = [];
    foreach ($this->applications as $application) {
      if ($application->candidateId()->equals($candidateId) && $application->status() !== ApplicationStatus::Withdrawn) {
        $ids[] = $application->vacancyId()->value();
      }
    }

    return $ids;
  }

  public function forVacancy(VacancyId $vacancyId): array
  {
    return array_values(array_filter(
      $this->applications,
      static fn (JobApplication $a): bool => $a->vacancyId()->equals($vacancyId)
    ));
  }

  public function forCandidate(UserId $candidateId): array
  {
    $items = array_values(array_filter(
      $this->applications,
      static fn (JobApplication $a): bool => $a->candidateId()->equals($candidateId)
    ));
    usort($items, static fn (JobApplication $a, JobApplication $b): int => $b->statusChangedAt() <=> $a->statusChangedAt());

    return $items;
  }

  public function countPerVacancy(): array
  {
    $counts = [];
    foreach ($this->applications as $application) {
      if ($application->status() === ApplicationStatus::Withdrawn) {
        continue;
      }
      $key = $application->vacancyId()->value();
      $counts[$key] = ($counts[$key] ?? 0) + 1;
    }

    return $counts;
  }

  public function approvedPerVacancy(): array
  {
    $counts = [];
    foreach ($this->applications as $application) {
      if ($application->status() === ApplicationStatus::Approved) {
        $key = $application->vacancyId()->value();
        $counts[$key] = ($counts[$key] ?? 0) + 1;
      }
    }

    return $counts;
  }

  public function approvedCountFor(VacancyId $vacancyId): int
  {
    return $this->approvedPerVacancy()[$vacancyId->value()] ?? 0;
  }

  public function addNote(ApplicationNote $note): void
  {
    $this->notes[] = $note;
  }

  public function notesFor(array $applicationIds): array
  {
    $wanted = array_fill_keys(array_map(static fn (JobApplicationId $id): string => $id->value(), $applicationIds), true);
    $grouped = [];
    foreach ($this->notes as $note) {
      $key = $note->applicationId->value();
      if (isset($wanted[$key])) {
        $grouped[$key][] = $note;
      }
    }

    return $grouped;
  }
}
