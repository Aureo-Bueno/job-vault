<?php

namespace App\Application\DTOs;

use App\Domain\User\User;

final class UserDto
{
  public function __construct(
    public readonly string $id,
    public readonly string $name,
    public readonly string $email,
    public readonly ?string $roleId,
    public readonly ?string $companyId = null
  ) {
  }

  public static function fromUser(User $user): self
  {
    return new self(
      $user->id()->value(),
      $user->name()->value(),
      $user->email()->value(),
      $user->roleId()?->value(),
      $user->companyId()?->value()
    );
  }
}
