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

    $technicianId = isset($_GET['technician_id']) ? (int)$_GET['technician_id'] : null;

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
        case 'checklists':
            handleChecklistsReport($db, $dateFrom, $dateTo, $technicianId);
            break;
        case 'technician_detail':
            handleTechnicianDetailReport($db, $dateFrom, $dateTo, $technicianId);
            break;
        case 'ranking':
            handleRankingReport($db, $dateFrom, $dateTo);
            break;
        case 'technicians_list':
            handleTechniciansListReport($db);
            break;
        default:
            jsonResponse(['success' => false, 'message' => 'Tipo de relatório inválido'], 400);
    }
} catch (PDOException $e) {
    jsonResponse(['success' => false, 'message' => 'Erro ao gerar relatório'], 500);
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
        'message' => 'Relatório gerado com sucesso',
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
        'message' => 'Relatório gerado com sucesso',
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
        'message' => 'Relatório gerado com sucesso',
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

function handleChecklistsReport($db, $dateFrom, $dateTo, $technicianId = null) {
    $where = "WHERE DATE(ic.created_at) BETWEEN ? AND ?";
    $params = [$dateFrom, $dateTo];

    if ($technicianId) {
        $where .= " AND ic.technician_id = ?";
        $params[] = $technicianId;
    }

    $sql = "SELECT ic.id, ic.client_name, ic.client_cpf, ic.installation_type,
                   ic.status, ic.approval_status, ic.completed_tasks, ic.total_tasks,
                   u.full_name as technician_name,
                   DATE_FORMAT(ic.created_at, '%d/%m/%Y') as data,
                   DATE_FORMAT(ic.created_at, '%H:%i') as horario,
                   ic.notes
            FROM installation_checklists ic
            LEFT JOIN users u ON u.id = ic.technician_id
            $where
            ORDER BY ic.created_at DESC";

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $data = $stmt->fetchAll();

    // Traduz tipos
    $typeLabels = ['new' => 'Nova Instalacao', 'migration' => 'Migracao', 'repair' => 'Reparo', 'maintenance' => 'Manutencao'];
    $statusLabels = ['pending' => 'Pendente', 'in_progress' => 'Em andamento', 'completed' => 'Concluido', 'cancelled' => 'Cancelado'];

    foreach ($data as &$row) {
        $row['installation_type'] = $typeLabels[$row['installation_type']] ?? $row['installation_type'];
        $row['status'] = $statusLabels[$row['status']] ?? $row['status'];
        $row['progresso'] = $row['total_tasks'] > 0
            ? round(($row['completed_tasks'] / $row['total_tasks']) * 100) . '%'
            : '0%';
    }

    // Summary por tecnico
    $summaryStmt = $db->prepare("
        SELECT u.full_name, COUNT(*) as total
        FROM installation_checklists ic
        LEFT JOIN users u ON u.id = ic.technician_id
        $where
        GROUP BY ic.technician_id
        ORDER BY total DESC
    ");
    $summaryStmt->execute($params);
    $byTechnician = $summaryStmt->fetchAll();

    jsonResponse([
        'success' => true,
        'data' => $data,
        'summary' => [
            'total' => count($data),
            'by_technician' => $byTechnician
        ],
        'columns' => [
            ['key' => 'technician_name', 'label' => 'Tecnico'],
            ['key' => 'client_name', 'label' => 'Cliente'],
            ['key' => 'client_cpf', 'label' => 'CPF'],
            ['key' => 'installation_type', 'label' => 'Tipo'],
            ['key' => 'status', 'label' => 'Status'],
            ['key' => 'progresso', 'label' => 'Progresso'],
            ['key' => 'data', 'label' => 'Data'],
            ['key' => 'horario', 'label' => 'Horario']
        ]
    ]);
}

function handleTechnicianDetailReport($db, $dateFrom, $dateTo, $technicianId) {
    if (!$technicianId) {
        jsonResponse(['success' => false, 'message' => 'Selecione um tecnico'], 400);
    }

    // Info do tecnico
    $stmt = $db->prepare("SELECT id, full_name, username, phone, photo FROM users WHERE id = ?");
    $stmt->execute([$technicianId]);
    $technician = $stmt->fetch();

    if (!$technician) {
        jsonResponse(['success' => false, 'message' => 'Tecnico nao encontrado'], 404);
    }

    $data = [];

    // Cadastros do tecnico no periodo
    $stmt = $db->prepare("
        SELECT 'Cadastro' as tipo_servico, c.name as cliente, c.cpf,
               c.plan as servico_detalhe,
               DATE_FORMAT(c.created_at, '%d/%m/%Y') as data,
               DATE_FORMAT(c.created_at, '%H:%i') as horario
        FROM clients c
        WHERE c.installer = ? AND DATE(c.created_at) BETWEEN ? AND ?
        ORDER BY c.created_at DESC
    ");
    $stmt->execute([$technician['username'], $dateFrom, $dateTo]);
    $cadastros = $stmt->fetchAll();
    $data = array_merge($data, $cadastros);

    // Checklists do tecnico no periodo
    try {
        $stmt = $db->prepare("
            SELECT
                CASE ic.installation_type
                    WHEN 'new' THEN 'Instalacao'
                    WHEN 'migration' THEN 'Migracao'
                    WHEN 'repair' THEN 'Reparo'
                    WHEN 'maintenance' THEN 'Manutencao'
                    ELSE ic.installation_type
                END as tipo_servico,
                ic.client_name as cliente,
                ic.client_cpf as cpf,
                CONCAT(ic.completed_tasks, '/', ic.total_tasks, ' tarefas') as servico_detalhe,
                DATE_FORMAT(ic.created_at, '%d/%m/%Y') as data,
                DATE_FORMAT(ic.created_at, '%H:%i') as horario
            FROM installation_checklists ic
            WHERE ic.technician_id = ? AND DATE(ic.created_at) BETWEEN ? AND ?
            ORDER BY ic.created_at DESC
        ");
        $stmt->execute([$technicianId, $dateFrom, $dateTo]);
        $checklists = $stmt->fetchAll();
        $data = array_merge($data, $checklists);
    } catch (Exception $e) { /* tabela pode nao existir */ }

    // Ordens de servico do tecnico no periodo
    try {
        $stmt = $db->prepare("
            SELECT
                CONCAT('OS #', wo.order_number) as tipo_servico,
                wo.client_name as cliente,
                wo.client_cpf as cpf,
                CONCAT(wo.type, ' - ', wo.status) as servico_detalhe,
                DATE_FORMAT(wo.created_at, '%d/%m/%Y') as data,
                DATE_FORMAT(wo.created_at, '%H:%i') as horario
            FROM work_orders wo
            WHERE wo.assigned_to = ? AND DATE(wo.created_at) BETWEEN ? AND ?
            ORDER BY wo.created_at DESC
        ");
        $stmt->execute([$technicianId, $dateFrom, $dateTo]);
        $ordens = $stmt->fetchAll();
        $data = array_merge($data, $ordens);
    } catch (Exception $e) { /* tabela pode nao existir */ }

    // Ordena tudo por data desc
    usort($data, function($a, $b) {
        $dateA = \DateTime::createFromFormat('d/m/Y', $a['data']);
        $dateB = \DateTime::createFromFormat('d/m/Y', $b['data']);
        if ($dateA == $dateB) {
            return strcmp($b['horario'], $a['horario']);
        }
        return $dateB <=> $dateA;
    });

    // Summary
    $totalCadastros = count($cadastros);
    $totalChecklists = isset($checklists) ? count($checklists) : 0;
    $totalOrdens = isset($ordens) ? count($ordens) : 0;

    jsonResponse([
        'success' => true,
        'data' => $data,
        'technician' => $technician,
        'summary' => [
            'total' => count($data),
            'cadastros' => $totalCadastros,
            'checklists' => $totalChecklists,
            'ordens' => $totalOrdens,
            'technician_name' => $technician['full_name']
        ],
        'columns' => [
            ['key' => 'tipo_servico', 'label' => 'Tipo'],
            ['key' => 'cliente', 'label' => 'Cliente'],
            ['key' => 'cpf', 'label' => 'CPF'],
            ['key' => 'servico_detalhe', 'label' => 'Detalhe'],
            ['key' => 'data', 'label' => 'Data'],
            ['key' => 'horario', 'label' => 'Horario']
        ]
    ]);
}

function handleRankingReport($db, $dateFrom, $dateTo) {
    // Ranking consolidado: cadastros + checklists + OS por tecnico
    $stmt = $db->prepare("
        SELECT
            u.id,
            u.full_name,
            u.username,
            u.photo,
            COALESCE(cli.total_cadastros, 0) as total_cadastros,
            COALESCE(chk.total_checklists, 0) as total_checklists,
            COALESCE(os.total_ordens, 0) as total_ordens,
            (COALESCE(cli.total_cadastros, 0) + COALESCE(chk.total_checklists, 0) + COALESCE(os.total_ordens, 0)) as total_geral
        FROM users u
        LEFT JOIN (
            SELECT installer, COUNT(*) as total_cadastros
            FROM clients
            WHERE DATE(created_at) BETWEEN ? AND ?
            GROUP BY installer
        ) cli ON cli.installer = u.username
        LEFT JOIN (
            SELECT technician_id, COUNT(*) as total_checklists
            FROM installation_checklists
            WHERE DATE(created_at) BETWEEN ? AND ?
            GROUP BY technician_id
        ) chk ON chk.technician_id = u.id
        LEFT JOIN (
            SELECT assigned_to, COUNT(*) as total_ordens
            FROM work_orders
            WHERE DATE(created_at) BETWEEN ? AND ?
            GROUP BY assigned_to
        ) os ON os.assigned_to = u.id
        WHERE u.active = 1 AND u.role IN ('user', 'tecnico')
        HAVING total_geral > 0
        ORDER BY total_geral DESC
    ");
    $stmt->execute([$dateFrom, $dateTo, $dateFrom, $dateTo, $dateFrom, $dateTo]);
    $ranking = $stmt->fetchAll();

    jsonResponse([
        'success' => true,
        'data' => $ranking,
        'summary' => ['total_technicians' => count($ranking)],
        'columns' => [
            ['key' => 'full_name', 'label' => 'Tecnico'],
            ['key' => 'total_cadastros', 'label' => 'Cadastros'],
            ['key' => 'total_checklists', 'label' => 'Checklists'],
            ['key' => 'total_ordens', 'label' => 'Ordens'],
            ['key' => 'total_geral', 'label' => 'Total']
        ]
    ]);
}

function handleTechniciansListReport($db) {
    $stmt = $db->prepare("
        SELECT id, full_name, username
        FROM users
        WHERE active = 1 AND role IN ('user', 'tecnico')
        ORDER BY full_name
    ");
    $stmt->execute();
    jsonResponse(['success' => true, 'data' => $stmt->fetchAll()]);
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
        'message' => 'Relatório gerado com sucesso',
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
