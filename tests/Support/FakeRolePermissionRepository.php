<?php

namespace Tests\Support;

use App\Domain\Model\Permission;
use App\Domain\Model\Role;
use App\Domain\Repository\RolePermissionRepositoryInterface;

class FakeRolePermissionRepository implements RolePermissionRepositoryInterface
{
  /** @var array<int,string[]> */
  private array $rolePermissions = [];

  public function setPermissions(int $roleId, array $permissionNames): void
  {
    $this->rolePermissions[$roleId] = $permissionNames;
  }

  /** @return Permission[] */
  public function getPermissionsByRoleId(int $roleId): array
  {
    $names = $this->rolePermissions[$roleId] ?? [];
    $permissions = [];
    foreach ($names as $name) {
      $perm = new Permission(null, $name, '', '', '', '');
      $permissions[] = $perm;
    }

    return $permissions;
  }

  public function roleHasPermission(int $roleId, string $permissionName): bool
  {
    return in_array($permissionName, $this->rolePermissions[$roleId] ?? [], true);
  }

  /** @return Role[] */
  public function getRolesByPermissionId(int $permissionId): array
  {
    return [];
  }

  public function assignPermissionToRole(int $roleId, int $permissionId): bool
  {
    return true;
  }

  public function removePermissionFromRole(int $roleId, int $permissionId): bool
  {
    return true;
  }
}
