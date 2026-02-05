<?php
/**
 * API de Upload de Fotos - Simplificada
 * Não exige autenticação estrita
 */

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once 'config.php';

// Configurações de upload
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('MAX_FILE_SIZE', 10 * 1024 * 1024); // 10MB
define('ALLOWED_TYPES', ['image/jpeg', 'image/png', 'image/webp', 'image/gif']);

// Cria pasta de uploads se não existir
if (!file_exists(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0755, true);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
    exit;
}

try {
    $db = Database::getInstance()->getConnection();
    
    // Tenta autenticar (opcional)
    $userId = null;
    $headers = getallheaders();
    $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
    
    if (!empty($authHeader) && preg_match('/Bearer\s+(.+)/', $authHeader, $matches)) {
        $payload = verifyToken($matches[1]);
        if ($payload) {
            $userId = $payload['user_id'] ?? null;
        }
    }
    
    // Verifica se é upload via FormData ou Base64
    if (isset($_FILES['photo'])) {
        handleFileUpload($db, $userId);
    } else {
        handleBase64Upload($db, $userId);
    }
    
} catch (Exception $e) {
    error_log('Erro upload-foto.php: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erro: ' . $e->getMessage()]);
}

/**
 * Upload via arquivo (FormData)
 */
function handleFileUpload($db, $userId) {
    $file = $_FILES['photo'];
    $cpf = preg_replace('/\D/', '', $_POST['cpf'] ?? '');
    $type = $_POST['type'] ?? 'other';

    if (empty($cpf)) {
        echo json_encode(['success' => false, 'message' => 'CPF é obrigatório']);
        exit;
    }

    // Validações
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errors = [
            UPLOAD_ERR_INI_SIZE => 'Arquivo muito grande',
            UPLOAD_ERR_FORM_SIZE => 'Arquivo muito grande',
            UPLOAD_ERR_PARTIAL => 'Upload incompleto',
            UPLOAD_ERR_NO_FILE => 'Nenhum arquivo enviado',
        ];
        echo json_encode(['success' => false, 'message' => $errors[$file['error']] ?? 'Erro no upload']);
        exit;
    }

    if ($file['size'] > MAX_FILE_SIZE) {
        echo json_encode(['success' => false, 'message' => 'Arquivo muito grande (máx 10MB)']);
        exit;
    }

    // Verifica tipo MIME
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mimeType, ALLOWED_TYPES)) {
        echo json_encode(['success' => false, 'message' => 'Tipo de arquivo não permitido: ' . $mimeType]);
        exit;
    }

    // Gera nome único
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION) ?: 'jpg';
    $filename = $cpf . '_' . $type . '_' . time() . '_' . uniqid() . '.' . strtolower($extension);
    $filepath = UPLOAD_DIR . $filename;

    // Move o arquivo
    if (!move_uploaded_file($file['tmp_name'], $filepath)) {
        echo json_encode(['success' => false, 'message' => 'Erro ao salvar arquivo']);
        exit;
    }

    // Salva no banco
    savePhotoRecord($db, $cpf, $filename, $type, $userId);

    echo json_encode([
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
function handleBase64Upload($db, $userId) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $base64 = $input['photo'] ?? null;
    $cpf = preg_replace('/\D/', '', $input['cpf'] ?? '');
    $type = $input['type'] ?? 'other';

    if (empty($cpf) || empty($base64)) {
        echo json_encode(['success' => false, 'message' => 'CPF e foto são obrigatórios']);
        exit;
    }

    // Remove prefixo data:image/...;base64,
    $extension = 'jpg';
    if (preg_match('/^data:image\/(\w+);base64,/', $base64, $matches)) {
        $extension = $matches[1];
        if ($extension === 'jpeg') $extension = 'jpg';
        $base64 = substr($base64, strpos($base64, ',') + 1);
    }

    // Decodifica
    $imageData = base64_decode($base64);
    if ($imageData === false) {
        echo json_encode(['success' => false, 'message' => 'Imagem inválida']);
        exit;
    }

    // Verifica tamanho
    if (strlen($imageData) > MAX_FILE_SIZE) {
        echo json_encode(['success' => false, 'message' => 'Imagem muito grande (máx 10MB)']);
        exit;
    }

    // Gera nome único
    $filename = $cpf . '_' . $type . '_' . time() . '_' . uniqid() . '.' . $extension;
    $filepath = UPLOAD_DIR . $filename;

    // Salva o arquivo
    if (file_put_contents($filepath, $imageData) === false) {
        echo json_encode(['success' => false, 'message' => 'Erro ao salvar imagem']);
        exit;
    }

    // Salva no banco
    savePhotoRecord($db, $cpf, $filename, $type, $userId);

    echo json_encode([
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3
    ");

    $stmt = $db->prepare("INSERT INTO client_photos (cpf, filename, type, uploaded_by) VALUES (?, ?, ?, ?)");
    $stmt->execute([$cpf, $filename, $type, $userId]);
}
