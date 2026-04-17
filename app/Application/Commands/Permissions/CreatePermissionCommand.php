<?php

namespace App\Application\Commands\Permissions;

use App\Application\Abstractions\Command;

/**
 * Command to create a permission.
 */
final class CreatePermissionCommand implements Command
{
  public function __construct(
    public string $name,
    public string $description = '',
    public string $module = '',
    public string $action = ''
  ) {
  }
}
