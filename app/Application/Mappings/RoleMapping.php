<?php

namespace App\Application\Mappings;

use App\Application\DTOs\RoleDto;
use App\Domain\Model\Role;

/**
 * Maps role domain models into application DTOs.
 */
final class RoleMapping
{
  public static function toDto(Role $role): RoleDto
  {
    return new RoleDto(
      $role->id,
      $role->name,
      $role->description,
      $role->createdAt
    );
  }
}
