<?php

namespace App\Application\Features\Roles;

use App\Application\Exceptions\NotFoundException;
use App\Domain\Exception\AlreadyExistsException;
use App\Domain\Permission\Permission;
use App\Domain\Permission\PermissionId;
use App\Domain\Permission\PermissionName;
use App\Domain\Permission\PermissionRepositoryInterface;
use App\Domain\Role\Role;
use App\Domain\Role\RoleId;
use App\Domain\Role\RoleName;
use App\Domain\Role\RoleRepositoryInterface;

/**
 * Loading and uniqueness rules shared by role/permission use cases.
 */
final class AccessControlLookup
{
  public static function role(RoleRepositoryInterface $roles, string $id): Role
  {
    $role = $roles->get(RoleId::fromString($id));
    if ($role === null) {
      throw new NotFoundException('Role não encontrada.');
    }

    return $role;
  }

  public static function permission(PermissionRepositoryInterface $permissions, string $id): Permission
  {
    $permission = $permissions->get(PermissionId::fromString($id));
    if ($permission === null) {
      throw new NotFoundException('Permissão não encontrada.');
    }

    return $permission;
  }

  public static function ensureRoleNameIsAvailable(RoleRepositoryInterface $roles, RoleName $name, ?RoleId $owner = null): void
  {
    $existing = $roles->findByName($name);
    if ($existing !== null && ($owner === null || !$existing->id()->equals($owner))) {
      throw new AlreadyExistsException('Já existe uma role com esse nome.');
    }
  }

  public static function ensurePermissionNameIsAvailable(
    PermissionRepositoryInterface $permissions,
    PermissionName $name,
    ?PermissionId $owner = null
  ): void {
    $existing = $permissions->findByName($name);
    if ($existing !== null && ($owner === null || !$existing->id()->equals($owner))) {
      throw new AlreadyExistsException('Já existe uma permissão com esse nome.');
    }
  }
}
