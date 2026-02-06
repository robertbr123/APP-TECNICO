<?php
/**
 * API de Autenticação
 * Ondeline Tech - App do Técnico
 */

require_once 'config.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Método não permitido'], 405);
}

$data = getRequestBody();

// Validação dos campos
if (empty($data['username']) || empty($data['password'])) {
    jsonResponse(['success' => false, 'message' => 'Usuário e senha são obrigatórios'], 400);
}

$username = trim($data['username']);
$password = $data['password'];

try {
    $db = Database::getInstance()->getConnection();
    
    // Busca o usuário no banco
    $stmt = $db->prepare("SELECT id, username, password, full_name, email, role FROM users WHERE username = ? LIMIT 1");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if (!$user) {
        jsonResponse(['success' => false, 'message' => 'Usuário não encontrado'], 401);
    }

    // Verifica a senha
    // Se a senha está em hash (bcrypt), usa password_verify
    // Se a senha está em texto puro (como no exemplo do dump), compara diretamente
    $passwordValid = false;
    
    if (password_get_info($user['password'])['algo'] !== 0) {
        // Senha está em hash bcrypt
        $passwordValid = password_verify($password, $user['password']);
    } else {
        // Senha em texto puro (não recomendado, mas para compatibilidade)
        $passwordValid = ($password === $user['password']);
    }

    if (!$passwordValid) {
        jsonResponse(['success' => false, 'message' => 'Senha incorreta'], 401);
    }

    // Gera o token JWT
    $token = generateToken($user['id'], $user['username'], $user['role']);

    // Remove a senha do objeto de retorno
    unset($user['password']);

    // Registra o login no log de auditoria
    try {
        $auditStmt = $db->prepare("
            INSERT INTO audit_logs 
            (user_id, username, action_type, action_description, entity_type, entity_id, entity_name, ip_address, user_agent)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $auditStmt->execute([
            $user['id'],
            $user['username'],
            'login',
            'Usuário fez login no sistema',
            'user',
            $user['id'],
            $user['full_name'] ?? $user['username'],
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? null
        ]);
    } catch (Exception $e) {
        // Não falha o login se o auditoria falhar
        error_log('Erro ao registrar auditoria: ' . $e->getMessage());
    }

    jsonResponse([
        'success' => true,
        'message' => 'Login realizado com sucesso',
        'token' => $token,
        'user' => $user
    ]);

} catch (PDOException $e) {
    jsonResponse(['success' => false, 'message' => 'Erro ao processar login', 'error' => $e->getMessage()], 500);
}
