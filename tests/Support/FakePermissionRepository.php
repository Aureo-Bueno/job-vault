<?php

namespace Tests\Support;

use App\Domain\Model\Permission;
use App\Domain\Repository\PermissionRepositoryInterface;

class FakePermissionRepository implements PermissionRepositoryInterface
{
  /** @var array<string,Permission> */
  private array $permissions = [];
  private int $nextId = 1;

  public function findById(string $id): ?Permission
  {
    return $this->permissions[$id] ?? null;
  }

  public function findByName(string $name): ?Permission
  {
    foreach ($this->permissions as $permission) {
      if ($permission->name === $name) {
        return $permission;
      }
    }

    return null;
  }

  /** @return Permission[] */
  public function findAll(): array
  {
    return array_values($this->permissions);
  }

  /** @return Permission[] */
  public function findByModule(string $module): array
  {
    return array_values(array_filter($this->permissions, function (Permission $permission) use ($module) {
      return $permission->module === $module;
    }));
  }

  public function create(Permission $permission): ?string
  {
    if ($permission->id === null) {
      $permission->id = (string) $this->nextId++;
    }

    $this->permissions[$permission->id] = clone $permission;
    return $permission->id;
  }

  public function update(Permission $permission): bool
  {
    if ($permission->id === null || !isset($this->permissions[$permission->id])) {
      return false;
    }

    $this->permissions[$permission->id] = clone $permission;
    return true;
  }

  public function delete(string $id): bool
  {
    if (!isset($this->permissions[$id])) {
      return false;
    }

    unset($this->permissions[$id]);
    return true;
  }
}
