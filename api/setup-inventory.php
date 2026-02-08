<?php
/**
 * Setup Inventory - Verifica e cria tabelas de estoque se necessário
 * Acesse: /api/setup-inventory.php para verificar/criar tabelas
 */

require_once 'config.php';

header('Content-Type: application/json');

try {
    $db = Database::getInstance()->getConnection();
    
    // Cria tabela de equipamentos
    $db->exec("
        CREATE TABLE IF NOT EXISTS `equipment_inventory` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `serial_number` varchar(100) NOT NULL,
          `model` varchar(150) NOT NULL,
          `brand` varchar(100) DEFAULT NULL,
          `type` enum('router','modem','ont','cable','accessory','other') DEFAULT 'router',
          `status` enum('available','in_use','maintenance','defective') DEFAULT 'available',
          `location` varchar(200) DEFAULT 'Estoque Principal',
          `purchase_date` date DEFAULT NULL,
          `purchase_price` decimal(10,2) DEFAULT NULL,
          `warranty_until` date DEFAULT NULL,
          `current_user_id` int(11) DEFAULT NULL,
          `current_client_cpf` varchar(11) DEFAULT NULL,
          `notes` text DEFAULT NULL,
          `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
          `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`),
          UNIQUE KEY `serial_number` (`serial_number`),
          KEY `status` (`status`),
          KEY `type` (`type`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    
    // Cria tabela de movimentações
    $db->exec("
        CREATE TABLE IF NOT EXISTS `inventory_movements` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `equipment_id` int(11) NOT NULL,
          `movement_type` enum('in','out','defective','maintenance') NOT NULL,
          `from_location` varchar(200) DEFAULT NULL,
          `to_location` varchar(200) NOT NULL,
          `user_id` int(11) NOT NULL,
          `username` varchar(100) NOT NULL,
          `client_cpf` varchar(11) DEFAULT NULL,
          `client_name` varchar(150) DEFAULT NULL,
          `notes` text DEFAULT NULL,
          `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`),
          KEY `equipment_id` (`equipment_id`),
          KEY `movement_type` (`movement_type`),
          KEY `user_id` (`user_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    
    // Cria tabela de alertas
    $db->exec("
        CREATE TABLE IF NOT EXISTS `inventory_alerts` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `equipment_id` int(11) DEFAULT NULL,
          `alert_type` enum('low_stock','overdue_return','defective','missing') NOT NULL,
          `severity` enum('low','medium','high','critical') DEFAULT 'medium',
          `title` varchar(200) NOT NULL,
          `description` text DEFAULT NULL,
          `resolved` tinyint(1) DEFAULT 0,
          `resolved_at` timestamp NULL DEFAULT NULL,
          `resolved_by` int(11) DEFAULT NULL,
          `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`),
          KEY `equipment_id` (`equipment_id`),
          KEY `alert_type` (`alert_type`),
          KEY `resolved` (`resolved`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    
    // Insere dados de exemplo
    $stmt = $db->prepare("
        INSERT IGNORE INTO `equipment_inventory` 
        (`serial_number`, `model`, `brand`, `type`, `location`, `purchase_date`, `purchase_price`) 
        VALUES 
        ('HW1020304050', 'HG8145V5', 'Huawei', 'ont', 'Estoque Principal', CURDATE() - INTERVAL 30 DAY, 180.00),
        ('HW2040608010', 'HG8245H', 'Huawei', 'ont', 'Estoque Principal', CURDATE() - INTERVAL 25 DAY, 195.00),
        ('TP1122334455', 'Archer C6', 'TP-Link', 'router', 'Estoque Principal', CURDATE() - INTERVAL 20 DAY, 350.00),
        ('DLM123456789', 'DIR-850L', 'D-Link', 'router', 'Estoque Principal', CURDATE() - INTERVAL 15 DAY, 280.00),
        ('HW9988776655', 'EchoLife HG8247H', 'Huawei', 'ont', 'Estoque Principal', CURDATE() - INTERVAL 10 DAY, 210.00)
    ");
    $stmt->execute();
    
    // Conta registros
    $count = $db->query("SELECT COUNT(*) as total FROM equipment_inventory")->fetch()['total'];
    
    jsonResponse([
        'success' => true,
        'message' => 'Tabelas de estoque verificadas/criadas com sucesso',
        'equipment_count' => $count
    ]);
    
} catch (Exception $e) {
    jsonResponse([
        'success' => false,
        'message' => 'Erro: ' . $e->getMessage()
    ], 500);
}
