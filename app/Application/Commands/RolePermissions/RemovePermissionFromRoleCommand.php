<?php

namespace App\Application\Commands\RolePermissions;

use App\Application\Abstractions\Command;

/**
 * Revokes a permission from a role.
 */
final class RemovePermissionFromRoleCommand implements Command
{
  public function __construct(
    public readonly string $roleId,
    public readonly string $permissionId
  ) {
  }
}
