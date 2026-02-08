-- =====================================================
-- Tabela de Registro de Ponto Eletrônico
-- Execute este script no phpMyAdmin
-- =====================================================

USE onde2292_cadastro;

-- =====================================================
-- Tabela de Registro de Ponto (Time Clock)
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
  UNIQUE KEY `user_date_unique` (`user_id`, `clock_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Registro de ponto eletrônico dos técnicos';

-- =====================================================
-- Índices para Performance
-- =====================================================
CREATE INDEX IF NOT EXISTS `idx_timeclock_user_date` ON `time_clock` (`user_id`, `clock_date`);
CREATE INDEX IF NOT EXISTS `idx_timeclock_date` ON `time_clock` (`clock_date` DESC);
CREATE INDEX IF NOT EXISTS `idx_timeclock_entry` ON `time_clock` (`entry_time`);
CREATE INDEX IF NOT EXISTS `idx_timeclock_exit` ON `time_clock` (`exit_time`);

-- =====================================================
-- Otimizações na tabela clients
-- =====================================================

-- Adiciona índice para busca de clientes com GPS
CREATE INDEX IF NOT EXISTS `idx_clients_location_coords` ON `clients` (`latitude`, `longitude`);

-- Adiciona índice para busca por cidade (usado por técnicos)
CREATE INDEX IF NOT EXISTS `idx_clients_city_name` ON `clients` (`city`, `name`);

-- Adiciona índice para busca por status
CREATE INDEX IF NOT EXISTS `idx_clients_status` ON `clients` (`status`, `active`);

-- Adiciona índice para busca por serial
CREATE INDEX IF NOT EXISTS `idx_clients_serial` ON `clients` (`serial`);

-- =====================================================
-- Otimizações na tabela client_photos
-- =====================================================

-- Índice para carregar fotos de um cliente em lote
CREATE INDEX IF NOT EXISTS `idx_client_photos_cpf_type` ON `client_photos` (`cpf`, `type`);

-- =====================================================
-- Tabela de Estoque de Equipamentos
-- =====================================================
CREATE TABLE IF NOT EXISTS `equipment_inventory` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `serial_number` varchar(100) NOT NULL,
  `model` varchar(150) NOT NULL,
  `brand` varchar(100) DEFAULT NULL,
  `type` enum('router','modem','onu','cpe','antenna','other') DEFAULT 'router',
  `status` enum('available','in_use','maintenance','defective','lost') DEFAULT 'available',
  `location` varchar(150) DEFAULT NULL,
  `purchase_date` date DEFAULT NULL,
  `purchase_price` decimal(10, 2) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `current_user_id` int(11) DEFAULT NULL,
  `current_client_cpf` varchar(11) DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `serial_unique` (`serial_number`),
  KEY `status` (`status`),
  KEY `type` (`type`),
  KEY `current_client` (`current_client_cpf`),
  KEY `current_user` (`current_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Controle de estoque de equipamentos';

-- =====================================================
-- Tabela de Movimentação de Estoque
-- =====================================================
CREATE TABLE IF NOT EXISTS `inventory_movements` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `equipment_id` int(11) NOT NULL,
  `movement_type` enum('in','out','transfer','maintenance','defective','lost') NOT NULL,
  `from_location` varchar(150) DEFAULT NULL,
  `to_location` varchar(150) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `username` varchar(100) DEFAULT NULL,
  `client_cpf` varchar(11) DEFAULT NULL,
  `client_name` varchar(150) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `equipment_id` (`equipment_id`),
  KEY `movement_type` (`movement_type`),
  KEY `created_at` (`created_at`),
  KEY `client_cpf` (`client_cpf`),
  CONSTRAINT `fk_inventory_equipment` FOREIGN KEY (`equipment_id`) REFERENCES `equipment_inventory` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Histórico de movimentações de estoque';

-- =====================================================
-- Tabela de Alertas de Estoque
-- =====================================================
CREATE TABLE IF NOT EXISTS `inventory_alerts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `alert_type` enum('low_stock','no_stock','maintenance_needed','defective_equipment') NOT NULL,
  `equipment_type` varchar(100) DEFAULT NULL,
  `message` text NOT NULL,
  `severity` enum('info','warning','critical') DEFAULT 'warning',
  `resolved` tinyint(1) DEFAULT 0,
  `resolved_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `alert_type` (`alert_type`),
  KEY `severity` (`severity`),
  KEY `resolved` (`resolved`),
  KEY `created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Alertas de estoque';

-- =====================================================
-- Dados iniciais de exemplo (opcional)
-- =====================================================

-- Insere alguns equipamentos de exemplo
INSERT INTO `equipment_inventory` (`serial_number`, `model`, `brand`, `type`, `status`, `location`, `purchase_date`, `purchase_price`) VALUES
('ONU-HUAWEI-001', 'HG8145V5', 'Huawei', 'onu', 'available', 'Estoque Principal', '2024-01-15', 150.00),
('ONU-HUAWEI-002', 'HG8145V5', 'Huawei', 'onu', 'available', 'Estoque Principal', '2024-01-15', 150.00),
('ONU-HUAWEI-003', 'HG8145V5', 'Huawei', 'onu', 'available', 'Estoque Principal', '2024-01-15', 150.00),
('ROUTER-TP-001', 'Archer C6', 'TP-Link', 'router', 'available', 'Estoque Principal', '2024-02-01', 280.00),
('ROUTER-TP-002', 'Archer C6', 'TP-Link', 'router', 'available', 'Estoque Principal', '2024-02-01', 280.00),
('ROUTER-TP-003', 'Archer C6', 'TP-Link', 'router', 'in_use', 'Estoque Principal', '2024-02-01', 280.00),
('CPE-UBI-001', 'NanoStation Loco M5', 'Ubiquiti', 'cpe', 'available', 'Estoque Principal', '2024-01-20', 320.00),
('CPE-UBI-002', 'NanoStation Loco M5', 'Ubiquiti', 'cpe', 'available', 'Estoque Principal', '2024-01-20', 320.00);

-- Registra movimentações iniciais
INSERT INTO `inventory_movements` (`equipment_id`, `movement_type`, `from_location`, `to_location`, `notes`) VALUES
(1, 'in', NULL, 'Estoque Principal', 'Entrada inicial no estoque'),
(2, 'in', NULL, 'Estoque Principal', 'Entrada inicial no estoque'),
(3, 'in', NULL, 'Estoque Principal', 'Entrada inicial no estoque'),
(4, 'in', NULL, 'Estoque Principal', 'Entrada inicial no estoque'),
(5, 'in', NULL, 'Estoque Principal', 'Entrada inicial no estoque'),
(6, 'out', 'Estoque Principal', 'Em uso', 'Equipamento entregue ao cliente'),
(7, 'in', NULL, 'Estoque Principal', 'Entrada inicial no estoque'),
(8, 'in', NULL, 'Estoque Principal', 'Entrada inicial no estoque');

-- =====================================================
-- Consultas úteis
-- =====================================================

-- Consulta: Equipamentos disponíveis por tipo
SELECT type, COUNT(*) as count 
FROM equipment_inventory 
WHERE status = 'available' 
GROUP BY type;

-- Consulta: Movimentações recentes
SELECT m.*, e.serial_number, e.model, e.brand 
FROM inventory_movements m
LEFT JOIN equipment_inventory e ON m.equipment_id = e.id
ORDER BY m.created_at DESC
LIMIT 20;

-- Consulta: Estatísticas de estoque
SELECT 
    status,
    type,
    COUNT(*) as count,
    COUNT(*) * AVG(purchase_price) as total_value
FROM equipment_inventory
GROUP BY status, type;

SELECT 'Script de criação de tabelas executado com sucesso!' as status;