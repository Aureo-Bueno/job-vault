<?php

namespace App\Domain\Matching;

/**
 * Value object describing the compatibility between a candidate profile and a vacancy.
 */
final class MatchResult
{
  /**
   * @param list<string> $matchedTopics
   * @param list<string> $missingTopics
   */
  public function __construct(
    public readonly int $score,
    public readonly array $matchedTopics,
    public readonly array $missingTopics,
    public readonly bool $areaMatched,
    public readonly bool $seniorityMatched,
    public readonly bool $workModeMatched
  ) {
  }

  /**
   * Semantic tier used by presentation badges ('high' >= 70, 'medium' >= 40, 'low' < 40).
   */
  public function level(): string
  {
    if ($this->score >= 70) {
      return 'high';
    }

    if ($this->score >= 40) {
      return 'medium';
    }

    return 'low';
  }
}
