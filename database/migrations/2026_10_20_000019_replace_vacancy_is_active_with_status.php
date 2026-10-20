<?php

use App\Infrastructure\Persistence\Database;
use App\Infrastructure\Persistence\Migrations\Migration;

/**
 * Vacancy lifecycle: the legacy `is_active enum('s','n')` becomes
 * `status` (rascunho | aberta | encerrada) with the closing reason/date,
 * plus the number of positions to fill.
 *
 * Data: 's' -> aberta, 'n' -> encerrada (reason "manual").
 * down(): aberta -> 's', rascunho/encerrada -> 'n'.
 */
return new class extends Migration {
  public function up(Database $db): void
  {
    $db->exec(<<<'SQL'
      ALTER TABLE `vacancies`
        ADD COLUMN `status` varchar(20) NOT NULL DEFAULT 'aberta' AFTER `work_mode`,
        ADD COLUMN `close_reason` varchar(20) NULL AFTER `status`,
        ADD COLUMN `closed_at` timestamp NULL AFTER `close_reason`,
        ADD COLUMN `positions` smallint unsigned NOT NULL DEFAULT 1 AFTER `closed_at`
      SQL);

    $db->exec(<<<'SQL'
      UPDATE `vacancies`
         SET `status` = IF(`is_active` = 's', 'aberta', 'encerrada'),
             `close_reason` = IF(`is_active` = 's', NULL, 'manual')
      SQL);

    $db->exec(<<<'SQL'
      ALTER TABLE `vacancies`
        DROP INDEX `idx_vacancies_active_created`,
        DROP COLUMN `is_active`,
        ADD INDEX `idx_vacancies_status_created` (`status`, `created_at`)
      SQL);
  }

  public function down(Database $db): void
  {
    $db->exec("ALTER TABLE `vacancies` ADD COLUMN `is_active` enum('s','n') NOT NULL DEFAULT 'n' AFTER `work_mode`");
    $db->exec("UPDATE `vacancies` SET `is_active` = IF(`status` = 'aberta', 's', 'n')");
    $db->exec(<<<'SQL'
      ALTER TABLE `vacancies`
        MODIFY `is_active` enum('s','n') NOT NULL,
        DROP INDEX `idx_vacancies_status_created`,
        DROP COLUMN `positions`,
        DROP COLUMN `closed_at`,
        DROP COLUMN `close_reason`,
        DROP COLUMN `status`,
        ADD INDEX `idx_vacancies_active_created` (`is_active`, `created_at`)
      SQL);
  }
};
