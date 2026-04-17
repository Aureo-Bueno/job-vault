<?php

namespace Tests\Support;

use App\Domain\Model\User;
use App\Domain\Repository\UserRepositoryInterface;

class FakeUserRepository implements UserRepositoryInterface
{
  /** @var array<string,User> */
  private array $items = [];
  private int $nextId = 1;

  public function findByEmail(string $email): ?User
  {
    foreach ($this->items as $user) {
      if ($user->email === $email) {
        return $user;
      }
    }

    return null;
  }

  public function findById(string $id): ?User
  {
    return $this->items[$id] ?? null;
  }

  /** @return User[] */
  public function findAll(
    ?string $where = null,
    ?string $order = null,
    ?string $limit = null,
    array $params = []
  ): array {
    return array_values($this->items);
  }

  public function count(?string $where = null, array $params = []): int
  {
    return count($this->items);
  }

  public function create(User $user): ?string
  {
    if ($user->id === null) {
      $user->id = (string) $this->nextId++;
    }

    $this->items[$user->id] = clone $user;
    return $user->id;
  }

  public function update(User $user): bool
  {
    if ($user->id === null || !isset($this->items[$user->id])) {
      return false;
    }

    $this->items[$user->id] = clone $user;
    return true;
  }

  public function delete(string $id): bool
  {
    if (!isset($this->items[$id])) {
      return false;
    }

    unset($this->items[$id]);
    return true;
  }

  public function getDefaultRoleId(): ?string
  {
    return 'default-role-id';
  }
}
