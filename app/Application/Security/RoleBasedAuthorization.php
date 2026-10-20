<?php

namespace App\Application\Security;

use App\Application\Exceptions\ForbiddenException;
use App\Domain\Permission\PermissionRepositoryInterface;
use App\Domain\Role\Role;
use App\Domain\Role\RoleRepositoryInterface;
use App\Domain\User\UserId;
use App\Domain\User\UserRepositoryInterface;

/**
 * Resolves user -> role -> permissions, cached per request.
 */
final class RoleBasedAuthorization implements Authorization
{
  /** @var array<string,array{role:?Role,permissions:array<string,true>}> */
  private array $profiles = [];

  public function __construct(
    private UserRepositoryInterface $users,
    private RoleRepositoryInterface $roles,
    private PermissionRepositoryInterface $permissions
  ) {
  }

  public function can(string $userId, string $permission): bool
  {
    return isset($this->profile($userId)['permissions'][$permission]);
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
    return $this->profile($userId)['role']?->isAdmin() ?? false;
  }

  public function roleName(string $userId): ?string
  {
    return $this->profile($userId)['role']?->name()->value();
  }

  public function ensure(string $userId, string $permission): void
  {
    if (!$this->can($userId, $permission)) {
      throw new ForbiddenException('Você não tem permissão para acessar este recurso.');
    }
  }

  /**
   * @return array{role:?Role,permissions:array<string,true>}
   */
  private function profile(string $userId): array
  {
    if (isset($this->profiles[$userId])) {
      return $this->profiles[$userId];
    }

    $profile = ['role' => null, 'permissions' => []];
    $user = UserId::isValid($userId) ? $this->users->get(UserId::fromString($userId)) : null;
    $roleId = $user?->roleId();

    if ($roleId !== null) {
      $profile['role'] = $this->roles->get($roleId);
      if ($profile['role'] !== null) {
        $profile['permissions'] = array_fill_keys($this->permissions->namesGrantedToRole($roleId), true);
      }
    }

    return $this->profiles[$userId] = $profile;
  }
}
