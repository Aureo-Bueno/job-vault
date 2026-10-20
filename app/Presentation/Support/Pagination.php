<?php

namespace App\Presentation\Support;

use App\Application\DTOs\PagedResult;

/**
 * Builds pagination links for the views.
 */
final class Pagination
{
  /**
   * @return array<int,array{pagina:int,atual:bool}> empty when there is a single page
   */
  public static function links(PagedResult $result): array
  {
    $totalPages = $result->totalPages();
    if ($totalPages <= 1) {
      return [];
    }

    $links = [];
    for ($page = 1; $page <= $totalPages; $page++) {
      $links[] = ['pagina' => $page, 'atual' => $page === $result->page];
    }

    return $links;
  }

  public static function currentPage(): int
  {
    $page = filter_var($_GET['pagina'] ?? 1, FILTER_VALIDATE_INT);
    return $page === false ? 1 : max(1, $page);
  }

  /**
   * Current query string without pagination/alert params (for building page links).
   */
  public static function preservedQuery(): string
  {
    $params = $_GET;
    unset($params['status'], $params['message'], $params['pagina'], $params['r']);
    return http_build_query($params);
  }
}
