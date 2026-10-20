<?php

namespace Tests\Support;

use App\Domain\Role\Role;
use App\Domain\Role\RoleId;
use App\Domain\Role\RoleName;
use App\Domain\Role\RoleRepositoryInterface;

final class InMemoryRoleRepository implements RoleRepositoryInterface
{
  /** @var array<string,Role> */
  private array $roles = [];

  public function get(RoleId $id): ?Role
  {
    return isset($this->roles[$id->value()]) ? clone $this->roles[$id->value()] : null;
  }

  public function findByName(RoleName $name): ?Role
  {
    foreach ($this->roles as $role) {
      if ($role->name()->equals($name)) {
        return clone $role;
      }
    }

    return null;
  }

  public function all(): array
  {
    $roles = array_map(static fn (Role $r): Role => clone $r, array_values($this->roles));
    usort($roles, static fn (Role $a, Role $b): int => strcmp($a->name()->value(), $b->name()->value()));
    return $roles;
  }

  public function save(Role $role): void
  {
    $this->roles[$role->id()->value()] = clone $role;
  }

  public function remove(RoleId $id): void
  {
    unset($this->roles[$id->value()]);
  }
}
