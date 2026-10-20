<?php

namespace App\Application\Commands\Permissions;

use App\Application\Abstractions\Command;

/**
 * Removes a permission.
 */
final class DeletePermissionCommand implements Command
{
  public function __construct(public readonly string $permissionId)
  {
  }
}
