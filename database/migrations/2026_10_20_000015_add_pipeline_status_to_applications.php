<?php

use App\Infrastructure\Persistence\Database;
use App\Infrastructure\Persistence\Migrations\Migration;

/**
 * Hiring pipeline: each application now has a stage, when it last changed and
 * the feedback shown to the candidate when rejected.
 */
return new class extends Migration {
  public function up(Database $db): void
  {
    $db->exec(<<<'SQL'
      ALTER TABLE `applications`
        ADD COLUMN `status` varchar(20) NOT NULL DEFAULT 'recebida' AFTER `vacancy_id`,
        ADD COLUMN `status_changed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER `status`,
        ADD COLUMN `candidate_feedback` varchar(500) NULL AFTER `status_changed_at`,
        MODIFY `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        ADD INDEX `idx_applications_vacancy_status` (`vacancy_id`, `status`)
      SQL);

    // Existing applications entered the pipeline when they were submitted.
    $db->exec('UPDATE `applications` SET `status_changed_at` = `created_at`');
  }

  public function down(Database $db): void
  {
    // MySQL may have dropped the automatic `vacancy_id` FK index when the composite index
    // was added; recreate it first, otherwise the composite one cannot be dropped.
    $hasFkIndex = $db->fetchValue(
      "SELECT 1 FROM information_schema.statistics
        WHERE table_schema = DATABASE() AND table_name = 'applications' AND index_name = 'vacancy_id' LIMIT 1"
    );
    if ($hasFkIndex === null) {
      $db->exec('ALTER TABLE `applications` ADD INDEX `vacancy_id` (`vacancy_id`)');
    }

    $db->exec(<<<'SQL'
      ALTER TABLE `applications`
        DROP INDEX `idx_applications_vacancy_status`,
        DROP COLUMN `candidate_feedback`,
        DROP COLUMN `status_changed_at`,
        DROP COLUMN `status`,
        MODIFY `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
      SQL);
  }
};
