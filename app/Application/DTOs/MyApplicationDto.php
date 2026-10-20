<?php

namespace App\Application\DTOs;

/**
 * An application as seen by the candidate (no internal notes, no recruiter names).
 */
final class MyApplicationDto
{
  /**
   * @param list<ApplicationEventDto> $timeline oldest first
   */
  public function __construct(
    public readonly string $applicationId,
    public readonly string $vacancyId,
    public readonly string $vacancyTitle,
    public readonly string $companyName,
    public readonly bool $vacancyOpen,
    public readonly string $status,
    public readonly string $statusLabel,
    public readonly string $appliedAt,
    public readonly string $statusChangedAt,
    public readonly string $feedback,
    public readonly bool $canWithdraw,
    public readonly bool $canReapply,
    public readonly array $timeline
  ) {
  }
}
