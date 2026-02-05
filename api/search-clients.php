<?php
/**
 * API de Busca de Clientes - SEM autenticação para teste
 * Depois de testar, você pode remover este arquivo
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

try {
    $db = Database::getInstance()->getConnection();
    
    $searchTerm = "%$search%";
    $sql = "SELECT cpf, name, address, number, complement, city, serial, phone, pppoe, password, dueDay, planId, status, observation FROM clients WHERE name LIKE ? OR cpf LIKE ? ORDER BY name LIMIT ?";
    $stmt = $db->prepare($sql);
    $stmt->execute([$searchTerm, $searchTerm, $limit]);
    $clients = $stmt->fetchAll();
    
    jsonResponse([
        'success' => true,
        'data' => $clients,
        'total' => count($clients)
    ]);
    
} catch (PDOException $e) {
    jsonResponse(['success' => false, 'message' => 'Erro no banco de dados', 'error' => $e->getMessage()], 500);
}
