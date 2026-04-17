<?php

namespace App\Application\DTOs;

/**
 * Output DTO representing permission data in application layer.
 */
final class PermissionDto
{
  public function __construct(
    public ?string $id,
    public string $name,
    public string $description,
    public string $module,
    public string $action,
    public string $createdAt
  ) {
  }
}
