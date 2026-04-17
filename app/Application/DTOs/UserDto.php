<?php

namespace App\Application\DTOs;

/**
 * Output DTO representing user data in application layer.
 */
final class UserDto
{
  public function __construct(
    public ?string $id,
    public string $name,
    public string $email,
    public ?string $roleId
  ) {
  }
}
