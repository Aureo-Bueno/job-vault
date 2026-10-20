<?php

use App\Infrastructure\Persistence\Database;
use App\Infrastructure\Persistence\Migrations\Migration;

/**
 * Indexes for the vacancy listing: status filter + ORDER BY created_at, area and work mode filters.
 * (company_id already has an index through its foreign key.)
 */
return new class extends Migration {
  public function up(Database $db): void
  {
    $db->exec(<<<'SQL'
      ALTER TABLE `vacancies`
        ADD INDEX `idx_vacancies_active_created` (`is_active`, `created_at`),
        ADD INDEX `idx_vacancies_created` (`created_at`),
        ADD INDEX `idx_vacancies_area` (`area`),
        ADD INDEX `idx_vacancies_work_mode` (`work_mode`)
      SQL);
  }

  public function down(Database $db): void
  {
    $db->exec(<<<'SQL'
      ALTER TABLE `vacancies`
        DROP INDEX `idx_vacancies_active_created`,
        DROP INDEX `idx_vacancies_created`,
        DROP INDEX `idx_vacancies_area`,
        DROP INDEX `idx_vacancies_work_mode`
      SQL);
  }
};
