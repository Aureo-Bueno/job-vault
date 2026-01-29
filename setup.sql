-- ============================================
-- COMPLETE DATABASE SETUP
-- JobVault - Job Vacancy Management System
-- ============================================

-- ============================================
-- 1. CREATE DATABASE
-- ============================================
CREATE DATABASE IF NOT EXISTS myapp_db;
USE myapp_db;

-- ============================================
-- 2. CREATE USUARIOS TABLE (with role_id)
-- ============================================
DROP TABLE IF EXISTS `usuarios`;
CREATE TABLE `usuarios` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nome` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `senha` varchar(255) DEFAULT NULL,
  `role_id` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ============================================
-- 3. CREATE VAGAS TABLE
-- ============================================
DROP TABLE IF EXISTS `vagas`;
CREATE TABLE `vagas` (
  `id` int NOT NULL AUTO_INCREMENT,
  `titulo` varchar(255) NOT NULL,
  `descricao` text NOT NULL,
  `ativo` enum('s','n') NOT NULL,
  `data` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ============================================
-- 4. CREATE ROLES TABLE
-- ============================================
DROP TABLE IF EXISTS `roles`;
CREATE TABLE `roles` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nome` varchar(100) NOT NULL UNIQUE,
  `descricao` varchar(255),
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_nome` (`nome`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ============================================
-- 5. CREATE PERMISSIONS TABLE
-- ============================================
DROP TABLE IF EXISTS `permissions`;
CREATE TABLE `permissions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nome` varchar(100) NOT NULL UNIQUE,
  `descricao` varchar(255),
  `modulo` varchar(50),
  `acao` varchar(50),
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_nome` (`nome`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ============================================
-- 6. CREATE ROLE_PERMISSIONS TABLE
-- ============================================
DROP TABLE IF EXISTS `role_permissions`;
CREATE TABLE `role_permissions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `role_id` int NOT NULL,
  `permission_id` int NOT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_role_permission` (`role_id`, `permission_id`),
  FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ============================================
-- 7. ADD FOREIGN KEY TO USUARIOS
-- ============================================
ALTER TABLE `usuarios` ADD CONSTRAINT `fk_usuarios_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE SET NULL;

-- ============================================
-- 8. INSERT USUARIOS DATA
-- ============================================
INSERT INTO `usuarios` (`id`, `nome`, `email`, `senha`, `role_id`) VALUES
(1, 'Aureo Bueno', 'aureo@live.com', '$2y$10$IZB3TIpYA0aPP4U5Adls0ed3Idelm548Hko92MIFBTkYq2CZHpGnW', NULL),
(2, 'Andre Pinheiro', 'andre@live.com', '$2y$10$VPQ9hfi8oC/prTc7H2WNMe.yMxtBAp.tPT0OqclNFu4B4/fq2bB1q', NULL);

-- ============================================
-- 9. INSERT VAGAS DATA
-- ============================================
INSERT INTO `vagas` (`id`, `titulo`, `descricao`, `ativo`, `data`) VALUES
(4, 'Programador FullStack', 'Backend PHP,JavaScript,NodeJS', 's', '2020-11-11 20:51:05'),
(6, 'Analista', 'Saiba Backend,PHPOO,MYSQL,JS', 's', '2020-11-11 21:42:45'),
(8, 'Programador C#', 'Programador Mobile', 's', '2020-11-13 17:18:06'),
(9, 'Programador C++', 'Programaçao Desktop.', 'n', '2020-11-13 17:19:04'),
(10, 'Engenheiro de Software', 'Desejavel, ReactJS,NodeJS', 's', '2020-11-13 17:19:58'),
(11, 'Programador Frontend', 'Desejavel, HTML5, CSS3.', 'n', '2020-11-13 17:20:40'),
(12, 'Analista Junior', 'Desejavel cursando Superior', 's', '2020-11-13 17:21:50'),
(13, 'Programador Pleno', 'Desejavel Backend', 'n', '2020-11-13 17:22:14'),
(14, 'Programador Sênior', 'Fullstack', 's', '2020-11-13 17:23:39'),
(15, 'Engenheiro da Computação', 'Desenvolvedor C++ e Arduino', 's', '2020-11-13 18:02:20'),
(16, 'Backend', 'PHP,C#,JS,NODEJS', 's', '2020-11-13 20:31:54'),
(18, 'DataScience Junior', 'Data science', 's', '2020-11-13 20:33:31'),
(19, 'Programador VueJS', 'VueJS', 's', '2020-11-13 20:33:49'),
(20, 'Analista de sistemas Junior', 'CRUD,PHP E MYSQL', 's', '2020-11-13 20:34:48'),
(21, 'Analista de Sistemas Sênior', 'PHP Orientado a Objetos', 's', '2020-11-13 20:35:13');

-- ============================================
-- 10. INSERT ROLES
-- ============================================
INSERT INTO `roles` (`nome`, `descricao`) VALUES
('admin', 'Administrador com acesso total'),
('gestor', 'Gerenciador de vagas'),
('usuario', 'Usuário padrão com permissões limitadas');

-- ============================================
-- 11. INSERT PERMISSIONS
-- ============================================
INSERT INTO `permissions` (`nome`, `descricao`, `modulo`, `acao`) VALUES
('vaga.criar', 'Criar novas vagas', 'vaga', 'criar'),
('vaga.editar', 'Editar vagas existentes', 'vaga', 'editar'),
('vaga.deletar', 'Deletar vagas', 'vaga', 'deletar'),
('vaga.visualizar', 'Visualizar vagas', 'vaga', 'visualizar'),
('vaga.publicar', 'Publicar/Ativar vagas', 'vaga', 'publicar'),
('vaga.gerenciar', 'Gerenciar todas as vagas', 'vaga', 'gerenciar'),
('usuario.listar', 'Listar usuários', 'usuario', 'listar'),
('usuario.editar', 'Editar usuários', 'usuario', 'editar'),
('usuario.deletar', 'Deletar usuários', 'usuario', 'deletar'),
('usuario.atribuir_role', 'Atribuir roles a usuários', 'usuario', 'atribuir_role');

-- ============================================
-- 12. ASSIGN PERMISSIONS TO ADMIN ROLE
-- ============================================
INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT r.id, p.id FROM `roles` r, `permissions` p
WHERE r.nome = 'admin';

-- ============================================
-- 13. ASSIGN PERMISSIONS TO GESTOR ROLE
-- ============================================
INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT r.id, p.id FROM `roles` r, `permissions` p
WHERE r.nome = 'gestor' AND p.nome IN (
  'vaga.criar', 'vaga.editar', 'vaga.deletar',
  'vaga.visualizar', 'vaga.publicar', 'vaga.gerenciar',
  'usuario.listar'
);

-- ============================================
-- 14. ASSIGN PERMISSIONS TO USUARIO ROLE
-- ============================================
INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT r.id, p.id FROM `roles` r, `permissions` p
WHERE r.nome = 'usuario' AND p.nome IN ('vaga.visualizar');

-- ============================================
-- 15. ASSIGN DEFAULT ROLE TO EXISTING USERS
-- ============================================
UPDATE `usuarios` SET `role_id` = (SELECT `id` FROM `roles` WHERE `nome` = 'usuario')
WHERE `role_id` IS NULL;

-- ============================================
-- SETUP COMPLETE
-- ============================================
-- All tables created and populated successfully!
-- Users: Aureo Bueno (aureo@live.com), Andre Pinheiro (andre@live.com)
-- Roles: admin, gestor, usuario
-- Permissions: vaga.* and usuario.*
-- ============================================
