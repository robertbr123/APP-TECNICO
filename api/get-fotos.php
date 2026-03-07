<?php
/**
 * API para buscar fotos de um cliente
 */

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once 'config.php';
require_once 'Logger.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse(['success' => false, 'message' => 'Método não permitido'], 405);
}

try {
    $db = Database::getInstance()->getConnection();
    
    $cpf = preg_replace('/\D/', '', $_GET['cpf'] ?? '');
    
    if (empty($cpf)) {
        jsonResponse(['success' => false, 'message' => 'CPF é obrigatório'], 400);
    }
    
    // Verifica se a tabela existe
    $tableExists = $db->query("SHOW TABLES LIKE 'client_photos'")->rowCount() > 0;
    
    if (!$tableExists) {
        jsonResponse([
            'success' => true,
            'message' => 'Dados carregados com sucesso',
            'data' => [],
            'count' => 0
        ]);
    }
    
    // Busca as fotos do cliente (limite de 50 para performance)
    $stmt = $db->prepare("
        SELECT id, filename, type, created_at
        FROM client_photos
        WHERE cpf = ?
        ORDER BY created_at DESC
        LIMIT 50
    ");
    $stmt->execute([$cpf]);
    $photos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Adiciona URL completa
    foreach ($photos as &$photo) {
        $photo['url'] = '/uploads/' . $photo['filename'];
        $photo['thumb_url'] = '/uploads/' . $photo['filename']; // Mesma imagem por enquanto
    }
    
    jsonResponse([
        'success' => true,
        'message' => 'Dados carregados com sucesso',
        'data' => $photos,
        'count' => count($photos)
    ]);
    
} catch (Exception $e) {
    Logger::logException($e, ['endpoint' => 'get-fotos.php']);
    jsonResponse(['success' => false, 'message' => 'Erro interno do servidor'], 500);
}
