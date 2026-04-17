<?php

namespace App\Domain\Repository;

use App\Domain\Model\Permission;

/**
 * Persistence contract for permission entities.
 */
interface PermissionRepositoryInterface
{
  /**
   * Finds a permission by its identifier.
   */
  public function findById(string $id): ?Permission;

  /**
   * Finds a permission by its unique name.
   */
  public function findByName(string $name): ?Permission;

  /**
   * Returns all permissions.
   *
   * @return Permission[]
   */
  public function findAll(): array;

  /**
   * Returns all permissions that belong to a module.
   *
   * @return Permission[]
   */
  public function findByModule(string $module): array;

  /**
   * Persists a new permission and returns its identifier.
   */
  public function create(Permission $permission): ?string;

  /**
   * Updates an existing permission.
   */
  public function update(Permission $permission): bool;

  /**
   * Removes a permission by its identifier.
   */
  public function delete(string $id): bool;
}
