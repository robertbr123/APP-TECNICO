-- =====================================================
-- Script para ATUALIZAR tabela users existente
-- Execute no phpMyAdmin
-- =====================================================

USE onde2292_cadastro;

-- Adiciona coluna full_name (nome completo)
ALTER TABLE `users` 
ADD COLUMN IF NOT EXISTS `full_name` varchar(150) DEFAULT NULL AFTER `password`;

-- Adiciona coluna email
ALTER TABLE `users` 
ADD COLUMN IF NOT EXISTS `email` varchar(150) DEFAULT NULL AFTER `full_name`;

-- Adiciona coluna role (admin ou user)
ALTER TABLE `users` 
ADD COLUMN IF NOT EXISTS `role` enum('user','admin') DEFAULT 'user' AFTER `email`;

-- Adiciona coluna created_at (data de criação)
ALTER TABLE `users` 
ADD COLUMN IF NOT EXISTS `created_at` timestamp NULL DEFAULT current_timestamp() AFTER `role`;

-- =====================================================
-- Se o comando acima der erro (versão antiga do MySQL),
-- use estes comandos alternativos (um por vez):
-- =====================================================

-- ALTER TABLE `users` ADD COLUMN `full_name` varchar(150) DEFAULT NULL;
-- ALTER TABLE `users` ADD COLUMN `email` varchar(150) DEFAULT NULL;
-- ALTER TABLE `users` ADD COLUMN `role` enum('user','admin') DEFAULT 'user';
-- ALTER TABLE `users` ADD COLUMN `created_at` timestamp NULL DEFAULT current_timestamp();

-- =====================================================
-- Atualiza os usuários existentes com dados
-- =====================================================

UPDATE `users` SET 
    `full_name` = 'Administrador',
    `email` = 'admin@ondeline.com',
    `role` = 'admin'
WHERE `username` = 'admin';

UPDATE `users` SET 
    `full_name` = 'Robert',
    `email` = 'robert@ondeline.com',
    `role` = 'user'
WHERE `username` = 'robert';

-- =====================================================
-- Cria tabela de fotos (se não existir)
-- =====================================================

CREATE TABLE IF NOT EXISTS `client_photos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cpf` varchar(11) NOT NULL,
  `filename` varchar(255) NOT NULL,
  `type` enum('router','cabling','signal','other') DEFAULT 'other',
  `uploaded_by` int(11) DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `cpf` (`cpf`),
  KEY `uploaded_by` (`uploaded_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

-- Verifica a estrutura final
DESCRIBE `users`;

SELECT 'Atualização concluída!' as status;
