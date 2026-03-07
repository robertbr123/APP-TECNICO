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

    $action = $_GET['action'] ?? null;
    $cpf = $_GET['cpf'] ?? null;
    $search = $_GET['search'] ?? null;
    $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $limit = isset($_GET['limit']) ? min(1000, max(1, (int)$_GET['limit'])) : 20;
    $offset = ($page - 1) * $limit;

    // Timeline completa do cliente
    if ($action === 'timeline' && $cpf) {
        handleTimeline($db, $cpf);
        return;
    }

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
 * POST - Criar novo cliente ou ações especiais
 */
function handlePost($db, $userData) {
    $data = getRequestBody();

    // Verificar se é uma ação especial
    if (isset($data['action'])) {
        switch ($data['action']) {
            case 'update_location':
                handleUpdateLocation($db, $userData, $data);
                return;
        }
    }

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

    // Verifica se o cliente existe e obtém dados atuais
    $stmt = $db->prepare("SELECT * FROM clients WHERE cpf = ?");
    $stmt->execute([$cpf]);
    $existingClient = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$existingClient) {
        Logger::warning('Cliente não encontrado para atualização', ['cpf' => $cpf]);
        jsonResponse(['success' => false, 'message' => 'Cliente não encontrado'], 404);
    }
    
    // Verifica conflito baseado em updated_at (se não for force_update)
    $forceUpdate = isset($data['_force_update']) && $data['_force_update'] === true;
    
    if (!$forceUpdate && isset($data['_last_sync']) && isset($existingClient['updated_at'])) {
        $lastSync = strtotime($data['_last_sync']);
        $serverUpdated = strtotime($existingClient['updated_at']);
        
        // Se o servidor foi atualizado depois do último sync do cliente, há conflito
        if ($serverUpdated > $lastSync) {
            Logger::info('Conflito detectado na atualização', [
                'cpf' => $cpf, 
                'server_updated' => $existingClient['updated_at'],
                'client_last_sync' => $data['_last_sync']
            ]);
            
            jsonResponse([
                'success' => false, 
                'message' => 'Conflito: O registro foi modificado por outro usuário',
                'conflict' => true,
                'server_data' => $existingClient
            ], 409);
        }
    }
    
    // Remove campos de controle do update
    unset($data['_force_update']);
    unset($data['_resolved_conflict']);
    unset($data['_last_sync']);

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
    
    // Obtém o timestamp atualizado para sincronização futura
    $stmt = $db->prepare("SELECT updated_at FROM clients WHERE cpf = ?");
    $stmt->execute([$cpf]);
    $updated = $stmt->fetch(PDO::FETCH_ASSOC);

    jsonResponse([
        'success' => true, 
        'message' => 'Cliente atualizado com sucesso',
        'updated_at' => $updated['updated_at'] ?? date('Y-m-d H:i:s')
    ]);
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

/**
 * Atualizar localização do cliente (permite técnicos)
 */
function handleUpdateLocation($db, $userData, $data) {
    if (empty($data['cpf'])) {
        jsonResponse(['success' => false, 'message' => 'CPF é obrigatório'], 400);
    }

    $cpfValidation = Validator::validateCPF($data['cpf']);
    $cpf = $cpfValidation['valid'] ? $cpfValidation['clean'] : preg_replace('/\D/', '', $data['cpf']);

    // Verifica se o cliente existe
    $stmt = $db->prepare("SELECT cpf, name FROM clients WHERE cpf = ?");
    $stmt->execute([$cpf]);
    $client = $stmt->fetch();
    
    if (!$client) {
        Logger::warning('Cliente não encontrado para atualização de localização', ['cpf' => $cpf]);
        jsonResponse(['success' => false, 'message' => 'Cliente não encontrado'], 404);
    }

    // Valida latitude e longitude
    $latitude = isset($data['latitude']) ? floatval($data['latitude']) : null;
    $longitude = isset($data['longitude']) ? floatval($data['longitude']) : null;
    $accuracy = isset($data['accuracy']) ? floatval($data['accuracy']) : null;

    if (!$latitude || !$longitude) {
        jsonResponse(['success' => false, 'message' => 'Latitude e longitude são obrigatórios'], 400);
    }

    // Atualiza apenas a localização
    $stmt = $db->prepare("UPDATE clients SET latitude = ?, longitude = ?, location_accuracy = ? WHERE cpf = ?");
    $stmt->execute([$latitude, $longitude, $accuracy, $cpf]);

    Logger::logDatabase('UPDATE', 'clients', $stmt->rowCount());
    Logger::info('Localização do cliente atualizada', [
        'cpf' => $cpf,
        'name' => $client['name'],
        'latitude' => $latitude,
        'longitude' => $longitude,
        'updated_by' => $userData['username']
    ]);

    jsonResponse(['success' => true, 'message' => 'Localização atualizada com sucesso']);
}

/**
 * Timeline completa do cliente — agrega OS, Checklists, Serial History e Fotos
 */
function handleTimeline($db, $cpfRaw) {
    $cpfValidation = Validator::validateCPF($cpfRaw);
    $cpf = $cpfValidation['valid'] ? $cpfValidation['clean'] : preg_replace('/\D/', '', $cpfRaw);

    $events = [];

    // Ordens de Serviço
    try {
        $stmt = $db->prepare("
            SELECT 'os' as event_type,
                   id, order_number as ref, type as subtype,
                   status, description,
                   COALESCE(assigned_name, created_by_name, '') as actor,
                   priority,
                   COALESCE(completed_at, created_at) as event_date,
                   created_at
            FROM work_orders
            WHERE client_cpf = ?
            ORDER BY created_at DESC
        ");
        $stmt->execute([$cpf]);
        $events = array_merge($events, $stmt->fetchAll());
    } catch (Exception $e) { /* tabela pode não existir */ }

    // Checklists de instalação
    try {
        $stmt = $db->prepare("
            SELECT 'checklist' as event_type,
                   id, checklist_number as ref, installation_type as subtype,
                   status, COALESCE(notes, '') as description,
                   COALESCE(approved_by, '') as actor,
                   '' as priority,
                   COALESCE(completed_at, created_at) as event_date,
                   created_at
            FROM installation_checklists
            WHERE client_cpf = ?
            ORDER BY created_at DESC
        ");
        $stmt->execute([$cpf]);
        $events = array_merge($events, $stmt->fetchAll());
    } catch (Exception $e) { /* tabela pode não existir */ }

    // Histórico de serial (trocas de equipamento)
    try {
        $stmt = $db->prepare("
            SELECT 'serial' as event_type,
                   id,
                   CONCAT(COALESCE(old_serial,'—'), ' → ', new_serial) as ref,
                   'troca' as subtype,
                   'Troca de Equipamento' as status,
                   CONCAT('Serial anterior: ', COALESCE(old_serial, 'Primeiro registro'), ' | Novo: ', new_serial) as description,
                   COALESCE(changed_by, '') as actor,
                   '' as priority,
                   changed_at as event_date,
                   changed_at as created_at
            FROM serial_history
            WHERE client_cpf = ?
            ORDER BY changed_at DESC
        ");
        $stmt->execute([$cpf]);
        $events = array_merge($events, $stmt->fetchAll());
    } catch (Exception $e) { /* tabela pode não existir */ }

    // Fotos do cliente
    try {
        $stmt = $db->prepare("
            SELECT 'foto' as event_type,
                   id, file_path as ref, type as subtype,
                   'Foto Registrada' as status,
                   CONCAT('Tipo: ', COALESCE(type, 'instalação')) as description,
                   COALESCE(uploaded_by, '') as actor,
                   '' as priority,
                   created_at as event_date,
                   created_at
            FROM client_photos
            WHERE client_cpf = ?
            ORDER BY created_at DESC
        ");
        $stmt->execute([$cpf]);
        $events = array_merge($events, $stmt->fetchAll());
    } catch (Exception $e) { /* tabela pode não existir */ }

    // Ordena tudo por data decrescente
    usort($events, function($a, $b) {
        return strtotime($b['event_date']) - strtotime($a['event_date']);
    });

    Logger::info('Timeline do cliente gerada', ['cpf' => $cpf, 'total_events' => count($events)]);

    jsonResponse(['success' => true, 'data' => $events, 'total' => count($events)]);
}
