<?php

namespace Tests\Support;

use App\Application\Ports\SessionInterface;

final class InMemorySession implements SessionInterface
{
  /** @var array<string,mixed> */
  public array $data = [];
  public int $regenerations = 0;

  public function get(string $key, mixed $default = null): mixed
  {
    return $this->data[$key] ?? $default;
  }

  public function set(string $key, mixed $value): void
  {
    $this->data[$key] = $value;
  }

  public function remove(string $key): void
  {
    unset($this->data[$key]);
  }

  public function regenerate(): void
  {
    $this->regenerations++;
  }

  public function destroy(): void
  {
    $this->data = [];
  }
}
