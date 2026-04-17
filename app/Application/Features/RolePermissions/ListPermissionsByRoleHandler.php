<?php

namespace App\Application\Features\RolePermissions;

use App\Application\Abstractions\Query;
use App\Application\Abstractions\QueryHandlerInterface;
use App\Application\Mappings\PermissionMapping;
use App\Application\Queries\RolePermissions\ListPermissionsByRoleQuery;
use App\Application\Service\AccessControlService;
use InvalidArgumentException;

/**
 * Handles listing permissions assigned to one role.
 */
final class ListPermissionsByRoleHandler implements QueryHandlerInterface
{
  public function __construct(private AccessControlService $accessControlService)
  {
  }

  public function queryClass(): string
  {
    return ListPermissionsByRoleQuery::class;
  }

  public function handle(Query $query): array
  {
    if (!$query instanceof ListPermissionsByRoleQuery) {
      throw new InvalidArgumentException('Invalid query for ListPermissionsByRoleHandler.');
    }

    $permissions = $this->accessControlService->listPermissionsByRole($query->roleId);

    return array_map([PermissionMapping::class, 'toDto'], $permissions);
  }
}
