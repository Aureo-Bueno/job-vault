<?php

namespace App\Domain\Repository;

use App\Domain\Model\Permission;

interface PermissionRepositoryInterface
{
  public function findById(string $id): ?Permission;

  public function findByName(string $name): ?Permission;

  /** @return Permission[] */
  public function findAll(): array;

  /** @return Permission[] */
  public function findByModule(string $module): array;
}
