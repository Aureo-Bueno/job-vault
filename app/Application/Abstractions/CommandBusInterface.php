<?php

namespace App\Application\Abstractions;

/**
 * Dispatches command messages to registered handlers.
 */
interface CommandBusInterface
{
  public function dispatch(Command $command): mixed;
}
