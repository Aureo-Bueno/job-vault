<?php

namespace App\Application\Abstractions;

/**
 * Contract for query handlers.
 */
interface QueryHandlerInterface
{
  /**
   * Returns the query class handled by this handler.
   *
   * @return class-string<Query>
   */
  public function queryClass(): string;

  /**
   * Handles query message.
   */
  public function handle(Query $query): mixed;
}
