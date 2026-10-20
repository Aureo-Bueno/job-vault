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
   *
   * No native return type on purpose: handlers may declare `void` or a concrete DTO.
   *
   * @return mixed
   */
  public function handle(Command $command);
}
