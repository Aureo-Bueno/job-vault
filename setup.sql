-- ============================================
-- COMPLETE DATABASE SETUP
-- JobVault - Job Vacancy Management System
-- ============================================

-- ============================================
-- 1. CREATE DATABASE
-- ============================================
CREATE DATABASE IF NOT EXISTS myapp_db
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_0900_ai_ci;
USE myapp_db;
SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `role_permissions`;
DROP TABLE IF EXISTS `applications`;
DROP TABLE IF EXISTS `vacancy_topics`;
DROP TABLE IF EXISTS `candidate_topics`;
DROP TABLE IF EXISTS `candidate_profiles`;
DROP TABLE IF EXISTS `vacancies`;
DROP TABLE IF EXISTS `users`;
DROP TABLE IF EXISTS `companies`;
DROP TABLE IF EXISTS `permissions`;
DROP TABLE IF EXISTS `roles`;

-- ============================================
-- 2. CREATE ROLES TABLE
-- ============================================
CREATE TABLE `roles` (
  `id` char(36) NOT NULL DEFAULT (UUID()),
  `name` varchar(100) NOT NULL UNIQUE,
  `description` varchar(255),
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ============================================
-- 3. CREATE PERMISSIONS TABLE
-- ============================================
CREATE TABLE `permissions` (
  `id` char(36) NOT NULL DEFAULT (UUID()),
  `name` varchar(100) NOT NULL UNIQUE,
  `description` varchar(255),
  `module` varchar(50),
  `action` varchar(50),
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ============================================
-- 4. CREATE ROLE_PERMISSIONS TABLE
-- ============================================
CREATE TABLE `role_permissions` (
  `id` char(36) NOT NULL DEFAULT (UUID()),
  `role_id` char(36) NOT NULL,
  `permission_id` char(36) NOT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_role_permission` (`role_id`, `permission_id`),
  FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ============================================
-- 5. CREATE COMPANIES TABLE (1 company : N recruiters)
-- ============================================
CREATE TABLE `companies` (
  `id` char(36) NOT NULL DEFAULT (UUID()),
  `name` varchar(150) NOT NULL,
  `sector` varchar(100) NOT NULL DEFAULT 'Tecnologia',
  `website` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_company_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ============================================
-- 6. CREATE USERS TABLE (with role_id and company_id)
-- ============================================
CREATE TABLE `users` (
  `id` char(36) NOT NULL DEFAULT (UUID()),
  `name` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `role_id` char(36) DEFAULT NULL,
  `company_id` char(36) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  CONSTRAINT `fk_users_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_users_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ============================================
-- 7. CREATE CANDIDATE PROFILES & TOPICS TABLES
-- ============================================
CREATE TABLE `candidate_profiles` (
  `user_id` char(36) NOT NULL,
  `headline` varchar(160) NOT NULL DEFAULT '',
  `bio` text DEFAULT NULL,
  `area` varchar(40) NOT NULL DEFAULT 'backend',
  `seniority` varchar(40) NOT NULL DEFAULT 'pleno',
  `work_mode` varchar(40) NOT NULL DEFAULT 'remoto',
  `linkedin_url` varchar(255) DEFAULT NULL,
  `github_url` varchar(255) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`),
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `candidate_topics` (
  `user_id` char(36) NOT NULL,
  `slug` varchar(60) NOT NULL,
  `label` varchar(60) NOT NULL,
  PRIMARY KEY (`user_id`, `slug`),
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ============================================
-- 8. CREATE VACANCIES & VACANCY TOPICS TABLES
-- ============================================
CREATE TABLE `vacancies` (
  `id` char(36) NOT NULL DEFAULT (UUID()),
  `company_id` char(36) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `area` varchar(40) NOT NULL DEFAULT 'backend',
  `seniority` varchar(40) NOT NULL DEFAULT 'pleno',
  `work_mode` varchar(40) NOT NULL DEFAULT 'remoto',
  `is_active` enum('s','n') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_vacancies_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `vacancy_topics` (
  `vacancy_id` char(36) NOT NULL,
  `slug` varchar(60) NOT NULL,
  `label` varchar(60) NOT NULL,
  PRIMARY KEY (`vacancy_id`, `slug`),
  FOREIGN KEY (`vacancy_id`) REFERENCES `vacancies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ============================================
-- 9. CREATE APPLICATIONS TABLE
-- ============================================
CREATE TABLE `applications` (
  `id` char(36) NOT NULL DEFAULT (UUID()),
  `user_id` char(36) NOT NULL,
  `vacancy_id` char(36) NOT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user_vacancy` (`user_id`, `vacancy_id`),
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`vacancy_id`) REFERENCES `vacancies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================
-- 10. INSERT COMPANIES
-- ============================================
INSERT INTO `companies` (`id`, `name`, `sector`, `website`, `description`) VALUES
('11111111-1111-4111-8111-111111111111', 'TechNova Sistemas', 'SaaS & Cloud', 'https://technova.example.com', 'Plataforma SaaS de automação financeira e engenharia distribuída.'),
('22222222-2222-4222-8222-222222222222', 'CloudBridge Labs', 'Consultoria & Dev', 'https://cloudbridge.example.com', 'Squads ágeis de desenvolvimento web, mobile e arquitetura em nuvem.'),
('33333333-3333-4333-8333-333333333333', 'DataPulse Analytics', 'Dados & IA', 'https://datapulse.example.com', 'Soluções de engenharia de dados, BI e inteligência artificial aplicada.');

-- ============================================
-- 11. INSERT USERS DATA
-- ============================================
INSERT INTO `users` (`id`, `name`, `email`, `password`, `role_id`, `company_id`) VALUES
(UUID(), 'Aureo Bueno', 'aureo@live.com', '$2y$10$IZB3TIpYA0aPP4U5Adls0ed3Idelm548Hko92MIFBTkYq2CZHpGnW', NULL, NULL),
(UUID(), 'Andre Pinheiro', 'andre@live.com', '$2y$10$VPQ9hfi8oC/prTc7H2WNMe.yMxtBAp.tPT0OqclNFu4B4/fq2bB1q', NULL, '11111111-1111-4111-8111-111111111111');

-- ============================================
-- 12. INSERT VACANCIES DATA
-- ============================================
INSERT INTO `vacancies` (`id`, `company_id`, `title`, `description`, `area`, `seniority`, `work_mode`, `is_active`, `created_at`) VALUES
('a0000001-0000-4000-8000-000000000001', '11111111-1111-4111-8111-111111111111', 'Programador FullStack', 'Desenvolvimento e manutenção de aplicações web escaláveis com Backend PHP, JavaScript e Node.js.', 'fullstack', 'pleno', 'remoto', 's', '2020-11-11 20:51:05'),
('a0000002-0000-4000-8000-000000000002', '11111111-1111-4111-8111-111111111111', 'Analista', 'Atuação com arquitetura Backend, PHP Orientado a Objetos, modelagem MySQL e integrações JS.', 'backend', 'pleno', 'hibrido', 's', '2020-11-11 21:42:45'),
('a0000003-0000-4000-8000-000000000003', '22222222-2222-4222-8222-222222222222', 'Programador C#', 'Desenvolvimento de aplicativos corporativos e soluções Mobile com ecossistema .NET / C#.', 'mobile', 'pleno', 'remoto', 's', '2020-11-13 17:18:06'),
('a0000004-0000-4000-8000-000000000004', '22222222-2222-4222-8222-222222222222', 'Programador C++', 'Programação Desktop de alto desempenho e manutenção de módulos nativos.', 'backend', 'pleno', 'presencial', 'n', '2020-11-13 17:19:04'),
('a0000005-0000-4000-8000-000000000005', '22222222-2222-4222-8222-222222222222', 'Engenheiro de Software', 'Construção de produtos digitais ponta a ponta com ReactJS, Node.js e boas práticas de testes.', 'fullstack', 'senior', 'remoto', 's', '2020-11-13 17:19:58'),
('a0000006-0000-4000-8000-000000000006', '22222222-2222-4222-8222-222222222222', 'Programador Frontend', 'Criação de interfaces responsivas e acessíveis com HTML5, CSS3 e JavaScript moderno.', 'frontend', 'junior', 'remoto', 'n', '2020-11-13 17:20:40'),
('a0000007-0000-4000-8000-000000000007', '11111111-1111-4111-8111-111111111111', 'Analista Junior', 'Apoio no levantamento de requisitos, testes e manutenção de sistemas web. Desejável cursando Superior.', 'backend', 'junior', 'hibrido', 's', '2020-11-13 17:21:50'),
('a0000008-0000-4000-8000-000000000008', '11111111-1111-4111-8111-111111111111', 'Programador Pleno', 'Evolução de serviços Backend, APIs REST e bancos relacionais.', 'backend', 'pleno', 'remoto', 'n', '2020-11-13 17:22:14'),
('a0000009-0000-4000-8000-000000000009', '11111111-1111-4111-8111-111111111111', 'Programador Sênior', 'Liderança técnica em projetos Fullstack, revisão de código e desenho de arquitetura.', 'fullstack', 'senior', 'remoto', 's', '2020-11-13 17:23:39'),
('a0000010-0000-4000-8000-000000000010', '33333333-3333-4333-8333-333333333333', 'Engenheiro da Computação', 'Desenvolvimento de sistemas embarcados e integração IoT com C++ e Arduino.', 'outros', 'pleno', 'presencial', 's', '2020-11-13 18:02:20'),
('a0000011-0000-4000-8000-000000000011', '11111111-1111-4111-8111-111111111111', 'Backend', 'Desenvolvimento de microsserviços e APIs com PHP, C#, JavaScript e Node.js.', 'backend', 'pleno', 'remoto', 's', '2020-11-13 20:31:54'),
('a0000012-0000-4000-8000-000000000012', '33333333-3333-4333-8333-333333333333', 'DataScience Junior', 'Análise exploratória, pipelines de dados e modelos preditivos em Data Science.', 'dados', 'junior', 'remoto', 's', '2020-11-13 20:33:31'),
('a0000013-0000-4000-8000-000000000013', '22222222-2222-4222-8222-222222222222', 'Programador VueJS', 'Desenvolvimento de SPAs modernas e componentizadas utilizando Vue.js e TypeScript.', 'frontend', 'pleno', 'remoto', 's', '2020-11-13 20:33:49'),
('a0000014-0000-4000-8000-000000000014', '11111111-1111-4111-8111-111111111111', 'Analista de sistemas Junior', 'Construção de módulos CRUD, manutenção evolutiva com PHP e consultas MySQL.', 'backend', 'junior', 'hibrido', 's', '2020-11-13 20:34:48'),
('a0000015-0000-4000-8000-000000000015', '11111111-1111-4111-8111-111111111111', 'Analista de Sistemas Sênior', 'Arquitetura limpa, testes automatizados e domínio rico em PHP Orientado a Objetos.', 'backend', 'senior', 'remoto', 's', '2020-11-13 20:35:13');

INSERT INTO `vacancy_topics` (`vacancy_id`, `slug`, `label`) VALUES
('a0000001-0000-4000-8000-000000000001', 'php', 'PHP'),
('a0000001-0000-4000-8000-000000000001', 'javascript', 'JavaScript'),
('a0000001-0000-4000-8000-000000000001', 'nodejs', 'Node.js'),
('a0000002-0000-4000-8000-000000000002', 'php', 'PHP'),
('a0000002-0000-4000-8000-000000000002', 'poo', 'POO'),
('a0000002-0000-4000-8000-000000000002', 'mysql', 'MySQL'),
('a0000002-0000-4000-8000-000000000002', 'javascript', 'JavaScript'),
('a0000003-0000-4000-8000-000000000003', 'c#', 'C#'),
('a0000003-0000-4000-8000-000000000003', 'dotnet', '.NET'),
('a0000003-0000-4000-8000-000000000003', 'mobile', 'Mobile'),
('a0000004-0000-4000-8000-000000000004', 'c++', 'C++'),
('a0000004-0000-4000-8000-000000000004', 'desktop', 'Desktop'),
('a0000005-0000-4000-8000-000000000005', 'react', 'React'),
('a0000005-0000-4000-8000-000000000005', 'nodejs', 'Node.js'),
('a0000005-0000-4000-8000-000000000005', 'typescript', 'TypeScript'),
('a0000006-0000-4000-8000-000000000006', 'html5', 'HTML5'),
('a0000006-0000-4000-8000-000000000006', 'css3', 'CSS3'),
('a0000006-0000-4000-8000-000000000006', 'javascript', 'JavaScript'),
('a0000007-0000-4000-8000-000000000007', 'sql', 'SQL'),
('a0000007-0000-4000-8000-000000000007', 'requisitos', 'Requisitos'),
('a0000008-0000-4000-8000-000000000008', 'php', 'PHP'),
('a0000008-0000-4000-8000-000000000008', 'api-rest', 'API REST'),
('a0000009-0000-4000-8000-000000000009', 'php', 'PHP'),
('a0000009-0000-4000-8000-000000000009', 'arquitetura', 'Arquitetura'),
('a0000009-0000-4000-8000-000000000009', 'docker', 'Docker'),
('a0000010-0000-4000-8000-000000000010', 'c++', 'C++'),
('a0000010-0000-4000-8000-000000000010', 'arduino', 'Arduino'),
('a0000011-0000-4000-8000-000000000011', 'php', 'PHP'),
('a0000011-0000-4000-8000-000000000011', 'c#', 'C#'),
('a0000011-0000-4000-8000-000000000011', 'javascript', 'JavaScript'),
('a0000011-0000-4000-8000-000000000011', 'nodejs', 'Node.js'),
('a0000012-0000-4000-8000-000000000012', 'python', 'Python'),
('a0000012-0000-4000-8000-000000000012', 'sql', 'SQL'),
('a0000012-0000-4000-8000-000000000012', 'machine-learning', 'Machine Learning'),
('a0000013-0000-4000-8000-000000000013', 'vuejs', 'Vue.js'),
('a0000013-0000-4000-8000-000000000013', 'javascript', 'JavaScript'),
('a0000014-0000-4000-8000-000000000014', 'php', 'PHP'),
('a0000014-0000-4000-8000-000000000014', 'mysql', 'MySQL'),
('a0000015-0000-4000-8000-000000000015', 'php', 'PHP'),
('a0000015-0000-4000-8000-000000000015', 'poo', 'POO'),
('a0000015-0000-4000-8000-000000000015', 'ddd', 'DDD'),
('a0000015-0000-4000-8000-000000000015', 'phpunit', 'PHPUnit');

-- ============================================
-- 11. INSERT ROLES
-- ============================================
INSERT INTO `roles` (`id`, `name`, `description`) VALUES
(UUID(), 'admin', 'Administrador com acesso total'),
(UUID(), 'gestor', 'Gerenciador de vagas'),
(UUID(), 'usuario', 'Usuário padrão com permissões limitadas');

-- ============================================
-- 12. INSERT PERMISSIONS
-- ============================================
INSERT INTO `permissions` (`id`, `name`, `description`, `module`, `action`) VALUES
(UUID(), 'vacancy.create', 'Criar novas vagas', 'vacancy', 'create'),
(UUID(), 'vacancy.edit', 'Editar vagas existentes', 'vacancy', 'edit'),
(UUID(), 'vacancy.delete', 'Deletar vagas', 'vacancy', 'delete'),
(UUID(), 'vacancy.view', 'Visualizar vagas', 'vacancy', 'view'),
(UUID(), 'vacancy.publish', 'Publicar/Ativar vagas', 'vacancy', 'publish'),
(UUID(), 'vacancy.manage', 'Gerenciar todas as vagas', 'vacancy', 'manage'),
(UUID(), 'user.list', 'Listar usuários', 'user', 'list'),
(UUID(), 'user.create', 'Criar usuários', 'user', 'create'),
(UUID(), 'user.edit', 'Editar usuários', 'user', 'edit'),
(UUID(), 'user.delete', 'Deletar usuários', 'user', 'delete'),
(UUID(), 'user.assign_role', 'Atribuir roles a usuários', 'user', 'assign_role'),
(UUID(), 'role.list', 'Listar roles', 'role', 'list'),
(UUID(), 'role.create', 'Criar roles', 'role', 'create'),
(UUID(), 'role.edit', 'Editar roles', 'role', 'edit'),
(UUID(), 'role.delete', 'Remover roles', 'role', 'delete'),
(UUID(), 'role.assign_permission', 'Vincular permissões em roles', 'role', 'assign_permission'),
(UUID(), 'permission.list', 'Listar permissões', 'permission', 'list'),
(UUID(), 'permission.create', 'Criar permissões', 'permission', 'create'),
(UUID(), 'permission.edit', 'Editar permissões', 'permission', 'edit'),
(UUID(), 'permission.delete', 'Remover permissões', 'permission', 'delete');

-- ============================================
-- 13. ASSIGN PERMISSIONS TO ADMIN ROLE
-- ============================================
INSERT INTO `role_permissions` (`id`, `role_id`, `permission_id`)
SELECT UUID(), r.id, p.id FROM `roles` r, `permissions` p
WHERE r.name = 'admin';

-- ============================================
-- 14. ASSIGN PERMISSIONS TO GESTOR ROLE
-- ============================================
INSERT INTO `role_permissions` (`id`, `role_id`, `permission_id`)
SELECT UUID(), r.id, p.id FROM `roles` r, `permissions` p
WHERE r.name = 'gestor' AND p.name IN (
  'vacancy.create', 'vacancy.edit', 'vacancy.delete',
  'vacancy.view', 'vacancy.publish', 'vacancy.manage',
  'user.list'
);

-- ============================================
-- 15. ASSIGN PERMISSIONS TO USUARIO ROLE
-- ============================================
INSERT INTO `role_permissions` (`id`, `role_id`, `permission_id`)
SELECT UUID(), r.id, p.id FROM `roles` r, `permissions` p
WHERE r.name = 'usuario' AND p.name IN ('vacancy.view');

-- ============================================
-- 16. ASSIGN ROLES TO EXISTING USERS
-- ============================================
UPDATE `users` SET `role_id` = (SELECT `id` FROM `roles` WHERE `name` = 'gestor')
WHERE `company_id` IS NOT NULL AND `role_id` IS NULL;

UPDATE `users` SET `role_id` = (SELECT `id` FROM `roles` WHERE `name` = 'usuario')
WHERE `role_id` IS NULL;

-- ============================================
-- 17. INSERT SAMPLE CANDIDATE PROFILE
-- ============================================
INSERT INTO `candidate_profiles` (`user_id`, `headline`, `bio`, `area`, `seniority`, `work_mode`, `linkedin_url`, `github_url`)
SELECT `id`, 'Desenvolvedor Backend PHP & Arquitetura Limpa', 'Experiência com PHP Orientado a Objetos, DDD, MySQL, APIs REST e testes automatizados com PHPUnit.', 'backend', 'pleno', 'remoto', 'https://linkedin.com/in/aureo-bueno', 'https://github.com/Aureo-Bueno'
FROM `users` WHERE `email` = 'aureo@live.com';

INSERT INTO `candidate_topics` (`user_id`, `slug`, `label`)
SELECT `id`, 'php', 'PHP' FROM `users` WHERE `email` = 'aureo@live.com'
UNION ALL
SELECT `id`, 'poo', 'POO' FROM `users` WHERE `email` = 'aureo@live.com'
UNION ALL
SELECT `id`, 'mysql', 'MySQL' FROM `users` WHERE `email` = 'aureo@live.com'
UNION ALL
SELECT `id`, 'javascript', 'JavaScript' FROM `users` WHERE `email` = 'aureo@live.com'
UNION ALL
SELECT `id`, 'docker', 'Docker' FROM `users` WHERE `email` = 'aureo@live.com';

-- ============================================
-- SETUP COMPLETE
-- ============================================
-- All tables created and populated successfully!
-- Users: Aureo Bueno (aureo@live.com), Andre Pinheiro (andre@live.com)
-- Roles: admin, gestor, usuario
-- Permissions: vacancy.* and user.*
-- ============================================
