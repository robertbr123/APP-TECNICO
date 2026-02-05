<?php
/**
 * API para vincular equipamento a cliente
 * Endpoint simplificado que não exige autenticação estrita
 */

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once 'config.php';

// Apenas POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
    exit;
}

try {
    // Tenta autenticar (opcional)
    $userId = null;
    $headers = getallheaders();
    $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
    
    if (!empty($authHeader) && preg_match('/Bearer\s+(.+)/', $authHeader, $matches)) {
        try {
            $payload = verifyJWT($matches[1]);
            if ($payload) {
                $userId = $payload['user_id'] ?? null;
            }
        } catch (Exception $e) {
            // Token inválido, continua sem autenticação
        }
    }
    
    // Lê o corpo da requisição
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        echo json_encode(['success' => false, 'message' => 'JSON inválido']);
        exit;
    }
    
    $cpf = preg_replace('/\D/', '', $input['cpf'] ?? '');
    $serial = strtoupper(trim($input['serial'] ?? ''));
    
    // Validações
    if (empty($cpf)) {
        echo json_encode(['success' => false, 'message' => 'CPF é obrigatório']);
        exit;
    }
    
    if (empty($serial)) {
        echo json_encode(['success' => false, 'message' => 'Número de série é obrigatório']);
        exit;
    }
    
    if (strlen($serial) < 3) {
        echo json_encode(['success' => false, 'message' => 'Número de série deve ter pelo menos 3 caracteres']);
        exit;
    }
    
    // Verifica se o cliente existe
    $stmt = $pdo->prepare("SELECT id, name, serial FROM clients WHERE REPLACE(REPLACE(REPLACE(cpf, '.', ''), '-', ''), ' ', '') = ?");
    $stmt->execute([$cpf]);
    $client = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$client) {
        echo json_encode(['success' => false, 'message' => 'Cliente não encontrado']);
        exit;
    }
    
    $oldSerial = $client['serial'];
    
    // Atualiza o serial do cliente
    $stmt = $pdo->prepare("UPDATE clients SET serial = ?, updated_at = NOW() WHERE id = ?");
    $result = $stmt->execute([$serial, $client['id']]);
    
    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'Equipamento vinculado com sucesso',
            'data' => [
                'client_id' => $client['id'],
                'client_name' => $client['name'],
                'old_serial' => $oldSerial,
                'new_serial' => $serial,
                'linked_by' => $userId
            ]
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erro ao atualizar cliente']);
    }
    
} catch (PDOException $e) {
    error_log('Erro PDO em vincular.php: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erro de banco de dados']);
} catch (Exception $e) {
    error_log('Erro em vincular.php: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erro interno do servidor']);
}
