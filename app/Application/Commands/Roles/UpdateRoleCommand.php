<?php

namespace App\Application\Commands\Roles;

use App\Application\Abstractions\Command;

/**
 * Command to update a role.
 */
final class UpdateRoleCommand implements Command
{
  public function __construct(
    public string $roleId,
    public string $name,
    public string $description = ''
  ) {
  }
}
