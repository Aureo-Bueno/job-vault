<?php

namespace App\Domain\User;

use App\Domain\Role\RoleId;
use App\Domain\Shared\SearchTerm;

/**
 * Search criteria for users (no SQL knowledge).
 */
final class UserFilter
{
  public function __construct(
    public readonly ?SearchTerm $term = null,
    public readonly ?RoleId $roleId = null
  ) {
  }
}
