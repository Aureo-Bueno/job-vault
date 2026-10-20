<?php

namespace Tests\Support;

use App\Application\Abstractions\Command;
use App\Application\Abstractions\CommandHandlerInterface;

/**
 * Handles any command class and records what was dispatched.
 */
final class RecordingHandler implements CommandHandlerInterface
{
  /** @var Command[] */
  public array $handled = [];

  /**
   * @param class-string<Command> $commandClass
   */
  public function __construct(private string $commandClass, private mixed $result = null)
  {
  }

  public function commandClass(): string
  {
    return $this->commandClass;
  }

  public function handle(Command $command): mixed
  {
    $this->handled[] = $command;
    return $this->result;
  }
}
