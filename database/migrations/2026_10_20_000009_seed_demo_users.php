<?php

use App\Infrastructure\Persistence\Database;
use App\Infrastructure\Persistence\Migrations\Migration;

/**
 * Seed: demo users (a candidate with a profile and a recruiter at TechNova).
 *
 * down() deletes them by e-mail; profiles, topics and applications cascade.
 */
return new class extends Migration {
  /** @var list<array{email:string,name:string,password:string,role:string,company:string|null}> */
  private const USERS = [
    [
      'email' => 'aureo@live.com',
      'name' => 'Aureo Bueno',
      'password' => '$2y$10$IZB3TIpYA0aPP4U5Adls0ed3Idelm548Hko92MIFBTkYq2CZHpGnW',
      'role' => 'usuario',
      'company' => null,
    ],
    [
      'email' => 'andre@live.com',
      'name' => 'Andre Pinheiro',
      'password' => '$2y$10$VPQ9hfi8oC/prTc7H2WNMe.yMxtBAp.tPT0OqclNFu4B4/fq2bB1q',
      'role' => 'gestor',
      'company' => '11111111-1111-4111-8111-111111111111',
    ],
  ];

  private const CANDIDATE_EMAIL = 'aureo@live.com';

  /** @var array<string,string> slug => label */
  private const CANDIDATE_TOPICS = [
    'php' => 'PHP',
    'poo' => 'POO',
    'mysql' => 'MySQL',
    'javascript' => 'JavaScript',
    'docker' => 'Docker',
  ];

  public function up(Database $db): void
  {
    foreach (self::USERS as $user) {
      $db->run(
        'INSERT INTO `users` (`id`, `name`, `email`, `password`, `role_id`, `company_id`)'
        . ' VALUES (UUID(), ?, ?, ?, (SELECT `id` FROM `roles` WHERE `name` = ?), ?)',
        [$user['name'], $user['email'], $user['password'], $user['role'], $user['company']]
      );
    }

    $candidateId = (string) $db->fetchValue('SELECT `id` FROM `users` WHERE `email` = ?', [self::CANDIDATE_EMAIL]);

    $db->run(
      'INSERT INTO `candidate_profiles` (`user_id`, `headline`, `bio`, `area`, `seniority`, `work_mode`, `linkedin_url`, `github_url`)'
      . ' VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
      [
        $candidateId,
        'Desenvolvedor Backend PHP & Arquitetura Limpa',
        'Experiência com PHP Orientado a Objetos, DDD, MySQL, APIs REST e testes automatizados com PHPUnit.',
        'backend',
        'pleno',
        'remoto',
        'https://linkedin.com/in/aureo-bueno',
        'https://github.com/Aureo-Bueno',
      ]
    );

    foreach (self::CANDIDATE_TOPICS as $slug => $label) {
      $db->run('INSERT INTO `candidate_topics` (`user_id`, `slug`, `label`) VALUES (?, ?, ?)', [$candidateId, $slug, $label]);
    }
  }

  public function down(Database $db): void
  {
    $emails = array_column(self::USERS, 'email');
    $db->run('DELETE FROM `users` WHERE `email` IN (' . implode(', ', array_fill(0, count($emails), '?')) . ')', $emails);
  }
};
