<?php

namespace App\Application\Commands\Users;

use App\Application\Abstractions\Command;

/**
 * Command to update an existing user account.
 */
final class UpdateUserCommand implements Command
{
  public function __construct(
    public string $userId,
    public string $name,
    public string $email,
    public ?string $password = null,
    public ?string $roleId = null
  ) {
  }
}
