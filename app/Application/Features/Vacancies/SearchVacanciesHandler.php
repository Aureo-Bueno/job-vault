<?php

namespace App\Application\Features\Vacancies;

use App\Application\Abstractions\Query;
use App\Application\Abstractions\QueryHandlerInterface;
use App\Application\DTOs\PagedResult;
use App\Application\DTOs\VacancyDto;
use App\Application\Queries\Vacancies\SearchVacanciesQuery;
use App\Domain\Company\Company;
use App\Domain\Company\CompanyRepositoryInterface;
use App\Domain\JobApplication\JobApplicationRepositoryInterface;
use App\Domain\Shared\PageRequest;
use App\Domain\Shared\SearchTerm;
use App\Domain\Shared\WorkArea;
use App\Domain\Vacancy\Vacancy;
use App\Domain\Vacancy\VacancyFilter;
use App\Domain\Vacancy\VacancyRepositoryInterface;
use App\Domain\Vacancy\VacancyStatus;
use InvalidArgumentException;

final class SearchVacanciesHandler implements QueryHandlerInterface
{
  public function __construct(
    private VacancyRepositoryInterface $vacancies,
    private ?VacancyAccessScope $scope = null,
    private ?CompanyRepositoryInterface $companies = null,
    private ?JobApplicationRepositoryInterface $applications = null
  ) {
  }

  public function queryClass(): string
  {
    return SearchVacanciesQuery::class;
  }

  /** @return PagedResult<VacancyDto> */
  public function handle(Query $query): PagedResult
  {
    if (!$query instanceof SearchVacanciesQuery) {
      throw new InvalidArgumentException('Invalid query for ' . self::class);
    }

    $scope = $this->scope ?? new VacancyAccessScope(companies: $this->companies);
    $companyId = $scope->resolveFilterCompany($query->companyId);
    $area = ($query->area !== null && trim($query->area) !== '')
      ? WorkArea::tryFrom(strtolower(trim($query->area)))
      : null;

    $filter = new VacancyFilter(
      SearchTerm::tryFrom($query->search),
      $query->status === null || $query->status === '' ? null : VacancyStatus::fromString($query->status),
      $companyId,
      $area
    );

    $total = $this->vacancies->countMatching($filter);
    $page = (new PageRequest($query->page, $query->perPage))->clampTo($total);
    $companyById = $this->loadCompanyMap();
    $applicantCounts = $this->applications !== null ? $this->applications->countPerVacancy() : [];
    $approvedCounts = $this->applications !== null ? $this->applications->approvedPerVacancy() : [];

    $items = array_map(
      static function (Vacancy $vacancy) use ($companyById, $applicantCounts, $approvedCounts): VacancyDto {
        $cid = $vacancy->companyId()?->value();
        $company = $cid !== null ? ($companyById[$cid] ?? null) : null;
        $vid = $vacancy->id()->value();

        return VacancyDto::fromVacancy($vacancy, $company, $applicantCounts[$vid] ?? 0, null, $approvedCounts[$vid] ?? 0);
      },
      $this->vacancies->matching($filter, $page)
    );

    return new PagedResult($items, $total, $page->number, $page->size);
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
