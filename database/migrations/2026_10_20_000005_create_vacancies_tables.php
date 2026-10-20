<?php

use App\Infrastructure\Persistence\Database;
use App\Infrastructure\Persistence\Migrations\Migration;

/**
 * Baseline: vacancies and their topic tags.
 */
return new class extends Migration {
  public function up(Database $db): void
  {
    $db->exec(<<<'SQL'
      CREATE TABLE `vacancies` (
        `id` char(36) NOT NULL DEFAULT (UUID()),
        `company_id` char(36) DEFAULT NULL,
        `title` varchar(255) NOT NULL,
        `description` text NOT NULL,
        `area` varchar(40) NOT NULL DEFAULT 'backend',
        `seniority` varchar(40) NOT NULL DEFAULT 'pleno',
        `work_mode` varchar(40) NOT NULL DEFAULT 'remoto',
        `is_active` enum('s','n') NOT NULL,
        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        CONSTRAINT `fk_vacancies_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci
      SQL);

    $db->exec(<<<'SQL'
      CREATE TABLE `vacancy_topics` (
        `vacancy_id` char(36) NOT NULL,
        `slug` varchar(60) NOT NULL,
        `label` varchar(60) NOT NULL,
        PRIMARY KEY (`vacancy_id`, `slug`),
        FOREIGN KEY (`vacancy_id`) REFERENCES `vacancies` (`id`) ON DELETE CASCADE
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci
      SQL);
  }

  public function down(Database $db): void
  {
    $db->exec('DROP TABLE IF EXISTS `vacancy_topics`');
    $db->exec('DROP TABLE IF EXISTS `vacancies`');
  }
};
