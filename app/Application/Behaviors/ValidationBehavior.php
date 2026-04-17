<?php

namespace App\Application\Behaviors;

/**
 * Runs custom validation callback before continuing message pipeline.
 */
final class ValidationBehavior implements BehaviorInterface
{
  /**
   * @var callable(object):void
   */
  private $validator;

  /**
   * @param callable(object):void $validator
   */
  public function __construct(callable $validator)
  {
    $this->validator = $validator;
  }

  public function handle(object $message, callable $next): mixed
  {
    ($this->validator)($message);

    return $next($message);
  }
}
