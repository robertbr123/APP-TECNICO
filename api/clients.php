<?php
/**
 * API de Clientes - CRUD Completo (Melhorado)
 * Ondeline Tech - App do Técnico
 * 
 * Melhorias:
 * - Validação de CPF
 * - Logs estruturados
 * - Tratamento de erros aprimorado
 */

require_once 'config.php';
require_once 'Logger.php';
require_once 'Validator.php';

$method = $_SERVER['REQUEST_METHOD'];
$userData = requireAuth();

// Log da requisição
Logger::logRequest('clients.php', $method, array_merge($_GET, ['body' => file_get_contents('php://input')]));

// Busca a cidade do técnico/user para filtro
$userCity = null;
if ($userData['role'] === 'tecnico' || $userData['role'] === 'user') {
    try {
        $tmpDb = Database::getInstance()->getConnection();
        $cityStmt = $tmpDb->prepare("SELECT city FROM users WHERE id = ?");
        $cityStmt->execute([$userData['user_id']]);
        $userCity = $cityStmt->fetch()['city'] ?? null;
        
        // Log para debug
        Logger::info('Filtro de cidade aplicado', [
            'user_id' => $userData['user_id'],
            'role' => $userData['role'],
            'city' => $userCity
        ]);
    } catch (Exception $e) {
        Logger::warning('Erro ao buscar cidade do técnico', ['user_id' => $userData['user_id']]);
    }
}

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
            Logger::warning('Método não permitido', ['method' => $method]);
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
 * GET - Listar clientes ou buscar um específico
 */
function handleGet($db) {
    global $userCity;

    $cpf = $_GET['cpf'] ?? null;
    $search = $_GET['search'] ?? null;
    $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $limit = isset($_GET['limit']) ? min(100, max(1, (int)$_GET['limit'])) : 20;
    $offset = ($page - 1) * $limit;

    // Busca por CPF específico
    if ($cpf) {
        $cpfValidation = Validator::validateCPF($cpf);
        $cleanCpf = $cpfValidation['valid'] ? $cpfValidation['clean'] : preg_replace('/\D/', '', $cpf);
        
        if ($userCity) {
            $stmt = $db->prepare("SELECT * FROM clients WHERE cpf = ? AND LOWER(TRIM(city)) = LOWER(TRIM(?))");
            $stmt->execute([$cleanCpf, $userCity]);
        } else {
            $stmt = $db->prepare("SELECT * FROM clients WHERE cpf = ?");
            $stmt->execute([$cleanCpf]);
        }
        $client = $stmt->fetch();

        if (!$client) {
            Logger::info('Cliente não encontrado', ['cpf' => $cleanCpf]);
            jsonResponse(['success' => false, 'message' => 'Cliente não encontrado'], 404);
        }

        jsonResponse(['success' => true, 'data' => $client]);
    }

    // Lista de clientes com busca e paginação
    $sql = "SELECT * FROM clients";
    $params = [];
    $conditions = [];

    if ($userCity) {
        $conditions[] = "LOWER(TRIM(city)) = LOWER(TRIM(?))";
        $params[] = $userCity;
    }

    if ($search) {
        $searchTerm = "%$search%";
        $conditions[] = "(name LIKE ? OR cpf LIKE ? OR phone LIKE ? OR city LIKE ?)";
        array_push($params, $searchTerm, $searchTerm, $searchTerm, $searchTerm);
    }

    if (!empty($conditions)) {
        $sql .= " WHERE " . implode(' AND ', $conditions);
    }

    // Log detalhado para debug
    Logger::info('Query de listagem', [
        'sql' => $sql,
        'params' => $params,
        'userCity' => $userCity ?? 'NULL'
    ]);

    // Conta total
    $countSql = str_replace("SELECT *", "SELECT COUNT(*) as total", $sql);
    $countStmt = $db->prepare($countSql);
    $countStmt->execute($params);
    $total = $countStmt->fetch()['total'];

    // Busca paginada
    $sql .= " ORDER BY created_at DESC LIMIT $limit OFFSET $offset";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $clients = $stmt->fetchAll();

    // Log das cidades retornadas
    $cities = array_unique(array_column($clients, 'city'));
    Logger::info('Cidades retornadas', ['cities' => $cities, 'count' => count($clients)]);

    Logger::logDatabase('SELECT', 'clients', count($clients));

    jsonResponse([
        'success' => true,
        'data' => $clients,
        'pagination' => [
            'page' => $page,
            'limit' => $limit,
            'total' => (int)$total,
            'pages' => ceil($total / $limit),
            'has_next' => ($page * $limit) < $total,
            'has_prev' => $page > 1
        ],
        'debug' => [
            'user_city' => $userCity,
            'cities_returned' => $cities
        ]
    ]);
}

/**
 * POST - Criar novo cliente
 */
function handlePost($db, $userData) {
    $data = getRequestBody();

    Logger::info('Dados recebidos para cadastro', ['data' => $data]);

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
        }
    }

    // Verifica se CPF já existe
    $stmt = $db->prepare("SELECT cpf FROM clients WHERE cpf = ?");
    $stmt->execute([$cpf]);
    if ($stmt->fetch()) {
        Logger::warning('CPF já cadastrado', ['cpf' => $cpf]);
        jsonResponse(['success' => false, 'message' => 'CPF já cadastrado'], 409);
    }

    $installer = $data['installer'] ?? $userData['username'];
    
    // Sanitiza nome
    $name = Validator::sanitizeString($data['name'], ['maxLength' => 150]);

    // Insere o cliente
    $stmt = $db->prepare("
        INSERT INTO clients (cpf, name, phone, birthDate, cep, city, address, number, complement, planId, pppoe, password, dueDay, observation, installer, status, active, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ");

    $stmt->execute([
        $cpf,
        $name,
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

    Logger::logDatabase('INSERT', 'clients', 1);
    Logger::info('Cliente cadastrado', ['cpf' => $cpf, 'name' => $name]);

    jsonResponse([
        'success' => true,
        'message' => 'Cliente cadastrado com sucesso',
        'data' => ['cpf' => $cpf]
    ], 201);
}

/**
 * PUT - Atualizar cliente (APENAS ADMIN)
 */
function handlePut($db, $userData) {
    // Verifica se é admin
    if ($userData['role'] !== 'admin') {
        Logger::warning('Tentativa de edição sem permissão', ['user' => $userData['username'], 'role' => $userData['role']]);
        jsonResponse(['success' => false, 'message' => 'Apenas administradores podem editar clientes'], 403);
    }
    
    $data = getRequestBody();

    if (empty($data['cpf'])) {
        jsonResponse(['success' => false, 'message' => 'CPF é obrigatório para atualização'], 400);
    }

    $cpfValidation = Validator::validateCPF($data['cpf']);
    $cpf = $cpfValidation['valid'] ? $cpfValidation['clean'] : preg_replace('/\D/', '', $data['cpf']);

    // Verifica se o cliente existe
    $stmt = $db->prepare("SELECT cpf FROM clients WHERE cpf = ?");
    $stmt->execute([$cpf]);
    if (!$stmt->fetch()) {
        Logger::warning('Cliente não encontrado para atualização', ['cpf' => $cpf]);
        jsonResponse(['success' => false, 'message' => 'Cliente não encontrado'], 404);
    }

    // Monta a query de atualização
    $updateFields = [];
    $params = [];
    
    // Mapeia campos do frontend para campos da tabela
    $fieldMapping = [
        'accuracy' => 'location_accuracy'
    ];
    
    $allowedFields = ['name', 'birthDate', 'phone', 'cep', 'state', 'address', 'number', 'complement', 'city', 'neighborhood', 'planId', 'pppoe', 'password', 'dueDay', 'installer', 'observation', 'status', 'active', 'serial', 'phone_number', 'contrato', 'latitude', 'longitude', 'accuracy'];

    foreach ($allowedFields as $field) {
        if (isset($data[$field])) {
            // Sanitiza nome se estiver sendo atualizado
            if ($field === 'name') {
                $data[$field] = Validator::sanitizeString($data[$field], ['maxLength' => 150]);
            }
            
            // Usa mapeamento se existir
            $dbField = $fieldMapping[$field] ?? $field;
            $updateFields[] = "$dbField = ?";
            $params[] = $data[$field];
        }
    }

    if (empty($updateFields)) {
        jsonResponse(['success' => false, 'message' => 'Nenhum campo para atualizar'], 400);
    }

    $params[] = $cpf;
    $sql = "UPDATE clients SET " . implode(', ', $updateFields) . " WHERE cpf = ?";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);

    Logger::logDatabase('UPDATE', 'clients', $stmt->rowCount());
    Logger::info('Cliente atualizado', ['cpf' => $cpf, 'fields' => array_keys($data)]);

    jsonResponse(['success' => true, 'message' => 'Cliente atualizado com sucesso']);
}

/**
 * DELETE - Excluir cliente (APENAS ADMIN)
 */
function handleDelete($db, $userData) {
    // Verifica se é admin
    if ($userData['role'] !== 'admin') {
        Logger::warning('Tentativa de exclusão sem permissão', ['user' => $userData['username'], 'role' => $userData['role']]);
        jsonResponse(['success' => false, 'message' => 'Apenas administradores podem excluir clientes'], 403);
    }
    
    $cpf = $_GET['cpf'] ?? null;

    if (!$cpf) {
        jsonResponse(['success' => false, 'message' => 'CPF é obrigatório para exclusão'], 400);
    }

    $cpfValidation = Validator::validateCPF($cpf);
    $cpf = $cpfValidation['valid'] ? $cpfValidation['clean'] : preg_replace('/\D/', '', $cpf);
    
    // Busca dados do cliente para log antes de excluir
    $clientStmt = $db->prepare("SELECT name, city FROM clients WHERE cpf = ?");
    $clientStmt->execute([$cpf]);
    $clientData = $clientStmt->fetch();

    $stmt = $db->prepare("DELETE FROM clients WHERE cpf = ?");
    $stmt->execute([$cpf]);

    if ($stmt->rowCount() === 0) {
        Logger::warning('Cliente não encontrado para exclusão', ['cpf' => $cpf]);
        jsonResponse(['success' => false, 'message' => 'Cliente não encontrado'], 404);
    }

    Logger::logDatabase('DELETE', 'clients', 1);
    Logger::info('Cliente excluído', [
        'cpf' => $cpf, 
        'client_name' => $clientData['name'] ?? 'N/A',
        'deleted_by' => $userData['username']
    ]);
    
    // Registra no log de auditoria
    try {
        $auditStmt = $db->prepare("
            INSERT INTO audit_logs 
            (user_id, username, action_type, action_description, entity_type, entity_id, entity_name, ip_address, user_agent)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $auditStmt->execute([
            $userData['user_id'],
            $userData['username'],
            'client_deleted',
            'Cliente excluído permanentemente',
            'client',
            $cpf,
            $clientData['name'] ?? 'N/A',
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? null
        ]);
    } catch (Exception $e) {
        Logger::warning('Erro ao registrar auditoria de exclusão', ['error' => $e->getMessage()]);
    }

    jsonResponse(['success' => true, 'message' => 'Cliente excluído com sucesso']);
}
