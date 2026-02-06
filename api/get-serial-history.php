<?php
/**
 * API para buscar histórico de seriais de um cliente
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

// Apenas GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
    exit;
}

try {
    // Conexão com banco
    $db = Database::getInstance()->getConnection();
    
    // Obtém o CPF
    $cpf = preg_replace('/\D/', '', $_GET['cpf'] ?? '');
    
    if (empty($cpf)) {
        echo json_encode(['success' => false, 'message' => 'CPF é obrigatório']);
        exit;
    }
    
    // Busca histórico
    $stmt = $db->prepare("
        SELECT 
            id,
            client_name,
            old_serial,
            new_serial,
            reason,
            reason_description,
            old_photos,
            changed_by_name,
            DATE_FORMAT(created_at, '%d/%m/%Y %H:%i') as formatted_date,
            created_at,
            TIMESTAMPDIFF(MINUTE, created_at, NOW()) as minutes_ago
        FROM serial_history
        WHERE cpf = ?
        ORDER BY created_at DESC
        LIMIT 50
    ");
    
    $stmt->execute([$cpf]);
    $history = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Processa resultados
    foreach ($history as &$item) {
        // Formata o tempo relativo
        $item['time_ago'] = formatTimeAgo($item['minutes_ago']);
        
        // Decodifica fotos antigas se existirem
        if (!empty($item['old_photos'])) {
            $item['old_photos'] = json_decode($item['old_photos'], true);
        }
        
        // Traduz motivo
        $reasons = [
            'defect' => 'Defeito',
            'upgrade' => 'Upgrade',
            'transfer' => 'Transferência',
            'theft' => 'Roubo/Furto',
            'other' => 'Outro'
        ];
        $item['reason_label'] = $reasons[$item['reason']] ?? 'Outro';
    }
    
    echo json_encode([
        'success' => true,
        'data' => $history,
        'total' => count($history),
        'cpf' => $cpf
    ]);
    
} catch (PDOException $e) {
    error_log('Erro PDO em get-serial-history.php: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erro de banco de dados']);
} catch (Exception $e) {
    error_log('Erro em get-serial-history.php: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erro interno do servidor']);
}

/**
 * Formata tempo relativo em português
 */
function formatTimeAgo($minutes) {
    if ($minutes < 1) return 'agora mesmo';
    if ($minutes < 60) return 'há ' . $minutes . ' minuto(s)';
    
    $hours = floor($minutes / 60);
    if ($hours < 24) return 'há ' . $hours . ' hora(s)';
    
    $days = floor($hours / 24);
    if ($days < 30) return 'há ' . $days . ' dia(s)';
    
    $months = floor($days / 30);
    if ($months < 12) return 'há ' . $months . ' mês(es)';
    
    $years = floor($months / 12);
    return 'há ' . $years . ' ano(s)';
}