<?php

namespace App\Db;

/**
 * Pagination Class
 *
 * Handles pagination logic for database result sets.
 * Calculates total pages, current page, and LIMIT offset for queries.
 *
 * @package App\Db
 * @version 2.0
 */
class Pagination
{
  /**
   * Maximum number of records per page
   *
   * @var int
   */
  private $limit;

  /**
   * Total number of results from database
   *
   * @var int
   */
  private $results;

  /**
   * Total number of pages
   *
   * @var int
   */
  private $pages;

  /**
   * Current page number
   *
   * @var int
   */
  private $currentPage;

  /**
   * Constructor
   *
   * Initializes pagination with result count and current page.
   * Automatically calculates total pages and validates current page.
   *
   * Example:
   *   $pagination = new Pagination(100, 2, 10);
   *   echo $pagination->getLimit();  // "10,10"
   *   print_r($pagination->getPages());
   *
   * @param int $results Total number of results
   * @param int $currentPage Current page number (default: 1)
   * @param int $limit Records per page (default: 10)
   */
  public function __construct($results, $currentPage = 1, $limit = 10)
  {
    $this->results = (int) $results;
    $this->limit = (int) $limit;
    $this->currentPage = $this->validatePage($currentPage);
    $this->calculate();
  }

  /**
   * Validate and sanitize page number
   *
   * Ensures page number is numeric and positive.
   *
   * @param mixed $page Page number to validate
   * @return int Validated page number
   */
  private function validatePage($page)
  {
    return (is_numeric($page) && $page > 0) ? (int) $page : 1;
  }

  /**
   * Calculate total pages and adjust current page if necessary
   *
   * Called automatically in constructor.
   * If current page exceeds total pages, sets it to last page.
   *
   * @return void
   */
  private function calculate()
  {
    // Calculate total pages
    $this->pages = $this->results > 0 ? ceil($this->results / $this->limit) : 1;

    // Ensure current page doesn't exceed total pages
    $this->currentPage = min($this->currentPage, $this->pages);
  }

  /**
   * Get LIMIT clause for SQL query
   *
   * Returns the offset and limit for pagination.
   * Format: "offset,limit"
   *
   * Example:
   *   $pagination = new Pagination(100, 2, 10);
   *   $limit = $pagination->getLimit();  // "10,10"
   *   $query = "SELECT * FROM users LIMIT " . $limit;
   *
   * @return string LIMIT clause in format "offset,limit"
   */
  public function getLimit()
  {
    $offset = $this->limit * ($this->currentPage - 1);
    return "{$offset},{$this->limit}";
  }

  /**
   * Get available pages for pagination links
   *
   * Returns array of page objects with page number and current status.
   * Useful for generating pagination navigation in templates.
   *
   * Example:
   *   $pages = $pagination->getPages();
   *   foreach ($pages as $page) {
   *       $active = $page['atual'] ? 'class="active"' : '';
   *       echo "<a {$active} href='?pagina={$page['pagina']}'>
   *           {$page['pagina']}</a>";
   *   }
   *
   * @return array Array of page info [['pagina' => 1, 'atual' => false], ...]
   */
  public function getPages()
  {
    // Return empty if only one page
    if ($this->pages <= 1) {
      return [];
    }

    $paginas = [];

    for ($i = 1; $i <= $this->pages; $i++) {
      $paginas[] = [
        'pagina' => $i,
        'atual' => ($i === $this->currentPage)
      ];
    }

    return $paginas;
  }

  /**
   * Get current page number
   *
   * @return int Current page number
   */
  public function getCurrentPage()
  {
    return $this->currentPage;
  }

  /**
   * Get total number of pages
   *
   * @return int Total pages
   */
  public function getTotalPages()
  {
    return $this->pages;
  }

  /**
   * Get total number of results
   *
   * @return int Total results
   */
  public function getTotalResults()
  {
    return $this->results;
  }

  /**
   * Get limit per page
   *
   * @return int Records per page
   */
  public function getRecordsPerPage()
  {
    return $this->limit;
  }

  /**
   * Check if there's a next page
   *
   * @return bool True if next page exists
   */
  public function hasNextPage()
  {
    return $this->currentPage < $this->pages;
  }

  /**
   * Check if there's a previous page
   *
   * @return bool True if previous page exists
   */
  public function hasPreviousPage()
  {
    return $this->currentPage > 1;
  }
}
