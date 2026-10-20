<?php

namespace App\Application\Features\Vacancies;

use App\Application\Abstractions\Query;
use App\Application\Abstractions\QueryHandlerInterface;
use App\Application\DTOs\VacancyDto;
use App\Application\Queries\Vacancies\GetVacancyByIdQuery;
use App\Domain\Company\CompanyRepositoryInterface;
use App\Domain\JobApplication\JobApplicationRepositoryInterface;
use App\Domain\Vacancy\VacancyId;
use App\Domain\Vacancy\VacancyRepositoryInterface;
use InvalidArgumentException;

final class GetVacancyByIdHandler implements QueryHandlerInterface
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
    return GetVacancyByIdQuery::class;
  }

  public function handle(Query $query): ?VacancyDto
  {
    if (!$query instanceof GetVacancyByIdQuery) {
      throw new InvalidArgumentException('Invalid query for ' . self::class);
    }

    if (!VacancyId::isValid($query->vacancyId)) {
      return null;
    }

    $vacancy = $this->vacancies->get(VacancyId::fromString($query->vacancyId));
    if ($vacancy === null) {
      return null;
    }

    ($this->scope ?? new VacancyAccessScope())->assertCanManage($vacancy);

    $company = ($vacancy->companyId() !== null && $this->companies !== null)
      ? $this->companies->get($vacancy->companyId())
      : null;
    $applicantCount = $this->applications !== null
      ? ($this->applications->countPerVacancy()[$vacancy->id()->value()] ?? 0)
      : 0;

    $filled = $this->applications !== null ? $this->applications->approvedCountFor($vacancy->id()) : 0;

    return VacancyDto::fromVacancy($vacancy, $company, $applicantCount, null, $filled);
  }
}
