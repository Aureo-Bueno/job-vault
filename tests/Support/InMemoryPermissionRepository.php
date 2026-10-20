<?php

namespace Tests\Support;

use App\Domain\Permission\Permission;
use App\Domain\Permission\PermissionId;
use App\Domain\Permission\PermissionName;
use App\Domain\Permission\PermissionRepositoryInterface;
use App\Domain\Role\RoleId;
use App\Domain\Role\RoleRepositoryInterface;

final class InMemoryPermissionRepository implements PermissionRepositoryInterface
{
  /** @var array<string,Permission> */
  private array $permissions = [];

  public function __construct(private ?RoleRepositoryInterface $roles = null)
  {
  }

  public function get(PermissionId $id): ?Permission
  {
    return isset($this->permissions[$id->value()]) ? clone $this->permissions[$id->value()] : null;
  }

  public function findByName(PermissionName $name): ?Permission
  {
    foreach ($this->permissions as $permission) {
      if ($permission->name()->equals($name)) {
        return clone $permission;
      }
    }

    return null;
  }

  public function all(): array
  {
    return array_map(static fn (Permission $p): Permission => clone $p, array_values($this->permissions));
  }

  public function namesGrantedToRole(RoleId $roleId): array
  {
    $role = $this->roles?->get($roleId);
    if ($role === null) {
      return [];
    }

    $names = [];
    foreach ($role->permissionIds() as $permissionId) {
      $permission = $this->permissions[$permissionId->value()] ?? null;
      if ($permission !== null) {
        $names[] = $permission->name()->value();
      }
    }

    return $names;
  }

  public function save(Permission $permission): void
  {
    $this->permissions[$permission->id()->value()] = clone $permission;
  }

  public function remove(PermissionId $id): void
  {
    unset($this->permissions[$id->value()]);
  }
}
