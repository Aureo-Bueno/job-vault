<?php

namespace App\Application\Commands\RolePermissions;

use App\Application\Abstractions\Command;

/**
 * Command to assign a permission to a role.
 */
final class AssignPermissionToRoleCommand implements Command
{
  public function __construct(
    public string $roleId,
    public string $permissionId
  ) {
  }
}
