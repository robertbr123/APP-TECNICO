<?php
/**
 * API de Clientes - CRUD Completo
 * Ondeline Tech - App do Técnico
 */

require_once 'config.php';

$method = $_SERVER['REQUEST_METHOD'];
$userData = requireAuth(); // Requer autenticação

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
            handlePut($db);
            break;
        case 'DELETE':
            handleDelete($db);
            break;
        default:
            jsonResponse(['success' => false, 'message' => 'Método não permitido'], 405);
    }
} catch (PDOException $e) {
    jsonResponse(['success' => false, 'message' => 'Erro no banco de dados', 'error' => $e->getMessage()], 500);
}

/**
 * GET - Listar clientes ou buscar um específico
 */
function handleGet($db) {
    // Verifica se foi passado um CPF específico
    $cpf = $_GET['cpf'] ?? null;
    $search = $_GET['search'] ?? null;
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
    $offset = ($page - 1) * $limit;

    if ($cpf) {
        // Busca cliente específico
        $stmt = $db->prepare("SELECT * FROM clients WHERE cpf = ?");
        $stmt->execute([preg_replace('/\D/', '', $cpf)]);
        $client = $stmt->fetch();

        if (!$client) {
            jsonResponse(['success' => false, 'message' => 'Cliente não encontrado'], 404);
        }

        jsonResponse(['success' => true, 'data' => $client]);
    }

    // Lista de clientes com busca opcional
    $sql = "SELECT * FROM clients";
    $params = [];

    if ($search) {
        $sql .= " WHERE name LIKE ? OR cpf LIKE ? OR phone LIKE ? OR city LIKE ?";
        $searchTerm = "%$search%";
        $params = [$searchTerm, $searchTerm, $searchTerm, $searchTerm];
    }

    // Conta total para paginação
    $countSql = str_replace("SELECT *", "SELECT COUNT(*) as total", $sql);
    $countStmt = $db->prepare($countSql);
    $countStmt->execute($params);
    $total = $countStmt->fetch()['total'];

    // Adiciona ordenação e paginação (created_at é o nome correto da coluna)
    $sql .= " ORDER BY created_at DESC LIMIT $limit OFFSET $offset";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $clients = $stmt->fetchAll();

    jsonResponse([
        'success' => true,
        'data' => $clients,
        'pagination' => [
            'page' => $page,
            'limit' => $limit,
            'total' => $total,
            'pages' => ceil($total / $limit)
        ]
    ]);
}

/**
 * POST - Criar novo cliente
 */
function handlePost($db, $userData) {
    $data = getRequestBody();

    // Log dos dados recebidos para debug
    error_log("Dados recebidos: " . json_encode($data));

    // Campos obrigatórios (mínimo necessário)
    $required = ['cpf', 'name'];
    foreach ($required as $field) {
        if (empty($data[$field])) {
            jsonResponse(['success' => false, 'message' => "Campo obrigatório: $field"], 400);
        }
    }

    // Limpa o CPF (remove formatação)
    $cpf = preg_replace('/\D/', '', $data['cpf']);

    // Verifica se o CPF já existe
    $stmt = $db->prepare("SELECT cpf FROM clients WHERE cpf = ?");
    $stmt->execute([$cpf]);
    if ($stmt->fetch()) {
        jsonResponse(['success' => false, 'message' => 'CPF já cadastrado'], 409);
    }

    // Prepara os dados com valores padrão
    $installer = $data['installer'] ?? $userData['username'];

    // Insere o cliente com os nomes corretos das colunas do banco
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
}

/**
 * PUT - Atualizar cliente
 */
function handlePut($db) {
    $data = getRequestBody();

    if (empty($data['cpf'])) {
        jsonResponse(['success' => false, 'message' => 'CPF é obrigatório para atualização'], 400);
    }

    $cpf = preg_replace('/\D/', '', $data['cpf']);

    // Verifica se o cliente existe
    $stmt = $db->prepare("SELECT cpf FROM clients WHERE cpf = ?");
    $stmt->execute([$cpf]);
    if (!$stmt->fetch()) {
        jsonResponse(['success' => false, 'message' => 'Cliente não encontrado'], 404);
    }

    // Monta a query de atualização dinamicamente
    $updateFields = [];
    $params = [];
    $allowedFields = ['name', 'birthDate', 'phone', 'cep', 'address', 'number', 'complement', 'city', 'planId', 'pppoe', 'password', 'dueDay', 'installer', 'observation', 'status', 'active', 'serial', 'phone_number'];

    foreach ($allowedFields as $field) {
        if (isset($data[$field])) {
            $updateFields[] = "$field = ?";
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

    jsonResponse(['success' => true, 'message' => 'Cliente atualizado com sucesso']);
}

/**
 * DELETE - Excluir cliente
 */
function handleDelete($db) {
    $cpf = $_GET['cpf'] ?? null;

    if (!$cpf) {
        jsonResponse(['success' => false, 'message' => 'CPF é obrigatório para exclusão'], 400);
    }

    $cpf = preg_replace('/\D/', '', $cpf);

    $stmt = $db->prepare("DELETE FROM clients WHERE cpf = ?");
    $stmt->execute([$cpf]);

    if ($stmt->rowCount() === 0) {
        jsonResponse(['success' => false, 'message' => 'Cliente não encontrado'], 404);
    }

    jsonResponse(['success' => true, 'message' => 'Cliente excluído com sucesso']);
}
