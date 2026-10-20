<?php

use App\Infrastructure\Persistence\Database;
use App\Infrastructure\Persistence\Migrations\Migration;

return new class extends Migration {
  public function up(Database $db): void
  {
    $db->run('INSERT INTO teams (name) VALUES (?), (?)', ['alpha', 'beta']);
  }

  public function down(Database $db): void
  {
    $db->run('DELETE FROM teams WHERE name IN (?, ?)', ['alpha', 'beta']);
  }
};
