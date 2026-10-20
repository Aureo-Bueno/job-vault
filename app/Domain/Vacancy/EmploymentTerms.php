<?php

namespace App\Domain\Vacancy;

/**
 * What the company offers: contract type, salary, workplace and benefits.
 */
final class EmploymentTerms
{
  /**
   * @param list<Benefit> $benefits
   */
  private function __construct(
    private ContractType $contractType,
    private SalaryRange $salary,
    private ?VacancyLocation $location,
    private array $benefits
  ) {
  }

  /**
   * @param string|iterable<mixed> $benefits labels or Benefit instances
   */
  public static function of(
    ContractType $contractType = ContractType::Clt,
    ?SalaryRange $salary = null,
    ?VacancyLocation $location = null,
    string|iterable $benefits = []
  ): self {
    return new self($contractType, $salary ?? SalaryRange::negotiable(), $location, Benefit::listFromInput($benefits));
  }

  /**
   * CLT, "A combinar", no location, no benefits.
   */
  public static function standard(): self
  {
    return new self(ContractType::Clt, SalaryRange::negotiable(), null, []);
  }

  /**
   * @param list<Benefit> $benefits
   */
  public static function reconstitute(ContractType $contractType, SalaryRange $salary, ?VacancyLocation $location, array $benefits): self
  {
    return new self($contractType, $salary, $location, array_values($benefits));
  }

  public function contractType(): ContractType
  {
    return $this->contractType;
  }

  public function salary(): SalaryRange
  {
    return $this->salary;
  }

  public function location(): ?VacancyLocation
  {
    return $this->location;
  }

  /**
   * @return list<Benefit>
   */
  public function benefits(): array
  {
    return $this->benefits;
  }

  /**
   * @return list<string>
   */
  public function benefitLabels(): array
  {
    return array_map(static fn (Benefit $b): string => $b->label(), $this->benefits);
  }
}
