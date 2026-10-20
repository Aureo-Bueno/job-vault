<?php

use App\Infrastructure\Persistence\Database;
use App\Infrastructure\Persistence\Migrations\Migration;

return new class extends Migration {
  public function up(Database $db): void
  {
    $db->exec('CREATE TABLE teams (name TEXT NOT NULL PRIMARY KEY)');
  }

  public function down(Database $db): void
  {
    $db->exec('DROP TABLE teams');
  }
};
