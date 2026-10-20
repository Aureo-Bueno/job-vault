<?php

namespace App\Application\Features\Users;

use App\Application\Abstractions\Query;
use App\Application\Abstractions\QueryHandlerInterface;
use App\Application\DTOs\PagedResult;
use App\Application\DTOs\UserDto;
use App\Application\Queries\Users\SearchUsersQuery;
use App\Domain\Role\RoleId;
use App\Domain\Shared\PageRequest;
use App\Domain\Shared\SearchTerm;
use App\Domain\User\UserFilter;
use App\Domain\User\UserRepositoryInterface;
use InvalidArgumentException;

final class SearchUsersHandler implements QueryHandlerInterface
{
  public function __construct(private UserRepositoryInterface $users)
  {
  }

  public function queryClass(): string
  {
    return SearchUsersQuery::class;
  }

  /** @return PagedResult<UserDto> */
  public function handle(Query $query): PagedResult
  {
    if (!$query instanceof SearchUsersQuery) {
      throw new InvalidArgumentException('Invalid query for ' . self::class);
    }

    $filter = new UserFilter(
      SearchTerm::tryFrom($query->search),
      RoleId::isValid($query->roleId) ? RoleId::fromString((string) $query->roleId) : null
    );

    $total = $this->users->countMatching($filter);
    $page = (new PageRequest($query->page, $query->perPage))->clampTo($total);
    $items = array_map([UserDto::class, 'fromUser'], $this->users->matching($filter, $page));

    return new PagedResult($items, $total, $page->number, $page->size);
  }
}
