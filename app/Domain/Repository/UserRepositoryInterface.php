<?php

namespace App\Domain\Repository;

use App\Domain\Model\User;

interface UserRepositoryInterface
{
  public function findByEmail(string $email): ?User;

  public function findById(string $id): ?User;

  /** @return User[] */
  public function findAll(?string $where = null, ?string $order = null, ?string $limit = null): array;

  public function count(?string $where = null): int;

  public function create(User $user): ?string;

  public function update(User $user): bool;

  public function delete(string $id): bool;

  public function getDefaultRoleId(): ?string;
}
