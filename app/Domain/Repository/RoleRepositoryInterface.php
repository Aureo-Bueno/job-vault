<?php

namespace App\Domain\Repository;

use App\Domain\Model\Role;

/**
 * Persistence contract for role entities.
 */
interface RoleRepositoryInterface
{
  /**
   * Finds a role by its identifier.
   */
  public function findById(string $id): ?Role;

  /**
   * Finds a role by its unique name.
   */
  public function findByName(string $name): ?Role;

  /**
   * Returns all available roles.
   *
   * @return Role[]
   */
  public function findAll(): array;

  /**
   * Persists a new role and returns its identifier.
   */
  public function create(Role $role): ?string;

  /**
   * Updates an existing role.
   */
  public function update(Role $role): bool;

  /**
   * Removes a role by its identifier.
   */
  public function delete(string $id): bool;
}
