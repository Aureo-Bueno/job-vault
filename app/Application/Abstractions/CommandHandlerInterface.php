<?php

namespace App\Application\Abstractions;

/**
 * Contract for command handlers.
 */
interface CommandHandlerInterface
{
  /**
   * Returns the command class handled by this handler.
   *
   * @return class-string<Command>
   */
  public function commandClass(): string;

  /**
   * Handles command message.
   */
  public function handle(Command $command): mixed;
}
