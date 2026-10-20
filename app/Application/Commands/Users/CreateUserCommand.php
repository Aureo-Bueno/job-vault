<?php

namespace App\Application\Commands\Users;

use App\Application\Abstractions\Command;

/**
 * Creates a user (admin panel). A role id requires the user.assign_role permission.
 */
final class CreateUserCommand implements Command
{
  public function __construct(
    public readonly string $name,
    public readonly string $email,
    public readonly string $password,
    public readonly ?string $roleId = null
  ) {
  }
}
