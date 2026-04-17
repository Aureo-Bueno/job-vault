<?php

namespace App\Application\Commands\Permissions;

use App\Application\Abstractions\Command;

/**
 * Command to delete a permission.
 */
final class DeletePermissionCommand implements Command
{
  public function __construct(public string $permissionId)
  {
  }
}
