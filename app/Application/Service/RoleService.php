<?php

namespace App\Application\Service;

use App\Application\Exceptions\ForbiddenException;
use App\Domain\Model\Role;
use App\Domain\Repository\RolePermissionRepositoryInterface;
use App\Domain\Repository\RoleRepositoryInterface;
use App\Domain\Repository\UserRepositoryInterface;

/**
 * Exposes role and permission checks used by presentation and utility layers.
 */
class RoleService
{
  private UserRepositoryInterface $userRepository;
  private RoleRepositoryInterface $roleRepository;
  private RolePermissionRepositoryInterface $rolePermissionRepository;

  public function __construct(
    UserRepositoryInterface $userRepository,
    RoleRepositoryInterface $roleRepository,
    RolePermissionRepositoryInterface $rolePermissionRepository
  ) {
    $this->userRepository = $userRepository;
    $this->roleRepository = $roleRepository;
    $this->rolePermissionRepository = $rolePermissionRepository;
  }

  /**
   * Checks whether a user has a specific permission name.
   */
  public function hasPermission(string $userId, string $permissionName): bool
  {
    $user = $this->userRepository->findById($userId);

    if (!$user || $user->roleId === null) {
      return false;
    }

    return $this->rolePermissionRepository->roleHasPermission($user->roleId, $permissionName);
  }

  /**
   * Returns permission names assigned to a role.
   *
   * @return string[]
   */
  public function getPermissionsByRole(string $roleId): array
  {
    $permissions = $this->rolePermissionRepository->getPermissionsByRoleId($roleId);

    $permissionNames = [];
    foreach ($permissions as $permission) {
      $permissionNames[] = $permission->name;
    }

    return $permissionNames;
  }

  /**
   * Returns the role currently assigned to a user.
   */
  public function getUserRole(string $userId): ?Role
  {
    $user = $this->userRepository->findById($userId);

    if (!$user || $user->roleId === null) {
      return null;
    }

    return $this->roleRepository->findById($user->roleId);
  }

  /**
   * Returns every role in the system.
   *
   * @return Role[]
   */
  public function listRoles(): array
  {
    return $this->roleRepository->findAll();
  }

  /**
   * Checks whether a user is assigned to the `admin` role.
   */
  public function isAdmin(string $userId): bool
  {
    $role = $this->getUserRole($userId);
    return $role && $role->name === 'admin';
  }

  /**
   * Checks whether a user is assigned to the `gestor` role.
   */
  public function isManager(string $userId): bool
  {
    $role = $this->getUserRole($userId);
    return $role && $role->name === 'gestor';
  }

  /**
   * Enforces permission check and terminates request with 403 when unauthorized.
   */
  public function requirePermission(string $userId, string $permissionName): void
  {
    if (!$this->hasPermission($userId, $permissionName)) {
      throw new ForbiddenException('Você não tem permissão para acessar este recurso.');
    }
  }
}
