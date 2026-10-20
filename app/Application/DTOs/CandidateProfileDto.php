<?php

namespace App\Application\DTOs;

use App\Domain\CandidateProfile\CandidateProfile;

final class CandidateProfileDto
{
  /**
   * @param list<string> $topics
   */
  public function __construct(
    public readonly string $userId,
    public readonly string $headline,
    public readonly string $bio,
    public readonly string $area,
    public readonly string $areaLabel,
    public readonly string $seniority,
    public readonly string $seniorityLabel,
    public readonly string $workMode,
    public readonly string $workModeLabel,
    public readonly string $linkedinUrl,
    public readonly string $githubUrl,
    public readonly array $topics,
    public readonly bool $isComplete
  ) {
  }

  public static function fromProfile(CandidateProfile $profile): self
  {
    return new self(
      $profile->userId()->value(),
      $profile->headline(),
      $profile->bio(),
      $profile->area()->value,
      $profile->area()->label(),
      $profile->seniority()->value,
      $profile->seniority()->label(),
      $profile->workMode()->value,
      $profile->workMode()->label(),
      $profile->linkedinUrl(),
      $profile->githubUrl(),
      $profile->topicLabels(),
      $profile->isComplete()
    );
  }
}
