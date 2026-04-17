<?php

namespace App\Application\Abstractions;

use App\Application\Behaviors\BehaviorInterface;

/**
 * In-memory query bus with optional behavior pipeline.
 */
final class SimpleQueryBus implements QueryBusInterface
{
  /** @var QueryHandlerInterface[] */
  private array $handlers;

  /** @var BehaviorInterface[] */
  private array $behaviors;

  /**
   * @param QueryHandlerInterface[] $handlers
   * @param BehaviorInterface[] $behaviors
   */
  public function __construct(array $handlers, array $behaviors = [])
  {
    $this->handlers = $handlers;
    $this->behaviors = $behaviors;
  }

  public function ask(Query $query): mixed
  {
    $handler = $this->resolveHandler($query);

    $pipeline = function (object $message) use ($handler): mixed {
      return $handler->handle($message);
    };

    foreach (array_reverse($this->behaviors) as $behavior) {
      $next = $pipeline;
      $pipeline = function (object $message) use ($behavior, $next): mixed {
        return $behavior->handle($message, $next);
      };
    }

    return $pipeline($query);
  }

  private function resolveHandler(Query $query): QueryHandlerInterface
  {
    foreach ($this->handlers as $handler) {
      $class = $handler->queryClass();
      if ($query instanceof $class) {
        return $handler;
      }
    }

    throw new HandlerNotFoundException('No query handler registered for ' . $query::class);
  }
}
