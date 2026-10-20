<?php

namespace App\Application\Commands\Users;

use App\Application\Abstractions\Command;

/**
 * Deletes a user. The logged user cannot delete itself.
 */
final class DeleteUserCommand implements Command
{
  public function __construct(public readonly string $userId)
  {
  }
}
