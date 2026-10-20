<?php

use App\Infrastructure\Persistence\Database;
use App\Infrastructure\Persistence\Migrations\Migration;

/**
 * Seed: demo vacancies with topic tags.
 *
 * down() deletes them by id; topics and applications cascade.
 */
return new class extends Migration {
  private const TECHNOVA = '11111111-1111-4111-8111-111111111111';
  private const CLOUDBRIDGE = '22222222-2222-4222-8222-222222222222';
  private const DATAPULSE = '33333333-3333-4333-8333-333333333333';

  /**
   * id => [company, title, description, area, seniority, work_mode, is_active, created_at, topics(slug => label)]
   *
   * @var array<string,array{0:string,1:string,2:string,3:string,4:string,5:string,6:string,7:string,8:array<string,string>}>
   */
  private const VACANCIES = [
    'a0000001-0000-4000-8000-000000000001' => [self::TECHNOVA, 'Programador FullStack', 'Desenvolvimento e manutenção de aplicações web escaláveis com Backend PHP, JavaScript e Node.js.', 'fullstack', 'pleno', 'remoto', 's', '2020-11-11 20:51:05', ['php' => 'PHP', 'javascript' => 'JavaScript', 'nodejs' => 'Node.js']],
    'a0000002-0000-4000-8000-000000000002' => [self::TECHNOVA, 'Analista', 'Atuação com arquitetura Backend, PHP Orientado a Objetos, modelagem MySQL e integrações JS.', 'backend', 'pleno', 'hibrido', 's', '2020-11-11 21:42:45', ['php' => 'PHP', 'poo' => 'POO', 'mysql' => 'MySQL', 'javascript' => 'JavaScript']],
    'a0000003-0000-4000-8000-000000000003' => [self::CLOUDBRIDGE, 'Programador C#', 'Desenvolvimento de aplicativos corporativos e soluções Mobile com ecossistema .NET / C#.', 'mobile', 'pleno', 'remoto', 's', '2020-11-13 17:18:06', ['c#' => 'C#', 'dotnet' => '.NET', 'mobile' => 'Mobile']],
    'a0000004-0000-4000-8000-000000000004' => [self::CLOUDBRIDGE, 'Programador C++', 'Programação Desktop de alto desempenho e manutenção de módulos nativos.', 'backend', 'pleno', 'presencial', 'n', '2020-11-13 17:19:04', ['c++' => 'C++', 'desktop' => 'Desktop']],
    'a0000005-0000-4000-8000-000000000005' => [self::CLOUDBRIDGE, 'Engenheiro de Software', 'Construção de produtos digitais ponta a ponta com ReactJS, Node.js e boas práticas de testes.', 'fullstack', 'senior', 'remoto', 's', '2020-11-13 17:19:58', ['react' => 'React', 'nodejs' => 'Node.js', 'typescript' => 'TypeScript']],
    'a0000006-0000-4000-8000-000000000006' => [self::CLOUDBRIDGE, 'Programador Frontend', 'Criação de interfaces responsivas e acessíveis com HTML5, CSS3 e JavaScript moderno.', 'frontend', 'junior', 'remoto', 'n', '2020-11-13 17:20:40', ['html5' => 'HTML5', 'css3' => 'CSS3', 'javascript' => 'JavaScript']],
    'a0000007-0000-4000-8000-000000000007' => [self::TECHNOVA, 'Analista Junior', 'Apoio no levantamento de requisitos, testes e manutenção de sistemas web. Desejável cursando Superior.', 'backend', 'junior', 'hibrido', 's', '2020-11-13 17:21:50', ['sql' => 'SQL', 'requisitos' => 'Requisitos']],
    'a0000008-0000-4000-8000-000000000008' => [self::TECHNOVA, 'Programador Pleno', 'Evolução de serviços Backend, APIs REST e bancos relacionais.', 'backend', 'pleno', 'remoto', 'n', '2020-11-13 17:22:14', ['php' => 'PHP', 'api-rest' => 'API REST']],
    'a0000009-0000-4000-8000-000000000009' => [self::TECHNOVA, 'Programador Sênior', 'Liderança técnica em projetos Fullstack, revisão de código e desenho de arquitetura.', 'fullstack', 'senior', 'remoto', 's', '2020-11-13 17:23:39', ['php' => 'PHP', 'arquitetura' => 'Arquitetura', 'docker' => 'Docker']],
    'a0000010-0000-4000-8000-000000000010' => [self::DATAPULSE, 'Engenheiro da Computação', 'Desenvolvimento de sistemas embarcados e integração IoT com C++ e Arduino.', 'outros', 'pleno', 'presencial', 's', '2020-11-13 18:02:20', ['c++' => 'C++', 'arduino' => 'Arduino']],
    'a0000011-0000-4000-8000-000000000011' => [self::TECHNOVA, 'Backend', 'Desenvolvimento de microsserviços e APIs com PHP, C#, JavaScript e Node.js.', 'backend', 'pleno', 'remoto', 's', '2020-11-13 20:31:54', ['php' => 'PHP', 'c#' => 'C#', 'javascript' => 'JavaScript', 'nodejs' => 'Node.js']],
    'a0000012-0000-4000-8000-000000000012' => [self::DATAPULSE, 'DataScience Junior', 'Análise exploratória, pipelines de dados e modelos preditivos em Data Science.', 'dados', 'junior', 'remoto', 's', '2020-11-13 20:33:31', ['python' => 'Python', 'sql' => 'SQL', 'machine-learning' => 'Machine Learning']],
    'a0000013-0000-4000-8000-000000000013' => [self::CLOUDBRIDGE, 'Programador VueJS', 'Desenvolvimento de SPAs modernas e componentizadas utilizando Vue.js e TypeScript.', 'frontend', 'pleno', 'remoto', 's', '2020-11-13 20:33:49', ['vuejs' => 'Vue.js', 'javascript' => 'JavaScript']],
    'a0000014-0000-4000-8000-000000000014' => [self::TECHNOVA, 'Analista de sistemas Junior', 'Construção de módulos CRUD, manutenção evolutiva com PHP e consultas MySQL.', 'backend', 'junior', 'hibrido', 's', '2020-11-13 20:34:48', ['php' => 'PHP', 'mysql' => 'MySQL']],
    'a0000015-0000-4000-8000-000000000015' => [self::TECHNOVA, 'Analista de Sistemas Sênior', 'Arquitetura limpa, testes automatizados e domínio rico em PHP Orientado a Objetos.', 'backend', 'senior', 'remoto', 's', '2020-11-13 20:35:13', ['php' => 'PHP', 'poo' => 'POO', 'ddd' => 'DDD', 'phpunit' => 'PHPUnit']],
  ];

  public function up(Database $db): void
  {
    foreach (self::VACANCIES as $id => [$company, $title, $description, $area, $seniority, $workMode, $isActive, $createdAt, $topics]) {
      $db->run(
        'INSERT INTO `vacancies` (`id`, `company_id`, `title`, `description`, `area`, `seniority`, `work_mode`, `is_active`, `created_at`)'
        . ' VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
        [$id, $company, $title, $description, $area, $seniority, $workMode, $isActive, $createdAt]
      );

      foreach ($topics as $slug => $label) {
        $db->run('INSERT INTO `vacancy_topics` (`vacancy_id`, `slug`, `label`) VALUES (?, ?, ?)', [$id, $slug, $label]);
      }
    }
  }

  public function down(Database $db): void
  {
    $ids = array_keys(self::VACANCIES);
    $db->run('DELETE FROM `vacancies` WHERE `id` IN (' . implode(', ', array_fill(0, count($ids), '?')) . ')', $ids);
  }
};
