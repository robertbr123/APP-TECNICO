<?php
/**
 * API de Registro de Auditoria
 * Registra todas as ações realizadas pelos técnicos
 */

require_once 'config.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Método não permitido'], 405);
}

$data = getRequestBody();

// Validação básica
if (empty($data['action_type']) || empty($data['action_description'])) {
    jsonResponse(['success' => false, 'message' => 'action_type e action_description são obrigatórios'], 400);
}

try {
    $db = Database::getInstance()->getConnection();
    
    // Obtém informações do usuário se estiver autenticado
    $userData = null;
    $token = getBearerToken();
    
    if ($token) {
        $userData = verifyToken($token);
    }
    
    $userId = $userData['user_id'] ?? null;
    $username = $userData['username'] ?? 'sistema';
    
    // Obtém IP e User-Agent
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
    
    // Prepara os dados
    $actionType = $data['action_type'];
    $actionDescription = $data['action_description'];
    $entityType = $data['entity_type'] ?? null;
    $entityId = $data['entity_id'] ?? null;
    $entityName = $data['entity_name'] ?? null;
    $details = isset($data['details']) ? json_encode($data['details']) : null;
    
    // Insere o log de auditoria
    $stmt = $db->prepare("
        INSERT INTO audit_logs 
        (user_id, username, action_type, action_description, entity_type, entity_id, entity_name, details, ip_address, user_agent)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $result = $stmt->execute([
        $userId,
        $username,
        $actionType,
        $actionDescription,
        $entityType,
        $entityId,
        $entityName,
        $details,
        $ipAddress,
        $userAgent
    ]);
    
    if ($result) {
        jsonResponse([
            'success' => true,
            'message' => 'Log de auditoria registrado com sucesso',
            'log_id' => $db->lastInsertId()
        ]);
    } else {
        jsonResponse(['success' => false, 'message' => 'Erro ao registrar log'], 500);
    }
    
} catch (PDOException $e) {
    jsonResponse(['success' => false, 'message' => 'Erro no banco de dados', 'error' => $e->getMessage()], 500);
}