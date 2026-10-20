<?php

namespace App\Application\Commands\Users;

use App\Application\Abstractions\Command;

/**
 * Updates profile, optionally password, role (when changeRole is true) and company (when changeCompany is true).
 */
final class UpdateUserCommand implements Command
{
  public function __construct(
    public readonly string $userId,
    public readonly string $name,
    public readonly string $email,
    public readonly ?string $password = null,
    public readonly ?string $roleId = null,
    public readonly bool $changeRole = false,
    public readonly ?string $companyId = null,
    public readonly bool $changeCompany = false
  ) {
  }
}
