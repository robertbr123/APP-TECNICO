<?php
/**
 * API de Autenticação
 * Ondeline Tech - App do Técnico
 */

require_once 'config.php';
require_once 'Logger.php';

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

    // Verifica se a coluna 'active' existe (necessário para verificar usuários desativados)
    $stmt = $db->prepare("SELECT id, username, password, full_name, email, role, city, photo, active FROM users WHERE username = ? LIMIT 1");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if (!$user) {
        jsonResponse(['success' => false, 'message' => 'Usuário ou senha incorretos'], 401);
    }

    // Verifica se o usuário está ativo
    if (isset($user['active']) && $user['active'] == 0) {
        jsonResponse(['success' => false, 'message' => 'Usuário desativado. Contate o administrador.'], 403);
    }

    // Verifica a senha com bcrypt
    $passwordValid = false;
    $needsRehash = false;

    if (password_get_info($user['password'])['algo'] !== 0) {
        // Senha em hash bcrypt - verificação segura
        $passwordValid = password_verify($password, $user['password']);
        
        // Verifica se precisa re-hash (custo atualizado)
        if ($passwordValid && password_needs_rehash($user['password'], PASSWORD_BCRYPT, ['cost' => 12])) {
            $needsRehash = true;
        }
    } else {
        // Senha legada em texto puro - verifica e migra automaticamente
        $passwordValid = ($password === $user['password']);
        if ($passwordValid) {
            $needsRehash = true;
        }
    }

    if (!$passwordValid) {
        jsonResponse(['success' => false, 'message' => 'Usuário ou senha incorretos'], 401);
    }

    // Migra senha para bcrypt automaticamente se necessário
    if ($needsRehash) {
        try {
            $newHash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
            $updateStmt = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
            $updateStmt->execute([$newHash, $user['id']]);
            Logger::info('Senha migrada para bcrypt', ['user_id' => $user['id'], 'username' => $user['username']]);
        } catch (Exception $e) {
            // Não falha o login se o rehash falhar
            Logger::warning('Falha ao migrar senha', ['user_id' => $user['id'], 'error' => $e->getMessage()]);
        }
    }

    // Gera o token JWT
    $token = generateToken($user['id'], $user['username'], $user['role']);

    // Remove a senha do objeto de retorno
    unset($user['password']);

    // Registra o login no log de auditoria
    logAudit($db, [
        'user_id' => $user['id'],
        'username' => $user['username']
    ], 'login', 'Usuário fez login no sistema', 'user', $user['id'], $user['full_name'] ?? $user['username']);

    jsonResponse([
        'success' => true,
        'message' => 'Login realizado com sucesso',
        'data' => [
            'token' => $token,
            'user' => $user
        ]
    ]);

} catch (PDOException $e) {
    Logger::logException($e, ['context' => 'login']);
    jsonResponse(['success' => false, 'message' => 'Erro ao processar login'], 500);
}
