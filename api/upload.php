<?php
/**
 * API de Upload de Fotos - Melhorada
 * Ondeline Tech - App do Técnico
 * 
 * Melhorias:
 * - Validação robusta de arquivos
 * - Verificação de MIME type real
 * - Limite de dimensões
 * - Logs estruturados
 */

require_once 'config.php';
require_once 'Logger.php';
require_once 'Validator.php';

$method = $_SERVER['REQUEST_METHOD'];
$userData = requireAuth();

Logger::logRequest('upload.php', $method, $_REQUEST);

// Configurações de upload
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('MAX_FILE_SIZE', 10 * 1024 * 1024); // 10MB
define('MAX_IMAGE_WIDTH', 4000);
define('MAX_IMAGE_HEIGHT', 4000);
define('ALLOWED_TYPES', ['image/jpeg', 'image/png', 'image/webp', 'image/heic', 'image/heif']);
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'webp', 'heic', 'heif']);

// Cria pasta de uploads se não existir
if (!file_exists(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0755, true);
}

// Protege a pasta com .htaccess
$htaccess = UPLOAD_DIR . '.htaccess';
if (!file_exists($htaccess)) {
    file_put_contents($htaccess, "Options -Indexes\n<FilesMatch '\.(php|php\\.|php3|php4|phtml|pl|py|jsp|asp|aspx|cgi|sh|bash)$'>\nOrder allow,deny\nDeny from all\n</FilesMatch>");
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
            handleDelete($db, $userData);
            break;
        default:
            Logger::warning('Método não permitido', ['method' => $method]);
            jsonResponse(['success' => false, 'message' => 'Método não permitido'], 405);
    }
} catch (Exception $e) {
    Logger::logException($e);
    jsonResponse(['success' => false, 'message' => 'Erro interno do servidor'], 500);
}

/**
 * Upload de foto
 */
function handleUpload($db, $userData) {
    // Verifica se é upload via FormData ou Base64
    if (isset($_FILES['photo'])) {
        handleFileUpload($db, $userData);
    } else {
        handleBase64Upload($db, $userData);
    }
}

/**
 * Upload via arquivo (FormData) - com validação aprimorada
 */
function handleFileUpload($db, $userData) {
    $file = $_FILES['photo'];
    $cpf = $_POST['cpf'] ?? null;
    $type = $_POST['type'] ?? 'other';

    if (!$cpf) {
        jsonResponse(['success' => false, 'message' => 'CPF é obrigatório'], 400);
    }

    // Valida CPF
    $cpfValidation = Validator::validateCPF($cpf);
    if (!$cpfValidation['valid']) {
        jsonResponse(['success' => false, 'message' => $cpfValidation['message']], 400);
    }
    $cpf = $cpfValidation['clean'];

    // Valida upload usando Validator
    $validation = Validator::validateUpload($file, [
        'maxSize' => MAX_FILE_SIZE,
        'allowedTypes' => ALLOWED_TYPES,
        'allowedExtensions' => ALLOWED_EXTENSIONS
    ]);

    if (!$validation['valid']) {
        Logger::warning('Upload rejeitado', ['error' => $validation['message'], 'cpf' => $cpf]);
        jsonResponse(['success' => false, 'message' => $validation['message']], 400);
    }

    // Verifica dimensões da imagem
    $imageInfo = getimagesize($file['tmp_name']);
    if ($imageInfo) {
        list($width, $height) = $imageInfo;
        
        if ($width > MAX_IMAGE_WIDTH || $height > MAX_IMAGE_HEIGHT) {
            Logger::warning('Imagem muito grande', [
                'width' => $width, 
                'height' => $height,
                'cpf' => $cpf
            ]);
            jsonResponse([
                'success' => false, 
                'message' => "Imagem muito grande (máx: " . MAX_IMAGE_WIDTH . "x" . MAX_IMAGE_HEIGHT . ")"
            ], 400);
        }
    }

    // Gera nome único seguro
    $extension = $validation['extension'];
    $filename = generateSafeFilename($cpf, $type, $extension);
    $filepath = UPLOAD_DIR . $filename;

    // Move o arquivo
    if (!move_uploaded_file($file['tmp_name'], $filepath)) {
        Logger::error('Erro ao mover arquivo', ['filepath' => $filepath]);
        jsonResponse(['success' => false, 'message' => 'Erro ao salvar arquivo'], 500);
    }

    // Verifica se o arquivo foi salvo corretamente
    if (!file_exists($filepath) || filesize($filepath) === 0) {
        Logger::error('Arquivo não salvo corretamente', ['filepath' => $filepath]);
        jsonResponse(['success' => false, 'message' => 'Erro ao salvar arquivo'], 500);
    }

    // Salva no banco
    savePhotoRecord($db, $cpf, $filename, $type, $userData['user_id']);

    Logger::info('Upload concluído', [
        'cpf' => $cpf,
        'type' => $type,
        'filename' => $filename,
        'size' => filesize($filepath)
    ]);

    jsonResponse([
        'success' => true,
        'message' => 'Foto enviada com sucesso',
        'data' => [
            'filename' => $filename,
            'url' => '/uploads/' . $filename,
            'type' => $validation['mime_type'],
            'width' => $width ?? null,
            'height' => $height ?? null
        ]
    ]);
}

/**
 * Upload via Base64 (câmera) - com validação aprimorada
 */
function handleBase64Upload($db, $userData) {
    $data = getRequestBody();
    
    $base64 = $data['photo'] ?? null;
    $cpf = $data['cpf'] ?? null;
    $type = $data['type'] ?? 'other';

    if (!$cpf || !$base64) {
        jsonResponse(['success' => false, 'message' => 'CPF e foto são obrigatórios'], 400);
    }

    // Valida CPF
    $cpfValidation = Validator::validateCPF($cpf);
    if (!$cpfValidation['valid']) {
        jsonResponse(['success' => false, 'message' => $cpfValidation['message']], 400);
    }
    $cpf = $cpfValidation['clean'];

    // Valida base64
    $validation = Validator::validateBase64Image($base64, [
        'maxSize' => MAX_FILE_SIZE,
        'allowedTypes' => ['jpeg', 'jpg', 'png', 'webp']
    ]);

    if (!$validation['valid']) {
        Logger::warning('Base64 inválido', ['error' => $validation['message'], 'cpf' => $cpf]);
        jsonResponse(['success' => false, 'message' => $validation['message']], 400);
    }

    // Verifica dimensões
    if ($validation['width'] > MAX_IMAGE_WIDTH || $validation['height'] > MAX_IMAGE_HEIGHT) {
        jsonResponse([
            'success' => false, 
            'message' => "Imagem muito grande (máx: " . MAX_IMAGE_WIDTH . "x" . MAX_IMAGE_HEIGHT . ")"
        ], 400);
    }

    // Extrai dados base64
    $data = substr($base64, strpos($base64, ',') + 1);
    $imageData = base64_decode($data);

    // Gera nome único
    $extension = $validation['type'] === 'jpeg' ? 'jpg' : $validation['type'];
    $filename = generateSafeFilename($cpf, $type, $extension);
    $filepath = UPLOAD_DIR . $filename;

    // Salva o arquivo
    if (file_put_contents($filepath, $imageData) === false) {
        Logger::error('Erro ao salvar base64', ['filepath' => $filepath]);
        jsonResponse(['success' => false, 'message' => 'Erro ao salvar imagem'], 500);
    }

    // Salva no banco
    savePhotoRecord($db, $cpf, $filename, $type, $userData['user_id']);

    Logger::info('Upload base64 concluído', [
        'cpf' => $cpf,
        'type' => $type,
        'filename' => $filename,
        'size' => strlen($imageData)
    ]);

    jsonResponse([
        'success' => true,
        'message' => 'Foto enviada com sucesso',
        'data' => [
            'filename' => $filename,
            'url' => '/uploads/' . $filename,
            'width' => $validation['width'],
            'height' => $validation['height']
        ]
    ]);
}

/**
 * Gera nome de arquivo seguro
 */
function generateSafeFilename($cpf, $type, $extension) {
    $timestamp = time();
    $random = bin2hex(random_bytes(8));
    return $cpf . '_' . $type . '_' . $timestamp . '_' . $random . '.' . strtolower($extension);
}

/**
 * Salva registro da foto no banco
 */
function savePhotoRecord($db, $cpf, $filename, $type, $userId) {
    // Cria tabela se não existir (com VARCHAR para aceitar qualquer tipo)
    $db->exec("
        CREATE TABLE IF NOT EXISTS `client_photos` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `cpf` varchar(11) NOT NULL,
            `filename` varchar(255) NOT NULL,
            `type` varchar(50) DEFAULT 'other',
            `uploaded_by` int(11) DEFAULT NULL,
            `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `cpf` (`cpf`),
            KEY `uploaded_by` (`uploaded_by`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    $stmt = $db->prepare("
        INSERT INTO client_photos (cpf, filename, type, uploaded_by)
        VALUES (?, ?, ?, ?)
    ");
    $stmt->execute([$cpf, $filename, $type, $userId]);
    
    return $db->lastInsertId();
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
        jsonResponse(['success' => true, 'message' => 'Dados carregados com sucesso', 'data' => []]);
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

    Logger::info('Fotos listadas', ['cpf' => $cpf, 'count' => count($photos)]);

    jsonResponse(['success' => true, 'message' => 'Dados carregados com sucesso', 'data' => $photos]);
}

/**
 * Exclui uma foto
 */
function handleDelete($db, $userData) {
    $id = $_GET['id'] ?? null;

    if (!$id) {
        jsonResponse(['success' => false, 'message' => 'ID é obrigatório'], 400);
    }

    // Busca a foto
    $stmt = $db->prepare("SELECT filename, uploaded_by FROM client_photos WHERE id = ?");
    $stmt->execute([$id]);
    $photo = $stmt->fetch();

    if (!$photo) {
        jsonResponse(['success' => false, 'message' => 'Foto não encontrada'], 404);
    }

    // Verifica permissão (só quem enviou ou admin pode deletar)
    if ($photo['uploaded_by'] != $userData['user_id'] && $userData['role'] !== 'admin') {
        Logger::warning('Tentativa não autorizada de deletar foto', [
            'photo_id' => $id,
            'user' => $userData['username']
        ]);
        jsonResponse(['success' => false, 'message' => 'Sem permissão'], 403);
    }

    // Remove o arquivo
    $filepath = UPLOAD_DIR . $photo['filename'];
    if (file_exists($filepath)) {
        unlink($filepath);
    }

    // Remove do banco
    $stmt = $db->prepare("DELETE FROM client_photos WHERE id = ?");
    $stmt->execute([$id]);

    Logger::info('Foto deletada', ['photo_id' => $id, 'user' => $userData['username']]);

    jsonResponse(['success' => true, 'message' => 'Foto excluída com sucesso']);
}
