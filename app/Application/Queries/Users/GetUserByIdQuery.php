<?php

namespace App\Application\Queries\Users;

use App\Application\Abstractions\Query;

/**
 * Query to get one user by identifier.
 */
final class GetUserByIdQuery implements Query
{
  public function __construct(public string $userId)
  {
  }
}
