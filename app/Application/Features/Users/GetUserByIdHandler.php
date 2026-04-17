<?php

namespace App\Application\Features\Users;

use App\Application\Abstractions\Query;
use App\Application\Abstractions\QueryHandlerInterface;
use App\Application\DTOs\UserDto;
use App\Application\Mappings\UserMapping;
use App\Application\Queries\Users\GetUserByIdQuery;
use App\Application\Service\UserService;
use InvalidArgumentException;

/**
 * Handles user retrieval by identifier query.
 */
final class GetUserByIdHandler implements QueryHandlerInterface
{
  public function __construct(private UserService $userService)
  {
  }

  public function queryClass(): string
  {
    return GetUserByIdQuery::class;
  }

  public function handle(Query $query): ?UserDto
  {
    if (!$query instanceof GetUserByIdQuery) {
      throw new InvalidArgumentException('Invalid query for GetUserByIdHandler.');
    }

    $user = $this->userService->getById($query->userId);

    return $user ? UserMapping::toDto($user) : null;
  }
}
