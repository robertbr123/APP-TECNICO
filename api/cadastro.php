<?php
/**
 * API de Cadastro de Clientes - Melhorada
 * Ondeline Tech
 * 
 * Melhorias:
 * - Validação de CPF no backend
 * - Paginação completa
 * - Logs estruturados
 * - Validações aprimoradas
 */

require_once 'config.php';
require_once 'Logger.php';
require_once 'Validator.php';

// Headers CORS
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Log da requisição
Logger::logRequest('cadastro.php', $_SERVER['REQUEST_METHOD'], $_GET);

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
        // ===== LISTAGEM COM PAGINAÇÃO =====
        $search = $_GET['search'] ?? null;
        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $limit = isset($_GET['limit']) ? min(100, max(1, (int)$_GET['limit'])) : 20;
        $offset = ($page - 1) * $limit;
        
        // Monta a query base
        $sql = "SELECT * FROM clients";
        $countSql = "SELECT COUNT(*) as total FROM clients";
        $params = [];
        $whereConditions = [];
        
        // Filtro de busca
        if ($search) {
            $searchTerm = "%$search%";
            $whereConditions[] = "(name LIKE ? OR cpf LIKE ? OR phone LIKE ? OR city LIKE ?)";
            $params = [$searchTerm, $searchTerm, $searchTerm, $searchTerm];
        }
        
        // Aplica where se houver condições
        if (!empty($whereConditions)) {
            $whereClause = " WHERE " . implode(' AND ', $whereConditions);
            $sql .= $whereClause;
            $countSql .= $whereClause;
        }
        
        // Conta total de registros
        $countStmt = $db->prepare($countSql);
        $countStmt->execute($params);
        $total = $countStmt->fetch()['total'];
        
        // Busca registros paginados
        $sql .= " ORDER BY created_at DESC LIMIT $limit OFFSET $offset";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $clients = $stmt->fetchAll();
        
        Logger::logDatabase('SELECT', 'clients', count($clients));
        
        $response = [
            'success' => true,
            'data' => $clients,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => (int)$total,
                'pages' => ceil($total / $limit),
                'has_next' => ($page * $limit) < $total,
                'has_prev' => $page > 1
            ]
        ];
        
        Logger::logResponse('cadastro.php', 200, ['count' => count($clients)]);
        jsonResponse($response);
        
    } elseif ($method === 'POST') {
        // ===== CADASTRO COM VALIDAÇÃO =====
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);
        
        if (!$data) {
            Logger::warning('Dados JSON inválidos recebidos');
            jsonResponse(['success' => false, 'message' => 'Dados inválidos'], 400);
        }
        
        Logger::info('Cadastro recebido', ['name' => $data['name'] ?? 'N/A']);
        
        // Validação de campos obrigatórios
        $requiredValidation = Validator::validateRequired($data, ['cpf', 'name']);
        if (!$requiredValidation['valid']) {
            Logger::warning('Campos obrigatórios faltando', ['errors' => $requiredValidation['errors']]);
            jsonResponse([
                'success' => false, 
                'message' => $requiredValidation['message'],
                'errors' => $requiredValidation['errors']
            ], 400);
        }
        
        // ===== VALIDAÇÃO DE CPF =====
        $cpfValidation = Validator::validateCPF($data['cpf']);
        if (!$cpfValidation['valid']) {
            Logger::warning('CPF inválido', ['cpf' => $data['cpf'], 'error' => $cpfValidation['message']]);
            jsonResponse([
                'success' => false, 
                'message' => $cpfValidation['message']
            ], 400);
        }
        
        $cpf = $cpfValidation['clean'];
        
        // Validação de telefone (se fornecido)
        if (!empty($data['phone'])) {
            $phoneValidation = Validator::validatePhone($data['phone']);
            if (!$phoneValidation['valid']) {
                Logger::warning('Telefone inválido', ['phone' => $data['phone']]);
                jsonResponse([
                    'success' => false, 
                    'message' => $phoneValidation['message']
                ], 400);
            }
        }
        
        // Validação de CEP (se fornecido)
        if (!empty($data['cep'])) {
            $cepValidation = Validator::validateCEP($data['cep']);
            if (!$cepValidation['valid']) {
                Logger::warning('CEP inválido', ['cep' => $data['cep']]);
            }
        }
        
        // Verifica se CPF já existe
        $stmt = $db->prepare("SELECT cpf FROM clients WHERE cpf = ?");
        $stmt->execute([$cpf]);
        if ($stmt->fetch()) {
            Logger::warning('Tentativa de cadastro com CPF duplicado', ['cpf' => $cpf]);
            jsonResponse([
                'success' => false, 
                'message' => 'CPF já cadastrado'
            ], 409);
        }
        
        // Prepara dados
        $installer = $userData ? $userData['username'] : 'app';
        $planId = (int)($data['planId'] ?? 6);
        $pppoe = !empty($data['pppoe']) ? $data['pppoe'] : $cpf . '@ondeline';
        $password = !empty($data['password']) ? $data['password'] : '123';
        $address = !empty($data['address']) ? $data['address'] : (!empty($data['city']) ? $data['city'] : 'Não informado');
        $dueDay = in_array((int)($data['dueDay'] ?? 10), [10, 20, 30]) ? (int)$data['dueDay'] : 10;
        
        // Trata birthDate
        $birthDate = null;
        if (!empty($data['birthDate']) && $data['birthDate'] !== '0000-00-00') {
            $birthDate = $data['birthDate'];
        }
        
        // Coordenadas de geolocalização
        $latitude = isset($data['latitude']) ? (float)$data['latitude'] : null;
        $longitude = isset($data['longitude']) ? (float)$data['longitude'] : null;
        $locationAccuracy = isset($data['accuracy']) ? (float)$data['accuracy'] : null;
        
        // Sanitiza nome
        $name = Validator::sanitizeString($data['name'], ['maxLength' => 150]);
        
        // Insere o cliente
        $stmt = $db->prepare("
            INSERT INTO clients (
                cpf, name, phone, birthDate, city, address, number, complement, 
                planId, pppoe, password, dueDay, observation, 
                latitude, longitude, location_accuracy, installer, status, active, created_at
            ) VALUES (
                ?, ?, ?, ?, ?, ?, ?, ?, 
                ?, ?, ?, ?, ?, 
                ?, ?, ?, ?, ?, ?, NOW()
            )
        ");
        
        $stmt->execute([
            $cpf,
            $name,
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
        
        $clientId = $db->lastInsertId();
        
        Logger::logDatabase('INSERT', 'clients', 1);
        Logger::info('Cliente cadastrado com sucesso', [
            'cpf' => $cpf,
            'name' => $name,
            'installer' => $installer
        ]);
        
        // Registra auditoria
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
                $name,
                json_encode([
                    'plan' => $planId,
                    'city' => $data['city'] ?? '',
                    'phone' => $data['phone'] ?? ''
                ]),
                $_SERVER['REMOTE_ADDR'] ?? null,
                $_SERVER['HTTP_USER_AGENT'] ?? null
            ]);
        } catch (Exception $e) {
            Logger::logException($e, ['context' => 'audit_log']);
        }
        
        jsonResponse([
            'success' => true,
            'message' => 'Cliente cadastrado com sucesso',
            'data' => [
                'cpf' => $cpf,
                'name' => $name,
                'formatted_cpf' => Validator::formatCPF($cpf)
            ]
        ], 201);
        
    } else {
        Logger::warning('Método não permitido', ['method' => $method]);
        jsonResponse(['success' => false, 'message' => 'Método não permitido'], 405);
    }
    
} catch (PDOException $e) {
    Logger::logException($e, ['context' => 'database']);
    jsonResponse([
        'success' => false, 
        'message' => 'Erro no banco de dados. Tente novamente.'
    ], 500);
} catch (Exception $e) {
    Logger::logException($e);
    jsonResponse([
        'success' => false, 
        'message' => 'Erro interno do servidor'
    ], 500);
}
