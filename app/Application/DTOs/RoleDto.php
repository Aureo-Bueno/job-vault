<?php

namespace App\Application\DTOs;

use App\Domain\Role\Role;

final class RoleDto
{
  /**
   * @param string[] $permissionIds
   */
  public function __construct(
    public readonly string $id,
    public readonly string $name,
    public readonly string $description,
    public readonly string $createdAt,
    public readonly bool $isSystem = false,
    public readonly int $userCount = 0,
    public readonly array $permissionIds = []
  ) {
  }

  public static function fromRole(Role $role, int $userCount = 0): self
  {
    return new self(
      $role->id()->value(),
      $role->name()->value(),
      $role->description(),
      $role->createdAt()->format('Y-m-d H:i:s'),
      $role->isAdmin(),
      $userCount,
      array_map(static fn ($id): string => $id->value(), $role->permissionIds())
    );
  }

  public function hasPermission(string $permissionId): bool
  {
    return in_array($permissionId, $this->permissionIds, true);
  }
}
