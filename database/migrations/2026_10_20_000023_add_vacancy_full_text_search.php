<?php

use App\Infrastructure\Persistence\Database;
use App\Infrastructure\Persistence\Migrations\Migration;
use App\Infrastructure\Persistence\Search\VacancySearchIndexer;

/**
 * Full-text search for vacancies: `search_document` holds the normalized text of
 * title, description, topics and benefits (see VacancySearchDocument) under a FULLTEXT index.
 *
 * The index is created with InnoDB's built-in (English) stopword list disabled: words such as
 * "about" or "will" would be silently dropped and break "all words required" queries.
 * That setting is stored with the index, so it does not depend on the server config later.
 *
 * Existing rows are indexed here; the repository keeps the column in sync on every save.
 */
return new class extends Migration {
  public function up(Database $db): void
  {
    $db->exec('ALTER TABLE `vacancies` ADD COLUMN `search_document` mediumtext NULL AFTER `closes_at`');
    (new VacancySearchIndexer($db))->reindexAll();

    $db->exec('SET SESSION innodb_ft_enable_stopword = OFF');
    try {
      $db->exec('ALTER TABLE `vacancies` ADD FULLTEXT INDEX `ft_vacancies_search` (`search_document`)');
    } finally {
      $db->exec('SET SESSION innodb_ft_enable_stopword = ON');
    }
  }

  public function down(Database $db): void
  {
    $db->exec('ALTER TABLE `vacancies` DROP INDEX `ft_vacancies_search`, DROP COLUMN `search_document`');
  }
};
