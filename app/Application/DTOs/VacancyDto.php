<?php

namespace App\Application\DTOs;

use App\Domain\Company\Company;
use App\Domain\Matching\MatchResult;
use App\Domain\Vacancy\Vacancy;

final class VacancyDto
{
  /**
   * @param list<string> $topics
   * @param list<string> $matchedTopics
   * @param list<string> $missingTopics
   */
  public function __construct(
    public readonly string $id,
    public readonly string $title,
    public readonly string $description,
    public readonly bool $isActive,
    public readonly string $createdAt,
    public readonly ?string $companyId = null,
    public readonly string $companyName = '',
    public readonly string $companySector = '',
    public readonly string $area = 'backend',
    public readonly string $areaLabel = 'Backend',
    public readonly string $seniority = 'pleno',
    public readonly string $seniorityLabel = 'Pleno',
    public readonly string $workMode = 'remoto',
    public readonly string $workModeLabel = 'Remoto',
    public readonly array $topics = [],
    public readonly int $applicantCount = 0,
    public readonly ?int $matchScore = null,
    public readonly string $matchLevel = 'low',
    public readonly array $matchedTopics = [],
    public readonly array $missingTopics = []
  ) {
  }

  public static function fromVacancy(
    Vacancy $vacancy,
    ?Company $company = null,
    int $applicantCount = 0,
    ?MatchResult $match = null
  ): self {
    return new self(
      $vacancy->id()->value(),
      $vacancy->title()->value(),
      $vacancy->description()->value(),
      $vacancy->isOpen(),
      $vacancy->publishedAt()->format('Y-m-d H:i:s'),
      $vacancy->companyId()?->value(),
      $company !== null ? $company->name()->value() : '',
      $company !== null ? $company->sector() : '',
      $vacancy->area()->value,
      $vacancy->area()->label(),
      $vacancy->seniority()->value,
      $vacancy->seniority()->label(),
      $vacancy->workMode()->value,
      $vacancy->workMode()->label(),
      $vacancy->topicLabels(),
      $applicantCount,
      $match?->score,
      $match !== null ? $match->level() : 'low',
      $match !== null ? $match->matchedTopics : [],
      $match !== null ? $match->missingTopics : $vacancy->topicLabels()
    );
  }
}
