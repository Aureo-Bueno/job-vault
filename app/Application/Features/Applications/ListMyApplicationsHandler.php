<?php

namespace App\Application\Features\Applications;

use App\Application\Abstractions\Query;
use App\Application\Abstractions\QueryHandlerInterface;
use App\Application\DTOs\MyApplicationDto;
use App\Application\Queries\Applications\ListMyApplicationsQuery;
use App\Application\Security\CurrentUserProvider;
use App\Domain\Company\CompanyRepositoryInterface;
use App\Domain\JobApplication\ApplicationStatus;
use App\Domain\JobApplication\JobApplicationRepositoryInterface;
use App\Domain\User\UserId;
use App\Domain\Vacancy\VacancyRepositoryInterface;
use InvalidArgumentException;

/**
 * "Minhas candidaturas": the current user's applications, most recently updated first.
 */
final class ListMyApplicationsHandler implements QueryHandlerInterface
{
  public function __construct(
    private JobApplicationRepositoryInterface $applications,
    private VacancyRepositoryInterface $vacancies,
    private CompanyRepositoryInterface $companies,
    private CurrentUserProvider $currentUser
  ) {
  }

  public function queryClass(): string
  {
    return ListMyApplicationsQuery::class;
  }

  /**
   * @return list<MyApplicationDto>
   */
  public function handle(Query $query): array
  {
    if (!$query instanceof ListMyApplicationsQuery) {
      throw new InvalidArgumentException('Invalid query for ' . self::class);
    }

    $current = $this->currentUser->currentUser();
    if ($current === null || !UserId::isValid($current->id)) {
      return [];
    }

    $companyNames = [];
    $items = [];
    foreach ($this->applications->forCandidate(UserId::fromString($current->id)) as $application) {
      $vacancy = $this->vacancies->get($application->vacancyId());
      if ($vacancy === null) {
        continue;
      }

      $companyName = '';
      if ($vacancy->companyId() !== null) {
        $key = $vacancy->companyId()->value();
        $companyName = $companyNames[$key] ??= $this->companies->get($vacancy->companyId())?->name()->value() ?? '';
      }

      $status = $application->status();
      $items[] = new MyApplicationDto(
        $application->id()->value(),
        $vacancy->id()->value(),
        $vacancy->title()->value(),
        $companyName,
        $vacancy->isOpen(),
        $status->value,
        $status->candidateLabel(),
        $application->submittedAt()->format('Y-m-d H:i:s'),
        $application->statusChangedAt()->format('Y-m-d H:i:s'),
        $application->candidateFeedback() ?? '',
        $status->isInProcess(),
        $status === ApplicationStatus::Withdrawn && $vacancy->isOpen(),
        ApplicationViews::candidateTimeline($application)
      );
    }

    return $items;
  }
}
