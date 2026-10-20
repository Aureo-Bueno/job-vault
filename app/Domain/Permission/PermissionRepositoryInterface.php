<?php

namespace App\Domain\Permission;

use App\Domain\Role\RoleId;

interface PermissionRepositoryInterface
{
  public function get(PermissionId $id): ?Permission;

  public function findByName(PermissionName $name): ?Permission;

  /** @return Permission[] sorted by module, action and name */
  public function all(): array;

  /**
   * Names of the permissions granted to a role (used for authorization checks).
   *
   * @return string[]
   */
  public function namesGrantedToRole(RoleId $roleId): array;

  public function save(Permission $permission): void;

  public function remove(PermissionId $id): void;
}
