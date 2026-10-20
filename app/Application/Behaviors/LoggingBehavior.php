<?php

namespace App\Application\Behaviors;

use App\Application\Ports\LoggerInterface;
use Throwable;

/**
 * Logs message lifecycle (including failures) around the next pipeline step.
 */
final class LoggingBehavior implements BehaviorInterface
{
  public function __construct(private LoggerInterface $logger)
  {
  }

  public function handle(object $message, callable $next): mixed
  {
    $context = ['message' => $message::class];
    $this->logger->info('pipeline.started', $context);

    try {
      $result = $next($message);
    } catch (Throwable $exception) {
      $this->logger->warning('pipeline.failed', $context + [
        'exception' => $exception::class,
        'error' => $exception->getMessage(),
      ]);
      throw $exception;
    }

    $this->logger->info('pipeline.finished', $context);

    return $result;
  }
}
