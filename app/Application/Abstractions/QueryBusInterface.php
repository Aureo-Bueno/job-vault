<?php

namespace App\Application\Abstractions;

/**
 * Dispatches query messages to registered handlers.
 */
interface QueryBusInterface
{
  public function ask(Query $query): mixed;
}
