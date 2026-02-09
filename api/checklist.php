<?php
/**
 * API de Checklist de Instalação - Com Sistema de Aprovação
 * Ondeline Tech - App do Técnico
 * 
 * Sistema de Aprovação:
 * - Técnicos: criam e finalizam checklists (vão para pending_approval)
 * - Admins: aprovam, rejeitam ou excluem qualquer checklist
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
$isAdmin = ($userData['role'] ?? '') === 'admin';
$method = $_SERVER['REQUEST_METHOD'];

try {
    $db = Database::getInstance()->getConnection();

    switch ($method) {
        case 'GET':
            handleGet($db, $userData, $isAdmin);
            break;
        case 'POST':
            handlePost($db, $userData, $isAdmin);
            break;
        case 'PUT':
            handlePut($db, $userData, $isAdmin);
            break;
        case 'DELETE':
            handleDelete($db, $userData, $isAdmin);
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
function handleGet($db, $userData, $isAdmin) {
    $action = $_GET['action'] ?? 'list';
    
    switch ($action) {
        case 'list':
            // Lista checklists
            $status = $_GET['status'] ?? null;
            $approvalStatus = $_GET['approval_status'] ?? null;
            $filterMine = $_GET['mine'] ?? 'true'; // Por padrão, técnicos só veem os seus
            $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
            $limit = isset($_GET['limit']) ? min(50, max(1, (int)$_GET['limit'])) : 20;
            $offset = ($page - 1) * $limit;
            
            // Se não for admin e filterMine for true, só mostra os próprios
            $showAll = $isAdmin && $filterMine === 'false';
            
            $sql = "SELECT ic.*, 
                           u.full_name as technician_name,
                           approver.full_name as approved_by_name
                    FROM installation_checklists ic
                    LEFT JOIN users u ON ic.technician_id = u.id
                    LEFT JOIN users approver ON ic.approved_by = approver.id
                    WHERE 1=1";
            $params = [];
            
            // Filtra por técnico se não for admin ou se solicitou apenas os seus
            if (!$showAll) {
                $sql .= " AND ic.technician_id = ?";
                $params[] = $userData['user_id'];
            }
            
            if ($status) {
                $sql .= " AND ic.status = ?";
                $params[] = $status;
            }
            
            if ($approvalStatus) {
                $sql .= " AND ic.approval_status = ?";
                $params[] = $approvalStatus;
            }
            
            // Conta total
            $countSql = str_replace("SELECT ic.*, \n                           u.full_name as technician_name,\n                           approver.full_name as approved_by_name", "SELECT COUNT(*) as total", $sql);
            $countStmt = $db->prepare($countSql);
            $countStmt->execute($params);
            $total = $countStmt->fetch()['total'];
            
            $sql .= " ORDER BY ic.created_at DESC LIMIT $limit OFFSET $offset";
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $checklists = $stmt->fetchAll();
            
            // Adiciona contadores para admin
            $counts = null;
            if ($isAdmin) {
                $countsStmt = $db->query("
                    SELECT 
                        SUM(CASE WHEN approval_status = 'pending_approval' THEN 1 ELSE 0 END) as pending_approval,
                        SUM(CASE WHEN approval_status = 'approved' THEN 1 ELSE 0 END) as approved,
                        SUM(CASE WHEN approval_status = 'rejected' THEN 1 ELSE 0 END) as rejected
                    FROM installation_checklists
                ");
                $counts = $countsStmt->fetch();
            }
            
            jsonResponse([
                'success' => true,
                'data' => $checklists,
                'is_admin' => $isAdmin,
                'counts' => $counts,
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
            
            $sql = "SELECT ic.*, 
                           u.full_name as technician_name,
                           approver.full_name as approved_by_name
                    FROM installation_checklists ic
                    LEFT JOIN users u ON ic.technician_id = u.id
                    LEFT JOIN users approver ON ic.approved_by = approver.id
                    WHERE ic.id = ?";
            $params = [$id];
            
            // Se não for admin, só pode ver o próprio
            if (!$isAdmin) {
                $sql .= " AND ic.technician_id = ?";
                $params[] = $userData['user_id'];
            }
            
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $checklist = $stmt->fetch();
            
            if (!$checklist) {
                jsonResponse(['success' => false, 'message' => 'Checklist não encontrado ou sem permissão'], 404);
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
            
            // Busca histórico de aprovação
            $historyStmt = $db->prepare("
                SELECT * FROM checklist_approval_history 
                WHERE checklist_id = ? 
                ORDER BY created_at DESC
            ");
            $historyStmt->execute([$id]);
            $history = $historyStmt->fetchAll();
            
            jsonResponse([
                'success' => true,
                'data' => [
                    'checklist' => $checklist,
                    'items' => $items,
                    'history' => $history,
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
function handlePost($db, $userData, $isAdmin) {
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
            
            $installationType = $data['installation_type'] ?? 'new';
            
            // Cria checklist
            $stmt = $db->prepare("
                INSERT INTO installation_checklists 
                (client_cpf, client_name, technician_id, technician_name, installation_type, status, approval_status, started_at, notes)
                VALUES (?, ?, ?, ?, ?, 'pending', 'pending', NULL, ?)
            ");
            $stmt->execute([
                $cpf,
                $client['name'],
                $userData['user_id'],
                $userData['username'],
                $installationType,
                $data['notes'] ?? null
            ]);
            
            $checklistId = $db->lastInsertId();
            
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
            
            Logger::info('Checklist criado', [
                'checklist_id' => $checklistId,
                'client_cpf' => $cpf,
                'technician' => $userData['username'],
                'type' => $installationType
            ]);
            
            jsonResponse([
                'success' => true,
                'message' => 'Checklist criado com sucesso',
                'data' => [
                    'id' => $checklistId,
                    'templates_count' => count($templates)
                ]
            ], 201);
            
        case 'start':
            // Inicia o checklist
            $checklistId = (int)($data['checklist_id'] ?? 0);
            
            // Verifica se é o dono do checklist
            $checkStmt = $db->prepare("SELECT technician_id FROM installation_checklists WHERE id = ?");
            $checkStmt->execute([$checklistId]);
            $check = $checkStmt->fetch();
            
            if (!$check) {
                jsonResponse(['success' => false, 'message' => 'Checklist não encontrado'], 404);
            }
            
            if (!$isAdmin && $check['technician_id'] != $userData['user_id']) {
                jsonResponse(['success' => false, 'message' => 'Sem permissão'], 403);
            }
            
            $stmt = $db->prepare("
                UPDATE installation_checklists 
                SET status = 'in_progress', started_at = NOW()
                WHERE id = ? AND status = 'pending'
            ");
            $stmt->execute([$checklistId]);
            
            if ($stmt->rowCount() === 0) {
                jsonResponse(['success' => false, 'message' => 'Checklist não encontrado ou já iniciado'], 400);
            }
            
            jsonResponse(['success' => true, 'message' => 'Checklist iniciado']);
            
        case 'complete_item':
            // Marca item como completo
            $itemId = (int)($data['item_id'] ?? 0);
            $notes = $data['notes'] ?? null;
            $photoUrl = $data['photo_url'] ?? null;
            
            Logger::info('Checklist complete_item chamado', [
                'item_id' => $itemId,
                'user_id' => $userData['user_id']
            ]);
            
            if (!$itemId) {
                jsonResponse(['success' => false, 'message' => 'ID do item é obrigatório'], 400);
            }
            
            // Verifica se o item existe
            $checkStmt = $db->prepare("SELECT id FROM checklist_items WHERE id = ?");
            $checkStmt->execute([$itemId]);
            if (!$checkStmt->fetch()) {
                Logger::error('Item não encontrado', ['item_id' => $itemId]);
                jsonResponse(['success' => false, 'message' => 'Item não encontrado: ' . $itemId], 404);
            }
            
            $stmt = $db->prepare("
                UPDATE checklist_items 
                SET is_completed = 1, completed_at = NOW(), completed_by = ?, notes = ?, photo_url = ?
                WHERE id = ?
            ");
            $result = $stmt->execute([$userData['user_id'], $notes, $photoUrl, $itemId]);
            
            Logger::info('Item marcado como completo', ['item_id' => $itemId, 'result' => $result]);
            
            jsonResponse(['success' => true, 'message' => 'Item marcado como completo']);
            
        default:
            jsonResponse(['success' => false, 'message' => 'Ação inválida'], 400);
    }
}

/**
 * PUT - Atualiza checklist (aprovação, rejeição, etc)
 */
function handlePut($db, $userData, $isAdmin) {
    $data = getRequestBody();
    $action = $data['action'] ?? 'update';
    
    switch ($action) {
        case 'complete':
            // Finaliza checklist (técnico) - vai para pending_approval
            $checklistId = (int)($data['checklist_id'] ?? 0);
            
            // Verifica se é o dono
            $checkStmt = $db->prepare("SELECT technician_id, approval_status FROM installation_checklists WHERE id = ?");
            $checkStmt->execute([$checklistId]);
            $check = $checkStmt->fetch();
            
            if (!$check) {
                jsonResponse(['success' => false, 'message' => 'Checklist não encontrado'], 404);
            }
            
            if (!$isAdmin && $check['technician_id'] != $userData['user_id']) {
                jsonResponse(['success' => false, 'message' => 'Sem permissão'], 403);
            }
            
            // NOTA: Verificação de itens obrigatórios removida - todos os itens são opcionais
            
            // Atualiza para aguardando aprovação
            $stmt = $db->prepare("
                UPDATE installation_checklists 
                SET status = 'completed', 
                    approval_status = 'pending_approval',
                    completed_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$checklistId]);
            
            // Registra no histórico
            $historyStmt = $db->prepare("
                INSERT INTO checklist_approval_history 
                (checklist_id, action, action_by, action_by_name, action_by_role, notes)
                VALUES (?, 'submitted', ?, ?, ?, ?)
            ");
            $historyStmt->execute([
                $checklistId,
                $userData['user_id'],
                $userData['username'],
                $isAdmin ? 'admin' : 'tecnico',
                'Checklist finalizado e enviado para aprovação'
            ]);
            
            Logger::info('Checklist finalizado (pending approval)', [
                'checklist_id' => $checklistId,
                'technician' => $userData['username']
            ]);
            
            jsonResponse([
                'success' => true, 
                'message' => 'Checklist enviado para aprovação do administrador'
            ]);
            
        case 'approve':
            // Aprova checklist (apenas admin)
            if (!$isAdmin) {
                jsonResponse(['success' => false, 'message' => 'Apenas administradores podem aprovar'], 403);
            }
            
            $checklistId = (int)($data['checklist_id'] ?? 0);
            $approvalNotes = $data['notes'] ?? null;
            
            $stmt = $db->prepare("
                UPDATE installation_checklists 
                SET approval_status = 'approved',
                    approved_by = ?,
                    approved_at = NOW()
                WHERE id = ? AND approval_status = 'pending_approval'
            ");
            $stmt->execute([$userData['user_id'], $checklistId]);
            
            if ($stmt->rowCount() === 0) {
                jsonResponse(['success' => false, 'message' => 'Checklist não encontrado ou não está aguardando aprovação'], 404);
            }
            
            // Registra no histórico
            $historyStmt = $db->prepare("
                INSERT INTO checklist_approval_history 
                (checklist_id, action, action_by, action_by_name, action_by_role, notes)
                VALUES (?, 'approved', ?, ?, ?, ?)
            ");
            $historyStmt->execute([
                $checklistId,
                $userData['user_id'],
                $userData['username'],
                'admin',
                $approvalNotes
            ]);
            
            Logger::info('Checklist aprovado', [
                'checklist_id' => $checklistId,
                'approved_by' => $userData['username']
            ]);
            
            jsonResponse(['success' => true, 'message' => 'Checklist aprovado com sucesso']);
            
        case 'reject':
            // Rejeita checklist (apenas admin)
            if (!$isAdmin) {
                jsonResponse(['success' => false, 'message' => 'Apenas administradores podem rejeitar'], 403);
            }
            
            $checklistId = (int)($data['checklist_id'] ?? 0);
            $reason = $data['reason'] ?? null;
            $notes = $data['notes'] ?? null;
            
            if (!$reason) {
                jsonResponse(['success' => false, 'message' => 'Motivo da rejeição é obrigatório'], 400);
            }
            
            $stmt = $db->prepare("
                UPDATE installation_checklists 
                SET approval_status = 'rejected',
                    rejection_reason = ?,
                    rejection_notes = ?
                WHERE id = ? AND approval_status = 'pending_approval'
            ");
            $stmt->execute([$reason, $notes, $checklistId]);
            
            if ($stmt->rowCount() === 0) {
                jsonResponse(['success' => false, 'message' => 'Checklist não encontrado ou não está aguardando aprovação'], 404);
            }
            
            // Registra no histórico
            $historyStmt = $db->prepare("
                INSERT INTO checklist_approval_history 
                (checklist_id, action, action_by, action_by_name, action_by_role, notes)
                VALUES (?, 'rejected', ?, ?, ?, ?)
            ");
            $historyStmt->execute([
                $checklistId,
                $userData['user_id'],
                $userData['username'],
                'admin',
                "Motivo: $reason. $notes"
            ]);
            
            Logger::info('Checklist rejeitado', [
                'checklist_id' => $checklistId,
                'rejected_by' => $userData['username'],
                'reason' => $reason
            ]);
            
            jsonResponse(['success' => true, 'message' => 'Checklist rejeitado. O técnico foi notificado.']);
            
        case 'reopen':
            // Reabre checklist rejeitado para correção (técnico)
            $checklistId = (int)($data['checklist_id'] ?? 0);
            
            // Verifica se é o dono
            $checkStmt = $db->prepare("SELECT technician_id, approval_status FROM installation_checklists WHERE id = ?");
            $checkStmt->execute([$checklistId]);
            $check = $checkStmt->fetch();
            
            if (!$check) {
                jsonResponse(['success' => false, 'message' => 'Checklist não encontrado'], 404);
            }
            
            if (!$isAdmin && $check['technician_id'] != $userData['user_id']) {
                jsonResponse(['success' => false, 'message' => 'Sem permissão'], 403);
            }
            
            if ($check['approval_status'] !== 'rejected') {
                jsonResponse(['success' => false, 'message' => 'Apenas checklists rejeitados podem ser reabertos'], 400);
            }
            
            // Reabre o checklist
            $stmt = $db->prepare("
                UPDATE installation_checklists 
                SET status = 'in_progress',
                    approval_status = 'pending',
                    completed_at = NULL
                WHERE id = ?
            ");
            $stmt->execute([$checklistId]);
            
            // Registra no histórico
            $historyStmt = $db->prepare("
                INSERT INTO checklist_approval_history 
                (checklist_id, action, action_by, action_by_name, action_by_role, notes)
                VALUES (?, 'reopened', ?, ?, ?, ?)
            ");
            $historyStmt->execute([
                $checklistId,
                $userData['user_id'],
                $userData['username'],
                $isAdmin ? 'admin' : 'tecnico',
                'Checklist reaberto para correção'
            ]);
            
            jsonResponse(['success' => true, 'message' => 'Checklist reaberto para correção']);
            
        case 'uncheck_item':
            // Desmarca item
            $itemId = (int)($data['item_id'] ?? 0);
            
            if (!$itemId) {
                jsonResponse(['success' => false, 'message' => 'ID do item é obrigatório'], 400);
            }
            
            // Verifica se o item existe
            $checkStmt = $db->prepare("SELECT id FROM checklist_items WHERE id = ?");
            $checkStmt->execute([$itemId]);
            if (!$checkStmt->fetch()) {
                jsonResponse(['success' => false, 'message' => 'Item não encontrado: ' . $itemId], 404);
            }
            
            $stmt = $db->prepare("
                UPDATE checklist_items 
                SET is_completed = 0, completed_at = NULL, completed_by = NULL
                WHERE id = ?
            ");
            $stmt->execute([$itemId]);
            
            jsonResponse(['success' => true, 'message' => 'Item desmarcado']);
            
        case 'update_photo':
            // Atualiza apenas a foto de um item
            $itemId = (int)($data['item_id'] ?? 0);
            $photoUrl = $data['photo_url'] ?? null;
            
            if (!$itemId) {
                jsonResponse(['success' => false, 'message' => 'ID do item é obrigatório'], 400);
            }
            
            if (!$photoUrl) {
                jsonResponse(['success' => false, 'message' => 'URL da foto é obrigatória'], 400);
            }
            
            // Verifica se o item existe
            $checkStmt = $db->prepare("SELECT id FROM checklist_items WHERE id = ?");
            $checkStmt->execute([$itemId]);
            if (!$checkStmt->fetch()) {
                jsonResponse(['success' => false, 'message' => 'Item não encontrado'], 404);
            }
            
            $stmt = $db->prepare("
                UPDATE checklist_items 
                SET photo_url = ?
                WHERE id = ?
            ");
            $stmt->execute([$photoUrl, $itemId]);
            
            jsonResponse(['success' => true, 'message' => 'Foto atualizada']);
            
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
function handleDelete($db, $userData, $isAdmin) {
    $id = (int)($_GET['id'] ?? 0);
    
    if (!$id) {
        jsonResponse(['success' => false, 'message' => 'ID obrigatório'], 400);
    }
    
    // Verifica se é admin ou o dono do checklist (só pode deletar se estiver pendente)
    $checkStmt = $db->prepare("SELECT technician_id, status, approval_status FROM installation_checklists WHERE id = ?");
    $checkStmt->execute([$id]);
    $check = $checkStmt->fetch();
    
    if (!$check) {
        jsonResponse(['success' => false, 'message' => 'Checklist não encontrado'], 404);
    }
    
    // Se for admin, pode deletar qualquer um
    // Se for técnico, só pode deletar o próprio e se estiver pendente
    if (!$isAdmin) {
        if ($check['technician_id'] != $userData['user_id']) {
            jsonResponse(['success' => false, 'message' => 'Sem permissão para excluir este checklist'], 403);
        }
        
        if ($check['approval_status'] === 'approved') {
            jsonResponse(['success' => false, 'message' => 'Checklists aprovados não podem ser excluídos'], 400);
        }
    }
    
    // Registra no histórico antes de deletar
    $historyStmt = $db->prepare("
        INSERT INTO checklist_approval_history 
        (checklist_id, action, action_by, action_by_name, action_by_role, notes)
        VALUES (?, 'deleted', ?, ?, ?, ?)
    ");
    $historyStmt->execute([
        $id,
        $userData['user_id'],
        $userData['username'],
        $isAdmin ? 'admin' : 'tecnico',
        'Checklist excluído'
    ]);
    
    // Deleta o checklist (cascade deletará os itens)
    $stmt = $db->prepare("DELETE FROM installation_checklists WHERE id = ?");
    $stmt->execute([$id]);
    
    Logger::info('Checklist deletado', [
        'checklist_id' => $id,
        'deleted_by' => $userData['username'],
        'is_admin' => $isAdmin
    ]);
    
    jsonResponse(['success' => true, 'message' => 'Checklist removido com sucesso']);
}
