<?php

use App\Infrastructure\Persistence\Database;
use App\Infrastructure\Persistence\Migrations\Migration;

/**
 * Inserts a row, then fails: the insert must be rolled back and the migration not recorded.
 */
return new class extends Migration {
  public function up(Database $db): void
  {
    $db->run('INSERT INTO teams (name) VALUES (?)', ['half-done']);
    $db->run('INSERT INTO missing_table (name) VALUES (?)', ['boom']);
  }

  public function down(Database $db): void
  {
  }
};
