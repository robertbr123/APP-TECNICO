<?php
/**
 * Setup Checklist V2 - Com Sistema de Aprovação
 * Acesse: /api/setup-checklist-v2.php
 */

require_once 'config.php';

header('Content-Type: application/json');

try {
    $db = Database::getInstance()->getConnection();
    
    // Cria tabela de checklists com sistema de aprovação
    $db->exec("
        CREATE TABLE IF NOT EXISTS `installation_checklists` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `client_cpf` VARCHAR(11) NOT NULL,
            `client_name` VARCHAR(150) NOT NULL,
            `technician_id` INT NOT NULL,
            `technician_name` VARCHAR(100) NOT NULL,
            `installation_type` ENUM('new', 'migration', 'repair', 'maintenance') DEFAULT 'new',
            `status` ENUM('pending', 'in_progress', 'completed', 'cancelled') DEFAULT 'pending',
            `approval_status` ENUM('pending', 'pending_approval', 'approved', 'rejected') DEFAULT 'pending',
            `approved_by` INT NULL,
            `approved_at` TIMESTAMP NULL,
            `rejection_reason` TEXT NULL,
            `rejection_notes` TEXT NULL,
            `started_at` TIMESTAMP NULL,
            `completed_at` TIMESTAMP NULL,
            `notes` TEXT,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX `idx_client_cpf` (`client_cpf`),
            INDEX `idx_technician` (`technician_id`),
            INDEX `idx_status` (`status`),
            INDEX `idx_approval_status` (`approval_status`),
            INDEX `idx_approved_by` (`approved_by`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    
    // Cria tabela de itens do checklist
    $db->exec("
        CREATE TABLE IF NOT EXISTS `checklist_items` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `checklist_id` INT NOT NULL,
            `task_name` VARCHAR(255) NOT NULL,
            `task_category` ENUM('pre_installation', 'installation', 'configuration', 'testing', 'documentation') DEFAULT 'installation',
            `is_required` BOOLEAN DEFAULT TRUE,
            `is_completed` BOOLEAN DEFAULT FALSE,
            `completed_at` TIMESTAMP NULL,
            `completed_by` INT NULL,
            `notes` TEXT,
            `photo_url` VARCHAR(255),
            `order_index` INT DEFAULT 0,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX `idx_checklist` (`checklist_id`),
            INDEX `idx_category` (`task_category`),
            INDEX `idx_completed` (`is_completed`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    
    // Cria tabela de templates
    $db->exec("
        CREATE TABLE IF NOT EXISTS `checklist_templates` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `task_name` VARCHAR(255) NOT NULL,
            `task_category` ENUM('pre_installation', 'installation', 'configuration', 'testing', 'documentation') DEFAULT 'installation',
            `is_required` BOOLEAN DEFAULT TRUE,
            `order_index` INT DEFAULT 0,
            `is_active` BOOLEAN DEFAULT TRUE,
            `applicable_types` VARCHAR(100) DEFAULT 'new,migration,repair,maintenance',
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX `idx_category` (`task_category`),
            INDEX `idx_active` (`is_active`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    
    // Cria tabela de histórico de aprovações
    $db->exec("
        CREATE TABLE IF NOT EXISTS `checklist_approval_history` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `checklist_id` INT NOT NULL,
            `action` ENUM('submitted', 'approved', 'rejected', 'deleted', 'reopened') NOT NULL,
            `action_by` INT NOT NULL,
            `action_by_name` VARCHAR(100) NOT NULL,
            `action_by_role` ENUM('admin', 'tecnico') NOT NULL,
            `notes` TEXT,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX `idx_checklist` (`checklist_id`),
            INDEX `idx_action` (`action`),
            INDEX `idx_created` (`created_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    
    // Insere templates padrão
    $templates = [
        // Pré-instalação - Nova Instalação
        ['Verificar sinal na caixa de atendimento', 'pre_installation', 1, 1, 'new,migration'],
        ['Conferir materiais e equipamentos', 'pre_installation', 1, 2, 'new,migration,repair'],
        ['Verificar rota de cabeamento', 'pre_installation', 1, 3, 'new,migration'],
        ['Identificar ponto de entrada', 'pre_installation', 1, 4, 'new,migration'],
        ['Cadastrar cliente no sistema', 'pre_installation', 1, 0, 'new'],
        ['Verificar viabilidade técnica', 'pre_installation', 1, 0, 'new'],
        
        // Reparo/Manutenção
        ['Diagnosticar problema reportado', 'pre_installation', 1, 0, 'repair,maintenance'],
        ['Verificar cabos e conexões', 'pre_installation', 1, 1, 'repair,maintenance'],
        ['Testar sinal na CTO', 'pre_installation', 1, 2, 'repair,maintenance'],
        
        // Instalação física
        ['Instalar caixa de proteção', 'installation', 1, 10, 'new,migration'],
        ['Passar cabo de fibra', 'installation', 1, 11, 'new,migration'],
        ['Conectar fibra na CTO', 'installation', 1, 12, 'new,migration'],
        ['Instalar roteador/ONU', 'installation', 1, 13, 'new,migration,repair'],
        ['Organizar cabos', 'installation', 1, 14, 'new,migration,repair'],
        ['Substituir equipamento defeituoso', 'installation', 1, 10, 'repair'],
        
        // Configuração
        ['Configurar PPPoE', 'configuration', 1, 20, 'new,migration,repair'],
        ['Alterar senha WiFi', 'configuration', 1, 21, 'new,migration'],
        ['Configurar 2.4GHz e 5GHz', 'configuration', 1, 22, 'new,migration'],
        ['Verificar sincronização', 'configuration', 1, 23, 'new,migration,repair,maintenance'],
        ['Atualizar firmware', 'configuration', 0, 24, 'maintenance'],
        ['Otimizar canais WiFi', 'configuration', 0, 25, 'maintenance'],
        
        // Testes
        ['Testar velocidade download', 'testing', 1, 30, 'new,migration,repair,maintenance'],
        ['Testar velocidade upload', 'testing', 1, 31, 'new,migration,repair,maintenance'],
        ['Verificar latência (ping)', 'testing', 1, 32, 'new,migration,repair,maintenance'],
        ['Testar estabilidade', 'testing', 1, 33, 'new,migration,repair,maintenance'],
        ['Verificar alcance WiFi', 'testing', 1, 34, 'new,migration,repair'],
        
        // Documentação
        ['Foto do roteador instalado', 'documentation', 1, 40, 'new,migration,repair'],
        ['Foto do cabeamento', 'documentation', 1, 41, 'new,migration,repair'],
        ['Foto do sinal na ONU', 'documentation', 1, 42, 'new,migration,repair,maintenance'],
        ['Orientar cliente', 'documentation', 1, 43, 'new,migration,repair'],
        ['Entregar senha anotada', 'documentation', 1, 44, 'new,migration'],
        ['Tutorial inicial', 'documentation', 1, 45, 'new'],
        ['Relatório de serviço', 'documentation', 1, 46, 'repair,maintenance'],
    ];
    
    $stmt = $db->prepare("
        INSERT IGNORE INTO checklist_templates 
        (task_name, task_category, is_required, order_index, applicable_types) 
        VALUES (?, ?, ?, ?, ?)
    ");
    
    foreach ($templates as $t) {
        $stmt->execute($t);
    }
    
    // Conta registros
    $count = $db->query("SELECT COUNT(*) as total FROM checklist_templates WHERE is_active = 1")->fetch()['total'];
    
    jsonResponse([
        'success' => true,
        'message' => 'Sistema de checklist V2 configurado com sucesso!',
        'templates_count' => $count,
        'features' => [
            'Sistema de aprovação por administrador',
            'Filtros por tipo de instalação',
            'Histórico de aprovações',
            'Notificações de status'
        ]
    ]);
    
} catch (Exception $e) {
    jsonResponse([
        'success' => false,
        'message' => 'Erro: ' . $e->getMessage()
    ], 500);
}
