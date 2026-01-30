<?php

namespace App\Domain\Repository;

use App\Domain\Model\Role;

interface RoleRepositoryInterface
{
  public function findById(string $id): ?Role;

  public function findByName(string $name): ?Role;

  /** @return Role[] */
  public function findAll(): array;
}
