<?php

namespace App\Application\Features\Roles;

use App\Application\Abstractions\Query;
use App\Application\Abstractions\QueryHandlerInterface;
use App\Application\DTOs\RoleDto;
use App\Application\Queries\Roles\ListRolesQuery;
use App\Domain\Role\Role;
use App\Domain\Role\RoleRepositoryInterface;
use App\Domain\Shared\SearchTerm;
use App\Domain\User\UserRepositoryInterface;
use InvalidArgumentException;

final class ListRolesHandler implements QueryHandlerInterface
{
  public function __construct(
    private RoleRepositoryInterface $roles,
    private UserRepositoryInterface $users
  ) {
  }

  public function queryClass(): string
  {
    return ListRolesQuery::class;
  }

  /** @return RoleDto[] */
  public function handle(Query $query): array
  {
    if (!$query instanceof ListRolesQuery) {
      throw new InvalidArgumentException('Invalid query for ' . self::class);
    }

    $term = SearchTerm::tryFrom($query->search);
    $roles = $this->roles->all();
    if ($term !== null) {
      $roles = array_filter($roles, static fn (Role $role): bool => $term->matches(
        $role->name()->value(),
        $role->description()
      ));
    }

    $userCount = $this->users->countPerRole();

    return array_values(array_map(
      static fn (Role $role): RoleDto => RoleDto::fromRole($role, $userCount[$role->id()->value()] ?? 0),
      $roles
    ));
  }
}
