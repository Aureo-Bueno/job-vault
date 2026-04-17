<?php

namespace App\Application\Queries\Users;

use App\Application\Abstractions\Query;

/**
 * Query to count users using optional criteria.
 */
final class CountUsersQuery implements Query
{
  /**
   * @param array<string,mixed> $params
   */
  public function __construct(
    public ?string $where = null,
    public array $params = []
  ) {
  }
}
