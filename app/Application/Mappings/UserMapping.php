<?php

namespace App\Application\Mappings;

use App\Application\DTOs\UserDto;
use App\Domain\Model\User;

/**
 * Maps user domain models into application DTOs.
 */
final class UserMapping
{
  public static function toDto(User $user): UserDto
  {
    return new UserDto(
      $user->id,
      $user->name,
      $user->email,
      $user->roleId
    );
  }
}
