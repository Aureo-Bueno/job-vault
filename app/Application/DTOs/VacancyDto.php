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
   * @param list<string> $benefits
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
    public readonly array $missingTopics = [],
    public readonly string $status = 'aberta',
    public readonly string $statusLabel = 'Aberta',
    public readonly string $closeReasonLabel = '',
    public readonly int $positions = 1,
    public readonly int $filledPositions = 0,
    public readonly string $contractType = 'clt',
    public readonly string $contractTypeLabel = 'CLT',
    public readonly ?int $salaryMin = null,
    public readonly ?int $salaryMax = null,
    public readonly string $salaryLabel = 'A combinar',
    public readonly string $city = '',
    public readonly string $state = '',
    public readonly string $locationLabel = '',
    public readonly array $benefits = [],
    public readonly string $closesAt = '',
    public readonly string $closesAtLabel = '',
    public readonly bool $acceptsApplications = true
  ) {
  }

  public function isDraft(): bool
  {
    return $this->status === 'rascunho';
  }

  public function hasDeadline(): bool
  {
    return $this->closesAt !== '';
  }

  public function isSalaryNegotiable(): bool
  {
    return $this->salaryMin === null && $this->salaryMax === null;
  }

  public static function fromVacancy(
    Vacancy $vacancy,
    ?Company $company = null,
    int $applicantCount = 0,
    ?MatchResult $match = null,
    int $filledPositions = 0
  ): self {
    $terms = $vacancy->terms();
    $location = $terms->location();

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
      $match !== null ? $match->missingTopics : $vacancy->topicLabels(),
      $vacancy->status()->value,
      $vacancy->status()->label(),
      $vacancy->closeReason()?->label() ?? '',
      $vacancy->positions()->value(),
      $filledPositions,
      $terms->contractType()->value,
      $terms->contractType()->label(),
      $terms->salary()->min(),
      $terms->salary()->max(),
      $terms->salary()->label(),
      $location?->city() ?? '',
      $location?->state()->value ?? '',
      $location?->label() ?? '',
      $terms->benefitLabels(),
      $vacancy->closesAt()?->value() ?? '',
      $vacancy->closesAt()?->label() ?? '',
      $vacancy->acceptsApplications()
    );
  }
}
