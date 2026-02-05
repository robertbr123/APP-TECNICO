-- =====================================================
-- Script de Criação de Usuário para o App do Técnico
-- Execute este script no phpMyAdmin se necessário
-- =====================================================

USE onde2292_cadastro;

-- Adiciona tabela de usuários se não existir
CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(150) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `role` enum('user','admin') DEFAULT 'user',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

-- Tabela de fotos dos clientes
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

-- Insere usuário admin padrão (senha: admin123)
-- A senha foi gerada com password_hash('admin123', PASSWORD_DEFAULT)
INSERT INTO `users` (`username`, `password`, `full_name`, `email`, `role`) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrador', 'admin@ondeline.com', 'admin')
ON DUPLICATE KEY UPDATE `username` = `username`;

-- Insere alguns planos de exemplo
INSERT INTO `plans` (`name`) VALUES 
('Fibra 100MB'),
('Fibra 200MB'),
('Fibra 300MB'),
('Fibra 500MB'),
('Fibra 1GB')
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);

-- Adiciona campo de telefone se não existir
-- ALTER TABLE `clients` ADD COLUMN IF NOT EXISTS `phone` varchar(15) NOT NULL AFTER `dob`;

SELECT 'Script executado com sucesso!' as status;
