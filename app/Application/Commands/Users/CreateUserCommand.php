<?php

namespace App\Application\Commands\Users;

use App\Application\Abstractions\Command;

/**
 * Command to create a new user account.
 */
final class CreateUserCommand implements Command
{
  public function __construct(
    public string $name,
    public string $email,
    public string $password,
    public ?string $roleId = null
  ) {
  }
}
