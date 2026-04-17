<?php

namespace App\Application\Queries\Users;

use App\Application\Abstractions\Query;

/**
 * Query to retrieve one user by e-mail.
 */
final class GetUserByEmailQuery implements Query
{
  public function __construct(public string $email)
  {
  }
}
