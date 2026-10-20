<?php

namespace App\Domain\Vacancy;

use App\Domain\Company\CompanyId;
use App\Domain\Exception\BusinessRuleViolationException;
use App\Domain\JobApplication\JobApplication;
use App\Domain\Shared\Seniority;
use App\Domain\Shared\TopicTag;
use App\Domain\Shared\WorkArea;
use App\Domain\Shared\WorkMode;
use App\Domain\User\UserId;
use DateTimeImmutable;

/**
 * Vacancy aggregate: a job opening of a company. Starts as a draft or open, receives
 * applications only while open and within the deadline, and closes manually, when all
 * positions are filled or when the deadline passes.
 *
 * On-site and hybrid vacancies must say where the work happens (city/UF).
 */
final class Vacancy
{
  /**
   * @param list<TopicTag> $topics
   */
  private function __construct(
    private VacancyId $id,
    private VacancyTitle $title,
    private VacancyDescription $description,
    private VacancyStatus $status,
    private DateTimeImmutable $publishedAt,
    private ?CompanyId $companyId = null,
    private WorkArea $area = WorkArea::Backend,
    private Seniority $seniority = Seniority::Pleno,
    private WorkMode $workMode = WorkMode::Remoto,
    private array $topics = [],
    private ?VacancyPositions $positions = null,
    private ?VacancyCloseReason $closeReason = null,
    private ?DateTimeImmutable $closedAt = null,
    private ?EmploymentTerms $terms = null,
    private ?VacancyDeadline $closesAt = null
  ) {
    $this->positions ??= VacancyPositions::one();
    $this->terms ??= EmploymentTerms::standard();
  }

  /**
   * @param string|iterable<mixed> $topics
   */
  public static function publish(
    VacancyId $id,
    VacancyTitle $title,
    VacancyDescription $description,
    VacancyStatus $status = VacancyStatus::Open,
    ?DateTimeImmutable $now = null,
    ?CompanyId $companyId = null,
    WorkArea $area = WorkArea::Backend,
    Seniority $seniority = Seniority::Pleno,
    WorkMode $workMode = WorkMode::Remoto,
    string|iterable $topics = [],
    ?VacancyPositions $positions = null,
    ?EmploymentTerms $terms = null,
    ?VacancyDeadline $closesAt = null
  ): self {
    $now ??= new DateTimeImmutable();
    $terms ??= EmploymentTerms::standard();
    self::assertLocationFits($workMode, $terms);
    self::assertDeadlineAhead($closesAt, $now);

    return new self(
      $id,
      $title,
      $description,
      $status,
      $now,
      $companyId,
      $area,
      $seniority,
      $workMode,
      TopicTag::listFromInput($topics),
      $positions,
      $status === VacancyStatus::Closed ? VacancyCloseReason::Manual : null,
      $status === VacancyStatus::Closed ? $now : null,
      $terms,
      $closesAt
    );
  }

  /**
   * @param list<TopicTag> $topics
   */
  public static function reconstitute(
    VacancyId $id,
    VacancyTitle $title,
    VacancyDescription $description,
    VacancyStatus $status,
    DateTimeImmutable $publishedAt,
    ?CompanyId $companyId = null,
    WorkArea $area = WorkArea::Backend,
    Seniority $seniority = Seniority::Pleno,
    WorkMode $workMode = WorkMode::Remoto,
    array $topics = [],
    ?VacancyPositions $positions = null,
    ?VacancyCloseReason $closeReason = null,
    ?DateTimeImmutable $closedAt = null,
    ?EmploymentTerms $terms = null,
    ?VacancyDeadline $closesAt = null
  ): self {
    return new self(
      $id,
      $title,
      $description,
      $status,
      $publishedAt,
      $companyId,
      $area,
      $seniority,
      $workMode,
      array_values($topics),
      $positions,
      $status === VacancyStatus::Closed ? ($closeReason ?? VacancyCloseReason::Manual) : null,
      $status === VacancyStatus::Closed ? $closedAt : null,
      $terms,
      $closesAt
    );
  }

  /**
   * @param string|iterable<mixed>|null $topics
   */
  public function revise(
    VacancyTitle $title,
    VacancyDescription $description,
    ?WorkArea $area = null,
    ?Seniority $seniority = null,
    ?WorkMode $workMode = null,
    string|iterable|null $topics = null,
    ?VacancyPositions $positions = null,
    ?EmploymentTerms $terms = null
  ): void {
    self::assertLocationFits($workMode ?? $this->workMode, $terms ?? $this->terms);

    $this->title = $title;
    $this->description = $description;
    if ($area !== null) {
      $this->area = $area;
    }
    if ($seniority !== null) {
      $this->seniority = $seniority;
    }
    if ($workMode !== null) {
      $this->workMode = $workMode;
    }
    if ($topics !== null) {
      $this->topics = TopicTag::listFromInput($topics);
    }
    if ($positions !== null) {
      $this->positions = $positions;
    }
    if ($terms !== null) {
      $this->terms = $terms;
    }
  }

  /**
   * Sets or removes the application deadline. Keeping the current one is always allowed
   * (even if it already passed); a new one cannot be in the past.
   */
  public function reschedule(?VacancyDeadline $closesAt, ?DateTimeImmutable $now = null): void
  {
    if ($closesAt === null ? $this->closesAt === null : $closesAt->equals($this->closesAt)) {
      return;
    }

    self::assertDeadlineAhead($closesAt, $now ?? new DateTimeImmutable());
    $this->closesAt = $closesAt;
  }

  public function assignCompany(?CompanyId $companyId): void
  {
    $this->companyId = $companyId;
  }

  public function belongsToCompany(CompanyId $companyId): bool
  {
    return $this->companyId !== null && $this->companyId->equals($companyId);
  }

  /**
   * Publishes a draft (the publication date becomes now) or reopens a closed vacancy.
   */
  public function open(?DateTimeImmutable $now = null): void
  {
    $now ??= new DateTimeImmutable();
    if ($this->closesAt?->hasPassed($now)) {
      throw new BusinessRuleViolationException(
        'O prazo de inscrição (' . $this->closesAt->label() . ') já passou. Defina um novo prazo ou remova-o para '
        . ($this->isDraft() ? 'publicar' : 'reabrir') . ' a vaga.'
      );
    }

    if ($this->status === VacancyStatus::Draft) {
      $this->publishedAt = $now;
    }

    $this->status = VacancyStatus::Open;
    $this->closeReason = null;
    $this->closedAt = null;
  }

  public function close(VacancyCloseReason $reason = VacancyCloseReason::Manual, ?DateTimeImmutable $now = null): void
  {
    if ($this->status === VacancyStatus::Draft) {
      throw new BusinessRuleViolationException('Um rascunho não pode ser encerrado: publique ou exclua a vaga.');
    }

    $this->status = VacancyStatus::Closed;
    $this->closeReason = $reason;
    $this->closedAt = $now ?? new DateTimeImmutable();
  }

  /**
   * Closes the vacancy when the number of approved candidates reaches the number of positions.
   * Returns true when this call closed it.
   */
  public function closeIfFilled(int $approvedCandidates, ?DateTimeImmutable $now = null): bool
  {
    if (!$this->isOpen() || !$this->positions->areFilledBy($approvedCandidates)) {
      return false;
    }

    $this->close(VacancyCloseReason::Filled, $now);
    return true;
  }

  /**
   * Closes an open vacancy whose deadline has passed. Returns true when this call closed it.
   */
  public function closeIfExpired(?DateTimeImmutable $now = null): bool
  {
    $now ??= new DateTimeImmutable();
    if (!$this->isOpen() || $this->closesAt === null || !$this->closesAt->hasPassed($now)) {
      return false;
    }

    $this->close(VacancyCloseReason::Deadline, $now);
    return true;
  }

  /**
   * Open and within the deadline (the closing job may not have run yet).
   */
  public function acceptsApplications(?DateTimeImmutable $now = null): bool
  {
    return $this->isOpen() && !($this->closesAt?->hasPassed($now) ?? false);
  }

  /**
   * Moves to the requested status; requesting the current status changes nothing.
   */
  public function changeStatus(VacancyStatus $status, ?DateTimeImmutable $now = null): void
  {
    if ($status === $this->status) {
      return;
    }

    match ($status) {
      VacancyStatus::Open => $this->open($now),
      VacancyStatus::Closed => $this->close(VacancyCloseReason::Manual, $now),
      VacancyStatus::Draft => throw new BusinessRuleViolationException('Uma vaga já publicada não pode voltar para rascunho.'),
    };
  }

  public function isOpen(): bool
  {
    return $this->status->isOpen();
  }

  public function isDraft(): bool
  {
    return $this->status === VacancyStatus::Draft;
  }

  public function positions(): VacancyPositions
  {
    return $this->positions;
  }

  public function closeReason(): ?VacancyCloseReason
  {
    return $this->closeReason;
  }

  public function closedAt(): ?DateTimeImmutable
  {
    return $this->closedAt;
  }

  /**
   * Only open vacancies within the deadline accept applications.
   */
  public function receiveApplicationFrom(UserId $candidate, ?DateTimeImmutable $now = null): JobApplication
  {
    if (!$this->acceptsApplications($now)) {
      throw new VacancyNotOpenException();
    }

    return JobApplication::submit($this->id, $candidate, $now);
  }

  /**
   * A candidate who withdrew applies again: allowed only while the vacancy is open.
   */
  public function acceptResubmission(JobApplication $application, UserId $candidate, ?DateTimeImmutable $now = null): void
  {
    if (!$application->vacancyId()->equals($this->id)) {
      throw new BusinessRuleViolationException('A candidatura não pertence a esta vaga.');
    }

    if (!$this->acceptsApplications($now)) {
      throw new VacancyNotOpenException();
    }

    $application->resubmit($candidate, $now);
  }

  public function terms(): EmploymentTerms
  {
    return $this->terms;
  }

  public function closesAt(): ?VacancyDeadline
  {
    return $this->closesAt;
  }

  public function id(): VacancyId
  {
    return $this->id;
  }

  public function title(): VacancyTitle
  {
    return $this->title;
  }

  public function description(): VacancyDescription
  {
    return $this->description;
  }

  public function status(): VacancyStatus
  {
    return $this->status;
  }

  public function publishedAt(): DateTimeImmutable
  {
    return $this->publishedAt;
  }

  public function companyId(): ?CompanyId
  {
    return $this->companyId;
  }

  public function area(): WorkArea
  {
    return $this->area;
  }

  public function seniority(): Seniority
  {
    return $this->seniority;
  }

  public function workMode(): WorkMode
  {
    return $this->workMode;
  }

  /**
   * @return list<TopicTag>
   */
  public function topics(): array
  {
    return $this->topics;
  }

  /**
   * @return list<string>
   */
  public function topicLabels(): array
  {
    return array_map(static fn (TopicTag $t): string => $t->label(), $this->topics);
  }

  private static function assertLocationFits(WorkMode $workMode, EmploymentTerms $terms): void
  {
    if ($workMode !== WorkMode::Remoto && $terms->location() === null) {
      throw new BusinessRuleViolationException(
        'Informe a cidade e a UF: vagas ' . ($workMode === WorkMode::Hibrido ? 'híbridas' : 'presenciais')
        . ' precisam dizer onde é o trabalho.'
      );
    }
  }

  private static function assertDeadlineAhead(?VacancyDeadline $closesAt, DateTimeImmutable $now): void
  {
    if ($closesAt !== null && $closesAt->hasPassed($now)) {
      throw new BusinessRuleViolationException('O prazo de inscrição não pode estar no passado.');
    }
  }
}
