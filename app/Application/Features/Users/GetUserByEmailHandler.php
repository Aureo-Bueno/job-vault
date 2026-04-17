<?php

namespace App\Application\Features\Users;

use App\Application\Abstractions\Query;
use App\Application\Abstractions\QueryHandlerInterface;
use App\Application\DTOs\UserDto;
use App\Application\Mappings\UserMapping;
use App\Application\Queries\Users\GetUserByEmailQuery;
use App\Application\Service\UserService;
use InvalidArgumentException;

/**
 * Handles user retrieval by e-mail query.
 */
final class GetUserByEmailHandler implements QueryHandlerInterface
{
  public function __construct(private UserService $userService)
  {
  }

  public function queryClass(): string
  {
    return GetUserByEmailQuery::class;
  }

  public function handle(Query $query): ?UserDto
  {
    if (!$query instanceof GetUserByEmailQuery) {
      throw new InvalidArgumentException('Invalid query for GetUserByEmailHandler.');
    }

    $user = $this->userService->getByEmail($query->email);

    return $user ? UserMapping::toDto($user) : null;
  }
}
