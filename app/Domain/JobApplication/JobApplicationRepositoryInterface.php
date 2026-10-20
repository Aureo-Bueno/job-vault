<?php

namespace App\Domain\JobApplication;

use App\Domain\User\UserId;
use App\Domain\Vacancy\VacancyId;

interface JobApplicationRepositoryInterface
{
  public function exists(UserId $candidateId, VacancyId $vacancyId): bool;

  public function save(JobApplication $application): void;

  /** @return string[] */
  public function vacancyIdsAppliedBy(UserId $candidateId): array;

  /** @return JobApplication[] */
  public function forVacancy(VacancyId $vacancyId): array;

  /** @return array<string,int> indexed by vacancy_id */
  public function countPerVacancy(): array;
}
