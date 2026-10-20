<?php

namespace App\Domain\Matching;

use App\Domain\CandidateProfile\CandidateProfile;
use App\Domain\Vacancy\Vacancy;

/**
 * Pure domain service that scores the compatibility (0..100%) between a candidate and a vacancy.
 */
final class MatchCalculator
{
  public function calculate(?CandidateProfile $profile, Vacancy $vacancy): MatchResult
  {
    if ($profile === null) {
      return new MatchResult(
        0,
        [],
        $vacancy->topicLabels(),
        false,
        false,
        false
      );
    }

    $candidateTopicSlugs = [];
    foreach ($profile->topics() as $topic) {
      $candidateTopicSlugs[$topic->slug()] = true;
    }

    $matchedTopics = [];
    $missingTopics = [];
    foreach ($vacancy->topics() as $vacancyTopic) {
      if (isset($candidateTopicSlugs[$vacancyTopic->slug()])) {
        $matchedTopics[] = $vacancyTopic->label();
      } else {
        $missingTopics[] = $vacancyTopic->label();
      }
    }

    $areaAffinity = $profile->area()->affinityWith($vacancy->area());
    $seniorityAffinity = $profile->seniority()->affinityWith($vacancy->seniority());
    $workModeAffinity = $profile->workMode()->affinityWith($vacancy->workMode());

    $totalVacancyTopics = count($vacancy->topics());
    if ($totalVacancyTopics > 0) {
      $topicRatio = count($matchedTopics) / $totalVacancyTopics;
      $raw = ($topicRatio * 50.0)
        + ($areaAffinity * 25.0)
        + ($seniorityAffinity * 15.0)
        + ($workModeAffinity * 10.0);
    } else {
      $raw = ($areaAffinity * 50.0)
        + ($seniorityAffinity * 30.0)
        + ($workModeAffinity * 20.0);
    }

    $score = max(0, min(100, (int) round($raw)));

    return new MatchResult(
      $score,
      $matchedTopics,
      $missingTopics,
      $areaAffinity >= 0.65,
      $seniorityAffinity >= 0.5,
      $workModeAffinity >= 0.7
    );
  }
}
