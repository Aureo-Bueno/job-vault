<?php

namespace App\Application\Commands\Applications;

use App\Application\Abstractions\Command;

/**
 * Recruiter moves an application in the pipeline.
 *
 * `action`: advance (next stage) | reject | reopen (rejected -> em análise).
 * `expectedStatus`: stage shown on the board when the action was chosen; if it changed meanwhile
 * the command fails with a conflict instead of acting on stale data.
 */
final class MoveApplicationCommand implements Command
{
  public const ADVANCE = 'advance';
  public const REJECT = 'reject';
  public const REOPEN = 'reopen';

  public function __construct(
    public readonly string $applicationId,
    public readonly string $action,
    public readonly string $expectedStatus = '',
    public readonly string $feedback = ''
  ) {
  }
}
