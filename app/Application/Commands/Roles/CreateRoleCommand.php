<?php

namespace App\Application\Commands\Roles;

use App\Application\Abstractions\Command;

/**
 * Command to create a role.
 */
final class CreateRoleCommand implements Command
{
  public function __construct(
    public string $name,
    public string $description = ''
  ) {
  }
}
