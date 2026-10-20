<?php

namespace App\Domain\Shared;

/**
 * Requested slice of a result set (1-based page number).
 */
final class PageRequest
{
  public readonly int $number;
  public readonly int $size;

  public function __construct(int $number = 1, int $size = 10)
  {
    $this->number = max(1, $number);
    $this->size = max(1, $size);
  }

  public static function all(): self
  {
    return new self(1, PHP_INT_MAX);
  }

  public function offset(): int
  {
    return ($this->number - 1) * $this->size;
  }

  /**
   * Returns a page that does not go past the last page for the given total.
   */
  public function clampTo(int $total): self
  {
    $lastPage = max(1, (int) ceil($total / $this->size));
    return new self(min($this->number, $lastPage), $this->size);
  }
}
