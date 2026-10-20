<?php

use App\Infrastructure\Persistence\Database;
use App\Infrastructure\Persistence\Migrations\MigrationException;
use App\Infrastructure\Persistence\Migrations\Migration;

/**
 * The domain always requires name, e-mail and password: enforce it in the schema
 * and record when each account was created.
 */
return new class extends Migration {
  public function up(Database $db): void
  {
    $invalid = (int) $db->fetchValue(
      'SELECT COUNT(*) FROM `users` WHERE `name` IS NULL OR `email` IS NULL OR `password` IS NULL'
    );
    if ($invalid > 0) {
      throw new MigrationException(sprintf(
        '%d user(s) have NULL name/email/password. Fix or delete them before running this migration.',
        $invalid
      ));
    }

    $db->exec(<<<'SQL'
      ALTER TABLE `users`
        MODIFY `name` varchar(255) NOT NULL,
        MODIFY `email` varchar(255) NOT NULL,
        MODIFY `password` varchar(255) NOT NULL,
        ADD COLUMN `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER `company_id`
      SQL);
  }

  public function down(Database $db): void
  {
    $db->exec(<<<'SQL'
      ALTER TABLE `users`
        DROP COLUMN `created_at`,
        MODIFY `name` varchar(255) DEFAULT NULL,
        MODIFY `email` varchar(255) DEFAULT NULL,
        MODIFY `password` varchar(255) DEFAULT NULL
      SQL);
  }
};
