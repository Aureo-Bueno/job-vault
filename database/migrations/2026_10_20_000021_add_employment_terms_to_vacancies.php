<?php

use App\Infrastructure\Persistence\Database;
use App\Infrastructure\Persistence\Migrations\Migration;

/**
 * Employment terms on vacancies: contract type, monthly salary range (whole reais,
 * both ends optional = "A combinar"), city/UF, application deadline and free-form benefits.
 *
 * Existing vacancies become CLT, "A combinar", without location or deadline.
 */
return new class extends Migration {
  public function up(Database $db): void
  {
    $db->exec(<<<'SQL'
      ALTER TABLE `vacancies`
        ADD COLUMN `contract_type` varchar(20) NOT NULL DEFAULT 'clt' AFTER `positions`,
        ADD COLUMN `salary_min` int unsigned NULL AFTER `contract_type`,
        ADD COLUMN `salary_max` int unsigned NULL AFTER `salary_min`,
        ADD COLUMN `city` varchar(100) NULL AFTER `salary_max`,
        ADD COLUMN `state` char(2) NULL AFTER `city`,
        ADD COLUMN `closes_at` date NULL AFTER `state`,
        ADD INDEX `idx_vacancies_state` (`state`),
        ADD INDEX `idx_vacancies_contract_type` (`contract_type`),
        ADD INDEX `idx_vacancies_status_closes` (`status`, `closes_at`)
      SQL);

    $db->exec(<<<'SQL'
      CREATE TABLE `vacancy_benefits` (
        `vacancy_id` char(36) NOT NULL,
        `slug` varchar(60) NOT NULL,
        `label` varchar(60) NOT NULL,
        PRIMARY KEY (`vacancy_id`, `slug`),
        CONSTRAINT `fk_vacancy_benefits_vacancy` FOREIGN KEY (`vacancy_id`) REFERENCES `vacancies` (`id`) ON DELETE CASCADE
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci
      SQL);
  }

  public function down(Database $db): void
  {
    $db->exec('DROP TABLE IF EXISTS `vacancy_benefits`');
    $db->exec(<<<'SQL'
      ALTER TABLE `vacancies`
        DROP INDEX `idx_vacancies_status_closes`,
        DROP INDEX `idx_vacancies_contract_type`,
        DROP INDEX `idx_vacancies_state`,
        DROP COLUMN `closes_at`,
        DROP COLUMN `state`,
        DROP COLUMN `city`,
        DROP COLUMN `salary_max`,
        DROP COLUMN `salary_min`,
        DROP COLUMN `contract_type`
      SQL);
  }
};
