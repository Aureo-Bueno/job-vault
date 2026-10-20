<?php

namespace App\Application\Commands\Roles;

use App\Application\Abstractions\Command;

/**
 * Creates a role.
 */
final class CreateRoleCommand implements Command
{
  public function __construct(
    public readonly string $name,
    public readonly string $description = ''
  ) {
  }
}
