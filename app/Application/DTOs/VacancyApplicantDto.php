<?php

namespace App\Application\DTOs;

/**
 * Candidate application enriched with profile details, vacancy match score and pipeline state.
 */
final class VacancyApplicantDto
{
  /**
   * @param list<string> $topics
   * @param list<string> $matchedTopics
   * @param list<string> $missingTopics
   * @param list<ApplicationEventDto> $history oldest first
   * @param list<ApplicationNoteDto> $notes oldest first
   */
  public function __construct(
    public readonly string $applicationId,
    public readonly string $appliedAt,
    public readonly string $userId,
    public readonly string $name,
    public readonly string $email,
    public readonly string $headline,
    public readonly string $bio,
    public readonly string $areaLabel,
    public readonly string $seniorityLabel,
    public readonly string $workModeLabel,
    public readonly string $linkedinUrl,
    public readonly string $githubUrl,
    public readonly array $topics,
    public readonly bool $hasProfile,
    public readonly int $matchScore,
    public readonly string $matchLevel,
    public readonly array $matchedTopics,
    public readonly array $missingTopics,
    public readonly string $status = 'recebida',
    public readonly string $statusLabel = 'Recebida',
    public readonly string $statusChangedAt = '',
    public readonly string $candidateFeedback = '',
    public readonly ?string $nextStatusLabel = null,
    public readonly bool $canReject = false,
    public readonly bool $canReopen = false,
    public readonly array $history = [],
    public readonly array $notes = []
  ) {
  }

  public function canAdvance(): bool
  {
    return $this->nextStatusLabel !== null;
  }
}
