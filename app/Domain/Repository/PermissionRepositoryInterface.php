<?php

namespace App\Domain\Repository;

use App\Domain\Model\Permission;

interface PermissionRepositoryInterface
{
  public function findById(int $id): ?Permission;

  public function findByName(string $nome): ?Permission;

  /** @return Permission[] */
  public function findAll(): array;

  /** @return Permission[] */
  public function findByModule(string $modulo): array;
}
