<?php

namespace App\Application\DTOs;

use App\Domain\Permission\Permission;

final class PermissionDto
{
  public function __construct(
    public readonly string $id,
    public readonly string $name,
    public readonly string $description,
    public readonly string $module,
    public readonly string $action,
    public readonly string $createdAt
  ) {
  }

  public static function fromPermission(Permission $permission): self
  {
    return new self(
      $permission->id()->value(),
      $permission->name()->value(),
      $permission->description(),
      $permission->module(),
      $permission->action(),
      $permission->createdAt()->format('Y-m-d H:i:s')
    );
  }
}
