<?php

namespace App\Application\Features\Vacancies;

use App\Domain\Vacancy\ContractType;
use App\Domain\Vacancy\EmploymentTerms;
use App\Domain\Vacancy\SalaryRange;
use App\Domain\Vacancy\VacancyLocation;

/**
 * Builds EmploymentTerms from the primitive fields of the vacancy commands.
 */
final class EmploymentTermsInput
{
  private function __construct()
  {
  }

  /**
   * @param string|list<string> $benefits
   */
  public static function toTerms(
    string $contractType,
    ?int $salaryMin,
    ?int $salaryMax,
    ?string $city,
    ?string $state,
    string|array $benefits
  ): EmploymentTerms {
    return EmploymentTerms::of(
      ContractType::fromString($contractType),
      SalaryRange::of($salaryMin, $salaryMax),
      VacancyLocation::fromInput($city, $state),
      $benefits
    );
  }
}
