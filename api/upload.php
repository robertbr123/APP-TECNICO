<?php
/**
 * API de Upload de Fotos
 * Ondeline Tech - App do Técnico
 * 
 * As fotos são salvas na pasta /uploads/ e o caminho é salvo no MySQL
 */

require_once 'config.php';

$method = $_SERVER['REQUEST_METHOD'];
$userData = requireAuth();

// Configurações de upload
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('MAX_FILE_SIZE', 10 * 1024 * 1024); // 10MB
define('ALLOWED_TYPES', ['image/jpeg', 'image/png', 'image/webp', 'image/heic']);

// Cria pasta de uploads se não existir
if (!file_exists(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0755, true);
}

try {
    $db = Database::getInstance()->getConnection();

    switch ($method) {
        case 'POST':
            handleUpload($db, $userData);
            break;
        case 'GET':
            handleGet($db);
            break;
        case 'DELETE':
            handleDelete($db);
            break;
        default:
            jsonResponse(['success' => false, 'message' => 'Método não permitido'], 405);
    }
} catch (Exception $e) {
    jsonResponse(['success' => false, 'message' => 'Erro: ' . $e->getMessage()], 500);
}

/**
 * Upload de foto
 */
function handleUpload($db, $userData) {
    // Verifica se é upload via FormData ou Base64
    if (isset($_FILES['photo'])) {
        // Upload tradicional via FormData
        handleFileUpload($db, $userData);
    } else {
        // Upload via Base64 (câmera do celular)
        handleBase64Upload($db, $userData);
    }
}

/**
 * Upload via arquivo (FormData)
 */
function handleFileUpload($db, $userData) {
    $file = $_FILES['photo'];
    $cpf = $_POST['cpf'] ?? null;
    $type = $_POST['type'] ?? 'other'; // router, cabling, signal, other

    if (!$cpf) {
        jsonResponse(['success' => false, 'message' => 'CPF é obrigatório'], 400);
    }

    // Validações
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errors = [
            UPLOAD_ERR_INI_SIZE => 'Arquivo muito grande',
            UPLOAD_ERR_FORM_SIZE => 'Arquivo muito grande',
            UPLOAD_ERR_PARTIAL => 'Upload incompleto',
            UPLOAD_ERR_NO_FILE => 'Nenhum arquivo enviado',
        ];
        jsonResponse(['success' => false, 'message' => $errors[$file['error']] ?? 'Erro no upload'], 400);
    }

    if ($file['size'] > MAX_FILE_SIZE) {
        jsonResponse(['success' => false, 'message' => 'Arquivo muito grande (máx 10MB)'], 400);
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mimeType, ALLOWED_TYPES)) {
        jsonResponse(['success' => false, 'message' => 'Tipo de arquivo não permitido'], 400);
    }

    // Gera nome único
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION) ?: 'jpg';
    $filename = $cpf . '_' . $type . '_' . time() . '_' . uniqid() . '.' . $extension;
    $filepath = UPLOAD_DIR . $filename;

    // Move o arquivo
    if (!move_uploaded_file($file['tmp_name'], $filepath)) {
        jsonResponse(['success' => false, 'message' => 'Erro ao salvar arquivo'], 500);
    }

    // Salva no banco
    savePhotoRecord($db, $cpf, $filename, $type, $userData['user_id']);

    jsonResponse([
        'success' => true,
        'message' => 'Foto enviada com sucesso',
        'data' => [
            'filename' => $filename,
            'url' => '/uploads/' . $filename
        ]
    ]);
}

/**
 * Upload via Base64 (câmera)
 */
function handleBase64Upload($db, $userData) {
    $data = getRequestBody();
    
    $base64 = $data['photo'] ?? null;
    $cpf = $data['cpf'] ?? null;
    $type = $data['type'] ?? 'other';

    if (!$cpf || !$base64) {
        jsonResponse(['success' => false, 'message' => 'CPF e foto são obrigatórios'], 400);
    }

    // Remove prefixo data:image/...;base64,
    if (preg_match('/^data:image\/(\w+);base64,/', $base64, $matches)) {
        $extension = $matches[1];
        $base64 = substr($base64, strpos($base64, ',') + 1);
    } else {
        $extension = 'jpg';
    }

    // Decodifica
    $imageData = base64_decode($base64);
    if ($imageData === false) {
        jsonResponse(['success' => false, 'message' => 'Imagem inválida'], 400);
    }

    // Verifica tamanho
    if (strlen($imageData) > MAX_FILE_SIZE) {
        jsonResponse(['success' => false, 'message' => 'Imagem muito grande (máx 10MB)'], 400);
    }

    // Gera nome único
    $filename = $cpf . '_' . $type . '_' . time() . '_' . uniqid() . '.' . $extension;
    $filepath = UPLOAD_DIR . $filename;

    // Salva o arquivo
    if (file_put_contents($filepath, $imageData) === false) {
        jsonResponse(['success' => false, 'message' => 'Erro ao salvar imagem'], 500);
    }

    // Salva no banco
    savePhotoRecord($db, $cpf, $filename, $type, $userData['user_id']);

    jsonResponse([
        'success' => true,
        'message' => 'Foto enviada com sucesso',
        'data' => [
            'filename' => $filename,
            'url' => '/uploads/' . $filename
        ]
    ]);
}

/**
 * Salva registro da foto no banco
 */
function savePhotoRecord($db, $cpf, $filename, $type, $userId) {
    // Cria tabela se não existir
    $db->exec("
        CREATE TABLE IF NOT EXISTS `client_photos` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `cpf` varchar(11) NOT NULL,
            `filename` varchar(255) NOT NULL,
            `type` enum('router','cabling','signal','other') DEFAULT 'other',
            `uploaded_by` int(11) DEFAULT NULL,
            `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `cpf` (`cpf`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci
    ");

    $stmt = $db->prepare("
        INSERT INTO client_photos (cpf, filename, type, uploaded_by)
        VALUES (?, ?, ?, ?)
    ");
    $stmt->execute([preg_replace('/\D/', '', $cpf), $filename, $type, $userId]);
}

/**
 * Busca fotos de um cliente
 */
function handleGet($db) {
    $cpf = $_GET['cpf'] ?? null;

    if (!$cpf) {
        jsonResponse(['success' => false, 'message' => 'CPF é obrigatório'], 400);
    }

    $cpf = preg_replace('/\D/', '', $cpf);

    // Verifica se a tabela existe
    $tableExists = $db->query("SHOW TABLES LIKE 'client_photos'")->rowCount() > 0;
    
    if (!$tableExists) {
        jsonResponse(['success' => true, 'data' => []]);
    }

    $stmt = $db->prepare("
        SELECT id, filename, type, created_at,
               CONCAT('/uploads/', filename) as url
        FROM client_photos 
        WHERE cpf = ?
        ORDER BY created_at DESC
    ");
    $stmt->execute([$cpf]);
    $photos = $stmt->fetchAll();

    jsonResponse(['success' => true, 'data' => $photos]);
}

/**
 * Exclui uma foto
 */
function handleDelete($db) {
    $id = $_GET['id'] ?? null;

    if (!$id) {
        jsonResponse(['success' => false, 'message' => 'ID é obrigatório'], 400);
    }

    // Busca a foto
    $stmt = $db->prepare("SELECT filename FROM client_photos WHERE id = ?");
    $stmt->execute([$id]);
    $photo = $stmt->fetch();

    if (!$photo) {
        jsonResponse(['success' => false, 'message' => 'Foto não encontrada'], 404);
    }

    // Remove o arquivo
    $filepath = UPLOAD_DIR . $photo['filename'];
    if (file_exists($filepath)) {
        unlink($filepath);
    }

    // Remove do banco
    $stmt = $db->prepare("DELETE FROM client_photos WHERE id = ?");
    $stmt->execute([$id]);

    jsonResponse(['success' => true, 'message' => 'Foto excluída com sucesso']);
}
