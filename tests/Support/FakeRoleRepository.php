<?php

namespace Tests\Support;

use App\Domain\Model\Role;
use App\Domain\Repository\RoleRepositoryInterface;

class FakeRoleRepository implements RoleRepositoryInterface
{
  /** @var array<string,Role> */
  private array $roles = [];
  private int $nextId = 1;

  public function add(Role $role): void
  {
    if ($role->id === null) {
      $role->id = (string) $this->nextId++;
    }
    $this->roles[$role->id] = $role;
  }

  public function findById(string $id): ?Role
  {
    return $this->roles[$id] ?? null;
  }

  public function findByName(string $name): ?Role
  {
    foreach ($this->roles as $role) {
      if ($role->name === $name) {
        return $role;
      }
    }

    return null;
  }

  /** @return Role[] */
  public function findAll(): array
  {
    return array_values($this->roles);
  }

  public function create(Role $role): ?string
  {
    if ($role->id === null) {
      $role->id = (string) $this->nextId++;
    }

    $this->roles[$role->id] = clone $role;
    return $role->id;
  }

  public function update(Role $role): bool
  {
    if ($role->id === null || !isset($this->roles[$role->id])) {
      return false;
    }

    $this->roles[$role->id] = clone $role;
    return true;
  }

  public function delete(string $id): bool
  {
    if (!isset($this->roles[$id])) {
      return false;
    }

    unset($this->roles[$id]);
    return true;
  }
}
