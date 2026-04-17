<?php

namespace Tests\Support;

use App\Domain\Model\Permission;
use App\Domain\Model\Role;
use App\Domain\Repository\RolePermissionRepositoryInterface;

class FakeRolePermissionRepository implements RolePermissionRepositoryInterface
{
  /** @var array<string,string[]> */
  private array $rolePermissions = [];

  public function setPermissions(string $roleId, array $permissionNames): void
  {
    $this->rolePermissions[$roleId] = $permissionNames;
  }

  /** @return Permission[] */
  public function getPermissionsByRoleId(string $roleId): array
  {
    $names = $this->rolePermissions[$roleId] ?? [];
    $permissions = [];
    foreach ($names as $name) {
      $perm = new Permission(null, $name, '', '', '', '');
      $permissions[] = $perm;
    }

    return $permissions;
  }

  public function roleHasPermission(string $roleId, string $permissionName): bool
  {
    return in_array($permissionName, $this->rolePermissions[$roleId] ?? [], true);
  }

  /** @return Role[] */
  public function getRolesByPermissionId(string $permissionId): array
  {
    return [];
  }

  public function assignPermissionToRole(string $roleId, string $permissionId): bool
  {
    return true;
  }

  public function removePermissionFromRole(string $roleId, string $permissionId): bool
  {
    return true;
  }
}
