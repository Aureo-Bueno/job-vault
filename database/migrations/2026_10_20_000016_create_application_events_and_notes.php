<?php

use App\Infrastructure\Persistence\Database;
use App\Infrastructure\Persistence\Migrations\Migration;

/**
 * Pipeline history (stage changes) and internal notes about applications.
 * Existing applications get their initial "recebida" event.
 */
return new class extends Migration {
  public function up(Database $db): void
  {
    $db->exec(<<<'SQL'
      CREATE TABLE `application_events` (
        `id` bigint unsigned NOT NULL AUTO_INCREMENT,
        `application_id` char(36) NOT NULL,
        `from_status` varchar(20) NULL,
        `to_status` varchar(20) NOT NULL,
        `actor_id` char(36) NULL,
        `reason` varchar(500) NULL,
        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `idx_application_events_application` (`application_id`, `id`),
        CONSTRAINT `fk_application_events_application` FOREIGN KEY (`application_id`) REFERENCES `applications` (`id`) ON DELETE CASCADE,
        CONSTRAINT `fk_application_events_actor` FOREIGN KEY (`actor_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci
      SQL);

    $db->exec(<<<'SQL'
      CREATE TABLE `application_notes` (
        `id` bigint unsigned NOT NULL AUTO_INCREMENT,
        `application_id` char(36) NOT NULL,
        `author_id` char(36) NULL,
        `body` text NOT NULL,
        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `idx_application_notes_application` (`application_id`, `id`),
        CONSTRAINT `fk_application_notes_application` FOREIGN KEY (`application_id`) REFERENCES `applications` (`id`) ON DELETE CASCADE,
        CONSTRAINT `fk_application_notes_author` FOREIGN KEY (`author_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci
      SQL);

    $db->exec(<<<'SQL'
      INSERT INTO `application_events` (`application_id`, `from_status`, `to_status`, `actor_id`, `created_at`)
      SELECT `id`, NULL, 'recebida', `user_id`, `created_at` FROM `applications` ORDER BY `created_at`, `id`
      SQL);
  }

  public function down(Database $db): void
  {
    $db->exec('DROP TABLE IF EXISTS `application_notes`');
    $db->exec('DROP TABLE IF EXISTS `application_events`');
  }
};
