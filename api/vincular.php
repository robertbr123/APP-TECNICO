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

// Função auxiliar para obter username do usuário
function getUserUsername($db, $userId) {
    try {
        $stmt = $db->prepare("SELECT username FROM users WHERE id = ? LIMIT 1");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        return $user['username'] ?? 'sistema';
    } catch (Exception $e) {
        return 'sistema';
    }
}

// Apenas POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
    exit;
}

try {
    // Conexão com banco
    $db = Database::getInstance()->getConnection();
    
    // Tenta autenticar (opcional)
    $userId = null;
    $headers = getallheaders();
    $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
    
    if (!empty($authHeader) && preg_match('/Bearer\s+(.+)/', $authHeader, $matches)) {
        try {
            $payload = verifyToken($matches[1]);
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
    
    // Verifica se o cliente existe (cpf é a primary key)
    $stmt = $db->prepare("SELECT cpf, name, serial FROM clients WHERE cpf = ?");
    $stmt->execute([$cpf]);
    $client = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$client) {
        echo json_encode(['success' => false, 'message' => 'Cliente não encontrado']);
        exit;
    }
    
    $oldSerial = $client['serial'];
    
    // Atualiza o serial do cliente (cpf é a primary key)
    $stmt = $db->prepare("UPDATE clients SET serial = ? WHERE cpf = ?");
    $result = $stmt->execute([$serial, $cpf]);
    
    if ($result) {
        // Registra a vinculação no log de auditoria
        try {
            $auditStmt = $db->prepare("
                INSERT INTO audit_logs 
                (user_id, username, action_type, action_description, entity_type, entity_id, entity_name, details, ip_address, user_agent)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $username = $userId ? getUserUsername($db, $userId) : 'sistema';
            
            $auditStmt->execute([
                $userId,
                $username,
                'equipment_linked',
                'Equipamento vinculado ao cliente',
                'client',
                $cpf,
                $client['name'],
                json_encode([
                    'old_serial' => $oldSerial,
                    'new_serial' => $serial,
                    'city' => $client['city'] ?? null
                ]),
                $_SERVER['REMOTE_ADDR'] ?? null,
                $_SERVER['HTTP_USER_AGENT'] ?? null
            ]);
        } catch (Exception $e) {
            // Não falha a vinculação se o auditoria falhar
            error_log('Erro ao registrar auditoria: ' . $e->getMessage());
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Equipamento vinculado com sucesso',
            'data' => [
                'client_cpf' => $cpf,
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
