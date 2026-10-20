<?php

namespace Tests\Support;

use App\Domain\Company\Company;
use App\Domain\Company\CompanyId;
use App\Domain\Company\CompanyName;
use App\Domain\Company\CompanyRepositoryInterface;

final class InMemoryCompanyRepository implements CompanyRepositoryInterface
{
  /** @var array<string,Company> */
  private array $companies = [];

  public function get(CompanyId $id): ?Company
  {
    return isset($this->companies[$id->value()]) ? clone $this->companies[$id->value()] : null;
  }

  public function findByName(CompanyName $name): ?Company
  {
    foreach ($this->companies as $company) {
      if ($company->name()->equals($name)) {
        return clone $company;
      }
    }

    return null;
  }

  public function all(): array
  {
    $list = array_map(static fn (Company $c): Company => clone $c, array_values($this->companies));
    usort($list, static fn (Company $a, Company $b): int => strcmp($a->name()->value(), $b->name()->value()));

    return $list;
  }

  public function save(Company $company): void
  {
    $this->companies[$company->id()->value()] = clone $company;
  }

  public function remove(CompanyId $id): void
  {
    unset($this->companies[$id->value()]);
  }
}
