<?php

use App\Infrastructure\Persistence\Database;
use App\Infrastructure\Persistence\Migrations\Migration;

/**
 * `vacancies.created_at` is the publication date: it must not change on every UPDATE.
 */
return new class extends Migration {
  public function up(Database $db): void
  {
    $db->exec('ALTER TABLE `vacancies` MODIFY `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP');
  }

  public function down(Database $db): void
  {
    $db->exec('ALTER TABLE `vacancies` MODIFY `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP');
  }
};
