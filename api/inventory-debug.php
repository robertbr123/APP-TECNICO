<?php
/**
 * Debug do Inventory - Verifica tabelas e conexão
 */

require_once 'config.php';

header('Content-Type: application/json');

try {
    $db = Database::getInstance()->getConnection();
    
    // Verifica se as tabelas existem
    $tables = ['equipment_inventory', 'inventory_movements', 'inventory_alerts'];
    $result = [];
    
    foreach ($tables as $table) {
        $stmt = $db->query("SHOW TABLES LIKE '$table'");
        $exists = $stmt->rowCount() > 0;
        $result[$table] = $exists ? 'existe' : 'não existe';
        
        if ($exists) {
            // Conta registros
            $countStmt = $db->query("SELECT COUNT(*) as total FROM $table");
            $count = $countStmt->fetch()['total'];
            $result[$table . '_count'] = $count;
        }
    }
    
    // Testa uma query simples
    try {
        $testStmt = $db->query("
            SELECT ei.*, c.name as client_name, u.username as current_user
            FROM equipment_inventory ei
            LEFT JOIN clients c ON ei.current_client_cpf = c.cpf
            LEFT JOIN users u ON ei.current_user_id = u.id
            LIMIT 1
        ");
        $result['query_test'] = 'sucesso';
    } catch (Exception $e) {
        $result['query_test'] = 'erro: ' . $e->getMessage();
    }
    
    jsonResponse([
        'success' => true,
        'tables' => $result,
        'php_version' => phpversion()
    ]);
    
} catch (Exception $e) {
    jsonResponse([
        'success' => false,
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ], 500);
}
