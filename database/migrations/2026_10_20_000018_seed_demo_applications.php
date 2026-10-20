<?php

use App\Infrastructure\Persistence\Database;
use App\Infrastructure\Persistence\Migrations\Migration;

/**
 * Seed: two more demo candidates and applications spread over the TechNova pipeline,
 * with history and notes written by the TechNova recruiter (andre@live.com).
 *
 * down() deletes these applications (events and notes cascade) and the two extra candidates.
 */
return new class extends Migration {
  private const RECRUITER = 'andre@live.com';
  private const PASSWORD_HASH = '$2y$10$IZB3TIpYA0aPP4U5Adls0ed3Idelm548Hko92MIFBTkYq2CZHpGnW';

  /** @var list<array{email:string,name:string,headline:string,bio:string,area:string,seniority:string,work_mode:string,topics:array<string,string>}> */
  private const CANDIDATES = [
    [
      'email' => 'beatriz.lima@example.com',
      'name' => 'Beatriz Lima',
      'headline' => 'Engenheira de Software Backend · PHP & DDD',
      'bio' => 'Seis anos construindo APIs em PHP, modelagem de domínio, MySQL e testes automatizados.',
      'area' => 'backend',
      'seniority' => 'senior',
      'work_mode' => 'remoto',
      'topics' => ['php' => 'PHP', 'poo' => 'POO', 'ddd' => 'DDD', 'mysql' => 'MySQL', 'phpunit' => 'PHPUnit'],
    ],
    [
      'email' => 'carlos.souza@example.com',
      'name' => 'Carlos Souza',
      'headline' => 'Desenvolvedor Fullstack JavaScript',
      'bio' => 'Interfaces em React e serviços Node.js; começando com PHP em projetos legados.',
      'area' => 'fullstack',
      'seniority' => 'pleno',
      'work_mode' => 'hibrido',
      'topics' => ['javascript' => 'JavaScript', 'nodejs' => 'Node.js', 'react' => 'React', 'php' => 'PHP'],
    ],
  ];

  /**
   * id => [candidate e-mail, vacancy id, final status, feedback, [[to, actor(candidate|recruiter), at]...], notes]
   *
   * @var array<string,array{0:string,1:string,2:string,3:string|null,4:list<array{0:string,1:string,2:string}>,5:list<string>}>
   */
  private const APPLICATIONS = [
    'b0000001-0000-4000-8000-000000000001' => ['aureo@live.com', 'a0000002-0000-4000-8000-000000000002', 'entrevista', null, [
      ['recebida', 'candidate', '2026-10-10 09:12:00'],
      ['em_analise', 'recruiter', '2026-10-12 14:30:00'],
      ['entrevista', 'recruiter', '2026-10-15 11:05:00'],
    ], ['Boa base em PHP OO e MySQL. Entrevista técnica marcada para quinta às 15h.']],
    'b0000002-0000-4000-8000-000000000002' => ['beatriz.lima@example.com', 'a0000002-0000-4000-8000-000000000002', 'em_analise', null, [
      ['recebida', 'candidate', '2026-10-11 16:40:00'],
      ['em_analise', 'recruiter', '2026-10-13 10:00:00'],
    ], ['Perfil acima do nível da vaga; confirmar pretensão salarial.']],
    'b0000003-0000-4000-8000-000000000003' => ['carlos.souza@example.com', 'a0000002-0000-4000-8000-000000000002', 'recebida', null, [
      ['recebida', 'candidate', '2026-10-18 08:25:00'],
    ], []],
    'b0000004-0000-4000-8000-000000000004' => ['aureo@live.com', 'a0000015-0000-4000-8000-000000000015', 'reprovada', 'Neste momento buscamos alguém com experiência sênior em DDD. Seu perfil continua no nosso banco para as próximas vagas.', [
      ['recebida', 'candidate', '2026-10-05 19:00:00'],
      ['em_analise', 'recruiter', '2026-10-06 09:30:00'],
      ['reprovada', 'recruiter', '2026-10-08 17:45:00'],
    ], []],
    'b0000005-0000-4000-8000-000000000005' => ['beatriz.lima@example.com', 'a0000015-0000-4000-8000-000000000015', 'aprovada', null, [
      ['recebida', 'candidate', '2026-10-04 12:00:00'],
      ['em_analise', 'recruiter', '2026-10-06 09:35:00'],
      ['entrevista', 'recruiter', '2026-10-09 10:00:00'],
      ['aprovada', 'recruiter', '2026-10-16 18:20:00'],
    ], ['Excelente entrevista técnica e de cultura.', 'Proposta aceita; início em 03/11.']],
    'b0000006-0000-4000-8000-000000000006' => ['carlos.souza@example.com', 'a0000001-0000-4000-8000-000000000001', 'em_analise', null, [
      ['recebida', 'candidate', '2026-10-14 13:10:00'],
      ['em_analise', 'recruiter', '2026-10-17 09:00:00'],
    ], []],
  ];

  public function up(Database $db): void
  {
    foreach (self::CANDIDATES as $candidate) {
      $db->run(
        'INSERT INTO `users` (`id`, `name`, `email`, `password`, `role_id`) VALUES (UUID(), ?, ?, ?, (SELECT `id` FROM `roles` WHERE `name` = ?))',
        [$candidate['name'], $candidate['email'], self::PASSWORD_HASH, 'usuario']
      );
      $userId = $this->userId($db, $candidate['email']);

      $db->run(
        'INSERT INTO `candidate_profiles` (`user_id`, `headline`, `bio`, `area`, `seniority`, `work_mode`) VALUES (?, ?, ?, ?, ?, ?)',
        [$userId, $candidate['headline'], $candidate['bio'], $candidate['area'], $candidate['seniority'], $candidate['work_mode']]
      );
      foreach ($candidate['topics'] as $slug => $label) {
        $db->run('INSERT INTO `candidate_topics` (`user_id`, `slug`, `label`) VALUES (?, ?, ?)', [$userId, $slug, $label]);
      }
    }

    $recruiterId = $this->userId($db, self::RECRUITER);

    foreach (self::APPLICATIONS as $id => [$email, $vacancyId, $status, $feedback, $events, $notes]) {
      $candidateId = $this->userId($db, $email);
      $submittedAt = $events[0][2];
      $changedAt = $events[count($events) - 1][2];

      $db->run(
        'INSERT INTO `applications` (`id`, `user_id`, `vacancy_id`, `status`, `status_changed_at`, `candidate_feedback`, `created_at`)'
        . ' VALUES (?, ?, ?, ?, ?, ?, ?)',
        [$id, $candidateId, $vacancyId, $status, $changedAt, $feedback, $submittedAt]
      );

      $from = null;
      foreach ($events as [$to, $actor, $at]) {
        $db->run(
          'INSERT INTO `application_events` (`application_id`, `from_status`, `to_status`, `actor_id`, `reason`, `created_at`)'
          . ' VALUES (?, ?, ?, ?, ?, ?)',
          [$id, $from, $to, $actor === 'candidate' ? $candidateId : $recruiterId, $to === 'reprovada' ? $feedback : null, $at]
        );
        $from = $to;
      }

      foreach ($notes as $index => $body) {
        $db->run(
          'INSERT INTO `application_notes` (`application_id`, `author_id`, `body`, `created_at`) VALUES (?, ?, ?, ?)',
          [$id, $recruiterId, $body, date('Y-m-d H:i:s', strtotime($changedAt) + 60 * ($index + 1))]
        );
      }
    }
  }

  public function down(Database $db): void
  {
    $ids = array_keys(self::APPLICATIONS);
    $db->run('DELETE FROM `applications` WHERE `id` IN (' . implode(', ', array_fill(0, count($ids), '?')) . ')', $ids);

    $emails = array_column(self::CANDIDATES, 'email');
    $db->run('DELETE FROM `users` WHERE `email` IN (' . implode(', ', array_fill(0, count($emails), '?')) . ')', $emails);
  }

  private function userId(Database $db, string $email): string
  {
    $id = $db->fetchValue('SELECT `id` FROM `users` WHERE `email` = ?', [$email]);
    if ($id === null) {
      throw new RuntimeException(sprintf('Demo user "%s" not found: run the earlier seed migrations first.', $email));
    }

    return (string) $id;
  }
};
