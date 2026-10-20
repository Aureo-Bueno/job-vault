<?php

namespace App\Application\Commands\Applications;

use App\Application\Abstractions\Command;

/**
 * Recruiter writes an internal note about an application.
 */
final class AddApplicationNoteCommand implements Command
{
  public function __construct(
    public readonly string $applicationId,
    public readonly string $body
  ) {
  }
}
