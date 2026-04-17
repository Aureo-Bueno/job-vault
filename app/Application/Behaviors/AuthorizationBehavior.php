<?php

namespace App\Application\Behaviors;

/**
 * Runs custom authorization callback before continuing message pipeline.
 */
final class AuthorizationBehavior implements BehaviorInterface
{
  /**
   * @var callable(object):void
   */
  private $authorizer;

  /**
   * @param callable(object):void $authorizer
   */
  public function __construct(callable $authorizer)
  {
    $this->authorizer = $authorizer;
  }

  public function handle(object $message, callable $next): mixed
  {
    ($this->authorizer)($message);

    return $next($message);
  }
}
