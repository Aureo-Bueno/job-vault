<?php

namespace App\Application\Commands\Permissions;

use App\Application\Abstractions\Command;

/**
 * Redefines a permission.
 */
final class UpdatePermissionCommand implements Command
{
  public function __construct(
    public readonly string $permissionId,
    public readonly string $name,
    public readonly string $description = '',
    public readonly string $module = '',
    public readonly string $action = ''
  ) {
  }
}
