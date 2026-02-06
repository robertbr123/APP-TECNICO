<?php
/**
 * API de Consulta de Logs de Auditoria
 * Retorna os registros de ações realizadas pelos técnicos
 */

require_once 'config.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method !== 'GET') {
    jsonResponse(['success' => false, 'message' => 'Método não permitido'], 405);
}

try {
    $db = Database::getInstance()->getConnection();
    
    // Parâmetros de filtros
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 100;
    $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
    $actionType = $_GET['action_type'] ?? null;
    $username = $_GET['username'] ?? null;
    $startDate = $_GET['start_date'] ?? null;
    $endDate = $_GET['end_date'] ?? null;
    
    // Monta a query base
    $sql = "SELECT a.*, 
            CASE 
                WHEN a.user_id IS NULL THEN 'Sistema'
                ELSE COALESCE(u.full_name, a.username)
            END as user_full_name
            FROM audit_logs a
            LEFT JOIN users u ON a.user_id = u.id";
    
    $params = [];
    $whereConditions = [];
    
    // Filtros
    if ($actionType) {
        $whereConditions[] = "a.action_type = ?";
        $params[] = $actionType;
    }
    
    if ($username) {
        $whereConditions[] = "a.username LIKE ?";
        $params[] = "%$username%";
    }
    
    if ($startDate) {
        $whereConditions[] = "DATE(a.created_at) >= ?";
        $params[] = $startDate;
    }
    
    if ($endDate) {
        $whereConditions[] = "DATE(a.created_at) <= ?";
        $params[] = $endDate;
    }
    
    // Adiciona condições WHERE
    if (!empty($whereConditions)) {
        $sql .= " WHERE " . implode(" AND ", $whereConditions);
    }
    
    // Ordena por data (mais recente primeiro) e limita
    $sql .= " ORDER BY a.created_at DESC LIMIT $limit OFFSET $offset";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $logs = $stmt->fetchAll();
    
    // Converte o campo details de JSON para array
    foreach ($logs as &$log) {
        if ($log['details']) {
            $log['details'] = json_decode($log['details'], true);
        } else {
            $log['details'] = null;
        }
        
        // Formata a data
        $log['created_at_formatted'] = date('d/m/Y H:i:s', strtotime($log['created_at']));
        $log['created_at_relative'] = getRelativeTime($log['created_at']);
    }
    
    // Conta total de registros
    $countSql = "SELECT COUNT(*) as total FROM audit_logs a";
    if (!empty($whereConditions)) {
        $countSql .= " WHERE " . implode(" AND ", $whereConditions);
    }
    
    $countStmt = $db->prepare($countSql);
    $countStmt->execute($params);
    $total = $countStmt->fetch()['total'];
    
    jsonResponse([
        'success' => true,
        'data' => $logs,
        'total' => $total,
        'limit' => $limit,
        'offset' => $offset
    ]);
    
} catch (PDOException $e) {
    jsonResponse(['success' => false, 'message' => 'Erro no banco de dados', 'error' => $e->getMessage()], 500);
}

/**
 * Retorna tempo relativo (ex: "há 5 minutos")
 */
function getRelativeTime($datetime) {
    $time = strtotime($datetime);
    $now = time();
    $diff = $now - $time;
    
    if ($diff < 60) {
        return 'agora mesmo';
    } elseif ($diff < 3600) {
        $minutes = floor($diff / 60);
        return $minutes == 1 ? 'há 1 minuto' : "há $minutes minutos";
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return $hours == 1 ? 'há 1 hora' : "há $hours horas";
    } elseif ($diff < 604800) {
        $days = floor($diff / 86400);
        return $days == 1 ? 'ontem' : "há $days dias";
    } elseif ($diff < 2629743) {
        $weeks = floor($diff / 604800);
        return $weeks == 1 ? 'há 1 semana' : "há $weeks semanas";
    } else {
        return date('d/m/Y', $time);
    }
}