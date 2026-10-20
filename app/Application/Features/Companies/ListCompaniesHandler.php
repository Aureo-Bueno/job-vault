<?php

namespace App\Application\Features\Companies;

use App\Application\Abstractions\Query;
use App\Application\Abstractions\QueryHandlerInterface;
use App\Application\DTOs\CompanyDto;
use App\Application\Queries\Companies\ListCompaniesQuery;
use App\Domain\Company\Company;
use App\Domain\Company\CompanyRepositoryInterface;
use App\Domain\User\UserRepositoryInterface;
use InvalidArgumentException;

final class ListCompaniesHandler implements QueryHandlerInterface
{
  public function __construct(
    private CompanyRepositoryInterface $companies,
    private ?UserRepositoryInterface $users = null
  ) {
  }

  public function queryClass(): string
  {
    return ListCompaniesQuery::class;
  }

  /** @return CompanyDto[] */
  public function handle(Query $query): array
  {
    if (!$query instanceof ListCompaniesQuery) {
      throw new InvalidArgumentException('Invalid query for ' . self::class);
    }

    $counts = $this->users !== null ? $this->users->countPerCompany() : [];

    return array_map(
      static fn (Company $c): CompanyDto => CompanyDto::fromCompany($c, $counts[$c->id()->value()] ?? 0),
      $this->companies->all()
    );
  }
}
