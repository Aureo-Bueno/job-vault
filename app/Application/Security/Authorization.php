<?php

namespace App\Application\Security;

/**
 * Permission checks for a given user. Single source of truth for access decisions.
 */
interface Authorization
{
  public function can(string $userId, string $permission): bool;

  /**
   * @param string[] $permissions
   */
  public function canAny(string $userId, array $permissions): bool;

  public function isAdmin(string $userId): bool;

  public function roleName(string $userId): ?string;

  /**
   * @throws \App\Application\Exceptions\ForbiddenException
   */
  public function ensure(string $userId, string $permission): void;
}
