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

-- ============================================
-- 2. CREATE USERS TABLE (with role_id)
-- ============================================
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` char(36) NOT NULL DEFAULT (UUID()),
  `name` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `role_id` char(36) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ============================================
-- 3. CREATE VACANCIES TABLE
-- ============================================
DROP TABLE IF EXISTS `vacancies`;
CREATE TABLE `vacancies` (
  `id` char(36) NOT NULL DEFAULT (UUID()),
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `is_active` enum('s','n') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ============================================
-- 4. CREATE APPLICATIONS TABLE
-- ============================================
DROP TABLE IF EXISTS `applications`;
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

-- ============================================
-- 5. CREATE ROLES TABLE
-- ============================================
DROP TABLE IF EXISTS `roles`;
CREATE TABLE `roles` (
  `id` char(36) NOT NULL DEFAULT (UUID()),
  `name` varchar(100) NOT NULL UNIQUE,
  `description` varchar(255),
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ============================================
-- 6. CREATE PERMISSIONS TABLE
-- ============================================
DROP TABLE IF EXISTS `permissions`;
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
-- 7. CREATE ROLE_PERMISSIONS TABLE
-- ============================================
DROP TABLE IF EXISTS `role_permissions`;
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
-- 8. ADD FOREIGN KEY TO USERS
-- ============================================
ALTER TABLE `users` ADD CONSTRAINT `fk_users_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE SET NULL;

-- ============================================
-- 9. INSERT USERS DATA
-- ============================================
INSERT INTO `users` (`id`, `name`, `email`, `password`, `role_id`) VALUES
(UUID(), 'Aureo Bueno', 'aureo@live.com', '$2y$10$IZB3TIpYA0aPP4U5Adls0ed3Idelm548Hko92MIFBTkYq2CZHpGnW', NULL),
(UUID(), 'Andre Pinheiro', 'andre@live.com', '$2y$10$VPQ9hfi8oC/prTc7H2WNMe.yMxtBAp.tPT0OqclNFu4B4/fq2bB1q', NULL);

-- ============================================
-- 10. INSERT VACANCIES DATA
-- ============================================
INSERT INTO `vacancies` (`id`, `title`, `description`, `is_active`, `created_at`) VALUES
(UUID(), 'Programador FullStack', 'Backend PHP,JavaScript,NodeJS', 's', '2020-11-11 20:51:05'),
(UUID(), 'Analista', 'Saiba Backend,PHPOO,MYSQL,JS', 's', '2020-11-11 21:42:45'),
(UUID(), 'Programador C#', 'Programador Mobile', 's', '2020-11-13 17:18:06'),
(UUID(), 'Programador C++', 'Programaçao Desktop.', 'n', '2020-11-13 17:19:04'),
(UUID(), 'Engenheiro de Software', 'Desejavel, ReactJS,NodeJS', 's', '2020-11-13 17:19:58'),
(UUID(), 'Programador Frontend', 'Desejavel, HTML5, CSS3.', 'n', '2020-11-13 17:20:40'),
(UUID(), 'Analista Junior', 'Desejavel cursando Superior', 's', '2020-11-13 17:21:50'),
(UUID(), 'Programador Pleno', 'Desejavel Backend', 'n', '2020-11-13 17:22:14'),
(UUID(), 'Programador Sênior', 'Fullstack', 's', '2020-11-13 17:23:39'),
(UUID(), 'Engenheiro da Computação', 'Desenvolvedor C++ e Arduino', 's', '2020-11-13 18:02:20'),
(UUID(), 'Backend', 'PHP,C#,JS,NODEJS', 's', '2020-11-13 20:31:54'),
(UUID(), 'DataScience Junior', 'Data science', 's', '2020-11-13 20:33:31'),
(UUID(), 'Programador VueJS', 'VueJS', 's', '2020-11-13 20:33:49'),
(UUID(), 'Analista de sistemas Junior', 'CRUD,PHP E MYSQL', 's', '2020-11-13 20:34:48'),
(UUID(), 'Analista de Sistemas Sênior', 'PHP Orientado a Objetos', 's', '2020-11-13 20:35:13');

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
-- 16. ASSIGN DEFAULT ROLE TO EXISTING USERS
-- ============================================
UPDATE `users` SET `role_id` = (SELECT `id` FROM `roles` WHERE `name` = 'usuario')
WHERE `role_id` IS NULL;

-- ============================================
-- SETUP COMPLETE
-- ============================================
-- All tables created and populated successfully!
-- Users: Aureo Bueno (aureo@live.com), Andre Pinheiro (andre@live.com)
-- Roles: admin, gestor, usuario
-- Permissions: vacancy.* and user.*
-- ============================================
