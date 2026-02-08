<?php
/**
 * API de Checklist de Instalação
 * Ondeline Tech - App do Técnico
 */

require_once 'config.php';
require_once 'Logger.php';
require_once 'Validator.php';

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$userData = requireAuth();
$method = $_SERVER['REQUEST_METHOD'];

try {
    $db = Database::getInstance()->getConnection();

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
            jsonResponse(['success' => false, 'message' => 'Método não permitido'], 405);
    }
} catch (PDOException $e) {
    Logger::logException($e, ['context' => 'database']);
    jsonResponse(['success' => false, 'message' => 'Erro no banco de dados'], 500);
} catch (Exception $e) {
    Logger::logException($e);
    jsonResponse(['success' => false, 'message' => 'Erro interno do servidor'], 500);
}

/**
 * GET - Busca checklists
 */
function handleGet($db, $userData) {
    $action = $_GET['action'] ?? 'list';
    
    switch ($action) {
        case 'list':
            // Lista checklists do técnico
            $status = $_GET['status'] ?? null;
            $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
            $limit = isset($_GET['limit']) ? min(50, max(1, (int)$_GET['limit'])) : 20;
            $offset = ($page - 1) * $limit;
            
            $sql = "SELECT * FROM installation_checklists WHERE technician_id = ?";
            $params = [$userData['user_id']];
            
            if ($status) {
                $sql .= " AND status = ?";
                $params[] = $status;
            }
            
            // Conta total
            $countStmt = $db->prepare(str_replace("SELECT *", "SELECT COUNT(*) as total", $sql));
            $countStmt->execute($params);
            $total = $countStmt->fetch()['total'];
            
            $sql .= " ORDER BY created_at DESC LIMIT $limit OFFSET $offset";
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $checklists = $stmt->fetchAll();
            
            jsonResponse([
                'success' => true,
                'data' => $checklists,
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => (int)$total,
                    'pages' => ceil($total / $limit)
                ]
            ]);
            
        case 'get':
            // Busca checklist específico
            $id = (int)($_GET['id'] ?? 0);
            
            if (!$id) {
                jsonResponse(['success' => false, 'message' => 'ID obrigatório'], 400);
            }
            
            $stmt = $db->prepare("
                SELECT ic.*, 
                    COUNT(ci.id) as total_tasks,
                    SUM(CASE WHEN ci.is_completed = 1 THEN 1 ELSE 0 END) as completed_tasks
                FROM installation_checklists ic
                LEFT JOIN checklist_items ci ON ic.id = ci.checklist_id
                WHERE ic.id = ? AND ic.technician_id = ?
                GROUP BY ic.id
            ");
            $stmt->execute([$id, $userData['user_id']]);
            $checklist = $stmt->fetch();
            
            if (!$checklist) {
                jsonResponse(['success' => false, 'message' => 'Checklist não encontrado'], 404);
            }
            
            // Busca itens do checklist
            $itemsStmt = $db->prepare("
                SELECT 
                    id,
                    checklist_id,
                    task_name,
                    task_category,
                    is_required,
                    is_completed,
                    completed_at,
                    completed_by,
                    notes,
                    photo_url,
                    order_index,
                    created_at
                FROM checklist_items 
                WHERE checklist_id = ? 
                ORDER BY task_category, order_index
            ");
            $itemsStmt->execute([$id]);
            $items = $itemsStmt->fetchAll();
            
            // Converte valores booleanos
            foreach ($items as &$item) {
                $item['is_required'] = (bool)$item['is_required'];
                $item['is_completed'] = (bool)$item['is_completed'];
            }
            
            jsonResponse([
                'success' => true,
                'data' => [
                    'checklist' => $checklist,
                    'items' => $items,
                    'progress' => $checklist['total_tasks'] > 0 
                        ? round(($checklist['completed_tasks'] / $checklist['total_tasks']) * 100) 
                        : 0
                ]
            ]);
            
        case 'templates':
            // Busca templates disponíveis
            $category = $_GET['category'] ?? null;
            $installationType = $_GET['installation_type'] ?? 'new';
            
            $sql = "SELECT * FROM checklist_templates WHERE is_active = 1";
            $params = [];
            
            // Filtra por tipo de instalação
            $sql .= " AND (applicable_types LIKE ? OR applicable_types = 'all')";
            $params[] = "%$installationType%";
            
            if ($category) {
                $sql .= " AND task_category = ?";
                $params[] = $category;
            }
            
            $sql .= " ORDER BY order_index";
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $templates = $stmt->fetchAll();
            
            jsonResponse(['success' => true, 'data' => $templates, 'filter' => $installationType]);
            
        default:
            jsonResponse(['success' => false, 'message' => 'Ação inválida'], 400);
    }
}

/**
 * POST - Cria novo checklist ou atualiza item
 */
function handlePost($db, $userData) {
    $data = getRequestBody();
    $action = $data['action'] ?? 'create';
    
    switch ($action) {
        case 'create':
            // Valida CPF do cliente
            $cpfValidation = Validator::validateCPF($data['client_cpf'] ?? '');
            if (!$cpfValidation['valid']) {
                jsonResponse(['success' => false, 'message' => 'CPF inválido'], 400);
            }
            
            $cpf = $cpfValidation['clean'];
            
            // Busca dados do cliente
            $clientStmt = $db->prepare("SELECT name FROM clients WHERE cpf = ?");
            $clientStmt->execute([$cpf]);
            $client = $clientStmt->fetch();
            
            if (!$client) {
                jsonResponse(['success' => false, 'message' => 'Cliente não encontrado'], 404);
            }
            
            // Cria checklist
            $stmt = $db->prepare("
                INSERT INTO installation_checklists 
                (client_cpf, client_name, technician_id, technician_name, installation_type, status, started_at, notes)
                VALUES (?, ?, ?, ?, ?, 'pending', NULL, ?)
            ");
            $stmt->execute([
                $cpf,
                $client['name'],
                $userData['user_id'],
                $userData['username'],
                $data['installation_type'] ?? 'new',
                $data['notes'] ?? null
            ]);
            
            $checklistId = $db->lastInsertId();
            $installationType = $data['installation_type'] ?? 'new';
            
            // Cria itens do checklist baseado nos templates aplicáveis ao tipo
            $templatesStmt = $db->prepare("
                SELECT * FROM checklist_templates 
                WHERE is_active = 1 
                AND (applicable_types LIKE ? OR applicable_types = 'all')
                ORDER BY order_index
            ");
            $templatesStmt->execute(["%$installationType%"]);
            $templates = $templatesStmt->fetchAll();
            
            $itemStmt = $db->prepare("
                INSERT INTO checklist_items 
                (checklist_id, task_name, task_category, is_required, order_index)
                VALUES (?, ?, ?, ?, ?)
            ");
            
            foreach ($templates as $template) {
                $itemStmt->execute([
                    $checklistId,
                    $template['task_name'],
                    $template['task_category'],
                    $template['is_required'],
                    $template['order_index']
                ]);
            }
            
            Logger::info('Templates aplicados', [
                'checklist_id' => $checklistId,
                'type' => $installationType,
                'templates_count' => count($templates)
            ]);
            
            Logger::info('Checklist criado', [
                'checklist_id' => $checklistId,
                'client_cpf' => $cpf,
                'technician' => $userData['username']
            ]);
            
            jsonResponse([
                'success' => true,
                'message' => 'Checklist criado com sucesso',
                'data' => ['id' => $checklistId]
            ], 201);
            
        case 'start':
            // Inicia o checklist
            $checklistId = (int)($data['checklist_id'] ?? 0);
            
            $stmt = $db->prepare("
                UPDATE installation_checklists 
                SET status = 'in_progress', started_at = NOW()
                WHERE id = ? AND technician_id = ? AND status = 'pending'
            ");
            $stmt->execute([$checklistId, $userData['user_id']]);
            
            if ($stmt->rowCount() === 0) {
                jsonResponse(['success' => false, 'message' => 'Checklist não encontrado ou já iniciado'], 400);
            }
            
            jsonResponse(['success' => true, 'message' => 'Checklist iniciado']);
            
        case 'complete_item':
            // Marca item como completo
            $itemId = (int)($data['item_id'] ?? 0);
            $notes = $data['notes'] ?? null;
            $photoUrl = $data['photo_url'] ?? null;
            
            $stmt = $db->prepare("
                UPDATE checklist_items 
                SET is_completed = 1, completed_at = NOW(), completed_by = ?, notes = ?, photo_url = ?
                WHERE id = ?
            ");
            $stmt->execute([$userData['user_id'], $notes, $photoUrl, $itemId]);
            
            if ($stmt->rowCount() === 0) {
                jsonResponse(['success' => false, 'message' => 'Item não encontrado'], 404);
            }
            
            jsonResponse(['success' => true, 'message' => 'Item marcado como completo']);
            
        default:
            jsonResponse(['success' => false, 'message' => 'Ação inválida'], 400);
    }
}

/**
 * PUT - Atualiza checklist ou marca como completo
 */
function handlePut($db, $userData) {
    $data = getRequestBody();
    $action = $data['action'] ?? 'update';
    
    switch ($action) {
        case 'complete':
            $checklistId = (int)($data['checklist_id'] ?? 0);
            
            // Verifica se todos os itens obrigatórios estão completos
            $stmt = $db->prepare("
                SELECT COUNT(*) as pending_required
                FROM checklist_items
                WHERE checklist_id = ? AND is_required = 1 AND is_completed = 0
            ");
            $stmt->execute([$checklistId]);
            $pending = $stmt->fetch()['pending_required'];
            
            if ($pending > 0) {
                jsonResponse([
                    'success' => false, 
                    'message' => "Ainda há {$pending} item(s) obrigatório(s) pendente(s)"
                ], 400);
            }
            
            // Marca como completo
            $stmt = $db->prepare("
                UPDATE installation_checklists 
                SET status = 'completed', completed_at = NOW()
                WHERE id = ? AND technician_id = ?
            ");
            $stmt->execute([$checklistId, $userData['user_id']]);
            
            if ($stmt->rowCount() === 0) {
                jsonResponse(['success' => false, 'message' => 'Checklist não encontrado'], 404);
            }
            
            Logger::info('Checklist completado', [
                'checklist_id' => $checklistId,
                'technician' => $userData['username']
            ]);
            
            jsonResponse(['success' => true, 'message' => 'Instalação finalizada com sucesso']);
            
        case 'uncheck_item':
            // Desmarca item
            $itemId = (int)($data['item_id'] ?? 0);
            
            $stmt = $db->prepare("
                UPDATE checklist_items 
                SET is_completed = 0, completed_at = NULL, completed_by = NULL
                WHERE id = ?
            ");
            $stmt->execute([$itemId]);
            
            jsonResponse(['success' => true, 'message' => 'Item desmarcado']);
            
        case 'mark_na':
            // Marca item como N/A (Não Aplicável) - apenas para itens não obrigatórios
            $itemId = (int)($data['item_id'] ?? 0);
            
            // Verifica se o item é obrigatório
            $checkStmt = $db->prepare("SELECT is_required FROM checklist_items WHERE id = ?");
            $checkStmt->execute([$itemId]);
            $item = $checkStmt->fetch();
            
            if (!$item) {
                jsonResponse(['success' => false, 'message' => 'Item não encontrado'], 404);
            }
            
            if ($item['is_required']) {
                jsonResponse(['success' => false, 'message' => 'Itens obrigatórios não podem ser marcados como N/A'], 400);
            }
            
            $stmt = $db->prepare("
                UPDATE checklist_items 
                SET is_completed = 1, completed_at = NOW(), completed_by = ?, notes = CONCAT(IFNULL(notes, ''), ' [N/A - Não Aplicável]')
                WHERE id = ?
            ");
            $stmt->execute([$userData['user_id'], $itemId]);
            
            jsonResponse(['success' => true, 'message' => 'Item marcado como N/A']);
            
        default:
            jsonResponse(['success' => false, 'message' => 'Ação inválida'], 400);
    }
}

/**
 * DELETE - Remove checklist
 */
function handleDelete($db, $userData) {
    $id = (int)($_GET['id'] ?? 0);
    
    if (!$id) {
        jsonResponse(['success' => false, 'message' => 'ID obrigatório'], 400);
    }
    
    // Só permite deletar se estiver pending
    $stmt = $db->prepare("
        DELETE FROM installation_checklists 
        WHERE id = ? AND technician_id = ? AND status = 'pending'
    ");
    $stmt->execute([$id, $userData['user_id']]);
    
    if ($stmt->rowCount() === 0) {
        jsonResponse(['success' => false, 'message' => 'Checklist não encontrado ou não pode ser removido'], 400);
    }
    
    Logger::info('Checklist deletado', ['checklist_id' => $id]);
    
    jsonResponse(['success' => true, 'message' => 'Checklist removido']);
}
