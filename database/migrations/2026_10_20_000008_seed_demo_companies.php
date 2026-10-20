<?php

use App\Infrastructure\Persistence\Database;
use App\Infrastructure\Persistence\Migrations\Migration;

/**
 * Seed: demo companies.
 *
 * down() deletes them; their vacancies follow via ON DELETE CASCADE and
 * recruiters keep their account with company_id set to NULL.
 */
return new class extends Migration {
  /** @var list<array{0:string,1:string,2:string,3:string,4:string}> id, name, sector, website, description */
  private const COMPANIES = [
    ['11111111-1111-4111-8111-111111111111', 'TechNova Sistemas', 'SaaS & Cloud', 'https://technova.example.com', 'Plataforma SaaS de automação financeira e engenharia distribuída.'],
    ['22222222-2222-4222-8222-222222222222', 'CloudBridge Labs', 'Consultoria & Dev', 'https://cloudbridge.example.com', 'Squads ágeis de desenvolvimento web, mobile e arquitetura em nuvem.'],
    ['33333333-3333-4333-8333-333333333333', 'DataPulse Analytics', 'Dados & IA', 'https://datapulse.example.com', 'Soluções de engenharia de dados, BI e inteligência artificial aplicada.'],
  ];

  public function up(Database $db): void
  {
    foreach (self::COMPANIES as $company) {
      $db->run(
        'INSERT INTO `companies` (`id`, `name`, `sector`, `website`, `description`) VALUES (?, ?, ?, ?, ?)',
        $company
      );
    }
  }

  public function down(Database $db): void
  {
    $ids = array_column(self::COMPANIES, 0);
    $db->run('DELETE FROM `companies` WHERE `id` IN (' . implode(', ', array_fill(0, count($ids), '?')) . ')', $ids);
  }
};
