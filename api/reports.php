<?php
/**
 * API de Relatórios / Exportação
 * Ondeline Tech - App do Técnico
 *
 * Retorna dados formatados para exportação no frontend (CSV/PDF)
 */

require_once 'config.php';

$method = $_SERVER['REQUEST_METHOD'];
$userData = requireAuth();

// Only admin can access reports
if ($userData['role'] !== 'admin') {
    jsonResponse(['success' => false, 'message' => 'Acesso restrito a administradores'], 403);
}

if ($method !== 'GET') {
    jsonResponse(['success' => false, 'message' => 'Método não permitido'], 405);
}

try {
    $db = Database::getInstance()->getConnection();
    $report = $_GET['report'] ?? null;

    if (!$report) {
        jsonResponse(['success' => false, 'message' => 'Tipo de relatório é obrigatório'], 400);
    }

    // City filter for technicians
    $cityFilter = '';
    $cityParam = [];
    if ($userData['role'] !== 'admin') {
        $cityStmt = $db->prepare("SELECT city FROM users WHERE id = ?");
        $cityStmt->execute([$userData['user_id']]);
        $userCity = $cityStmt->fetch()['city'] ?? null;
        if ($userCity) {
            $cityFilter = " AND LOWER(TRIM(c.city)) = LOWER(TRIM(?))";
            $cityParam = [$userCity];
        }
    }

    $dateFrom = $_GET['date_from'] ?? date('Y-m-01');
    $dateTo = $_GET['date_to'] ?? date('Y-m-d');

    switch ($report) {
        case 'clients':
            handleClientsReport($db, $cityFilter, $cityParam, $dateFrom, $dateTo);
            break;
        case 'timeclock':
            handleTimeClockReport($db, $userData, $dateFrom, $dateTo);
            break;
        case 'inventory':
            handleInventoryReport($db);
            break;
        case 'work_orders':
            handleWorkOrdersReport($db, $userData, $dateFrom, $dateTo);
            break;
        default:
            jsonResponse(['success' => false, 'message' => 'Tipo de relatório inválido'], 400);
    }
} catch (PDOException $e) {
    jsonResponse(['success' => false, 'message' => 'Erro ao gerar relatório', 'error' => $e->getMessage()], 500);
}

function handleClientsReport($db, $cityFilter, $cityParam, $dateFrom, $dateTo) {
    $sql = "SELECT c.cpf, c.name, c.phone, c.address, c.number, c.neighborhood, c.city, c.state,
                   c.plan, c.pppoe_user, c.installer, c.serial, c.registration_date, c.created_at
            FROM clients c
            WHERE DATE(c.created_at) BETWEEN ? AND ?" . str_replace('c.city', 'c.city', $cityFilter) . "
            ORDER BY c.created_at DESC";

    $stmt = $db->prepare($sql);
    $stmt->execute(array_merge([$dateFrom, $dateTo], $cityParam));
    $data = $stmt->fetchAll();

    // Summary
    $totalStmt = $db->prepare("SELECT COUNT(*) as total FROM clients c WHERE 1=1" . $cityFilter);
    $totalStmt->execute($cityParam);
    $totalClients = $totalStmt->fetch()['total'];

    $periodStmt = $db->prepare("SELECT COUNT(*) as total FROM clients c WHERE DATE(c.created_at) BETWEEN ? AND ?" . $cityFilter);
    $periodStmt->execute(array_merge([$dateFrom, $dateTo], $cityParam));
    $periodClients = $periodStmt->fetch()['total'];

    jsonResponse([
        'success' => true,
        'data' => $data,
        'summary' => [
            'total_all_time' => intval($totalClients),
            'total_period' => intval($periodClients),
            'date_from' => $dateFrom,
            'date_to' => $dateTo
        ],
        'columns' => [
            ['key' => 'cpf', 'label' => 'CPF'],
            ['key' => 'name', 'label' => 'Nome'],
            ['key' => 'phone', 'label' => 'Telefone'],
            ['key' => 'city', 'label' => 'Cidade'],
            ['key' => 'plan', 'label' => 'Plano'],
            ['key' => 'pppoe_user', 'label' => 'PPPoE'],
            ['key' => 'installer', 'label' => 'Instalador'],
            ['key' => 'serial', 'label' => 'Serial'],
            ['key' => 'registration_date', 'label' => 'Data Cadastro']
        ]
    ]);
}

function handleTimeClockReport($db, $userData, $dateFrom, $dateTo) {
    $where = "WHERE tc.clock_date BETWEEN ? AND ?";
    $params = [$dateFrom, $dateTo];

    if ($userData['role'] !== 'admin') {
        $where .= " AND tc.user_id = ?";
        $params[] = $userData['user_id'];
    }

    $sql = "SELECT tc.id, tc.username, tc.clock_date, tc.entry_time, tc.exit_time, tc.worked_hours, tc.notes
            FROM time_clock tc $where ORDER BY tc.clock_date DESC, tc.username";

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $data = $stmt->fetchAll();

    // Summary
    $summaryWhere = $where;
    $summaryStmt = $db->prepare("SELECT COUNT(*) as total_days,
                                        SEC_TO_TIME(SUM(TIME_TO_SEC(tc.worked_hours))) as total_hours
                                 FROM time_clock tc $summaryWhere AND tc.worked_hours IS NOT NULL");
    $summaryStmt->execute($params);
    $summary = $summaryStmt->fetch();

    jsonResponse([
        'success' => true,
        'data' => $data,
        'summary' => [
            'total_days' => intval($summary['total_days']),
            'total_hours' => $summary['total_hours'] ?? '00:00:00',
            'date_from' => $dateFrom,
            'date_to' => $dateTo
        ],
        'columns' => [
            ['key' => 'username', 'label' => 'Técnico'],
            ['key' => 'clock_date', 'label' => 'Data'],
            ['key' => 'entry_time', 'label' => 'Entrada'],
            ['key' => 'exit_time', 'label' => 'Saída'],
            ['key' => 'worked_hours', 'label' => 'Horas Trabalhadas'],
            ['key' => 'notes', 'label' => 'Observações']
        ]
    ]);
}

function handleInventoryReport($db) {
    $sql = "SELECT ei.serial_number, ei.model, ei.brand, ei.type, ei.status, ei.location,
                   ei.current_client_cpf, ei.purchase_date, ei.warranty_until, ei.notes
            FROM equipment_inventory ei ORDER BY ei.type, ei.status, ei.model";

    $stmt = $db->prepare($sql);
    $stmt->execute();
    $data = $stmt->fetchAll();

    // Summary by status
    $statsStmt = $db->prepare("SELECT status, COUNT(*) as total FROM equipment_inventory GROUP BY status");
    $statsStmt->execute();
    $statsByStatus = $statsStmt->fetchAll();

    // Summary by type
    $typeStmt = $db->prepare("SELECT type, COUNT(*) as total FROM equipment_inventory GROUP BY type");
    $typeStmt->execute();
    $statsByType = $typeStmt->fetchAll();

    jsonResponse([
        'success' => true,
        'data' => $data,
        'summary' => [
            'total' => count($data),
            'by_status' => $statsByStatus,
            'by_type' => $statsByType
        ],
        'columns' => [
            ['key' => 'serial_number', 'label' => 'Serial'],
            ['key' => 'model', 'label' => 'Modelo'],
            ['key' => 'brand', 'label' => 'Marca'],
            ['key' => 'type', 'label' => 'Tipo'],
            ['key' => 'status', 'label' => 'Status'],
            ['key' => 'location', 'label' => 'Localização'],
            ['key' => 'current_client_cpf', 'label' => 'CPF Cliente'],
            ['key' => 'warranty_until', 'label' => 'Garantia Até']
        ]
    ]);
}

function handleWorkOrdersReport($db, $userData, $dateFrom, $dateTo) {
    $where = "WHERE DATE(wo.created_at) BETWEEN ? AND ?";
    $params = [$dateFrom, $dateTo];

    if ($userData['role'] !== 'admin') {
        $where .= " AND (wo.assigned_to = ? OR wo.created_by = ?)";
        $params[] = $userData['user_id'];
        $params[] = $userData['user_id'];
    }

    $sql = "SELECT wo.order_number, wo.client_name, wo.client_cpf, wo.type, wo.priority, wo.status,
                   wo.assigned_name, wo.description, wo.resolution, wo.scheduled_date, wo.created_at, wo.completed_at
            FROM work_orders wo $where ORDER BY wo.created_at DESC";

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $data = $stmt->fetchAll();

    // Summary
    $summaryStmt = $db->prepare("SELECT status, COUNT(*) as total FROM work_orders wo $where GROUP BY status");
    $summaryStmt->execute($params);
    $byStatus = $summaryStmt->fetchAll();

    jsonResponse([
        'success' => true,
        'data' => $data,
        'summary' => [
            'total' => count($data),
            'by_status' => $byStatus,
            'date_from' => $dateFrom,
            'date_to' => $dateTo
        ],
        'columns' => [
            ['key' => 'order_number', 'label' => 'Número'],
            ['key' => 'client_name', 'label' => 'Cliente'],
            ['key' => 'type', 'label' => 'Tipo'],
            ['key' => 'priority', 'label' => 'Prioridade'],
            ['key' => 'status', 'label' => 'Status'],
            ['key' => 'assigned_name', 'label' => 'Técnico'],
            ['key' => 'description', 'label' => 'Descrição'],
            ['key' => 'resolution', 'label' => 'Resolução'],
            ['key' => 'scheduled_date', 'label' => 'Agendado'],
            ['key' => 'created_at', 'label' => 'Criado Em']
        ]
    ]);
}
