<?php

namespace Tests\Support;

use App\Domain\Model\Role;
use App\Domain\Repository\RoleRepositoryInterface;

class FakeRoleRepository implements RoleRepositoryInterface
{
  /** @var array<int,Role> */
  private array $roles = [];

  public function add(Role $role): void
  {
    if ($role->id === null) {
      $role->id = count($this->roles) + 1;
    }
    $this->roles[$role->id] = $role;
  }

  public function findById(int $id): ?Role
  {
    return $this->roles[$id] ?? null;
  }

  public function findByName(string $nome): ?Role
  {
    foreach ($this->roles as $role) {
      if ($role->nome === $nome) {
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
}
