<?php

namespace App\Domain\JobApplication;

use App\Domain\User\UserId;
use DateTimeImmutable;

/**
 * One entry of an application's history: a stage change, who made it and when.
 * `reason` is visible to the candidate (e.g. rejection feedback); internal remarks go in ApplicationNote.
 */
final class ApplicationEvent
{
  public function __construct(
    public readonly ?ApplicationStatus $from,
    public readonly ApplicationStatus $to,
    public readonly ?UserId $actorId,
    public readonly ?string $reason,
    public readonly DateTimeImmutable $at
  ) {
  }
}
