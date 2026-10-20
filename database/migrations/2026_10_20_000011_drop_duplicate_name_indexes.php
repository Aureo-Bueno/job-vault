<?php

use App\Infrastructure\Persistence\Database;
use App\Infrastructure\Persistence\Migrations\Migration;

/**
 * `roles.name` and `permissions.name` were declared with an inline UNIQUE *and* a
 * `UNIQUE KEY unique_name`, creating two identical indexes. Keep only `unique_name`.
 */
return new class extends Migration {
  public function up(Database $db): void
  {
    $db->exec('ALTER TABLE `roles` DROP INDEX `name`');
    $db->exec('ALTER TABLE `permissions` DROP INDEX `name`');
  }

  public function down(Database $db): void
  {
    $db->exec('ALTER TABLE `roles` ADD UNIQUE KEY `name` (`name`)');
    $db->exec('ALTER TABLE `permissions` ADD UNIQUE KEY `name` (`name`)');
  }
};
