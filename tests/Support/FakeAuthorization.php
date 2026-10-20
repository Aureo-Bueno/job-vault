<?php

namespace Tests\Support;

use App\Application\Exceptions\ForbiddenException;
use App\Application\Security\Authorization;

final class FakeAuthorization implements Authorization
{
  /** @var array<string,true> */
  private array $permissions;

  /**
   * @param string[] $permissions
   */
  public function __construct(array $permissions = [], private bool $admin = true, private ?string $role = 'admin')
  {
    $this->permissions = array_fill_keys($permissions, true);
  }

  public function can(string $userId, string $permission): bool
  {
    return isset($this->permissions[$permission]);
  }

  public function canAny(string $userId, array $permissions): bool
  {
    foreach ($permissions as $permission) {
      if ($this->can($userId, $permission)) {
        return true;
      }
    }

    return false;
  }

  public function isAdmin(string $userId): bool
  {
    return $this->admin;
  }

  public function roleName(string $userId): ?string
  {
    return $this->role;
  }

  public function ensure(string $userId, string $permission): void
  {
    if (!$this->can($userId, $permission)) {
      throw new ForbiddenException('Você não tem permissão para acessar este recurso.');
    }
  }
}
