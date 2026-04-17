<?php

namespace App\Application\Behaviors;

/**
 * Middleware-like behavior for command/query pipelines.
 */
interface BehaviorInterface
{
  /**
   * Executes behavior and forwards flow through next callback.
   */
  public function handle(object $message, callable $next): mixed;
}
