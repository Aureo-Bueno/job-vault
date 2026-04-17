<?php

namespace App\Application\Commands\Permissions;

use App\Application\Abstractions\Command;

/**
 * Command to update a permission.
 */
final class UpdatePermissionCommand implements Command
{
  public function __construct(
    public string $permissionId,
    public string $name,
    public string $description = '',
    public string $module = '',
    public string $action = ''
  ) {
  }
}
