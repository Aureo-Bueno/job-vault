<?php

namespace App\Application\Features\Users;

use App\Application\Abstractions\Query;
use App\Application\Abstractions\QueryHandlerInterface;
use App\Application\Mappings\UserMapping;
use App\Application\Queries\Users\ListUsersQuery;
use App\Application\Service\UserService;
use InvalidArgumentException;

/**
 * Handles user list query.
 */
final class ListUsersHandler implements QueryHandlerInterface
{
  public function __construct(private UserService $userService)
  {
  }

  public function queryClass(): string
  {
    return ListUsersQuery::class;
  }

  public function handle(Query $query): array
  {
    if (!$query instanceof ListUsersQuery) {
      throw new InvalidArgumentException('Invalid query for ListUsersHandler.');
    }

    $users = $this->userService->list(
      $query->where,
      $query->order,
      $query->limit,
      $query->params
    );

    return array_map([UserMapping::class, 'toDto'], $users);
  }
}
