<?php

namespace App\Application\Commands\RolePermissions;

use App\Application\Abstractions\Command;

/**
 * Grants a permission to a role.
 */
final class AssignPermissionToRoleCommand implements Command
{
  public function __construct(
    public readonly string $roleId,
    public readonly string $permissionId
  ) {
  }
}
