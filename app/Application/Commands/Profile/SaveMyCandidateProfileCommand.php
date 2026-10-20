<?php

namespace App\Application\Commands\Profile;

use App\Application\Abstractions\Command;

final class SaveMyCandidateProfileCommand implements Command
{
  /**
   * @param string|list<string> $topics
   */
  public function __construct(
    public readonly string $headline,
    public readonly string $bio,
    public readonly string $area,
    public readonly string $seniority,
    public readonly string $workMode,
    public readonly string $linkedinUrl = '',
    public readonly string $githubUrl = '',
    public readonly string|array $topics = []
  ) {
  }
}
