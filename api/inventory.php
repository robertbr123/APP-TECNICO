<?php
/**
 * API de Gestão de Estoque de Equipamentos - Melhorada
 * Controle completo de entrada/saída de equipamentos
 */

require_once 'config.php';
require_once 'Logger.php';
require_once 'Validator.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$userData = requireAuth();

Logger::logRequest('inventory.php', $method, $_REQUEST);

try {
    $db = Database::getInstance()->getConnection();
    
    // Verifica se as tabelas existem
    if (!checkTablesExist($db)) {
        jsonResponse([
            'success' => false, 
            'message' => 'Tabelas de estoque não configuradas. Execute o script create-inventory-tables.sql'
        ], 500);
    }

    switch ($method) {
        case 'GET':
            handleGet($db, $userData);
            break;
        case 'POST':
            handlePost($db, $userData);
            break;
        case 'PUT':
            handlePut($db, $userData);
            break;
        case 'DELETE':
            handleDelete($db, $userData);
            break;
        default:
            Logger::warning('Método não permitido', ['method' => $method]);
            jsonResponse(['success' => false, 'message' => 'Método não permitido'], 405);
    }
} catch (PDOException $e) {
    Logger::logException($e, ['context' => 'database']);
    jsonResponse([
        'success' => false, 
        'message' => 'Erro no banco de dados: ' . $e->getMessage()
    ], 500);
} catch (Exception $e) {
    Logger::logException($e);
    jsonResponse([
        'success' => false, 
        'message' => 'Erro interno do servidor: ' . $e->getMessage()
    ], 500);
}

/**
 * Verifica se as tabelas de estoque existem
 */
function checkTablesExist($db) {
    try {
        $stmt = $db->query("SHOW TABLES LIKE 'equipment_inventory'");
        return $stmt->rowCount() > 0;
    } catch (Exception $e) {
        return false;
    }
}

/**
 * GET - Busca equipamentos ou estatísticas
 */
function handleGet($db, $userData) {
    $action = $_GET['action'] ?? 'list';
    $userId = $userData['user_id'];
    $username = $userData['username'];

    switch ($action) {
        case 'list':
            // Lista equipamentos com filtros
            $status = $_GET['status'] ?? null;
            $type = $_GET['type'] ?? null;
            $search = $_GET['search'] ?? null;
            $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
            $limit = isset($_GET['limit']) ? min(100, max(1, (int)$_GET['limit'])) : 50;
            $offset = ($page - 1) * $limit;

            try {
                // Query simplificada para evitar problemas com JOINs
                $sql = "SELECT ei.* 
                         FROM equipment_inventory ei
                         WHERE 1=1";
                $params = [];

                if ($status) {
                    $sql .= " AND ei.status = ?";
                    $params[] = $status;
                }

                if ($type) {
                    $sql .= " AND ei.type = ?";
                    $params[] = $type;
                }

                if ($search) {
                    $searchTerm = "%$search%";
                    $sql .= " AND (ei.serial_number LIKE ? OR ei.model LIKE ? OR ei.brand LIKE ?)";
                    $params[] = $searchTerm;
                    $params[] = $searchTerm;
                    $params[] = $searchTerm;
                }

                // Conta total
                $countSql = "SELECT COUNT(*) as total FROM equipment_inventory ei WHERE 1=1";
                $countParams = [];
                
                if ($status) {
                    $countSql .= " AND ei.status = ?";
                    $countParams[] = $status;
                }
                if ($type) {
                    $countSql .= " AND ei.type = ?";
                    $countParams[] = $type;
                }
                if ($search) {
                    $searchTerm = "%$search%";
                    $countSql .= " AND (ei.serial_number LIKE ? OR ei.model LIKE ? OR ei.brand LIKE ?)";
                    $countParams[] = $searchTerm;
                    $countParams[] = $searchTerm;
                    $countParams[] = $searchTerm;
                }
                
                $countStmt = $db->prepare($countSql);
                $countStmt->execute($countParams);
                $total = $countStmt->fetch()['total'];

                $sql .= " ORDER BY ei.created_at DESC LIMIT $limit OFFSET $offset";
                $stmt = $db->prepare($sql);
                $stmt->execute($params);
                $equipment = $stmt->fetchAll();

                Logger::logDatabase('SELECT', 'equipment_inventory', count($equipment));

                jsonResponse([
                    'success' => true,
                    'data' => $equipment,
                    'pagination' => [
                        'page' => $page,
                        'limit' => $limit,
                        'total' => (int)$total,
                        'pages' => ceil($total / $limit)
                    ]
                ]);
            } catch (Exception $e) {
                Logger::logException($e, ['context' => 'list_equipment']);
                throw $e;
            }

        case 'statistics':
            // Estatísticas de estoque
            try {
                $stmt = $db->query("
                    SELECT 
                        status,
                        type,
                        COUNT(*) as count,
                        COALESCE(SUM(purchase_price), 0) as total_value
                    FROM equipment_inventory
                    GROUP BY status, type
                    ORDER BY status, type
                ");
                $stats = $stmt->fetchAll();

                $totalAvailable = 0;
                $totalInUse = 0;
                $totalValue = 0;

                foreach ($stats as $stat) {
                    if ($stat['status'] === 'available') {
                        $totalAvailable += $stat['count'];
                    } else if ($stat['status'] === 'in_use') {
                        $totalInUse += $stat['count'];
                    }
                    $totalValue += $stat['total_value'];
                }

                jsonResponse([
                    'success' => true,
                    'data' => [
                        'by_status_type' => $stats,
                        'summary' => [
                            'total_available' => $totalAvailable,
                            'total_in_use' => $totalInUse,
                            'total_value' => round($totalValue, 2)
                        ]
                    ]
                ]);
            } catch (Exception $e) {
                Logger::logException($e, ['context' => 'statistics']);
                throw $e;
            }

        case 'movements':
            // Histórico de movimentações
            try {
                $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
                $limit = isset($_GET['limit']) ? min(100, max(1, (int)$_GET['limit'])) : 20;
                $offset = ($page - 1) * $limit;

                $stmt = $db->prepare("
                    SELECT m.*, 
                           ei.serial_number, 
                           ei.model, 
                           ei.brand,
                           ei.type
                    FROM inventory_movements m
                    LEFT JOIN equipment_inventory ei ON m.equipment_id = ei.id
                    ORDER BY m.created_at DESC 
                    LIMIT $limit OFFSET $offset
                ");
                $stmt->execute();
                $movements = $stmt->fetchAll();

                jsonResponse([
                    'success' => true,
                    'data' => $movements,
                    'count' => count($movements)
                ]);
            } catch (Exception $e) {
                Logger::logException($e, ['context' => 'movements']);
                throw $e;
            }

        case 'alerts':
            // Alertas de estoque
            try {
                $stmt = $db->query("
                    SELECT * FROM inventory_alerts
                    WHERE resolved = 0
                    ORDER BY severity DESC, created_at DESC
                    LIMIT 20
                ");
                $alerts = $stmt->fetchAll();

                jsonResponse([
                    'success' => true,
                    'data' => $alerts,
                    'count' => count($alerts)
                ]);
            } catch (Exception $e) {
                Logger::logException($e, ['context' => 'alerts']);
                throw $e;
            }

        default:
            jsonResponse(['success' => false, 'message' => 'Ação inválida'], 400);
    }
}

/**
 * POST - Adiciona equipamento ou registra movimentação
 */
function handlePost($db, $userData) {
    $userId = $userData['user_id'];
    $username = $userData['username'];
    $input = json_decode(file_get_contents('php://input'), true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        jsonResponse(['success' => false, 'message' => 'JSON inválido'], 400);
    }

    $action = $input['action'] ?? 'add';

    switch ($action) {
        case 'add':
            // Adiciona novo equipamento
            $serialNumber = strtoupper(trim($input['serial_number'] ?? ''));
            $model = $input['model'] ?? '';
            $brand = $input['brand'] ?? null;
            $type = $input['type'] ?? 'router';
            $location = $input['location'] ?? 'Estoque Principal';
            $purchaseDate = $input['purchase_date'] ?? null;
            $purchasePrice = $input['purchase_price'] ?? null;
            $notes = $input['notes'] ?? null;

            // Validações
            if (empty($serialNumber)) {
                jsonResponse(['success' => false, 'message' => 'Número de série é obrigatório'], 400);
            }

            if (empty($model)) {
                jsonResponse(['success' => false, 'message' => 'Modelo é obrigatório'], 400);
            }

            // Verifica se já existe
            $stmt = $db->prepare("SELECT id FROM equipment_inventory WHERE serial_number = ?");
            $stmt->execute([$serialNumber]);
            if ($stmt->fetch()) {
                jsonResponse(['success' => false, 'message' => 'Número de série já cadastrado'], 400);
            }

            // Insere equipamento
            $stmt = $db->prepare("
                INSERT INTO equipment_inventory 
                (serial_number, model, brand, type, status, location, purchase_date, purchase_price, notes)
                VALUES (?, ?, ?, ?, 'available', ?, ?, ?, ?)
            ");
            $stmt->execute([
                $serialNumber,
                $model,
                $brand,
                $type,
                $location,
                $purchaseDate,
                $purchasePrice,
                $notes
            ]);

            $equipmentId = $db->lastInsertId();

            // Registra movimentação
            $stmt = $db->prepare("
                INSERT INTO inventory_movements 
                (equipment_id, movement_type, from_location, to_location, user_id, username, notes)
                VALUES (?, 'in', NULL, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $equipmentId,
                $location,
                $userId,
                $username,
                'Entrada de novo equipamento no estoque'
            ]);

            // Registra auditoria
            logAudit($db, $userData, 'inventory_add', "Equipamento adicionado: $serialNumber", 'equipment', $equipmentId, "$brand $model");
            
            Logger::info('Equipamento adicionado', [
                'equipment_id' => $equipmentId,
                'serial' => $serialNumber
            ]);

            jsonResponse([
                'success' => true,
                'message' => 'Equipamento adicionado com sucesso',
                'data' => ['id' => $equipmentId]
            ]);

        case 'checkout':
            // Saída de equipamento para cliente
            $equipmentId = (int)($input['equipment_id'] ?? 0);
            $clientCpf = preg_replace('/\D/', '', $input['client_cpf'] ?? '');
            $notes = $input['notes'] ?? null;

            // Validações
            if (!$equipmentId) {
                jsonResponse(['success' => false, 'message' => 'ID do equipamento é obrigatório'], 400);
            }

            if (empty($clientCpf)) {
                jsonResponse(['success' => false, 'message' => 'CPF do cliente é obrigatório'], 400);
            }

            // Valida CPF
            $cpfValidation = Validator::validateCPF($clientCpf);
            if (!$cpfValidation['valid']) {
                jsonResponse(['success' => false, 'message' => $cpfValidation['message']], 400);
            }

            $clientCpf = $cpfValidation['clean'];

            // Busca equipamento
            $stmt = $db->prepare("SELECT * FROM equipment_inventory WHERE id = ?");
            $stmt->execute([$equipmentId]);
            $equipment = $stmt->fetch();

            if (!$equipment) {
                jsonResponse(['success' => false, 'message' => 'Equipamento não encontrado'], 404);
            }

            if ($equipment['status'] !== 'available') {
                jsonResponse(['success' => false, 'message' => 'Equipamento não está disponível'], 400);
            }

            // Busca cliente
            $stmt = $db->prepare("SELECT name FROM clients WHERE cpf = ?");
            $stmt->execute([$clientCpf]);
            $client = $stmt->fetch();

            if (!$client) {
                jsonResponse(['success' => false, 'message' => 'Cliente não encontrado'], 404);
            }

            // Atualiza equipamento
            $stmt = $db->prepare("
                UPDATE equipment_inventory 
                SET status = 'in_use', 
                    current_user_id = ?,
                    current_client_cpf = ?,
                    location = 'Em uso',
                    updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$userId, $clientCpf, $equipmentId]);

            // Registra movimentação
            $stmt = $db->prepare("
                INSERT INTO inventory_movements 
                (equipment_id, movement_type, from_location, to_location, user_id, username, client_cpf, client_name, notes)
                VALUES (?, 'out', ?, 'Em uso', ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $equipmentId,
                $equipment['location'],
                $userId,
                $username,
                $clientCpf,
                $client['name'],
                $notes
            ]);

            // Registra auditoria
            logAudit($db, $userData, 'inventory_checkout', "Equipamento entregue: {$equipment['serial_number']}", 'equipment', $equipmentId, "{$equipment['brand']} {$equipment['model']}");
            
            Logger::info('Equipamento entregue', [
                'equipment_id' => $equipmentId,
                'client_cpf' => $clientCpf
            ]);

            jsonResponse([
                'success' => true,
                'message' => 'Equipamento entregue ao cliente',
                'data' => ['client_name' => $client['name']]
            ]);

        default:
            jsonResponse(['success' => false, 'message' => 'Ação inválida'], 400);
    }
}

/**
 * PUT - Atualiza equipamento ou resolve alerta
 */
function handlePut($db, $userData) {
    $userId = $userData['user_id'];
    $username = $userData['username'];
    $input = json_decode(file_get_contents('php://input'), true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        jsonResponse(['success' => false, 'message' => 'JSON inválido'], 400);
    }

    $action = $input['action'] ?? 'update';

    switch ($action) {
        case 'update':
            // Atualiza dados do equipamento
            $equipmentId = (int)($input['id'] ?? 0);
            
            $stmt = $db->prepare("SELECT * FROM equipment_inventory WHERE id = ?");
            $stmt->execute([$equipmentId]);
            $equipment = $stmt->fetch();

            if (!$equipment) {
                jsonResponse(['success' => false, 'message' => 'Equipamento não encontrado'], 404);
            }

            $updateFields = [];
            $params = [];
            $allowedFields = ['model', 'brand', 'type', 'location', 'purchase_date', 'purchase_price', 'notes'];

            foreach ($allowedFields as $field) {
                if (isset($input[$field])) {
                    $updateFields[] = "$field = ?";
                    $params[] = $input[$field];
                }
            }

            if (empty($updateFields)) {
                jsonResponse(['success' => false, 'message' => 'Nenhum campo para atualizar'], 400);
            }

            $params[] = $equipmentId;
            $sql = "UPDATE equipment_inventory SET " . implode(', ', $updateFields) . " WHERE id = ?";
            $stmt = $db->prepare($sql);
            $stmt->execute($params);

            // Registra auditoria
            logAudit($db, $userData, 'inventory_update', "Equipamento atualizado: {$equipment['serial_number']}", 'equipment', $equipmentId, "{$equipment['brand']} {$equipment['model']}");

            jsonResponse([
                'success' => true,
                'message' => 'Equipamento atualizado com sucesso'
            ]);

        case 'return':
            // Retorna equipamento ao estoque
            $equipmentId = (int)($input['id'] ?? 0);
            $status = $input['status'] ?? 'available';
            $location = $input['location'] ?? 'Estoque Principal';
            $notes = $input['notes'] ?? null;

            $stmt = $db->prepare("SELECT * FROM equipment_inventory WHERE id = ?");
            $stmt->execute([$equipmentId]);
            $equipment = $stmt->fetch();

            if (!$equipment) {
                jsonResponse(['success' => false, 'message' => 'Equipamento não encontrado'], 404);
            }

            // Atualiza equipamento
            $stmt = $db->prepare("
                UPDATE equipment_inventory 
                SET status = ?, 
                    current_user_id = NULL,
                    current_client_cpf = NULL,
                    location = ?,
                    updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$status, $location, $equipmentId]);

            // Registra movimentação
            $movementType = ($status === 'defective') ? 'defective' : 'in';
            $stmt = $db->prepare("
                INSERT INTO inventory_movements 
                (equipment_id, movement_type, from_location, to_location, user_id, username, notes)
                VALUES (?, ?, 'Em uso', ?, ?, ?, ?)
            ");
            $stmt->execute([
                $equipmentId,
                $movementType,
                $location,
                $userId,
                $username,
                $notes
            ]);

            // Registra auditoria
            logAudit($db, $userData, 'inventory_return', "Equipamento retornado: {$equipment['serial_number']}", 'equipment', $equipmentId, "{$equipment['brand']} {$equipment['model']}");

            jsonResponse([
                'success' => true,
                'message' => 'Equipamento retornado ao estoque'
            ]);

        case 'resolve_alert':
            // Resolve alerta
            $alertId = (int)($input['alert_id'] ?? 0);

            $stmt = $db->prepare("UPDATE inventory_alerts SET resolved = 1, resolved_at = NOW() WHERE id = ?");
            $stmt->execute([$alertId]);

            jsonResponse([
                'success' => true,
                'message' => 'Alerta resolvido'
            ]);

        default:
            jsonResponse(['success' => false, 'message' => 'Ação inválida'], 400);
    }
}

/**
 * DELETE - Remove equipamento
 */
function handleDelete($db, $userData) {
    $equipmentId = (int)($_GET['id'] ?? 0);

    if (!$equipmentId) {
        jsonResponse(['success' => false, 'message' => 'ID do equipamento é obrigatório'], 400);
    }

    // Busca equipamento antes de deletar
    $stmt = $db->prepare("SELECT * FROM equipment_inventory WHERE id = ?");
    $stmt->execute([$equipmentId]);
    $equipment = $stmt->fetch();

    if (!$equipment) {
        jsonResponse(['success' => false, 'message' => 'Equipamento não encontrado'], 404);
    }

    // Deleta equipamento
    $stmt = $db->prepare("DELETE FROM equipment_inventory WHERE id = ?");
    $stmt->execute([$equipmentId]);

    // Registra auditoria
    logAudit($db, $userData, 'inventory_delete', "Equipamento removido: {$equipment['serial_number']}", 'equipment', $equipmentId, "{$equipment['brand']} {$equipment['model']}");
    
    Logger::info('Equipamento deletado', ['equipment_id' => $equipmentId]);

    jsonResponse([
        'success' => true,
        'message' => 'Equipamento removido com sucesso'
    ]);
}

// logAudit() centralizado em config.php
