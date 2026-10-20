<?php

namespace App\Application\Features\Permissions;

use App\Application\Abstractions\Query;
use App\Application\Abstractions\QueryHandlerInterface;
use App\Application\DTOs\PermissionDto;
use App\Application\Queries\Permissions\ListPermissionsQuery;
use App\Domain\Permission\Permission;
use App\Domain\Permission\PermissionRepositoryInterface;
use App\Domain\Shared\SearchTerm;
use InvalidArgumentException;

final class ListPermissionsHandler implements QueryHandlerInterface
{
  public function __construct(private PermissionRepositoryInterface $permissions)
  {
  }

  public function queryClass(): string
  {
    return ListPermissionsQuery::class;
  }

  /** @return PermissionDto[] */
  public function handle(Query $query): array
  {
    if (!$query instanceof ListPermissionsQuery) {
      throw new InvalidArgumentException('Invalid query for ' . self::class);
    }

    $permissions = $this->permissions->all();

    $module = trim((string) $query->module);
    if ($module !== '') {
      $permissions = array_filter($permissions, static fn (Permission $p): bool => $p->belongsToModule($module));
    }

    $term = SearchTerm::tryFrom($query->search);
    if ($term !== null) {
      $permissions = array_filter($permissions, static fn (Permission $p): bool => $term->matches(
        $p->name()->value(),
        $p->description(),
        $p->module(),
        $p->action()
      ));
    }

    return array_values(array_map([PermissionDto::class, 'fromPermission'], $permissions));
  }
}
