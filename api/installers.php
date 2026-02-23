<?php
/**
 * API de Instaladores
 * Ondeline Tech - App do Técnico
 */

require_once 'config.php';

$method = $_SERVER['REQUEST_METHOD'];
$userData = requireAuth();

if ($method !== 'GET') {
    jsonResponse(['success' => false, 'message' => 'Método não permitido'], 405);
}

try {
    $db = Database::getInstance()->getConnection();
    
    $stmt = $db->query("SELECT id, name FROM installers ORDER BY name");
    $installers = $stmt->fetchAll();

    jsonResponse([
        'success' => true,
        'message' => 'Dados carregados com sucesso',
        'data' => $installers
    ]);

} catch (PDOException $e) {
    jsonResponse(['success' => false, 'message' => 'Erro ao buscar instaladores'], 500);
}
