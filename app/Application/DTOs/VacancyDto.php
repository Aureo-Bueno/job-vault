<?php

namespace App\Application\DTOs;

/**
 * Output DTO representing vacancy data in application layer.
 */
final class VacancyDto
{
  public function __construct(
    public ?string $id,
    public string $title,
    public string $description,
    public string $isActive,
    public string $createdAt
  ) {
  }
}
