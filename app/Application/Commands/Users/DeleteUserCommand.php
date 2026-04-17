<?php

namespace App\Application\Commands\Users;

use App\Application\Abstractions\Command;

/**
 * Command to delete a user account.
 */
final class DeleteUserCommand implements Command
{
  public function __construct(public string $userId)
  {
  }
}
