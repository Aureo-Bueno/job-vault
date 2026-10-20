<?php

namespace App\Infrastructure\Persistence;

use App\Domain\Shared\PageRequest;
use App\Domain\Shared\SearchTerm;
use DateTimeImmutable;

/**
 * SQL building helpers shared by the PDO repositories.
 */
final class Sql
{
  private function __construct()
  {
  }

  /**
   * `%word1%word2%` with LIKE wildcards in the user input escaped.
   */
  public static function likePattern(SearchTerm $term): string
  {
    $escaped = array_map(static fn (string $word): string => addcslashes($word, '%_\\'), $term->words());
    return '%' . implode('%', $escaped) . '%';
  }

  /**
   * LIMIT clause built from integers only (safe to concatenate).
   */
  public static function limit(PageRequest $page): string
  {
    if ($page->size === PHP_INT_MAX) {
      return '';
    }

    return sprintf(' LIMIT %d, %d', $page->offset(), $page->size);
  }

  /**
   * @param string[] $conditions
   */
  public static function where(array $conditions): string
  {
    return $conditions === [] ? '' : ' WHERE ' . implode(' AND ', $conditions);
  }

  public static function toDate(mixed $value): DateTimeImmutable
  {
    if (is_string($value) && trim($value) !== '') {
      return new DateTimeImmutable($value);
    }

    return new DateTimeImmutable();
  }

  public static function fromDate(DateTimeImmutable $date): string
  {
    return $date->format('Y-m-d H:i:s');
  }
}
