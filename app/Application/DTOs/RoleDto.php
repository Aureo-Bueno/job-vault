<?php

namespace App\Application\DTOs;

/**
 * Output DTO representing role data in application layer.
 */
final class RoleDto
{
  public function __construct(
    public ?string $id,
    public string $name,
    public string $description,
    public string $createdAt
  ) {
  }
}
