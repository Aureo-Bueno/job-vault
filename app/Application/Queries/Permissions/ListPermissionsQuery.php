<?php

namespace App\Application\Queries\Permissions;

use App\Application\Abstractions\Query;

/**
 * Lists permissions, optionally filtered by module and free text.
 */
final class ListPermissionsQuery implements Query
{
  public function __construct(
    public readonly ?string $module = null,
    public readonly ?string $search = null
  ) {
  }
}
