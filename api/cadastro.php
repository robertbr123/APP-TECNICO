<?php
/**
 * API de Cadastro de Clientes - Simplificada para teste
 * Ondeline Tech
 */

require_once 'config.php';

// Headers CORS
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Para POST, tenta autenticar mas não bloqueia se falhar
$userData = null;
$token = null;

// Tenta obter o token
$headers = getallheaders();
if (isset($headers['Authorization'])) {
    if (preg_match('/Bearer\s(\S+)/', $headers['Authorization'], $matches)) {
        $token = $matches[1];
    }
}

// Se tiver token, tenta validar
if ($token) {
    $parts = explode('.', $token);
    if (count($parts) === 3) {
        list($header, $payload, $signature) = $parts;
        $validSignature = base64_encode(hash_hmac('sha256', "$header.$payload", JWT_SECRET, true));
        if ($signature === $validSignature) {
            $data = json_decode(base64_decode($payload), true);
            if ($data && isset($data['exp']) && $data['exp'] > time()) {
                $userData = $data;
            }
        }
    }
}

$method = $_SERVER['REQUEST_METHOD'];

try {
    $db = Database::getInstance()->getConnection();

    if ($method === 'GET') {
        // Lista clientes
        $search = $_GET['search'] ?? null;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
        
        $sql = "SELECT * FROM clients";
        $params = [];
        
        if ($search) {
            $sql .= " WHERE name LIKE ? OR cpf LIKE ?";
            $searchTerm = "%$search%";
            $params = [$searchTerm, $searchTerm];
        }
        
        $sql .= " ORDER BY created_at DESC LIMIT $limit";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $clients = $stmt->fetchAll();
        
        jsonResponse([
            'success' => true,
            'data' => $clients
        ]);
        
    } elseif ($method === 'POST') {
        // Cadastra cliente
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);
        
        if (!$data) {
            jsonResponse(['success' => false, 'message' => 'Dados inválidos'], 400);
        }
        
        // Log para debug
        error_log("Cadastro recebido: " . json_encode($data));
        
        // Campos obrigatórios
        if (empty($data['cpf']) || empty($data['name'])) {
            jsonResponse(['success' => false, 'message' => 'Nome e CPF são obrigatórios'], 400);
        }
        
        $cpf = preg_replace('/\D/', '', $data['cpf']);
        
        // Verifica se CPF já existe
        $stmt = $db->prepare("SELECT cpf FROM clients WHERE cpf = ?");
        $stmt->execute([$cpf]);
        if ($stmt->fetch()) {
            jsonResponse(['success' => false, 'message' => 'CPF já cadastrado'], 409);
        }
        
        // Insere o cliente
        $installer = $userData ? $userData['username'] : 'app';
        
        // Prepara os valores garantindo que campos NOT NULL tenham valor
        // planId deve ser um dos IDs válidos: 6, 7, 8, 9
        $validPlanIds = [6, 7, 8, 9];
        $planId = (int)($data['planId'] ?? 6);
        if (!in_array($planId, $validPlanIds)) {
            $planId = 6; // Plano básico como padrão
        }
        
        $pppoe = !empty($data['pppoe']) ? $data['pppoe'] : $cpf . '@ondeline';
        $password = !empty($data['password']) ? $data['password'] : '123';
        $address = !empty($data['address']) ? $data['address'] : (!empty($data['city']) ? $data['city'] : 'Não informado');
        $dueDay = in_array((int)($data['dueDay'] ?? 10), [10, 20, 30]) ? (int)$data['dueDay'] : 10;
        
        // Trata birthDate - se vazio ou inválido, usa NULL
        $birthDate = null;
        if (!empty($data['birthDate']) && $data['birthDate'] !== '0000-00-00') {
            $birthDate = $data['birthDate'];
        }
        
        // Coordenadas de geolocalização
        $latitude = isset($data['latitude']) ? (float)$data['latitude'] : null;
        $longitude = isset($data['longitude']) ? (float)$data['longitude'] : null;
        $locationAccuracy = isset($data['accuracy']) ? (float)$data['accuracy'] : null;
        
        $stmt = $db->prepare("
            INSERT INTO clients (cpf, name, phone, birthDate, city, address, number, complement, planId, pppoe, password, dueDay, observation, latitude, longitude, location_accuracy, installer, status, active, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        
        $stmt->execute([
            $cpf,
            $data['name'],
            $data['phone'] ?? '',
            $birthDate,
            $data['city'] ?? '',
            $address,
            $data['number'] ?? '',
            $data['complement'] ?? '',
            $planId,
            $pppoe,
            $password,
            $dueDay,
            $data['observation'] ?? '',
            $latitude,
            $longitude,
            $locationAccuracy,
            $installer,
            $data['status'] ?? 'ativo',
            $data['active'] ?? 1
        ]);
        
        // Registra o cadastro no log de auditoria
        try {
            $auditStmt = $db->prepare("
                INSERT INTO audit_logs 
                (user_id, username, action_type, action_description, entity_type, entity_id, entity_name, details, ip_address, user_agent)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $auditStmt->execute([
                $userData['user_id'] ?? null,
                $userData['username'] ?? 'app',
                'client_created',
                'Novo cliente cadastrado',
                'client',
                $cpf,
                $data['name'],
                json_encode([
                    'plan' => $planId,
                    'city' => $data['city'] ?? '',
                    'phone' => $data['phone'] ?? ''
                ]),
                $_SERVER['REMOTE_ADDR'] ?? null,
                $_SERVER['HTTP_USER_AGENT'] ?? null
            ]);
        } catch (Exception $e) {
            // Não falha o cadastro se o auditoria falhar
            error_log('Erro ao registrar auditoria: ' . $e->getMessage());
        }
        
        jsonResponse([
            'success' => true,
            'message' => 'Cliente cadastrado com sucesso',
            'data' => ['cpf' => $cpf]
        ], 201);
        
    } else {
        jsonResponse(['success' => false, 'message' => 'Método não permitido'], 405);
    }
    
} catch (PDOException $e) {
    error_log("Erro no cadastro: " . $e->getMessage());
    jsonResponse(['success' => false, 'message' => 'Erro no banco de dados', 'error' => $e->getMessage()], 500);
}
