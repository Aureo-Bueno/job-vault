<?php

namespace App\Presentation\Support;

use RuntimeException;

/**
 * Thrown instead of exit() when APP_TEST_MODE=1 so tests can assert redirects.
 */
final class RedirectSignal extends RuntimeException
{
  public function __construct(public readonly string $location)
  {
    parent::__construct('REDIRECT:' . $location);
  }
}
