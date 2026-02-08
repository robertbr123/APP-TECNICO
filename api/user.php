<?php
/**
 * API de Perfil do Usuário
 * Ondeline Tech - App do Técnico
 */

require_once 'config.php';

$method = $_SERVER['REQUEST_METHOD'];
$userData = requireAuth();

// Diretório de uploads de perfil
define('PROFILE_UPLOAD_DIR', __DIR__ . '/../uploads/profiles/');
define('PROFILE_MAX_SIZE', 5 * 1024 * 1024); // 5MB

if (!file_exists(PROFILE_UPLOAD_DIR)) {
    mkdir(PROFILE_UPLOAD_DIR, 0755, true);
}

try {
    $db = Database::getInstance()->getConnection();

    // Auto-migrate: adiciona colunas city e photo se não existirem
    try {
        $db->exec("ALTER TABLE users ADD COLUMN city VARCHAR(100) DEFAULT NULL");
    } catch (PDOException $e) {
        // Coluna já existe, ignora
    }
    try {
        $db->exec("ALTER TABLE users ADD COLUMN photo VARCHAR(255) DEFAULT NULL");
    } catch (PDOException $e) {
        // Coluna já existe, ignora
    }

    switch ($method) {
        case 'GET':
            handleGet($db, $userData);
            break;
        case 'PUT':
            handlePut($db, $userData);
            break;
        case 'POST':
            handlePhotoUpload($db, $userData);
            break;
        default:
            jsonResponse(['success' => false, 'message' => 'Método não permitido'], 405);
    }
} catch (PDOException $e) {
    jsonResponse(['success' => false, 'message' => 'Erro no banco de dados', 'error' => $e->getMessage()], 500);
}

/**
 * GET - Retorna dados do perfil
 */
function handleGet($db, $userData) {
    $stmt = $db->prepare("SELECT id, username, full_name, email, role, city, photo FROM users WHERE id = ?");
    $stmt->execute([$userData['user_id']]);
    $user = $stmt->fetch();

    if (!$user) {
        jsonResponse(['success' => false, 'message' => 'Usuário não encontrado'], 404);
    }

    jsonResponse(['success' => true, 'data' => $user]);
}

/**
 * PUT - Atualiza dados do perfil
 */
function handlePut($db, $userData) {
    $data = getRequestBody();

    $allowedFields = ['full_name', 'email', 'city'];
    $updateFields = [];
    $params = [];

    foreach ($allowedFields as $field) {
        if (isset($data[$field])) {
            $updateFields[] = "$field = ?";
            $params[] = $data[$field];
        }
    }

    if (empty($updateFields)) {
        jsonResponse(['success' => false, 'message' => 'Nenhum campo para atualizar'], 400);
    }

    $params[] = $userData['user_id'];
    $sql = "UPDATE users SET " . implode(', ', $updateFields) . " WHERE id = ?";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);

    // Retorna dados atualizados
    $stmt = $db->prepare("SELECT id, username, full_name, email, role, city, photo FROM users WHERE id = ?");
    $stmt->execute([$userData['user_id']]);
    $user = $stmt->fetch();

    jsonResponse(['success' => true, 'message' => 'Perfil atualizado com sucesso', 'data' => $user]);
}

/**
 * POST - Upload de foto de perfil
 */
function handlePhotoUpload($db, $userData) {
    $data = getRequestBody();
    $base64 = $data['photo'] ?? null;

    if (!$base64) {
        jsonResponse(['success' => false, 'message' => 'Foto é obrigatória'], 400);
    }

    // Remove prefixo data:image/...;base64,
    if (preg_match('/^data:image\/(\w+);base64,/', $base64, $matches)) {
        $extension = $matches[1];
        $base64 = substr($base64, strpos($base64, ',') + 1);
    } else {
        $extension = 'jpg';
    }

    $imageData = base64_decode($base64);
    if ($imageData === false) {
        jsonResponse(['success' => false, 'message' => 'Imagem inválida'], 400);
    }

    if (strlen($imageData) > PROFILE_MAX_SIZE) {
        jsonResponse(['success' => false, 'message' => 'Imagem muito grande (máx 5MB)'], 400);
    }

    // Remove foto anterior se existir
    $stmt = $db->prepare("SELECT photo FROM users WHERE id = ?");
    $stmt->execute([$userData['user_id']]);
    $oldPhoto = $stmt->fetch()['photo'];

    if ($oldPhoto) {
        $oldPath = __DIR__ . '/../' . ltrim($oldPhoto, '/');
        if (file_exists($oldPath)) {
            unlink($oldPath);
        }
    }

    // Salva nova foto
    $filename = 'profile_' . $userData['user_id'] . '_' . time() . '.' . $extension;
    $filepath = PROFILE_UPLOAD_DIR . $filename;

    if (file_put_contents($filepath, $imageData) === false) {
        jsonResponse(['success' => false, 'message' => 'Erro ao salvar imagem'], 500);
    }

    // Atualiza no banco
    $photoUrl = '/uploads/profiles/' . $filename;
    $stmt = $db->prepare("UPDATE users SET photo = ? WHERE id = ?");
    $stmt->execute([$photoUrl, $userData['user_id']]);

    jsonResponse([
        'success' => true,
        'message' => 'Foto atualizada com sucesso',
        'data' => ['photo' => $photoUrl]
    ]);
}
