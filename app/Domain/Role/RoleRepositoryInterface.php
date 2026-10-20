<?php

namespace App\Domain\Role;

interface RoleRepositoryInterface
{
  public function get(RoleId $id): ?Role;

  public function findByName(RoleName $name): ?Role;

  /** @return Role[] sorted by name */
  public function all(): array;

  /**
   * Persists the role and its granted permissions.
   */
  public function save(Role $role): void;

  public function remove(RoleId $id): void;
}
