<?php

namespace App\Domain\Repository;

use App\Domain\Model\Role;

interface RoleRepositoryInterface
{
  public function findById(int $id): ?Role;

  public function findByName(string $nome): ?Role;

  /** @return Role[] */
  public function findAll(): array;
}
