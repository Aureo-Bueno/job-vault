<?php

namespace App\Application\Commands\Roles;

use App\Application\Abstractions\Command;

/**
 * Removes a role that is not in use.
 */
final class DeleteRoleCommand implements Command
{
  public function __construct(public readonly string $roleId)
  {
  }
}
