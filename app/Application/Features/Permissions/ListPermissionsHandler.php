<?php

namespace App\Application\Features\Permissions;

use App\Application\Abstractions\Query;
use App\Application\Abstractions\QueryHandlerInterface;
use App\Application\Mappings\PermissionMapping;
use App\Application\Queries\Permissions\ListPermissionsQuery;
use App\Application\Service\AccessControlService;
use InvalidArgumentException;

/**
 * Handles permission list query.
 */
final class ListPermissionsHandler implements QueryHandlerInterface
{
  public function __construct(private AccessControlService $accessControlService)
  {
  }

  public function queryClass(): string
  {
    return ListPermissionsQuery::class;
  }

  public function handle(Query $query): array
  {
    if (!$query instanceof ListPermissionsQuery) {
      throw new InvalidArgumentException('Invalid query for ListPermissionsHandler.');
    }

    $permissions = $this->accessControlService->listPermissions();

    if ($query->module !== null && $query->module !== '') {
      $requestedModule = strtolower($query->module);
      $permissions = array_values(array_filter($permissions, function ($permission) use ($requestedModule) {
        return strtolower($permission->module) === $requestedModule;
      }));
    }

    return array_map([PermissionMapping::class, 'toDto'], $permissions);
  }
}
