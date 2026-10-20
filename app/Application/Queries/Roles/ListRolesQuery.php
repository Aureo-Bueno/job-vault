<?php

namespace App\Application\Queries\Roles;

use App\Application\Abstractions\Query;

/**
 * Lists roles (with user count and granted permissions), optionally filtered.
 */
final class ListRolesQuery implements Query
{
  public function __construct(public readonly ?string $search = null)
  {
  }
}
