<?php

namespace App\Application\Queries\Users;

use App\Application\Abstractions\Query;

/**
 * Query to list users with optional query criteria.
 */
final class ListUsersQuery implements Query
{
  /**
   * @param array<string,mixed> $params
   */
  public function __construct(
    public ?string $where = null,
    public ?string $order = null,
    public ?string $limit = null,
    public array $params = []
  ) {
  }
}
