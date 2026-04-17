<?php

namespace App\Application\Abstractions;

use App\Application\Behaviors\BehaviorInterface;

/**
 * In-memory command bus with optional behavior pipeline.
 */
final class SimpleCommandBus implements CommandBusInterface
{
  /** @var CommandHandlerInterface[] */
  private array $handlers;

  /** @var BehaviorInterface[] */
  private array $behaviors;

  /**
   * @param CommandHandlerInterface[] $handlers
   * @param BehaviorInterface[] $behaviors
   */
  public function __construct(array $handlers, array $behaviors = [])
  {
    $this->handlers = $handlers;
    $this->behaviors = $behaviors;
  }

  public function dispatch(Command $command): mixed
  {
    $handler = $this->resolveHandler($command);

    $pipeline = function (object $message) use ($handler): mixed {
      return $handler->handle($message);
    };

    foreach (array_reverse($this->behaviors) as $behavior) {
      $next = $pipeline;
      $pipeline = function (object $message) use ($behavior, $next): mixed {
        return $behavior->handle($message, $next);
      };
    }

    return $pipeline($command);
  }

  private function resolveHandler(Command $command): CommandHandlerInterface
  {
    foreach ($this->handlers as $handler) {
      $class = $handler->commandClass();
      if ($command instanceof $class) {
        return $handler;
      }
    }

    throw new HandlerNotFoundException('No command handler registered for ' . $command::class);
  }
}
