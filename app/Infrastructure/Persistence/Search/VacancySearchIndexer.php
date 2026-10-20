<?php

namespace App\Infrastructure\Persistence\Search;

use App\Infrastructure\Persistence\Database;

/**
 * Rebuilds `vacancies.search_document` straight from the tables. Used by the migration
 * that creates the index and by `bin/console vacancies:reindex-search` (run it after
 * changing the tokenizer or inserting vacancies with raw SQL).
 */
final class VacancySearchIndexer
{
  public function __construct(private Database $db)
  {
  }

  /**
   * @return int number of vacancies indexed
   */
  public function reindexAll(): int
  {
    $tags = [];
    foreach (['topics' => 'vacancy_topics', 'benefits' => 'vacancy_benefits'] as $kind => $table) {
      foreach ($this->db->fetchAll('SELECT vacancy_id, label FROM ' . $table . ' ORDER BY label') as $row) {
        $tags[(string) $row['vacancy_id']][$kind][] = (string) $row['label'];
      }
    }

    $rows = $this->db->fetchAll('SELECT id, title, description FROM vacancies');
    foreach ($rows as $row) {
      $id = (string) $row['id'];
      $this->db->run('UPDATE vacancies SET search_document = ? WHERE id = ?', [
        VacancySearchDocument::build(
          (string) $row['title'],
          (string) $row['description'],
          $tags[$id]['topics'] ?? [],
          $tags[$id]['benefits'] ?? []
        ),
        $id,
      ]);
    }

    return count($rows);
  }
}
