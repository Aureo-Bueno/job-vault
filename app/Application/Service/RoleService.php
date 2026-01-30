<?php

namespace App\Application\Service;

use App\Domain\Model\Role;
use App\Domain\Repository\RolePermissionRepositoryInterface;
use App\Domain\Repository\RoleRepositoryInterface;
use App\Domain\Repository\UserRepositoryInterface;

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

  public function hasPermission(string $userId, string $permissionName): bool
  {
    $user = $this->userRepository->findById($userId);

    if (!$user || $user->roleId === null) {
      return false;
    }

    return $this->rolePermissionRepository->roleHasPermission($user->roleId, $permissionName);
  }

  /** @return string[] */
  public function getPermissionsByRole(string $roleId): array
  {
    $permissions = $this->rolePermissionRepository->getPermissionsByRoleId($roleId);

    $permissionNames = [];
    foreach ($permissions as $permission) {
      $permissionNames[] = $permission->name;
    }

    return $permissionNames;
  }

  public function getUserRole(string $userId): ?Role
  {
    $user = $this->userRepository->findById($userId);

    if (!$user || $user->roleId === null) {
      return null;
    }

    return $this->roleRepository->findById($user->roleId);
  }

  /** @return Role[] */
  public function listRoles(): array
  {
    return $this->roleRepository->findAll();
  }

  public function isAdmin(string $userId): bool
  {
    $role = $this->getUserRole($userId);
    return $role && $role->name === 'admin';
  }

  public function isManager(string $userId): bool
  {
    $role = $this->getUserRole($userId);
    return $role && $role->name === 'gestor';
  }

  public function requirePermission(string $userId, string $permissionName): void
  {
    if (!$this->hasPermission($userId, $permissionName)) {
      http_response_code(403);
      die('Você não tem permissão para acessar este recurso.');
    }
  }
}
