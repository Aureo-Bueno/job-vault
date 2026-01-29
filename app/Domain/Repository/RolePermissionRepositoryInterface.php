<?php

namespace App\Domain\Repository;

use App\Domain\Model\Permission;
use App\Domain\Model\Role;

interface RolePermissionRepositoryInterface
{
  /** @return Permission[] */
  public function getPermissionsByRoleId(int $roleId): array;

  public function roleHasPermission(int $roleId, string $permissionName): bool;

  /** @return Role[] */
  public function getRolesByPermissionId(int $permissionId): array;

  public function assignPermissionToRole(int $roleId, int $permissionId): bool;

  public function removePermissionFromRole(int $roleId, int $permissionId): bool;
}
