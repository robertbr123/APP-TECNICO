<?php
/**
 * API de Busca de Clientes - Com filtro por cidade do técnico
 * Ondeline Tech - App do Técnico
 */

require_once 'config.php';

// Headers CORS
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse(['success' => false, 'message' => 'Método não permitido'], 405);
}

$search = $_GET['search'] ?? null;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;

if (!$search || strlen($search) < 2) {
    jsonResponse(['success' => false, 'message' => 'Termo de busca deve ter pelo menos 2 caracteres']);
}

// Busca cidade do técnico para filtro
$userCity = null;
$headers = getallheaders();
$authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';

if (!empty($authHeader) && preg_match('/Bearer\s+(.+)/', $authHeader, $matches)) {
    try {
        $payload = verifyToken($matches[1]);
        if ($payload && isset($payload['role']) && $payload['role'] === 'tecnico') {
            $tmpDb = Database::getInstance()->getConnection();
            $cityStmt = $tmpDb->prepare("SELECT city FROM users WHERE id = ?");
            $cityStmt->execute([$payload['user_id']]);
            $userCity = $cityStmt->fetch()['city'] ?? null;
        }
    } catch (Exception $e) {
        // Token inválido, continua sem filtro
    }
}

try {
    $db = Database::getInstance()->getConnection();

    $searchTerm = "%$search%";
    $conditions = ["(name LIKE ? OR cpf LIKE ?)"];
    $params = [$searchTerm, $searchTerm];

    // Filtro por cidade do técnico
    if ($userCity) {
        $conditions[] = "LOWER(city) LIKE LOWER(?)";
        $params[] = "%$userCity%";
    }

    $sql = "SELECT cpf, name, address, number, complement, city, serial, phone, pppoe, password, dueDay, planId, status, observation, contrato FROM clients WHERE " . implode(' AND ', $conditions) . " ORDER BY name LIMIT " . (int)$limit;
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $clients = $stmt->fetchAll();

    jsonResponse([
        'success' => true,
        'data' => $clients,
        'total' => count($clients)
    ]);

} catch (PDOException $e) {
    jsonResponse(['success' => false, 'message' => 'Erro no banco de dados', 'error' => $e->getMessage()], 500);
}
