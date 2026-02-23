<?php
/**
 * API de Registro de Ponto Eletrônico
 * Gerencia entrada/saída de técnicos com foto e GPS
 */

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once 'config.php';
require_once 'Logger.php';

$method = $_SERVER['REQUEST_METHOD'];
$userData = requireAuth();

try {
    $db = Database::getInstance()->getConnection();

    switch ($method) {
        case 'GET':
            handleGet($db, $userData);
            break;
        case 'POST':
            handlePost($db, $userData);
            break;
        default:
            jsonResponse(['success' => false, 'message' => 'Método não permitido'], 405);
    }
} catch (PDOException $e) {
    Logger::logException($e, ['endpoint' => 'time-clock.php']);
    jsonResponse(['success' => false, 'message' => 'Erro no banco de dados'], 500);
}

/**
 * GET - Busca registros de ponto
 */
function handleGet($db, $userData) {
    $userId = $userData['user_id'];
    $date = $_GET['date'] ?? null;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
    $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;

    $sql = "SELECT * FROM time_clock WHERE user_id = ?";
    $params = [$userId];

    // Filtro por data específica
    if ($date) {
        $sql .= " AND clock_date = ?";
        $params[] = $date;
    }

    $sql .= " ORDER BY clock_date DESC, created_at DESC LIMIT $limit OFFSET $offset";

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $records = $stmt->fetchAll();

    // Se foi busca por data específica, retorna apenas o registro
    if ($date && count($records) > 0) {
        jsonResponse([
            'success' => true,
            'data' => $records[0]
        ]);
    }

    jsonResponse([
        'success' => true,
        'data' => $records,
        'count' => count($records)
    ]);
}

/**
 * POST - Registra entrada ou saída
 */
function handlePost($db, $userData) {
    $userId = $userData['user_id'];
    $username = $userData['username'];
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        jsonResponse(['success' => false, 'message' => 'JSON inválido'], 400);
    }

    $type = $input['type'] ?? '';
    $photo = $input['photo'] ?? '';
    $latitude = $input['latitude'] ?? null;
    $longitude = $input['longitude'] ?? null;
    $accuracy = $input['accuracy'] ?? null;

    // Validações
    if (!in_array($type, ['entry', 'exit'])) {
        jsonResponse(['success' => false, 'message' => 'Tipo deve ser entry ou exit'], 400);
    }

    if (empty($photo)) {
        jsonResponse(['success' => false, 'message' => 'Foto é obrigatória'], 400);
    }

    if ($latitude === null || $longitude === null) {
        jsonResponse(['success' => false, 'message' => 'Localização é obrigatória'], 400);
    }

    // Verifica se já existe registro hoje
    $today = date('Y-m-d');
    $stmt = $db->prepare("SELECT * FROM time_clock WHERE user_id = ? AND clock_date = ?");
    $stmt->execute([$userId, $today]);
    $todayRecord = $stmt->fetch();

    // Validações baseadas no tipo
    if ($type === 'entry') {
        if ($todayRecord && $todayRecord['entry_time']) {
            jsonResponse(['success' => false, 'message' => 'Já existe entrada registrada hoje'], 400);
        }
        
        if ($todayRecord && !$todayRecord['entry_time'] && $todayRecord['exit_time']) {
            jsonResponse(['success' => false, 'message' => 'Não é possível registrar entrada após saída'], 400);
        }

        $currentTime = date('H:i:s');
        
        // Se já existe registro sem entrada, atualiza
        if ($todayRecord) {
            $stmt = $db->prepare("
                UPDATE time_clock 
                SET entry_time = ?, entry_photo = ?, entry_latitude = ?, entry_longitude = ?, entry_accuracy = ?
                WHERE id = ?
            ");
            $stmt->execute([
                $currentTime,
                $photo,
                $latitude,
                $longitude,
                $accuracy,
                $todayRecord['id']
            ]);
        } else {
            // Cria novo registro
            $stmt = $db->prepare("
                INSERT INTO time_clock 
                (user_id, username, clock_date, entry_time, entry_photo, entry_latitude, entry_longitude, entry_accuracy)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $userId,
                $username,
                $today,
                $currentTime,
                $photo,
                $latitude,
                $longitude,
                $accuracy
            ]);
        }

        // Registra auditoria
        logAudit($db, $userData, 'time_clock_entry', "Entrada registrada às $currentTime", 'time_clock', $userId, $username);

        jsonResponse([
            'success' => true,
            'message' => 'Entrada registrada com sucesso',
            'data' => [
                'type' => 'entry',
                'time' => $currentTime,
                'date' => $today
            ]
        ]);

    } elseif ($type === 'exit') {
        if (!$todayRecord || !$todayRecord['entry_time']) {
            jsonResponse(['success' => false, 'message' => 'Necessário registrar entrada primeiro'], 400);
        }

        if ($todayRecord['exit_time']) {
            jsonResponse(['success' => false, 'message' => 'Já existe saída registrada hoje'], 400);
        }

        $currentTime = date('H:i:s');

        // Atualiza registro existente
        $stmt = $db->prepare("
            UPDATE time_clock 
            SET exit_time = ?, exit_photo = ?, exit_latitude = ?, exit_longitude = ?, exit_accuracy = ?
            WHERE id = ?
        ");
        $stmt->execute([
            $currentTime,
            $photo,
            $latitude,
            $longitude,
            $accuracy,
            $todayRecord['id']
        ]);

        // Calcula horas trabalhadas
        $entryTime = strtotime($todayRecord['entry_time']);
        $exitTime = strtotime($currentTime);
        $workedSeconds = $exitTime - $entryTime;
        $workedHours = gmdate('H:i:s', $workedSeconds);

        // Atualiza horas trabalhadas
        $stmt = $db->prepare("UPDATE time_clock SET worked_hours = ? WHERE id = ?");
        $stmt->execute([$workedHours, $todayRecord['id']]);

        // Registra auditoria
        logAudit($db, $userData, 'time_clock_exit', "Saída registrada às $currentTime", 'time_clock', $userId, $username);

        jsonResponse([
            'success' => true,
            'message' => 'Saída registrada com sucesso',
            'data' => [
                'type' => 'exit',
                'time' => $currentTime,
                'date' => $today,
                'worked_hours' => $workedHours
            ]
        ]);
    }
}

// logAudit() centralizado em config.php