<?php

namespace App\Application\DTOs;

/**
 * Internal note about an application (hiring team only).
 */
final class ApplicationNoteDto
{
  public function __construct(
    public readonly string $authorName,
    public readonly string $body,
    public readonly string $createdAt
  ) {
  }
}
