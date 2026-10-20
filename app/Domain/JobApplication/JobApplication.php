<?php

namespace App\Domain\JobApplication;

use App\Domain\Exception\BusinessRuleViolationException;
use App\Domain\Exception\InvalidValueException;
use App\Domain\User\UserId;
use App\Domain\Vacancy\VacancyId;
use DateTimeImmutable;

/**
 * A candidate's application to a vacancy and its progress through the hiring pipeline.
 * Created through Vacancy::receiveApplicationFrom(); every stage change is kept in history().
 */
final class JobApplication
{
  private const MAX_FEEDBACK_LENGTH = 500;

  /** @var list<ApplicationEvent> events not persisted yet */
  private array $newEvents = [];

  /**
   * @param list<ApplicationEvent> $history oldest first
   */
  private function __construct(
    private JobApplicationId $id,
    private VacancyId $vacancyId,
    private UserId $candidateId,
    private DateTimeImmutable $submittedAt,
    private ApplicationStatus $status,
    private DateTimeImmutable $statusChangedAt,
    private ?string $candidateFeedback,
    private array $history
  ) {
  }

  /**
   * @internal use Vacancy::receiveApplicationFrom() so the vacancy rules are enforced
   */
  public static function submit(VacancyId $vacancyId, UserId $candidateId, ?DateTimeImmutable $now = null): self
  {
    $now ??= new DateTimeImmutable();
    $application = new self(
      JobApplicationId::generate(),
      $vacancyId,
      $candidateId,
      $now,
      ApplicationStatus::Received,
      $now,
      null,
      []
    );
    $application->record(null, ApplicationStatus::Received, $candidateId, null, $now);

    return $application;
  }

  /**
   * @param list<ApplicationEvent> $history oldest first
   */
  public static function reconstitute(
    JobApplicationId $id,
    VacancyId $vacancyId,
    UserId $candidateId,
    DateTimeImmutable $submittedAt,
    ApplicationStatus $status = ApplicationStatus::Received,
    ?DateTimeImmutable $statusChangedAt = null,
    ?string $candidateFeedback = null,
    array $history = []
  ): self {
    return new self(
      $id,
      $vacancyId,
      $candidateId,
      $submittedAt,
      $status,
      $statusChangedAt ?? $submittedAt,
      $candidateFeedback,
      array_values($history)
    );
  }

  /**
   * Recruiter moves the application to the next stage (one stage at a time).
   */
  public function advance(UserId $actorId, ?DateTimeImmutable $now = null): void
  {
    $next = $this->status->next();
    if ($next === null) {
      throw InvalidApplicationTransitionException::cannotAdvance($this->status);
    }

    $this->transitionTo($next, $actorId, null, $now);
  }

  /**
   * Recruiter closes the application. `$feedback` is shown to the candidate.
   */
  public function reject(UserId $actorId, ?string $feedback = null, ?DateTimeImmutable $now = null): void
  {
    $feedback = self::normalizeFeedback($feedback);
    $this->transitionTo(ApplicationStatus::Rejected, $actorId, $feedback, $now);
    $this->candidateFeedback = $feedback;
  }

  /**
   * Recruiter reconsiders a rejected application: it goes back to review.
   */
  public function reopen(UserId $actorId, ?DateTimeImmutable $now = null): void
  {
    $this->transitionTo(ApplicationStatus::InReview, $actorId, null, $now, ApplicationStatus::Rejected);
    $this->candidateFeedback = null;
  }

  /**
   * The candidate gives up while the application is still in process.
   */
  public function withdraw(UserId $candidateId, ?DateTimeImmutable $now = null): void
  {
    $this->assertCandidate($candidateId, 'Apenas o próprio candidato pode desistir da candidatura.');
    $this->transitionTo(ApplicationStatus::Withdrawn, $candidateId, null, $now);
  }

  /**
   * @internal use Vacancy::acceptResubmission() so the vacancy must be open
   */
  public function resubmit(UserId $candidateId, ?DateTimeImmutable $now = null): void
  {
    $this->assertCandidate($candidateId, 'Apenas o próprio candidato pode se candidatar novamente.');
    $this->transitionTo(ApplicationStatus::Received, $candidateId, null, $now, ApplicationStatus::Withdrawn);
    $this->candidateFeedback = null;
  }

  /**
   * Returns the events recorded since the last call (for the repository to persist).
   *
   * @return list<ApplicationEvent>
   */
  public function releaseNewEvents(): array
  {
    $events = $this->newEvents;
    $this->newEvents = [];
    return $events;
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

  public function status(): ApplicationStatus
  {
    return $this->status;
  }

  public function statusChangedAt(): DateTimeImmutable
  {
    return $this->statusChangedAt;
  }

  public function candidateFeedback(): ?string
  {
    return $this->candidateFeedback;
  }

  /**
   * @return list<ApplicationEvent> oldest first
   */
  public function history(): array
  {
    return $this->history;
  }

  private function transitionTo(
    ApplicationStatus $to,
    UserId $actorId,
    ?string $reason,
    ?DateTimeImmutable $now,
    ?ApplicationStatus $requiredFrom = null
  ): void {
    if (($requiredFrom !== null && $this->status !== $requiredFrom) || !$this->status->canTransitionTo($to)) {
      throw InvalidApplicationTransitionException::between($this->status, $to);
    }

    $now ??= new DateTimeImmutable();
    $this->record($this->status, $to, $actorId, $reason, $now);
    $this->status = $to;
    $this->statusChangedAt = $now;
  }

  private function record(
    ?ApplicationStatus $from,
    ApplicationStatus $to,
    UserId $actorId,
    ?string $reason,
    DateTimeImmutable $at
  ): void {
    $event = new ApplicationEvent($from, $to, $actorId, $reason, $at);
    $this->history[] = $event;
    $this->newEvents[] = $event;
  }

  private function assertCandidate(UserId $userId, string $message): void
  {
    if (!$this->candidateId->equals($userId)) {
      throw new BusinessRuleViolationException($message);
    }
  }

  private static function normalizeFeedback(?string $feedback): ?string
  {
    $feedback = trim((string) $feedback);
    if ($feedback === '') {
      return null;
    }

    if (mb_strlen($feedback) > self::MAX_FEEDBACK_LENGTH) {
      throw new InvalidValueException('O retorno ao candidato deve ter no máximo ' . self::MAX_FEEDBACK_LENGTH . ' caracteres.');
    }

    return $feedback;
  }
}
