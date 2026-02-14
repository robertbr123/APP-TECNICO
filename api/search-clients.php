<?php
/**
 * API de Busca de Clientes
 * Endpoint otimizado para busca rápida
 */

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse(['success' => false, 'message' => 'Método não permitido'], 405);
}

$userData = requireAuth();

try {
    $db = Database::getInstance()->getConnection();
    
    // Parâmetros
    $search = $_GET['search'] ?? '';
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
    $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
    $sort = $_GET['sort'] ?? 'name'; // 'name' ou 'recent'
    $status = $_GET['status'] ?? null;
    
    // Se for modo recente, não precisa de termo de busca
    $isRecentMode = $sort === 'recent';
    
    if (empty($search) && !$isRecentMode) {
        jsonResponse([
            'success' => false,
            'message' => 'Termo de busca é obrigatório'
        ], 400);
    }
    
    // Busca cidade do usuário para filtro (técnico ou user)
    $userCity = null;
    if ($userData['role'] === 'tecnico' || $userData['role'] === 'user') {
        try {
            $cityStmt = $db->prepare("SELECT city FROM users WHERE id = ?");
            $cityStmt->execute([$userData['user_id']]);
            $userCity = $cityStmt->fetch()['city'] ?? null;
        } catch (Exception $e) {
            // Se falhar, não filtra
        }
    }
    
    // Query de busca otimizada
    $searchTerm = "%$search%";
    $sql = "SELECT 
                cpf, 
                name, 
                phone, 
                city, 
                address, 
                number, 
                serial,
                status,
                active,
                planId,
                latitude,
                longitude,
                created_at
             FROM clients 
             WHERE 1=1";
    
    $params = [];
    
    // Condição de busca (apenas se não for modo recente ou se tiver search)
    if (!empty($search)) {
        $sql .= " AND (name LIKE ? OR cpf LIKE ? OR phone LIKE ? OR city LIKE ?)";
        $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
    }
    
    // Filtro por cidade para técnicos
    if ($userCity) {
        $sql .= " AND LOWER(city) LIKE LOWER(?)";
        $params[] = "%$userCity%";
    }
    
    // Filtro por status
    if ($status === 'active') {
        $sql .= " AND (active = 1 OR status = 'ativo')";
    } else if ($status === 'inactive') {
        $sql .= " AND (active = 0 OR status = 'inativo')";
    }
    
    // Ordenação
    if ($sort === 'recent') {
        $sql .= " ORDER BY created_at DESC";
    } else {
        $sql .= " ORDER BY name ASC";
    }
    
    $sql .= " LIMIT $limit OFFSET $offset";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $clients = $stmt->fetchAll();
    
    jsonResponse([
        'success' => true,
        'data' => $clients,
        'count' => count($clients),
        'search' => $search,
        'filtered_by_city' => $userCity ? true : false
    ]);
    
} catch (PDOException $e) {
    error_log('Erro em search-clients.php: ' . $e->getMessage());
    jsonResponse([
        'success' => false,
        'message' => 'Erro ao buscar clientes',
        'error' => $e->getMessage()
    ], 500);
}