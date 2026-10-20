<?php

namespace App\Domain\Vacancy;

use App\Domain\JobApplication\JobApplication;
use App\Domain\User\UserId;
use DateTimeImmutable;

/**
 * Vacancy aggregate: a job opening that may receive applications while active.
 */
final class Vacancy
{
  private function __construct(
    private VacancyId $id,
    private VacancyTitle $title,
    private VacancyDescription $description,
    private VacancyStatus $status,
    private DateTimeImmutable $publishedAt
  ) {
  }

  public static function publish(
    VacancyId $id,
    VacancyTitle $title,
    VacancyDescription $description,
    VacancyStatus $status = VacancyStatus::Active,
    ?DateTimeImmutable $now = null
  ): self {
    return new self($id, $title, $description, $status, $now ?? new DateTimeImmutable());
  }

  public static function reconstitute(
    VacancyId $id,
    VacancyTitle $title,
    VacancyDescription $description,
    VacancyStatus $status,
    DateTimeImmutable $publishedAt
  ): self {
    return new self($id, $title, $description, $status, $publishedAt);
  }

  public function revise(VacancyTitle $title, VacancyDescription $description): void
  {
    $this->title = $title;
    $this->description = $description;
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
}
