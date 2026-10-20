<?php

namespace App\Application\DTOs;

/**
 * A page of results plus the data needed to render pagination.
 *
 * @template T
 */
final class PagedResult
{
  /**
   * @param list<T> $items
   */
  public function __construct(
    public readonly array $items,
    public readonly int $total,
    public readonly int $page,
    public readonly int $perPage
  ) {
  }

  public function totalPages(): int
  {
    return max(1, (int) ceil($this->total / max(1, $this->perPage)));
  }
}
