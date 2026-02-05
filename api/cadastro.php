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
        
        $stmt = $db->prepare("
            INSERT INTO clients (cpf, name, phone, birthDate, cep, city, address, number, complement, planId, pppoe, password, dueDay, observation, installer, status, active, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        
        $stmt->execute([
            $cpf,
            $data['name'],
            $data['phone'] ?? null,
            $data['birthDate'] ?? null,
            $data['cep'] ?? null,
            $data['city'] ?? null,
            $data['address'] ?? null,
            $data['number'] ?? null,
            $data['complement'] ?? null,
            $data['planId'] ?? null,
            $data['pppoe'] ?? null,
            $data['password'] ?? null,
            $data['dueDay'] ?? 10,
            $data['observation'] ?? null,
            $installer,
            $data['status'] ?? 'ativo',
            $data['active'] ?? 1
        ]);
        
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
