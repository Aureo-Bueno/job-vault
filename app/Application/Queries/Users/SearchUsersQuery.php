<?php

namespace App\Application\Queries\Users;

use App\Application\Abstractions\Query;

/**
 * Paged user search by name/e-mail and role.
 */
final class SearchUsersQuery implements Query
{
  public function __construct(
    public readonly ?string $search = null,
    public readonly ?string $roleId = null,
    public readonly int $page = 1,
    public readonly int $perPage = 10
  ) {
  }
}
