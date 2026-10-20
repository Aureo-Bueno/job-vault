<?php

namespace App\Application\DTOs;

/**
 * Candidate application enriched with profile details and vacancy match score.
 */
final class VacancyApplicantDto
{
  /**
   * @param list<string> $topics
   * @param list<string> $matchedTopics
   * @param list<string> $missingTopics
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
    public readonly array $missingTopics
  ) {
  }
}
