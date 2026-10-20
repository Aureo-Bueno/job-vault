<?php

namespace App\Application\Commands\Permissions;

use App\Application\Abstractions\Command;

/**
 * Defines a permission. Empty name falls back to module.action.
 */
final class CreatePermissionCommand implements Command
{
  public function __construct(
    public readonly string $name,
    public readonly string $description = '',
    public readonly string $module = '',
    public readonly string $action = ''
  ) {
  }
}
