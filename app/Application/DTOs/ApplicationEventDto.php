<?php

namespace App\Application\DTOs;

/**
 * One step of an application's history, ready for display.
 */
final class ApplicationEventDto
{
  public function __construct(
    public readonly string $status,
    public readonly string $label,
    public readonly string $actorName,
    public readonly string $reason,
    public readonly string $at
  ) {
  }
}
