<?php

namespace App\Application\Features\Roles;

use App\Application\Abstractions\Query;
use App\Application\Abstractions\QueryHandlerInterface;
use App\Application\Mappings\RoleMapping;
use App\Application\Queries\Roles\ListRolesQuery;
use App\Application\Service\AccessControlService;
use InvalidArgumentException;

/**
 * Handles role list query.
 */
final class ListRolesHandler implements QueryHandlerInterface
{
  public function __construct(private AccessControlService $accessControlService)
  {
  }

  public function queryClass(): string
  {
    return ListRolesQuery::class;
  }

  public function handle(Query $query): array
  {
    if (!$query instanceof ListRolesQuery) {
      throw new InvalidArgumentException('Invalid query for ListRolesHandler.');
    }

    $roles = $this->accessControlService->listRoles();

    return array_map([RoleMapping::class, 'toDto'], $roles);
  }
}
