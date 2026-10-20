<?php

namespace App\Application\Features\Vacancies;

use App\Application\Abstractions\Query;
use App\Application\Abstractions\QueryHandlerInterface;
use App\Application\DTOs\VacancyDto;
use App\Application\Queries\Vacancies\ListOpenVacanciesQuery;
use App\Application\Security\CurrentUserProvider;
use App\Domain\CandidateProfile\CandidateProfileRepositoryInterface;
use App\Domain\Company\Company;
use App\Domain\Company\CompanyRepositoryInterface;
use App\Domain\Matching\MatchCalculator;
use App\Domain\Shared\PageRequest;
use App\Domain\Shared\SearchTerm;
use App\Domain\Shared\WorkArea;
use App\Domain\Shared\WorkMode;
use App\Domain\User\UserId;
use App\Domain\Vacancy\Vacancy;
use App\Domain\Vacancy\VacancyFilter;
use App\Domain\Vacancy\VacancyRepositoryInterface;
use InvalidArgumentException;

final class ListOpenVacanciesHandler implements QueryHandlerInterface
{
  public function __construct(
    private VacancyRepositoryInterface $vacancies,
    private ?CompanyRepositoryInterface $companies = null,
    private ?CandidateProfileRepositoryInterface $profiles = null,
    private ?CurrentUserProvider $currentUser = null,
    private ?MatchCalculator $calculator = null
  ) {
  }

  public function queryClass(): string
  {
    return ListOpenVacanciesQuery::class;
  }

  /** @return VacancyDto[] */
  public function handle(Query $query): array
  {
    if (!$query instanceof ListOpenVacanciesQuery) {
      throw new InvalidArgumentException('Invalid query for ' . self::class);
    }

    $area = ($query->area !== null && trim($query->area) !== '')
      ? WorkArea::tryFrom(strtolower(trim($query->area)))
      : null;
    $workMode = ($query->workMode !== null && trim($query->workMode) !== '')
      ? WorkMode::tryFrom(strtolower(trim($query->workMode)))
      : null;

    $filter = VacancyFilter::openOnly(
      SearchTerm::tryFrom($query->search),
      $area,
      $workMode
    );

    $vacancies = $this->vacancies->matching($filter, PageRequest::all());
    $companyById = $this->loadCompanyMap();
    $logged = $this->currentUser?->currentUser();
    $candidateProfile = ($logged !== null && $this->profiles !== null && UserId::isValid($logged->id))
      ? $this->profiles->getByUserId(UserId::fromString($logged->id))
      : null;

    $calculator = $this->calculator ?? new MatchCalculator();
    $items = array_map(
      static function (Vacancy $vacancy) use ($companyById, $candidateProfile, $calculator): VacancyDto {
        $cid = $vacancy->companyId()?->value();
        $company = $cid !== null ? ($companyById[$cid] ?? null) : null;
        $match = $candidateProfile !== null ? $calculator->calculate($candidateProfile, $vacancy) : null;

        return VacancyDto::fromVacancy($vacancy, $company, 0, $match);
      },
      $vacancies
    );

    if ($query->sort === 'match' && $candidateProfile !== null) {
      usort(
        $items,
        static fn (VacancyDto $a, VacancyDto $b): int => ($b->matchScore ?? 0) <=> ($a->matchScore ?? 0)
          ?: strcmp($b->createdAt, $a->createdAt)
      );
    }

    return $items;
  }

  /**
   * @return array<string,Company>
   */
  private function loadCompanyMap(): array
  {
    if ($this->companies === null) {
      return [];
    }

    $map = [];
    foreach ($this->companies->all() as $company) {
      $map[$company->id()->value()] = $company;
    }

    return $map;
  }
}
