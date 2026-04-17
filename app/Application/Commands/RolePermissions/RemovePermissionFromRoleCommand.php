<?php

namespace App\Application\Commands\RolePermissions;

use App\Application\Abstractions\Command;

/**
 * Command to remove a permission from a role.
 */
final class RemovePermissionFromRoleCommand implements Command
{
  public function __construct(
    public string $roleId,
    public string $permissionId
  ) {
  }
}
