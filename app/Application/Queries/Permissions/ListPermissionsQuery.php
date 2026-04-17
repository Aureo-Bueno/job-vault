<?php

namespace App\Application\Queries\Permissions;

use App\Application\Abstractions\Query;

/**
 * Query to list permissions, optionally filtered by module.
 */
final class ListPermissionsQuery implements Query
{
  public function __construct(public ?string $module = null)
  {
  }
}
