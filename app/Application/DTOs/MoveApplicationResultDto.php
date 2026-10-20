<?php

namespace App\Application\DTOs;

/**
 * Outcome of moving an application in the pipeline.
 */
final class MoveApplicationResultDto
{
  public function __construct(
    public readonly string $status,
    public readonly string $statusLabel,
    public readonly bool $vacancyClosedAsFilled = false
  ) {
  }
}
