<?php

namespace App\Application\Commands\Applications;

use App\Application\Abstractions\Command;

/**
 * The current user (candidate) withdraws one of their applications.
 */
final class WithdrawApplicationCommand implements Command
{
  public function __construct(public readonly string $applicationId)
  {
  }
}
