<?php

use App\Infrastructure\Persistence\Database;
use App\Infrastructure\Persistence\Migrations\Migration;

/**
 * Baseline: companies (1 company : N recruiters).
 */
return new class extends Migration {
  public function up(Database $db): void
  {
    $db->exec(<<<'SQL'
      CREATE TABLE `companies` (
        `id` char(36) NOT NULL DEFAULT (UUID()),
        `name` varchar(150) NOT NULL,
        `sector` varchar(100) NOT NULL DEFAULT 'Tecnologia',
        `website` varchar(255) DEFAULT NULL,
        `description` text DEFAULT NULL,
        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        UNIQUE KEY `unique_company_name` (`name`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci
      SQL);
  }

  public function down(Database $db): void
  {
    $db->exec('DROP TABLE IF EXISTS `companies`');
  }
};
