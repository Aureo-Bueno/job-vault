<?php

namespace App\Application\Mappings;

use App\Application\DTOs\PermissionDto;
use App\Domain\Model\Permission;

/**
 * Maps permission domain models into application DTOs.
 */
final class PermissionMapping
{
  public static function toDto(Permission $permission): PermissionDto
  {
    return new PermissionDto(
      $permission->id,
      $permission->name,
      $permission->description,
      $permission->module,
      $permission->action,
      $permission->createdAt
    );
  }
}
