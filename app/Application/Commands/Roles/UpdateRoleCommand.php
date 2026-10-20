<?php

namespace App\Application\Commands\Roles;

use App\Application\Abstractions\Command;

/**
 * Renames/describes a role.
 */
final class UpdateRoleCommand implements Command
{
  public function __construct(
    public readonly string $roleId,
    public readonly string $name,
    public readonly string $description = ''
  ) {
  }
}
