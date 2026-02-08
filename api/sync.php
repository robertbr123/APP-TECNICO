<?php
/**
 * API de Sincronização Offline
 * Processa a fila de operações pendentes quando o usuário reconecta
 */

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once 'config.php';

$method = $_SERVER['REQUEST_METHOD'];
$userData = requireAuth();

try {
    $db = Database::getInstance()->getConnection();

    switch ($method) {
        case 'GET':
            // Retorna contagem de itens pendentes
            handleGet($db, $userData);
            break;
        case 'POST':
            // Processa a fila de sincronização
            handlePost($db, $userData);
            break;
        default:
            jsonResponse(['success' => false, 'message' => 'Método não permitido'], 405);
    }
} catch (PDOException $e) {
    jsonResponse(['success' => false, 'message' => 'Erro no banco de dados', 'error' => $e->getMessage()], 500);
}

/**
 * GET - Contagem de itens pendentes
 */
function handleGet($db, $userData) {
    $userId = $userData['user_id'];
    
    $stmt = $db->prepare("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed,
            SUM(CASE WHEN status = 'synced' THEN 1 ELSE 0 END) as synced
        FROM offline_queue 
        WHERE user_id = ? OR username = ?
    ");
    $stmt->execute([$userId, $userData['username']]);
    $result = $stmt->fetch();

    jsonResponse([
        'success' => true,
        'data' => [
            'total' => (int)$result['total'],
            'pending' => (int)$result['pending'],
            'failed' => (int)$result['failed'],
            'synced' => (int)$result['synced']
        ]
    ]);
}

/**
 * POST - Processa a fila de sincronização
 */
function handlePost($db, $userData) {
    $userId = $userData['user_id'];
    $username = $userData['username'];

    // Busca itens pendentes
    $stmt = $db->prepare("
        SELECT id, action_type, data_json, attempt_count 
        FROM offline_queue 
        WHERE (user_id = ? OR username = ?) AND status IN ('pending', 'failed')
        ORDER BY created_at ASC 
        LIMIT 10
    ");
    $stmt->execute([$userId, $username]);
    $items = $stmt->fetchAll();

    if (empty($items)) {
        jsonResponse([
            'success' => true,
            'message' => 'Nenhum item para sincronizar',
            'processed' => 0,
            'errors' => []
        ]);
    }

    $processed = 0;
    $errors = [];

    foreach ($items as $item) {
        $itemId = $item['id'];
        $actionType = $item['action_type'];
        $data = json_decode($item['data_json'], true);
        $attemptCount = $item['attempt_count'];

        $success = false;
        $errorMessage = null;

        try {
            switch ($actionType) {
                case 'create_client':
                    $success = syncCreateClient($db, $data, $userData);
                    break;
                case 'update_client':
                    $success = syncUpdateClient($db, $data, $userData);
                    break;
                case 'link_equipment':
                    $success = syncLinkEquipment($db, $data, $userData);
                    break;
                case 'upload_photo':
                    $success = syncUploadPhoto($db, $data, $userData);
                    break;
                default:
                    $errorMessage = 'Tipo de ação desconhecido: ' . $actionType;
                    $success = false;
            }
        } catch (Exception $e) {
            $errorMessage = $e->getMessage();
            $success = false;
        }

        // Atualiza status no banco
        if ($success) {
            $updateStmt = $db->prepare("
                UPDATE offline_queue 
                SET status = 'synced', synced_at = NOW(), attempt_count = attempt_count + 1
                WHERE id = ?
            ");
            $updateStmt->execute([$itemId]);
            $processed++;
        } else {
            $newAttemptCount = $attemptCount + 1;
            $newStatus = ($newAttemptCount >= 5) ? 'failed' : 'pending';
            
            $updateStmt = $db->prepare("
                UPDATE offline_queue 
                SET status = ?, attempt_count = ?, error_message = ?
                WHERE id = ?
            ");
            $updateStmt->execute([$newStatus, $newAttemptCount, $errorMessage, $itemId]);
            
            if ($newStatus === 'failed') {
                $errors[] = [
                    'action' => $actionType,
                    'data' => $data,
                    'error' => $errorMessage
                ];
            }
        }
    }

    jsonResponse([
        'success' => true,
        'message' => "Sincronização concluída: $processed item(s) processado(s)",
        'processed' => $processed,
        'total' => count($items),
        'errors' => $errors
    ]);
}

/**
 * Sincroniza criação de cliente
 */
function syncCreateClient($db, $data, $userData) {
    $cpf = preg_replace('/\D/', '', $data['cpf']);
    
    // Verifica se já existe
    $stmt = $db->prepare("SELECT cpf FROM clients WHERE cpf = ?");
    $stmt->execute([$cpf]);
    if ($stmt->fetch()) {
        throw new Exception("CPF já cadastrado: $cpf");
    }

    $installer = $data['installer'] ?? $userData['username'];
    
    $stmt = $db->prepare("
        INSERT INTO clients (cpf, name, phone, birthDate, cep, city, address, number, complement, 
                              planId, pppoe, password, dueDay, observation, installer, status, active, 
                              latitude, longitude, location_accuracy, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
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
        $data['active'] ?? 1,
        $data['latitude'] ?? null,
        $data['longitude'] ?? null,
        $data['accuracy'] ?? null
    ]);

    // Registra auditoria
    logAudit($db, $userData, 'client_created_offline', "Cliente sincronizado: {$data['name']}", 'client', $cpf, $data['name']);
    
    return true;
}

/**
 * Sincroniza atualização de cliente
 */
function syncUpdateClient($db, $data, $userData) {
    $cpf = preg_replace('/\D/', '', $data['cpf']);
    
    $updateFields = [];
    $params = [];
    $allowedFields = ['name', 'phone', 'cep', 'city', 'address', 'number', 'complement', 'planId', 'pppoe', 'password', 'dueDay', 'observation', 'status', 'active', 'serial'];

    foreach ($allowedFields as $field) {
        if (isset($data[$field])) {
            $updateFields[] = "$field = ?";
            $params[] = $data[$field];
        }
    }

    if (empty($updateFields)) {
        throw new Exception('Nenhum campo para atualizar');
    }

    $params[] = $cpf;
    $sql = "UPDATE clients SET " . implode(', ', $updateFields) . " WHERE cpf = ?";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);

    // Registra auditoria
    logAudit($db, $userData, 'client_updated_offline', "Cliente atualizado offline: $cpf", 'client', $cpf, '');
    
    return true;
}

/**
 * Sincroniza vinculação de equipamento
 */
function syncLinkEquipment($db, $data, $userData) {
    $cpf = preg_replace('/\D/', '', $data['cpf']);
    $serial = strtoupper(trim($data['serial']));
    $reason = $data['reason'] ?? 'other';
    $reasonDesc = $data['reason_description'] ?? '';

    // Busca cliente
    $stmt = $db->prepare("SELECT cpf, name, serial FROM clients WHERE cpf = ?");
    $stmt->execute([$cpf]);
    $client = $stmt->fetch();

    if (!$client) {
        throw new Exception("Cliente não encontrado: $cpf");
    }

    $oldSerial = $client['serial'];

    // Salva histórico
    $historyStmt = $db->prepare("
        INSERT INTO serial_history 
        (cpf, client_name, old_serial, new_serial, reason, reason_description, changed_by, changed_by_name)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $historyStmt->execute([
        $cpf,
        $client['name'],
        $oldSerial,
        $serial,
        $reason,
        $reasonDesc,
        $userData['user_id'],
        $userData['username']
    ]);

    // Atualiza serial
    $stmt = $db->prepare("UPDATE clients SET serial = ? WHERE cpf = ?");
    $stmt->execute([$serial, $cpf]);

    // Registra auditoria
    logAudit($db, $userData, 'equipment_linked_offline', "Equipamento vinculado offline: $serial", 'client', $cpf, $client['name']);
    
    return true;
}

/**
 * Sincroniza upload de foto
 */
function syncUploadPhoto($db, $data, $userData) {
    $cpf = preg_replace('/\D/', '', $data['cpf']);
    $photoData = $data['photo'] ?? '';
    $type = $data['type'] ?? 'other';

    if (empty($photoData)) {
        throw new Exception('Foto não encontrada nos dados');
    }

    // Decodifica base64
    if (preg_match('/^data:image\/(\w+);base64,/', $photoData, $typeMatches)) {
        $imageType = $typeMatches[1];
        $photoData = substr($photoData, strpos($photoData, ',') + 1);
        $photoData = base64_decode($photoData);

        if (!$photoData) {
            throw new Exception('Falha ao decodificar foto');
        }

        // Gera nome de arquivo
        $filename = uniqid($cpf . '_' . $type . '_') . '.' . $imageType;

        // Salva arquivo
        $uploadDir = __DIR__ . '/../uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $filepath = $uploadDir . $filename;
        if (file_put_contents($filepath, $photoData) === false) {
            throw new Exception('Falha ao salvar foto no servidor');
        }

        // Salva no banco
        $stmt = $db->prepare("
            INSERT INTO client_photos (cpf, filename, type, uploaded_by)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$cpf, $filename, $type, $userData['user_id']]);

        // Registra auditoria
        logAudit($db, $userData, 'photo_uploaded_offline', "Foto enviada offline: $type", 'client', $cpf, '');
        
        return true;
    }

    throw new Exception('Formato de foto inválido');
}

/**
 * Registra log de auditoria
 */
function logAudit($db, $userData, $actionType, $description, $entityType, $entityId, $entityName) {
    try {
        $stmt = $db->prepare("
            INSERT INTO audit_logs 
            (user_id, username, action_type, action_description, entity_type, entity_id, entity_name, ip_address, user_agent)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $userData['user_id'],
            $userData['username'],
            $actionType,
            $description,
            $entityType,
            $entityId,
            $entityName,
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? null
        ]);
    } catch (Exception $e) {
        error_log('Erro ao registrar auditoria: ' . $e->getMessage());
    }
}