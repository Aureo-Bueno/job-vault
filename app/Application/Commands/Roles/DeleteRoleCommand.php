<?php

namespace App\Application\Commands\Roles;

use App\Application\Abstractions\Command;

/**
 * Command to delete a role.
 */
final class DeleteRoleCommand implements Command
{
  public function __construct(public string $roleId)
  {
  }
}
