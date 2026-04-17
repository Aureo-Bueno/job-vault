<?php

namespace App\Application\Features\Users;

use App\Application\Abstractions\Query;
use App\Application\Abstractions\QueryHandlerInterface;
use App\Application\Queries\Users\CountUsersQuery;
use App\Application\Service\UserService;
use InvalidArgumentException;

/**
 * Handles user count query.
 */
final class CountUsersHandler implements QueryHandlerInterface
{
  public function __construct(private UserService $userService)
  {
  }

  public function queryClass(): string
  {
    return CountUsersQuery::class;
  }

  public function handle(Query $query): int
  {
    if (!$query instanceof CountUsersQuery) {
      throw new InvalidArgumentException('Invalid query for CountUsersHandler.');
    }

    return $this->userService->count($query->where, $query->params);
  }
}
