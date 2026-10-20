<?php

use App\Infrastructure\Persistence\Database;
use App\Infrastructure\Persistence\Migrations\Migration;

/**
 * Seed: demo data for the vacancy lifecycle.
 * - "Analista" and "Programador FullStack" hire 2 people each;
 * - "Analista de Sistemas Sênior" is closed as filled (Beatriz was approved for its only position);
 * - a TechNova draft that candidates cannot see yet.
 *
 * down() restores the previous values and deletes the draft.
 */
return new class extends Migration {
  private const TWO_POSITIONS = ['a0000001-0000-4000-8000-000000000001', 'a0000002-0000-4000-8000-000000000002'];
  private const FILLED = 'a0000015-0000-4000-8000-000000000015';
  private const FILLED_AT = '2026-10-16 18:20:00';
  private const DRAFT = 'a0000016-0000-4000-8000-000000000016';
  private const TECHNOVA = '11111111-1111-4111-8111-111111111111';

  public function up(Database $db): void
  {
    $db->run('UPDATE `vacancies` SET `positions` = 2 WHERE `id` IN (?, ?)', self::TWO_POSITIONS);
    $db->run(
      "UPDATE `vacancies` SET `status` = 'encerrada', `close_reason` = 'preenchida', `closed_at` = ? WHERE `id` = ?",
      [self::FILLED_AT, self::FILLED]
    );

    $db->run(
      'INSERT INTO `vacancies` (`id`, `company_id`, `title`, `description`, `area`, `seniority`, `work_mode`, `status`, `positions`, `created_at`)'
      . " VALUES (?, ?, ?, ?, 'backend', 'senior', 'remoto', 'rascunho', 1, ?)",
      [
        self::DRAFT,
        self::TECHNOVA,
        'Tech Lead PHP',
        'Liderança técnica do time de pagamentos: arquitetura, revisão de código e mentoria. (Rascunho em revisão pelo RH.)',
        '2026-10-19 10:00:00',
      ]
    );
    foreach (['php' => 'PHP', 'arquitetura' => 'Arquitetura', 'ddd' => 'DDD', 'lideranca' => 'Liderança'] as $slug => $label) {
      $db->run('INSERT INTO `vacancy_topics` (`vacancy_id`, `slug`, `label`) VALUES (?, ?, ?)', [self::DRAFT, $slug, $label]);
    }
  }

  public function down(Database $db): void
  {
    $db->run('DELETE FROM `vacancies` WHERE `id` = ?', [self::DRAFT]);
    $db->run(
      "UPDATE `vacancies` SET `status` = 'aberta', `close_reason` = NULL, `closed_at` = NULL WHERE `id` = ?",
      [self::FILLED]
    );
    $db->run('UPDATE `vacancies` SET `positions` = 1 WHERE `id` IN (?, ?)', self::TWO_POSITIONS);
  }
};
