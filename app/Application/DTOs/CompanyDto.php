<?php

namespace App\Application\DTOs;

use App\Domain\Company\Company;

final class CompanyDto
{
  public function __construct(
    public readonly string $id,
    public readonly string $name,
    public readonly string $sector,
    public readonly string $website,
    public readonly string $description,
    public readonly string $createdAt,
    public readonly int $userCount = 0
  ) {
  }

  public static function fromCompany(Company $company, int $userCount = 0): self
  {
    return new self(
      $company->id()->value(),
      $company->name()->value(),
      $company->sector(),
      $company->website(),
      $company->description(),
      $company->createdAt()->format('Y-m-d H:i:s'),
      $userCount
    );
  }
}
