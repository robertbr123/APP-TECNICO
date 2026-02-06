-- =====================================================
-- Atualizações do Banco de Dados - Novas Features
-- Execute este script no phpMyAdmin
-- =====================================================

USE onde2292_cadastro;

-- =====================================================
-- 1. Tabela para Geolocalização dos Clientes (sem foreign key)
-- =====================================================
CREATE TABLE IF NOT EXISTS `client_locations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cpf` varchar(11) NOT NULL,
  `latitude` decimal(10, 8) NOT NULL,
  `longitude` decimal(11, 8) NOT NULL,
  `address_computed` text,
  `accuracy` decimal(10, 2) DEFAULT NULL,
  `altitude` decimal(10, 2) DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `cpf` (`cpf`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 2. Tabela para Histórico de Seriais
-- =====================================================
CREATE TABLE IF NOT EXISTS `serial_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cpf` varchar(11) NOT NULL,
  `client_name` varchar(150) NOT NULL,
  `old_serial` varchar(100) DEFAULT NULL,
  `new_serial` varchar(100) NOT NULL,
  `reason` enum('defect','upgrade','transfer','theft','other') DEFAULT 'other',
  `reason_description` text,
  `old_photos` json DEFAULT NULL,
  `changed_by` int(11) DEFAULT NULL,
  `changed_by_name` varchar(150) DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `cpf` (`cpf`),
  KEY `new_serial` (`new_serial`),
  KEY `created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 3. Tabela para Fila de Cadastros Offline
-- =====================================================
CREATE TABLE IF NOT EXISTS `offline_queue` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `action_type` enum('create_client','update_client','link_equipment','upload_photo') NOT NULL,
  `data_json` text NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `username` varchar(100) DEFAULT NULL,
  `device_id` varchar(255) DEFAULT NULL,
  `status` enum('pending','synced','failed') DEFAULT 'pending',
  `error_message` text,
  `attempt_count` int(11) DEFAULT 0,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `synced_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `status` (`status`),
  KEY `user_id` (`user_id`),
  KEY `created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 4. Adicionar campos de localização na tabela clients
-- =====================================================
ALTER TABLE `clients` 
ADD COLUMN IF NOT EXISTS `latitude` decimal(10, 8) DEFAULT NULL AFTER `observation`,
ADD COLUMN IF NOT EXISTS `longitude` decimal(11, 8) DEFAULT NULL AFTER `latitude`,
ADD COLUMN IF NOT EXISTS `location_accuracy` decimal(10, 2) DEFAULT NULL AFTER `longitude`;

-- =====================================================
-- 5. Índices para performance
-- =====================================================
CREATE INDEX IF NOT EXISTS `idx_clients_location` ON `clients` (`latitude`, `longitude`);
CREATE INDEX IF NOT EXISTS `idx_clients_city` ON `clients` (`city`);
CREATE INDEX IF NOT EXISTS `idx_client_locations_cpf` ON `client_locations` (`cpf`);
CREATE INDEX IF NOT EXISTS `idx_serial_history_cpf` ON `serial_history` (`cpf`, `created_at`);

-- =====================================================
-- 6. Tabela para Fotos de Equipamento Antigo
-- =====================================================
CREATE TABLE IF NOT EXISTS `old_equipment_photos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `serial_history_id` int(11) NOT NULL,
  `filename` varchar(255) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `uploaded_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `serial_history_id` (`serial_history_id`),
  CONSTRAINT `fk_old_photo_history` FOREIGN KEY (`serial_history_id`) REFERENCES `serial_history` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 7. Tabela de Notificações
-- =====================================================
CREATE TABLE IF NOT EXISTS `notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `message` text NOT NULL,
  `type` enum('info','success','warning','error') DEFAULT 'info',
  `read` tinyint(1) DEFAULT 0,
  `action_url` varchar(255) DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `read` (`read`),
  KEY `created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SELECT 'Script de atualização executado com sucesso!' as status;
SELECT 'Tabelas criadas:' as info;
SHOW TABLES LIKE '%locations%';
SHOW TABLES LIKE '%serial_history%';
SHOW TABLES LIKE '%offline_queue%';
SHOW TABLES LIKE '%notifications%';