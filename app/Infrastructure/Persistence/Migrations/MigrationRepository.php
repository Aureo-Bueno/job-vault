<?php

namespace App\Infrastructure\Persistence\Migrations;

use App\Infrastructure\Persistence\Database;

/**
 * Persists which migrations ran and in which batch (`schema_migrations` table).
 */
final class MigrationRepository
{
  private const TABLE = 'schema_migrations';

  public function __construct(private Database $db)
  {
  }

  public function ensureTable(): void
  {
    $this->db->exec(
      'CREATE TABLE IF NOT EXISTS ' . self::TABLE . ' ('
      . 'migration VARCHAR(255) NOT NULL PRIMARY KEY, '
      . 'batch INT NOT NULL, '
      . 'applied_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP'
      . ')'
    );
  }

  /**
   * @return array<string,int> migration name => batch, oldest first
   */
  public function applied(): array
  {
    $rows = $this->db->fetchAll('SELECT migration, batch FROM ' . self::TABLE . ' ORDER BY batch ASC, migration ASC');

    $applied = [];
    foreach ($rows as $row) {
      $applied[(string) $row['migration']] = (int) $row['batch'];
    }

    return $applied;
  }

  public function nextBatch(): int
  {
    return (int) $this->db->fetchValue('SELECT COALESCE(MAX(batch), 0) FROM ' . self::TABLE) + 1;
  }

  public function log(string $migration, int $batch): void
  {
    $this->db->run('INSERT INTO ' . self::TABLE . ' (migration, batch) VALUES (?, ?)', [$migration, $batch]);
  }

  public function forget(string $migration): void
  {
    $this->db->run('DELETE FROM ' . self::TABLE . ' WHERE migration = ?', [$migration]);
  }
}
