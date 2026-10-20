<?php

namespace App\Application\Ports;

/**
 * Key/value session storage. Implemented by infrastructure (native PHP session, in-memory for tests).
 */
interface SessionInterface
{
  public function get(string $key, mixed $default = null): mixed;

  public function set(string $key, mixed $value): void;

  public function remove(string $key): void;

  /**
   * Regenerates the session id (prevents session fixation after login).
   */
  public function regenerate(): void;

  /**
   * Clears all data and invalidates the session.
   */
  public function destroy(): void;
}
