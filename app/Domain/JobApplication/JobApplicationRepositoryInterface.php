<?php

namespace App\Domain\JobApplication;

use App\Domain\User\UserId;
use App\Domain\Vacancy\VacancyId;

interface JobApplicationRepositoryInterface
{
  public function get(JobApplicationId $id): ?JobApplication;

  public function findFor(UserId $candidateId, VacancyId $vacancyId): ?JobApplication;

  public function exists(UserId $candidateId, VacancyId $vacancyId): bool;

  /**
   * Inserts or updates the application and appends its new history events.
   */
  public function save(JobApplication $application): void;

  /**
   * Vacancies the candidate is (or was) applied to, excluding withdrawn applications.
   *
   * @return string[]
   */
  public function vacancyIdsAppliedBy(UserId $candidateId): array;

  /** @return JobApplication[] */
  public function forVacancy(VacancyId $vacancyId): array;

  /** @return JobApplication[] newest first */
  public function forCandidate(UserId $candidateId): array;

  /**
   * Applications per vacancy, excluding withdrawn ones.
   *
   * @return array<string,int> indexed by vacancy_id
   */
  public function countPerVacancy(): array;

  /**
   * Approved applications per vacancy (filled positions).
   *
   * @return array<string,int> indexed by vacancy_id
   */
  public function approvedPerVacancy(): array;

  public function approvedCountFor(VacancyId $vacancyId): int;

  public function addNote(ApplicationNote $note): void;

  /**
   * @param list<JobApplicationId> $applicationIds
   * @return array<string,list<ApplicationNote>> indexed by application id, oldest first
   */
  public function notesFor(array $applicationIds): array;
}
