<?php

namespace App\Domain\JobApplication;

use App\Domain\User\UserId;
use App\Domain\Vacancy\VacancyId;
use DateTimeImmutable;

/**
 * A candidate's application to a vacancy. Created through Vacancy::receiveApplicationFrom().
 */
final class JobApplication
{
  private function __construct(
    private JobApplicationId $id,
    private VacancyId $vacancyId,
    private UserId $candidateId,
    private DateTimeImmutable $submittedAt
  ) {
  }

  /**
   * @internal use Vacancy::receiveApplicationFrom() so the vacancy rules are enforced
   */
  public static function submit(VacancyId $vacancyId, UserId $candidateId, ?DateTimeImmutable $now = null): self
  {
    return new self(JobApplicationId::generate(), $vacancyId, $candidateId, $now ?? new DateTimeImmutable());
  }

  public static function reconstitute(
    JobApplicationId $id,
    VacancyId $vacancyId,
    UserId $candidateId,
    DateTimeImmutable $submittedAt
  ): self {
    return new self($id, $vacancyId, $candidateId, $submittedAt);
  }

  public function id(): JobApplicationId
  {
    return $this->id;
  }

  public function vacancyId(): VacancyId
  {
    return $this->vacancyId;
  }

  public function candidateId(): UserId
  {
    return $this->candidateId;
  }

  public function submittedAt(): DateTimeImmutable
  {
    return $this->submittedAt;
  }
}
