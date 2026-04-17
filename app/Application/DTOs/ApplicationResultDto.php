<?php

namespace App\Application\DTOs;

/**
 * Output DTO representing vacancy application result.
 */
final class ApplicationResultDto
{
  public function __construct(
    public string $status,
    public string $userId,
    public string $vacancyId
  ) {
  }
}
