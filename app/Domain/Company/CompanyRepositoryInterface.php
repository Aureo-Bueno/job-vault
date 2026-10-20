<?php

namespace App\Domain\Company;

interface CompanyRepositoryInterface
{
  public function get(CompanyId $id): ?Company;

  public function findByName(CompanyName $name): ?Company;

  /** @return Company[] */
  public function all(): array;

  public function save(Company $company): void;

  public function remove(CompanyId $id): void;
}
