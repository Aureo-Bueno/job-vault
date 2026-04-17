<?php

namespace App\Application\Queries\RolePermissions;

use App\Application\Abstractions\Query;

/**
 * Query to list permissions assigned to one role.
 */
final class ListPermissionsByRoleQuery implements Query
{
  public function __construct(public string $roleId)
  {
  }
}
