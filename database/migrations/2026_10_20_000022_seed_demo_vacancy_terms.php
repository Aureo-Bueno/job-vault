<?php

use App\Domain\Shared\Slug;
use App\Domain\Vacancy\VacancyDeadline;
use App\Infrastructure\Persistence\Database;
use App\Infrastructure\Persistence\Migrations\Migration;

/**
 * Seed: contract, salary, location, benefits and deadlines for the demo vacancies.
 *
 * Deadlines are relative to the day the migration runs, so the demo always has
 * upcoming deadlines. "Programador C#" is still open with a deadline that already
 * passed: the scheduler closes it on its first run.
 *
 * down() resets the columns to the defaults and deletes the benefits.
 */
return new class extends Migration {
  private const CURITIBA = ['Curitiba', 'PR'];
  private const SAO_PAULO = ['São Paulo', 'SP'];
  private const BELO_HORIZONTE = ['Belo Horizonte', 'MG'];

  /**
   * suffix => [contract, salary_min, salary_max, [city, state]|null, benefits, deadline in days from today|null]
   */
  private const TERMS = [
    '01' => ['clt', 7000, 10000, null, ['Plano de saúde', 'Vale-refeição', 'Auxílio home office'], 40],
    '02' => ['clt', 6000, 8500, self::CURITIBA, ['Plano de saúde', 'Vale-transporte', 'Vale-refeição'], null],
    '03' => ['pj', 9000, 12000, null, ['Auxílio home office'], -2],
    '04' => ['clt', 8000, null, self::SAO_PAULO, ['Plano de saúde', 'Estacionamento'], null],
    '05' => ['pj', 14000, 18000, null, ['Day off no aniversário', 'Auxílio educação'], 25],
    '06' => ['clt', null, 4500, null, ['Vale-refeição'], null],
    '07' => ['estagio', 2200, 2200, self::CURITIBA, ['Vale-transporte', 'Auxílio educação'], 10],
    '08' => ['clt', null, null, null, [], null],
    '09' => ['clt', 13000, 17000, null, ['Plano de saúde', 'Plano odontológico', 'PLR', 'Auxílio home office'], null],
    '10' => ['clt', 7500, 9500, self::BELO_HORIZONTE, ['Plano de saúde', 'Vale-alimentação', 'Estacionamento'], 30],
    '11' => ['freelancer', null, null, null, ['Auxílio home office'], null],
    '12' => ['clt', 4000, 5500, null, ['Plano de saúde', 'Gympass', 'Auxílio educação'], 15],
    '13' => ['temporario', 8000, 8000, null, [], 11],
    '14' => ['clt', 3500, 4800, self::CURITIBA, ['Vale-refeição', 'Vale-transporte', 'Plano de saúde'], null],
    '15' => ['clt', 12000, 15000, null, ['Plano de saúde', 'PLR'], null],
    '16' => ['clt', 16000, 20000, null, ['Plano de saúde', 'PLR', 'Stock options'], 45],
  ];

  public function up(Database $db): void
  {
    $today = new DateTimeImmutable(VacancyDeadline::today());

    foreach (self::TERMS as $suffix => [$contract, $min, $max, $location, $benefits, $deadlineDays]) {
      $id = self::id($suffix);
      $db->run(
        'UPDATE `vacancies` SET `contract_type` = ?, `salary_min` = ?, `salary_max` = ?, `city` = ?, `state` = ?, `closes_at` = ? WHERE `id` = ?',
        [
          $contract,
          $min,
          $max,
          $location[0] ?? null,
          $location[1] ?? null,
          $deadlineDays === null ? null : $today->modify(sprintf('%+d days', $deadlineDays))->format('Y-m-d'),
          $id,
        ]
      );

      foreach ($benefits as $label) {
        $db->run('INSERT INTO `vacancy_benefits` (`vacancy_id`, `slug`, `label`) VALUES (?, ?, ?)', [$id, Slug::of($label), $label]);
      }
    }
  }

  public function down(Database $db): void
  {
    $ids = array_map(self::id(...), array_map('strval', array_keys(self::TERMS)));
    $placeholders = implode(', ', array_fill(0, count($ids), '?'));

    $db->run('DELETE FROM `vacancy_benefits` WHERE `vacancy_id` IN (' . $placeholders . ')', $ids);
    $db->run(
      "UPDATE `vacancies` SET `contract_type` = 'clt', `salary_min` = NULL, `salary_max` = NULL, `city` = NULL, `state` = NULL, `closes_at` = NULL"
      . ' WHERE `id` IN (' . $placeholders . ')',
      $ids
    );
  }

  private static function id(string $suffix): string
  {
    $suffix = str_pad($suffix, 2, '0', STR_PAD_LEFT);
    return sprintf('a00000%s-0000-4000-8000-0000000000%s', $suffix, $suffix);
  }
};
