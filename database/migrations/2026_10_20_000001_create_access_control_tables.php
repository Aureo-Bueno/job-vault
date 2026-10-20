<?php

use App\Infrastructure\Persistence\Database;
use App\Infrastructure\Persistence\Migrations\Migration;

/**
 * Baseline: access control tables, as originally defined in setup.sql.
 */
return new class extends Migration {
  public function up(Database $db): void
  {
    $db->exec(<<<'SQL'
      CREATE TABLE `roles` (
        `id` char(36) NOT NULL DEFAULT (UUID()),
        `name` varchar(100) NOT NULL UNIQUE,
        `description` varchar(255),
        `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        UNIQUE KEY `unique_name` (`name`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci
      SQL);

    $db->exec(<<<'SQL'
      CREATE TABLE `permissions` (
        `id` char(36) NOT NULL DEFAULT (UUID()),
        `name` varchar(100) NOT NULL UNIQUE,
        `description` varchar(255),
        `module` varchar(50),
        `action` varchar(50),
        `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        UNIQUE KEY `unique_name` (`name`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci
      SQL);

    $db->exec(<<<'SQL'
      CREATE TABLE `role_permissions` (
        `id` char(36) NOT NULL DEFAULT (UUID()),
        `role_id` char(36) NOT NULL,
        `permission_id` char(36) NOT NULL,
        `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        UNIQUE KEY `unique_role_permission` (`role_id`, `permission_id`),
        FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE,
        FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci
      SQL);
  }

  public function down(Database $db): void
  {
    $db->exec('DROP TABLE IF EXISTS `role_permissions`');
    $db->exec('DROP TABLE IF EXISTS `permissions`');
    $db->exec('DROP TABLE IF EXISTS `roles`');
  }
};
