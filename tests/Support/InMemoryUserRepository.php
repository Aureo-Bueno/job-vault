<?php

namespace Tests\Support;

use App\Domain\Shared\PageRequest;
use App\Domain\User\EmailAddress;
use App\Domain\User\User;
use App\Domain\User\UserFilter;
use App\Domain\User\UserId;
use App\Domain\User\UserRepositoryInterface;

final class InMemoryUserRepository implements UserRepositoryInterface
{
  /** @var array<string,User> */
  private array $users = [];

  public function get(UserId $id): ?User
  {
    return isset($this->users[$id->value()]) ? clone $this->users[$id->value()] : null;
  }

  public function findByEmail(EmailAddress $email): ?User
  {
    foreach ($this->users as $user) {
      if ($user->email()->equals($email)) {
        return clone $user;
      }
    }

    return null;
  }

  public function matching(UserFilter $filter, PageRequest $page): array
  {
    $matches = array_values(array_filter($this->users, static function (User $user) use ($filter): bool {
      if ($filter->term !== null && !$filter->term->matches($user->name()->value(), $user->email()->value())) {
        return false;
      }

      return $filter->roleId === null || ($user->roleId()?->equals($filter->roleId) ?? false);
    }));

    return array_map(static fn (User $u): User => clone $u, array_slice($matches, $page->offset(), $page->size));
  }

  public function countMatching(UserFilter $filter): int
  {
    return count($this->matching($filter, PageRequest::all()));
  }

  public function countPerRole(): array
  {
    $counts = [];
    foreach ($this->users as $user) {
      if ($user->roleId() !== null) {
        $counts[$user->roleId()->value()] = ($counts[$user->roleId()->value()] ?? 0) + 1;
      }
    }

    return $counts;
  }

  public function countPerCompany(): array
  {
    $counts = [];
    foreach ($this->users as $user) {
      if ($user->companyId() !== null) {
        $counts[$user->companyId()->value()] = ($counts[$user->companyId()->value()] ?? 0) + 1;
      }
    }

    return $counts;
  }

  public function save(User $user): void
  {
    $this->users[$user->id()->value()] = clone $user;
  }

  public function remove(UserId $id): void
  {
    unset($this->users[$id->value()]);
  }
}
