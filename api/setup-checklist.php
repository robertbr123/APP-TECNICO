<?php
/**
 * Setup Checklist - Verifica e cria tabelas de checklist se necessário
 * Acesse: /api/setup-checklist.php para verificar/criar tabelas
 */

require_once 'config.php';

header('Content-Type: application/json');

try {
    $db = Database::getInstance()->getConnection();
    
    // Cria tabela de checklists
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
    
    // Verifica se a coluna applicable_types existe
    try {
        $db->query("SELECT applicable_types FROM checklist_templates LIMIT 1");
    } catch (Exception $e) {
        // Coluna não existe, adiciona
        $db->exec("ALTER TABLE checklist_templates ADD COLUMN applicable_types VARCHAR(100) DEFAULT 'new,migration,repair,maintenance'");
    }
    
    // Verifica se as colunas de aprovação existem
    try {
        $db->query("SELECT approval_status FROM installation_checklists LIMIT 1");
    } catch (Exception $e) {
        $db->exec("
            ALTER TABLE installation_checklists 
            ADD COLUMN approval_status ENUM('pending', 'pending_approval', 'approved', 'rejected') DEFAULT 'pending',
            ADD COLUMN approved_by INT NULL,
            ADD COLUMN approved_at TIMESTAMP NULL,
            ADD COLUMN rejection_reason TEXT NULL,
            ADD COLUMN rejection_notes TEXT NULL,
            ADD INDEX idx_approval_status (approval_status),
            ADD INDEX idx_approved_by (approved_by)
        ");
    }
    
    // Insere templates padrão (todos opcionais - is_required = 0)
    // Cada template tem tipos aplicáveis específicos para evitar duplicação
    $templates = [
        // Pré-instalação
        ['Verificar sinal na caixa de atendimento', 'pre_installation', 0, 1, 'new,migration,repair,maintenance'],
        ['Conferir materiais e equipamentos', 'pre_installation', 0, 2, 'new,migration,repair,maintenance'],
        ['Verificar rota de cabeamento', 'pre_installation', 0, 3, 'new,migration'],
        ['Identificar ponto de entrada', 'pre_installation', 0, 4, 'new,migration'],
        ['Diagnosticar problema', 'pre_installation', 0, 5, 'repair,maintenance'],
        
        // Instalação física
        ['Instalar caixa de proteção', 'installation', 0, 10, 'new,migration'],
        ['Passar cabo de fibra', 'installation', 0, 11, 'new,migration'],
        ['Conectar fibra na CTO', 'installation', 0, 12, 'new,migration'],
        ['Instalar roteador/ONU', 'installation', 0, 13, 'new,migration,repair'],
        ['Organizar cabos', 'installation', 0, 14, 'new,migration,repair'],
        ['Verificar cabos e conexões', 'installation', 0, 15, 'repair,maintenance'],
        
        // Configuração
        ['Configurar PPPoE', 'configuration', 0, 20, 'new,migration,repair'],
        ['Alterar senha WiFi', 'configuration', 0, 21, 'new,migration'],
        ['Configurar 2.4GHz e 5GHz', 'configuration', 0, 22, 'new,migration'],
        ['Verificar sincronização', 'configuration', 0, 23, 'new,migration,repair,maintenance'],
        ['Atualizar firmware', 'configuration', 0, 24, 'maintenance'],
        
        // Testes
        ['Testar velocidade download', 'testing', 0, 30, 'new,migration,repair,maintenance'],
        ['Testar velocidade upload', 'testing', 0, 31, 'new,migration,repair,maintenance'],
        ['Verificar latência (ping)', 'testing', 0, 32, 'new,migration,repair,maintenance'],
        ['Testar estabilidade', 'testing', 0, 33, 'new,migration,repair,maintenance'],
        ['Verificar alcance WiFi', 'testing', 0, 34, 'new,migration,repair'],
        
        // Documentação
        ['Foto do roteador instalado', 'documentation', 0, 40, 'new,migration,repair'],
        ['Foto do cabeamento', 'documentation', 0, 41, 'new,migration,repair'],
        ['Foto do sinal na ONU', 'documentation', 0, 42, 'new,migration,repair,maintenance'],
        ['Orientar cliente', 'documentation', 0, 43, 'new,migration,repair'],
        ['Entregar senha anotada', 'documentation', 0, 44, 'new,migration'],
    ];
    
    // Verifica se já existem templates
    $existingCount = $db->query("SELECT COUNT(*) as total FROM checklist_templates")->fetch()['total'];
    
    // Se não existir nenhum template, insere os padrões
    if ($existingCount == 0) {
        $stmt = $db->prepare("
            INSERT INTO checklist_templates 
            (task_name, task_category, is_required, order_index, applicable_types) 
            VALUES (?, ?, ?, ?, ?)
        ");
        
        foreach ($templates as $t) {
            $stmt->execute($t);
        }
    }
    
    // NÃO DELETA templates existentes criados pelos administradores
    
    // Atualiza todos os templates existentes para não obrigatórios
    $db->exec("UPDATE checklist_templates SET is_required = 0");
    
    // Atualiza todos os itens existentes para não obrigatórios
    $db->exec("UPDATE checklist_items SET is_required = 0");
    
    // Conta registros
    $count = $db->query("SELECT COUNT(*) as total FROM checklist_templates WHERE is_active = 1")->fetch()['total'];
    
    jsonResponse([
        'success' => true,
        'message' => 'Tabelas de checklist verificadas/criadas com sucesso',
        'templates_count' => $count
    ]);
    
} catch (Exception $e) {
    jsonResponse([
        'success' => false,
        'message' => 'Erro: ' . $e->getMessage()
    ], 500);
}
