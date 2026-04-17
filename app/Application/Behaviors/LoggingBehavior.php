<?php

namespace App\Application\Behaviors;

/**
 * Logs message lifecycle around the next pipeline step.
 */
final class LoggingBehavior implements BehaviorInterface
{
  /**
   * @var callable(string,array<string,mixed>):void
   */
  private $logger;

  /**
   * @param callable(string,array<string,mixed>):void $logger
   */
  public function __construct(callable $logger)
  {
    $this->logger = $logger;
  }

  public function handle(object $message, callable $next): mixed
  {
    ($this->logger)('pipeline.started', ['message' => $message::class]);

    $result = $next($message);

    ($this->logger)('pipeline.finished', ['message' => $message::class]);

    return $result;
  }
}
