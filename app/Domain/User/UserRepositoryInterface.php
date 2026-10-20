<?php

namespace App\Domain\User;

use App\Domain\Shared\PageRequest;

interface UserRepositoryInterface
{
  public function get(UserId $id): ?User;

  public function findByEmail(EmailAddress $email): ?User;

  /** @return User[] */
  public function matching(UserFilter $filter, PageRequest $page): array;

  public function countMatching(UserFilter $filter): int;

  /**
   * Number of users per role id.
   *
   * @return array<string,int>
   */
  public function countPerRole(): array;

  public function save(User $user): void;

  public function remove(UserId $id): void;
}
