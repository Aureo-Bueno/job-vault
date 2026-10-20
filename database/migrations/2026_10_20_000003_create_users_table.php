<?php

use App\Infrastructure\Persistence\Database;
use App\Infrastructure\Persistence\Migrations\Migration;

/**
 * Baseline: users (with role_id and company_id).
 */
return new class extends Migration {
  public function up(Database $db): void
  {
    $db->exec(<<<'SQL'
      CREATE TABLE `users` (
        `id` char(36) NOT NULL DEFAULT (UUID()),
        `name` varchar(255) DEFAULT NULL,
        `email` varchar(255) DEFAULT NULL,
        `password` varchar(255) DEFAULT NULL,
        `role_id` char(36) DEFAULT NULL,
        `company_id` char(36) DEFAULT NULL,
        PRIMARY KEY (`id`),
        UNIQUE KEY `email` (`email`),
        CONSTRAINT `fk_users_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE SET NULL,
        CONSTRAINT `fk_users_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE SET NULL
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci
      SQL);
  }

  public function down(Database $db): void
  {
    $db->exec('DROP TABLE IF EXISTS `users`');
  }
};
