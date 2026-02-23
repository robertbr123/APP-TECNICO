<?php
/**
 * Script de Migração de Senhas - Plaintext para Bcrypt
 * Ondeline Tech - App do Técnico
 * 
 * Execute este script UMA VEZ para migrar todas as senhas
 * em texto puro para hash bcrypt seguro.
 * 
 * Acesso: /api/migrate-passwords.php (somente admin autenticado)
 * Após executar com sucesso, REMOVA ou BLOQUEIE este arquivo.
 */

require_once 'config.php';

// Exige autenticação de admin
$userData = requireAuth();
if ($userData['role'] !== 'admin') {
    jsonResponse(['success' => false, 'message' => 'Apenas administradores podem executar migração'], 403);
}

try {
    $db = Database::getInstance()->getConnection();
    
    // Busca todos os usuários
    $stmt = $db->query("SELECT id, username, password FROM users");
    $users = $stmt->fetchAll();
    
    $migrated = 0;
    $alreadyHashed = 0;
    $errors = [];
    
    foreach ($users as $user) {
        // Verifica se já está em bcrypt
        $info = password_get_info($user['password']);
        
        if ($info['algo'] !== 0) {
            // Já é bcrypt/argon - pula
            $alreadyHashed++;
            continue;
        }
        
        // Senha em plaintext - migra para bcrypt
        $hashedPassword = password_hash($user['password'], PASSWORD_BCRYPT, ['cost' => 12]);
        
        if ($hashedPassword === false) {
            $errors[] = "Falha ao gerar hash para usuário: {$user['username']}";
            continue;
        }
        
        $updateStmt = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
        $updateStmt->execute([$hashedPassword, $user['id']]);
        $migrated++;
    }
    
    // Registra auditoria
    logAudit($db, $userData, 'password_migration', 
        "Migração de senhas: {$migrated} migradas, {$alreadyHashed} já seguras", 
        'system', null, 'migrate-passwords');
    
    jsonResponse([
        'success' => true,
        'message' => "Migração concluída",
        'data' => [
            'total_users' => count($users),
            'migrated' => $migrated,
            'already_hashed' => $alreadyHashed,
            'errors' => $errors
        ]
    ]);
    
} catch (PDOException $e) {
    jsonResponse(['success' => false, 'message' => 'Erro no banco de dados'], 500);
}
