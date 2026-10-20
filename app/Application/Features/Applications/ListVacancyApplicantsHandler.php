<?php

namespace App\Application\Features\Applications;

use App\Application\Abstractions\Query;
use App\Application\Abstractions\QueryHandlerInterface;
use App\Application\DTOs\VacancyApplicantDto;
use App\Application\Features\Vacancies\VacancyAccessScope;
use App\Application\Features\Vacancies\VacancyLoader;
use App\Application\Queries\Applications\ListVacancyApplicantsQuery;
use App\Domain\CandidateProfile\CandidateProfileRepositoryInterface;
use App\Domain\JobApplication\ApplicationStatus;
use App\Domain\JobApplication\JobApplication;
use App\Domain\JobApplication\JobApplicationRepositoryInterface;
use App\Domain\Matching\MatchCalculator;
use App\Domain\User\UserId;
use App\Domain\User\UserRepositoryInterface;
use App\Domain\Vacancy\VacancyRepositoryInterface;
use InvalidArgumentException;

final class ListVacancyApplicantsHandler implements QueryHandlerInterface
{
  public function __construct(
    private VacancyRepositoryInterface $vacancies,
    private JobApplicationRepositoryInterface $applications,
    private UserRepositoryInterface $users,
    private CandidateProfileRepositoryInterface $profiles,
    private ?VacancyAccessScope $scope = null,
    private ?MatchCalculator $calculator = null
  ) {
  }

  public function queryClass(): string
  {
    return ListVacancyApplicantsQuery::class;
  }

  /**
   * @return list<VacancyApplicantDto>
   */
  public function handle(Query $query): array
  {
    if (!$query instanceof ListVacancyApplicantsQuery) {
      throw new InvalidArgumentException('Invalid query for ' . self::class);
    }

    $vacancy = VacancyLoader::load($this->vacancies, $query->vacancyId);
    ($this->scope ?? new VacancyAccessScope())->assertCanManage($vacancy);

    $applications = $this->applications->forVacancy($vacancy->id());
    if ($applications === []) {
      return [];
    }

    $candidateIds = array_map(static fn (JobApplication $a): UserId => $a->candidateId(), $applications);
    $profilesByUser = $this->profiles->getByUserIds($candidateIds);
    $notesByApplication = $this->applications->notesFor(
      array_map(static fn (JobApplication $a) => $a->id(), $applications)
    );
    $calculator = $this->calculator ?? new MatchCalculator();
    $views = new ApplicationViews($this->users);

    $items = [];
    foreach ($applications as $application) {
      $candidateId = $application->candidateId();
      $user = $this->users->get($candidateId);
      if ($user === null) {
        continue;
      }

      $profile = $profilesByUser[$candidateId->value()] ?? null;
      $match = $calculator->calculate($profile, $vacancy);
      $status = $application->status();

      $items[] = new VacancyApplicantDto(
        $application->id()->value(),
        $application->submittedAt()->format('Y-m-d H:i:s'),
        $user->id()->value(),
        $user->name()->value(),
        $user->email()->value(),
        $profile?->headline() ?? '',
        $profile?->bio() ?? '',
        $profile !== null ? $profile->area()->label() : 'Não informado',
        $profile !== null ? $profile->seniority()->label() : 'Não informado',
        $profile !== null ? $profile->workMode()->label() : 'Não informado',
        $profile?->linkedinUrl() ?? '',
        $profile?->githubUrl() ?? '',
        $profile !== null ? $profile->topicLabels() : [],
        $profile !== null && $profile->isComplete(),
        $match->score,
        $match->level(),
        $match->matchedTopics,
        $match->missingTopics,
        $status->value,
        $status->label(),
        $application->statusChangedAt()->format('Y-m-d H:i:s'),
        $application->candidateFeedback() ?? '',
        $status->next()?->label(),
        $status->canTransitionTo(ApplicationStatus::Rejected),
        $status === ApplicationStatus::Rejected,
        $views->recruiterHistory($application),
        $views->notes($notesByApplication[$application->id()->value()] ?? [])
      );
    }

    usort(
      $items,
      static fn (VacancyApplicantDto $a, VacancyApplicantDto $b): int => $b->matchScore <=> $a->matchScore
        ?: strcmp($b->appliedAt, $a->appliedAt)
    );

    return $items;
  }
}
