<?php

use App\Infrastructure\Persistence\Database;
use App\Infrastructure\Persistence\Migrations\Migration;

/**
 * Baseline: candidate profiles and their topic tags.
 */
return new class extends Migration {
  public function up(Database $db): void
  {
    $db->exec(<<<'SQL'
      CREATE TABLE `candidate_profiles` (
        `user_id` char(36) NOT NULL,
        `headline` varchar(160) NOT NULL DEFAULT '',
        `bio` text DEFAULT NULL,
        `area` varchar(40) NOT NULL DEFAULT 'backend',
        `seniority` varchar(40) NOT NULL DEFAULT 'pleno',
        `work_mode` varchar(40) NOT NULL DEFAULT 'remoto',
        `linkedin_url` varchar(255) DEFAULT NULL,
        `github_url` varchar(255) DEFAULT NULL,
        `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`user_id`),
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci
      SQL);

    $db->exec(<<<'SQL'
      CREATE TABLE `candidate_topics` (
        `user_id` char(36) NOT NULL,
        `slug` varchar(60) NOT NULL,
        `label` varchar(60) NOT NULL,
        PRIMARY KEY (`user_id`, `slug`),
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci
      SQL);
  }

  public function down(Database $db): void
  {
    $db->exec('DROP TABLE IF EXISTS `candidate_topics`');
    $db->exec('DROP TABLE IF EXISTS `candidate_profiles`');
  }
};
