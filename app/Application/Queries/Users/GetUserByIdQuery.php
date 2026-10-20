<?php

namespace App\Application\Queries\Users;

use App\Application\Abstractions\Query;

/**
 * Fetches a single user.
 */
final class GetUserByIdQuery implements Query
{
  public function __construct(public readonly string $userId)
  {
  }
}
