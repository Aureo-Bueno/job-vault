<?php

namespace App\Domain\Vacancy;

use App\Domain\Company\CompanyId;
use App\Domain\JobApplication\JobApplication;
use App\Domain\Shared\Seniority;
use App\Domain\Shared\TopicTag;
use App\Domain\Shared\WorkArea;
use App\Domain\Shared\WorkMode;
use App\Domain\User\UserId;
use DateTimeImmutable;

/**
 * Vacancy aggregate: a job opening published by a company that may receive applications while active.
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
    private array $topics = []
  ) {
  }

  /**
   * @param string|iterable<mixed> $topics
   */
  public static function publish(
    VacancyId $id,
    VacancyTitle $title,
    VacancyDescription $description,
    VacancyStatus $status = VacancyStatus::Active,
    ?DateTimeImmutable $now = null,
    ?CompanyId $companyId = null,
    WorkArea $area = WorkArea::Backend,
    Seniority $seniority = Seniority::Pleno,
    WorkMode $workMode = WorkMode::Remoto,
    string|iterable $topics = []
  ): self {
    return new self(
      $id,
      $title,
      $description,
      $status,
      $now ?? new DateTimeImmutable(),
      $companyId,
      $area,
      $seniority,
      $workMode,
      TopicTag::listFromInput($topics)
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
    array $topics = []
  ): self {
    return new self($id, $title, $description, $status, $publishedAt, $companyId, $area, $seniority, $workMode, array_values($topics));
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
    string|iterable|null $topics = null
  ): void {
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
  }

  public function assignCompany(?CompanyId $companyId): void
  {
    $this->companyId = $companyId;
  }

  public function belongsToCompany(CompanyId $companyId): bool
  {
    return $this->companyId !== null && $this->companyId->equals($companyId);
  }

  public function open(): void
  {
    $this->status = VacancyStatus::Active;
  }

  public function close(): void
  {
    $this->status = VacancyStatus::Inactive;
  }

  public function changeStatus(VacancyStatus $status): void
  {
    $status->isActive() ? $this->open() : $this->close();
  }

  public function isOpen(): bool
  {
    return $this->status->isActive();
  }

  /**
   * Only open vacancies accept applications.
   */
  public function receiveApplicationFrom(UserId $candidate, ?DateTimeImmutable $now = null): JobApplication
  {
    if (!$this->isOpen()) {
      throw new VacancyNotOpenException();
    }

    return JobApplication::submit($this->id, $candidate, $now);
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
}
