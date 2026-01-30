<?php

namespace App\Domain\Repository;

use App\Domain\Model\Permission;
use App\Domain\Model\Role;

interface RolePermissionRepositoryInterface
{
  /** @return Permission[] */
  public function getPermissionsByRoleId(string $roleId): array;

  public function roleHasPermission(string $roleId, string $permissionName): bool;

  /** @return Role[] */
  public function getRolesByPermissionId(string $permissionId): array;

  public function assignPermissionToRole(string $roleId, string $permissionId): bool;

  public function removePermissionFromRole(string $roleId, string $permissionId): bool;
}
