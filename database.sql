-- =====================================================
-- ONDELINE TECH APP - Script Completo do Banco de Dados
-- Versão consolidada de todos os scripts SQL do projeto
-- =====================================================
-- Este arquivo contém TODA a estrutura do banco de dados.
-- Execute no phpMyAdmin ou via CLI do MySQL.
-- =====================================================

-- #####################################################
-- BANCO DE DADOS: onde2292_cadastro
-- (Usuários, Auditoria, Ponto, Fotos, Estoque)
-- #####################################################

CREATE DATABASE IF NOT EXISTS `onde2292_cadastro` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `onde2292_cadastro`;

-- =====================================================
-- 1. TABELA DE USUÁRIOS
-- =====================================================
CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(150) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `role` enum('user','admin') DEFAULT 'user',
  `cargo` varchar(100) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Usuário admin padrão (senha: admin123)
INSERT INTO `users` (`username`, `password`, `full_name`, `email`, `role`) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrador', 'admin@ondeline.com', 'admin')
ON DUPLICATE KEY UPDATE `username` = `username`;

-- =====================================================
-- 2. TABELA DE LOGS DE AUDITORIA
-- =====================================================
CREATE TABLE IF NOT EXISTS `audit_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `username` varchar(100) DEFAULT NULL,
  `action_type` varchar(50) NOT NULL,
  `action_description` varchar(255) NOT NULL,
  `entity_type` varchar(50) DEFAULT NULL,
  `entity_id` varchar(100) DEFAULT NULL,
  `entity_name` varchar(255) DEFAULT NULL,
  `details` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `action_type` (`action_type`),
  KEY `created_at` (`created_at`),
  KEY `idx_audit_user_action` (`user_id`, `action_type`),
  KEY `idx_audit_date` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 3. TABELA DE REGISTRO DE PONTO (TIME CLOCK)
-- =====================================================
CREATE TABLE IF NOT EXISTS `time_clock` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `clock_date` date NOT NULL,
  `entry_time` time DEFAULT NULL,
  `entry_photo` longtext DEFAULT NULL,
  `entry_latitude` decimal(10, 8) DEFAULT NULL,
  `entry_longitude` decimal(11, 8) DEFAULT NULL,
  `entry_accuracy` decimal(10, 2) DEFAULT NULL,
  `exit_time` time DEFAULT NULL,
  `exit_photo` longtext DEFAULT NULL,
  `exit_latitude` decimal(10, 8) DEFAULT NULL,
  `exit_longitude` decimal(11, 8) DEFAULT NULL,
  `exit_accuracy` decimal(10, 2) DEFAULT NULL,
  `worked_hours` time DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `clock_date` (`clock_date`),
  KEY `username` (`username`),
  UNIQUE KEY `user_date_unique` (`user_id`, `clock_date`),
  KEY `idx_timeclock_date` (`clock_date` DESC),
  KEY `idx_timeclock_entry` (`entry_time`),
  KEY `idx_timeclock_exit` (`exit_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Registro de ponto eletrônico dos técnicos';

-- =====================================================
-- 4. TABELA DE FOTOS DOS CLIENTES
-- =====================================================
CREATE TABLE IF NOT EXISTS `client_photos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cpf` varchar(11) NOT NULL,
  `filename` varchar(255) NOT NULL,
  `type` varchar(50) DEFAULT 'other',
  `uploaded_by` int(11) DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `cpf` (`cpf`),
  KEY `uploaded_by` (`uploaded_by`),
  KEY `idx_client_photos_cpf_type` (`cpf`, `type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 5. TABELA DE ESTOQUE DE EQUIPAMENTOS
-- =====================================================
CREATE TABLE IF NOT EXISTS `equipment_inventory` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `serial_number` varchar(100) NOT NULL,
  `model` varchar(150) NOT NULL,
  `brand` varchar(100) DEFAULT NULL,
  `type` enum('router','modem','onu','cpe','antenna','cable','accessory','other') DEFAULT 'router',
  `status` enum('available','in_use','maintenance','defective','lost') DEFAULT 'available',
  `location` varchar(200) DEFAULT 'Estoque Principal',
  `purchase_date` date DEFAULT NULL,
  `purchase_price` decimal(10, 2) DEFAULT NULL,
  `warranty_until` date DEFAULT NULL,
  `current_user_id` int(11) DEFAULT NULL,
  `current_client_cpf` varchar(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `serial_number` (`serial_number`),
  KEY `status` (`status`),
  KEY `type` (`type`),
  KEY `current_client_cpf` (`current_client_cpf`),
  KEY `current_user_id` (`current_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Controle de estoque de equipamentos';

-- =====================================================
-- 6. TABELA DE MOVIMENTAÇÕES DE ESTOQUE
-- =====================================================
CREATE TABLE IF NOT EXISTS `inventory_movements` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `equipment_id` int(11) NOT NULL,
  `movement_type` enum('in','out','transfer','maintenance','defective','lost') NOT NULL,
  `from_location` varchar(200) DEFAULT NULL,
  `to_location` varchar(200) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `username` varchar(100) DEFAULT NULL,
  `client_cpf` varchar(11) DEFAULT NULL,
  `client_name` varchar(150) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `equipment_id` (`equipment_id`),
  KEY `movement_type` (`movement_type`),
  KEY `user_id` (`user_id`),
  KEY `created_at` (`created_at`),
  KEY `client_cpf` (`client_cpf`),
  CONSTRAINT `fk_movement_equipment` FOREIGN KEY (`equipment_id`) REFERENCES `equipment_inventory` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Histórico de movimentações de estoque';

-- =====================================================
-- 7. TABELA DE ALERTAS DE ESTOQUE
-- =====================================================
CREATE TABLE IF NOT EXISTS `inventory_alerts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `equipment_id` int(11) DEFAULT NULL,
  `alert_type` enum('low_stock','no_stock','overdue_return','defective','missing','maintenance_needed','defective_equipment') NOT NULL,
  `severity` enum('low','medium','high','critical','info','warning') DEFAULT 'medium',
  `title` varchar(200) DEFAULT NULL,
  `equipment_type` varchar(100) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `description` text DEFAULT NULL,
  `resolved` tinyint(1) DEFAULT 0,
  `resolved_at` timestamp NULL DEFAULT NULL,
  `resolved_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `equipment_id` (`equipment_id`),
  KEY `alert_type` (`alert_type`),
  KEY `severity` (`severity`),
  KEY `resolved` (`resolved`),
  KEY `created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Alertas de estoque';

-- =====================================================
-- 8. TABELA DE GEOLOCALIZAÇÃO DOS CLIENTES
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
  KEY `cpf` (`cpf`),
  KEY `idx_client_locations_cpf` (`cpf`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 9. TABELA DE HISTÓRICO DE SERIAIS
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
  KEY `created_at` (`created_at`),
  KEY `idx_serial_history_cpf` (`cpf`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 10. TABELA DE FOTOS DE EQUIPAMENTO ANTIGO
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
-- 11. TABELA DE FILA OFFLINE
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
-- 12. TABELA DE NOTIFICAÇÕES
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


-- #####################################################
-- BANCO DE DADOS: onde2292_erp
-- (Clientes, Checklists, Planos)
-- #####################################################

CREATE DATABASE IF NOT EXISTS `onde2292_erp` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `onde2292_erp`;

-- =====================================================
-- 13. TABELA DE CLIENTES
-- =====================================================
CREATE TABLE IF NOT EXISTS `clients` (
  `cpf` varchar(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `dob` date NOT NULL,
  `phone` varchar(15) NOT NULL,
  `cep` varchar(10) DEFAULT NULL,
  `address` varchar(255) NOT NULL,
  `number` varchar(10) NOT NULL,
  `complement` varchar(255) DEFAULT NULL,
  `neighborhood` varchar(100) DEFAULT NULL,
  `city` varchar(100) NOT NULL,
  `state` varchar(2) DEFAULT NULL,
  `plan` varchar(255) NOT NULL,
  `pppoe_user` varchar(100) DEFAULT NULL,
  `pppoe_pass` varchar(100) DEFAULT NULL,
  `due_date` int(11) NOT NULL,
  `installer` varchar(255) NOT NULL,
  `serial` varchar(100) DEFAULT NULL,
  `contrato` varchar(20) DEFAULT NULL,
  `observation` text DEFAULT NULL,
  `latitude` decimal(10, 8) DEFAULT NULL,
  `longitude` decimal(11, 8) DEFAULT NULL,
  `location_accuracy` decimal(10, 2) DEFAULT NULL,
  `registration_date` date NOT NULL,
  PRIMARY KEY (`cpf`),
  KEY `idx_clients_city` (`city`),
  KEY `idx_clients_city_name` (`city`, `name`),
  KEY `idx_clients_serial` (`serial`),
  KEY `idx_clients_location` (`latitude`, `longitude`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 14. TABELA DE PLANOS
-- =====================================================
CREATE TABLE IF NOT EXISTS `plans` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Planos padrão
INSERT INTO `plans` (`name`) VALUES
('Fibra 100MB'),
('Fibra 200MB'),
('Fibra 300MB'),
('Fibra 500MB'),
('Fibra 1GB')
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);

-- =====================================================
-- 15. TABELA DE CHECKLISTS DE INSTALAÇÃO
-- =====================================================
CREATE TABLE IF NOT EXISTS `installation_checklists` (
  `id` int(11) AUTO_INCREMENT PRIMARY KEY,
  `client_cpf` varchar(11) NOT NULL,
  `client_name` varchar(150) NOT NULL,
  `technician_id` int(11) NOT NULL,
  `technician_name` varchar(100) NOT NULL,
  `installation_type` enum('new', 'migration', 'repair', 'maintenance') DEFAULT 'new',
  `status` enum('pending', 'in_progress', 'completed', 'cancelled') DEFAULT 'pending',
  `approval_status` enum('pending', 'pending_approval', 'approved', 'rejected') DEFAULT 'pending',
  `approved_by` int(11) DEFAULT NULL,
  `approved_at` timestamp NULL,
  `rejection_reason` text DEFAULT NULL,
  `rejection_notes` text DEFAULT NULL,
  `started_at` timestamp NULL,
  `completed_at` timestamp NULL,
  `tech_latitude` decimal(10, 8) DEFAULT NULL,
  `tech_longitude` decimal(11, 8) DEFAULT NULL,
  `tech_location_accuracy` decimal(10, 2) DEFAULT NULL,
  `location_captured_at` timestamp NULL,
  `notes` text,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_client_cpf (`client_cpf`),
  INDEX idx_technician (`technician_id`),
  INDEX idx_status (`status`),
  INDEX idx_created (`created_at`),
  INDEX idx_approval_status (`approval_status`),
  INDEX idx_approved_by (`approved_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 16. TABELA DE ITENS DO CHECKLIST
-- =====================================================
CREATE TABLE IF NOT EXISTS `checklist_items` (
  `id` int(11) AUTO_INCREMENT PRIMARY KEY,
  `checklist_id` int(11) NOT NULL,
  `task_name` varchar(255) NOT NULL,
  `task_category` enum('pre_installation', 'installation', 'configuration', 'testing', 'documentation') DEFAULT 'installation',
  `is_required` boolean DEFAULT TRUE,
  `is_completed` boolean DEFAULT FALSE,
  `completed_at` timestamp NULL,
  `completed_by` int(11) NULL,
  `notes` text,
  `photo_url` varchar(255),
  `order_index` int(11) DEFAULT 0,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`checklist_id`) REFERENCES `installation_checklists`(`id`) ON DELETE CASCADE,
  INDEX idx_checklist (`checklist_id`),
  INDEX idx_category (`task_category`),
  INDEX idx_completed (`is_completed`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 17. TABELA DE TEMPLATES DE CHECKLIST
-- =====================================================
CREATE TABLE IF NOT EXISTS `checklist_templates` (
  `id` int(11) AUTO_INCREMENT PRIMARY KEY,
  `task_name` varchar(255) NOT NULL,
  `task_category` enum('pre_installation', 'installation', 'configuration', 'testing', 'documentation') DEFAULT 'installation',
  `is_required` boolean DEFAULT TRUE,
  `order_index` int(11) DEFAULT 0,
  `is_active` boolean DEFAULT TRUE,
  `applicable_types` varchar(100) DEFAULT 'new,migration,repair,maintenance',
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_category (`task_category`),
  INDEX idx_active (`is_active`),
  INDEX idx_order (`order_index`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Templates padrão de checklist
INSERT INTO `checklist_templates` (`task_name`, `task_category`, `is_required`, `order_index`, `applicable_types`) VALUES
-- Pré-instalação (todos os tipos)
('Verificar sinal na caixa de atendimento', 'pre_installation', TRUE, 1, 'new,migration,repair,maintenance'),
('Conferir materiais e equipamentos', 'pre_installation', TRUE, 2, 'new,migration,repair,maintenance'),
('Verificar rota de cabeamento', 'pre_installation', TRUE, 3, 'new,migration,repair,maintenance'),
('Identificar ponto de entrada na residência', 'pre_installation', TRUE, 4, 'new,migration,repair,maintenance'),

-- Pré-instalação (específicos)
('Cadastrar cliente no sistema', 'pre_installation', TRUE, 0, 'new'),
('Verificar viabilidade técnica', 'pre_installation', TRUE, 1, 'new'),
('Diagnosticar problema reportado', 'pre_installation', TRUE, 0, 'repair,maintenance'),
('Verificar cabos e conexões', 'pre_installation', TRUE, 1, 'repair,maintenance'),
('Testar sinal na CTO', 'pre_installation', TRUE, 2, 'repair,maintenance'),
('Verificar compatibilidade', 'pre_installation', TRUE, 0, 'migration'),
('Backup de configurações', 'pre_installation', TRUE, 1, 'migration'),

-- Instalação
('Instalar poste/caixa externa', 'installation', TRUE, 9, 'new'),
('Instalar caixa de proteção (se necessário)', 'installation', TRUE, 10, 'new,migration'),
('Passar cabo de fibra óptica', 'installation', TRUE, 11, 'new,migration'),
('Conectar fibra na CTO', 'installation', TRUE, 12, 'new,migration'),
('Instalar roteador/ONU na posição indicada', 'installation', TRUE, 13, 'new,migration'),
('Organizar cabos e identificar', 'installation', TRUE, 14, 'new,migration'),
('Fazer fusão da fibra (se necessário)', 'installation', FALSE, 15, 'new,migration'),
('Substituir equipamento defeituoso', 'installation', TRUE, 10, 'repair,maintenance'),

-- Configuração
('Configurar PPPoE no roteador', 'configuration', TRUE, 20, 'new,migration,repair,maintenance'),
('Configurar PPPoE inicial', 'configuration', TRUE, 20, 'new'),
('Alterar senha WiFi padrão', 'configuration', FALSE, 21, 'new,migration,repair,maintenance'),
('Configurar 2.4GHz e 5GHz', 'configuration', TRUE, 22, 'new,migration,repair,maintenance'),
('Verificar sincronização ONU', 'configuration', TRUE, 23, 'new,migration,repair,maintenance'),
('Migrar configurações', 'configuration', TRUE, 20, 'migration'),
('Atualizar firmware do roteador', 'configuration', FALSE, 20, 'maintenance'),
('Verificar atualizações de segurança', 'configuration', FALSE, 21, 'maintenance'),
('Otimizar canais WiFi', 'configuration', FALSE, 22, 'maintenance'),

-- Testes
('Testar velocidade de download', 'testing', TRUE, 30, 'new,migration,repair,maintenance'),
('Testar velocidade de upload', 'testing', TRUE, 31, 'new,migration,repair,maintenance'),
('Verificar latência (ping)', 'testing', TRUE, 32, 'new,migration,repair,maintenance'),
('Testar estabilidade da conexão', 'testing', TRUE, 33, 'new,migration,repair,maintenance'),
('Verificar alcance do WiFi nos cômodos', 'testing', TRUE, 34, 'new,migration,repair,maintenance'),
('Testar streaming de vídeo', 'testing', FALSE, 35, 'new,migration,repair,maintenance'),
('Verificar funcionamento após migração', 'testing', TRUE, 30, 'migration'),
('Verificar consumo de banda', 'testing', FALSE, 30, 'maintenance'),

-- Documentação
('Tirar foto do roteador instalado', 'documentation', TRUE, 40, 'new,migration,repair,maintenance'),
('Tirar foto do cabeamento', 'documentation', TRUE, 41, 'new,migration,repair,maintenance'),
('Tirar foto do sinal na ONU', 'documentation', TRUE, 42, 'new,migration,repair,maintenance'),
('Orientar cliente sobre uso', 'documentation', TRUE, 43, 'new,migration,repair,maintenance'),
('Tutorial inicial com cliente', 'documentation', TRUE, 43, 'new'),
('Entregar senha anotada ao cliente', 'documentation', TRUE, 44, 'new,migration,repair,maintenance'),
('Coletar assinatura do cliente', 'documentation', FALSE, 45, 'new,migration,repair,maintenance'),
('Limpeza do equipamento', 'documentation', FALSE, 40, 'maintenance'),
('Relatório de serviço', 'documentation', TRUE, 45, 'repair,maintenance')
ON DUPLICATE KEY UPDATE `task_name` = `task_name`;

-- =====================================================
-- 18. TABELA DE HISTÓRICO DE APROVAÇÕES DO CHECKLIST
-- =====================================================
CREATE TABLE IF NOT EXISTS `checklist_approval_history` (
  `id` int(11) AUTO_INCREMENT PRIMARY KEY,
  `checklist_id` int(11) NOT NULL,
  `action` enum('submitted', 'approved', 'rejected', 'deleted') NOT NULL,
  `action_by` int(11) NOT NULL,
  `action_by_name` varchar(100) NOT NULL,
  `action_by_role` enum('admin', 'tecnico') NOT NULL,
  `notes` text,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`checklist_id`) REFERENCES `installation_checklists`(`id`) ON DELETE CASCADE,
  INDEX idx_checklist (`checklist_id`),
  INDEX idx_action (`action`),
  INDEX idx_created (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- FIM DO SCRIPT
-- =====================================================
SELECT 'Banco de dados configurado com sucesso!' as status;
