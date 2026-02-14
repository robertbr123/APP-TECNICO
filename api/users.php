<?php
/**
 * API de Gerenciamento de Usuários (Admin)
 * Ondeline Tech - App do Técnico
 */

require_once 'config.php';
require_once 'Logger.php';

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$userData = requireAuth();
$isAdmin = ($userData['role'] ?? '') === 'admin';

// Apenas admin pode acessar
if (!$isAdmin) {
    jsonResponse(['success' => false, 'message' => 'Acesso restrito a administradores'], 403);
}

$method = $_SERVER['REQUEST_METHOD'];

try {
    $db = Database::getInstance()->getConnection();

    switch ($method) {
        case 'GET':
            handleGet($db);
            break;
        case 'POST':
            handlePost($db, $userData);
            break;
        case 'PUT':
            handlePut($db, $userData);
            break;
        case 'DELETE':
            handleDelete($db, $userData);
            break;
        default:
            jsonResponse(['success' => false, 'message' => 'Método não permitido'], 405);
    }
} catch (PDOException $e) {
    Logger::logException($e, ['context' => 'database']);
    jsonResponse(['success' => false, 'message' => 'Erro no banco de dados'], 500);
} catch (Exception $e) {
    Logger::logException($e);
    jsonResponse(['success' => false, 'message' => 'Erro interno do servidor'], 500);
}

/**
 * GET - Lista usuários
 */
function handleGet($db) {
    $id = $_GET['id'] ?? null;
    
    // Verifica quais colunas existem na tabela
    $columns = getAvailableColumns($db);
    $selectFields = implode(', ', $columns);
    
    if ($id) {
        // Busca usuário específico
        $stmt = $db->prepare("SELECT $selectFields FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $user = $stmt->fetch();
        
        if (!$user) {
            jsonResponse(['success' => false, 'message' => 'Usuário não encontrado'], 404);
        }
        
        jsonResponse(['success' => true, 'data' => $user]);
    } else {
        // Lista todos os usuários
        $stmt = $db->query("SELECT $selectFields FROM users ORDER BY id DESC");
        $users = $stmt->fetchAll();
        
        jsonResponse(['success' => true, 'data' => $users, 'count' => count($users)]);
    }
}

/**
 * Retorna lista de colunas disponíveis na tabela users
 */
function getAvailableColumns($db) {
    static $cache = null;
    if ($cache !== null) return $cache;
    
    // Colunas base que sempre existem
    $baseColumns = ['id', 'username'];
    
    // Colunas opcionais que queremos incluir se existirem
    $optionalColumns = ['full_name', 'email', 'role', 'city', 'cargo', 'active', 'created_at'];
    
    // Verifica quais colunas existem
    try {
        $stmt = $db->query("SHOW COLUMNS FROM users");
        $existingColumns = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
        
        $availableColumns = $baseColumns;
        foreach ($optionalColumns as $col) {
            if (in_array($col, $existingColumns)) {
                $availableColumns[] = $col;
            }
        }
        
        $cache = $availableColumns;
        return $cache;
    } catch (Exception $e) {
        // Fallback: retorna apenas colunas base
        return $baseColumns;
    }
}

/**
 * POST - Cria novo usuário
 */
function handlePost($db, $adminData) {
    $data = getRequestBody();
    
    // Validações
    if (empty($data['username']) || empty($data['password']) || empty($data['full_name'])) {
        jsonResponse(['success' => false, 'message' => 'Usuário, senha e nome completo são obrigatórios'], 400);
    }
    
    // Verifica se usuário já existe
    $checkStmt = $db->prepare("SELECT id FROM users WHERE username = ?");
    $checkStmt->execute([$data['username']]);
    if ($checkStmt->fetch()) {
        jsonResponse(['success' => false, 'message' => 'Nome de usuário já existe'], 400);
    }
    
    // Hash da senha
    $passwordHash = password_hash($data['password'], PASSWORD_BCRYPT);
    
    $stmt = $db->prepare("
        INSERT INTO users (username, password, full_name, email, role, city, cargo, active, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, 1, NOW())
    ");
    
    $stmt->execute([
        $data['username'],
        $passwordHash,
        $data['full_name'],
        $data['email'] ?? null,
        $data['role'] ?? 'tecnico',
        $data['city'] ?? null,
        $data['cargo'] ?? null
    ]);
    
    $userId = $db->lastInsertId();
    
    Logger::info('Usuário criado', [
        'user_id' => $userId,
        'username' => $data['username'],
        'by_admin' => $adminData['username']
    ]);
    
    jsonResponse([
        'success' => true,
        'message' => 'Usuário criado com sucesso',
        'data' => ['id' => $userId]
    ], 201);
}

/**
 * PUT - Atualiza usuário
 */
function handlePut($db, $adminData) {
    $data = getRequestBody();
    $id = $data['id'] ?? null;
    
    if (!$id) {
        jsonResponse(['success' => false, 'message' => 'ID do usuário é obrigatório'], 400);
    }
    
    // Verifica se usuário existe
    $checkStmt = $db->prepare("SELECT id FROM users WHERE id = ?");
    $checkStmt->execute([$id]);
    if (!$checkStmt->fetch()) {
        jsonResponse(['success' => false, 'message' => 'Usuário não encontrado'], 404);
    }
    
    $updates = [];
    $params = [];
    
    if (isset($data['full_name'])) {
        $updates[] = 'full_name = ?';
        $params[] = $data['full_name'];
    }
    if (isset($data['email'])) {
        $updates[] = 'email = ?';
        $params[] = $data['email'];
    }
    if (isset($data['role'])) {
        $updates[] = 'role = ?';
        $params[] = $data['role'];
    }
    if (isset($data['city'])) {
        $updates[] = 'city = ?';
        $params[] = $data['city'];
    }
    if (isset($data['cargo'])) {
        $updates[] = 'cargo = ?';
        $params[] = $data['cargo'];
    }
    if (isset($data['active'])) {
        $updates[] = 'active = ?';
        $params[] = $data['active'] ? 1 : 0;
    }
    if (!empty($data['password'])) {
        $updates[] = 'password = ?';
        $params[] = password_hash($data['password'], PASSWORD_BCRYPT);
    }
    
    if (empty($updates)) {
        jsonResponse(['success' => false, 'message' => 'Nenhum campo para atualizar'], 400);
    }
    
    $params[] = $id;
    
    $stmt = $db->prepare("UPDATE users SET " . implode(', ', $updates) . " WHERE id = ?");
    $stmt->execute($params);
    
    Logger::info('Usuário atualizado', [
        'user_id' => $id,
        'by_admin' => $adminData['username']
    ]);
    
    jsonResponse(['success' => true, 'message' => 'Usuário atualizado com sucesso']);
}

/**
 * DELETE - Desativa usuário (soft delete)
 */
function handleDelete($db, $adminData) {
    $id = $_GET['id'] ?? null;
    
    if (!$id) {
        jsonResponse(['success' => false, 'message' => 'ID do usuário é obrigatório'], 400);
    }
    
    // Não permite deletar a si mesmo
    if ($id == $adminData['user_id']) {
        jsonResponse(['success' => false, 'message' => 'Não pode excluir seu próprio usuário'], 400);
    }
    
    // Verifica se usuário existe
    $checkStmt = $db->prepare("SELECT id FROM users WHERE id = ?");
    $checkStmt->execute([$id]);
    if (!$checkStmt->fetch()) {
        jsonResponse(['success' => false, 'message' => 'Usuário não encontrado'], 404);
    }
    
    // Soft delete - apenas desativa
    $stmt = $db->prepare("UPDATE users SET active = 0 WHERE id = ?");
    $stmt->execute([$id]);
    
    Logger::info('Usuário desativado', [
        'user_id' => $id,
        'by_admin' => $adminData['username']
    ]);
    
    jsonResponse(['success' => true, 'message' => 'Usuário desativado com sucesso']);
}
