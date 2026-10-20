<?php

namespace App\Application\Features\Companies;

use App\Application\Abstractions\Query;
use App\Application\Abstractions\QueryHandlerInterface;
use App\Application\DTOs\CompanyDto;
use App\Application\Exceptions\UnauthorizedException;
use App\Application\Queries\Companies\GetMyCompanyQuery;
use App\Application\Security\CurrentUserProvider;
use App\Domain\Company\CompanyRepositoryInterface;
use App\Domain\User\UserId;
use App\Domain\User\UserRepositoryInterface;
use InvalidArgumentException;

final class GetMyCompanyHandler implements QueryHandlerInterface
{
  public function __construct(
    private CompanyRepositoryInterface $companies,
    private UserRepositoryInterface $users,
    private CurrentUserProvider $currentUser
  ) {
  }

  public function queryClass(): string
  {
    return GetMyCompanyQuery::class;
  }

  public function handle(Query $query): ?CompanyDto
  {
    if (!$query instanceof GetMyCompanyQuery) {
      throw new InvalidArgumentException('Invalid query for ' . self::class);
    }

    $logged = $this->currentUser->currentUser();
    if ($logged === null) {
      throw new UnauthorizedException('Você precisa entrar para continuar.');
    }

    $user = $this->users->get(UserId::fromString($logged->id));
    if ($user === null || $user->companyId() === null) {
      return null;
    }

    $company = $this->companies->get($user->companyId());
    if ($company === null) {
      return null;
    }

    $counts = $this->users->countPerCompany();
    return CompanyDto::fromCompany($company, $counts[$company->id()->value()] ?? 0);
  }
}
