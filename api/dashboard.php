<?php
/**
 * API do Dashboard - Estatísticas
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

    // Busca cidade do técnico/user para filtro
    $cityFilter = '';
    $cityParam = [];
    $isTechnician = ($userData['role'] === 'tecnico' || $userData['role'] === 'user');
    if ($isTechnician) {
        $cityStmt = $db->prepare("SELECT city FROM users WHERE id = ?");
        $cityStmt->execute([$userData['user_id']]);
        $userCity = trim($cityStmt->fetch()['city'] ?? '');
        if (!empty($userCity)) {
            $cityFilter = " AND LOWER(TRIM(city)) = LOWER(TRIM(?))";
            $cityParam = [$userCity];
        }
        // Se não tem cidade, vê todos os clientes (sem filtro)
    }

    // Total de clientes
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM clients WHERE 1=1" . $cityFilter);
    $stmt->execute($cityParam);
    $totalClients = $stmt->fetch()['total'];

    // Cadastros de hoje (usando created_at)
    $today = date('Y-m-d');
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM clients WHERE DATE(created_at) = ?" . $cityFilter);
    $stmt->execute(array_merge([$today], $cityParam));
    $todayRegistrations = $stmt->fetch()['total'];

    // Cadastros desta semana
    $weekStart = date('Y-m-d', strtotime('monday this week'));
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM clients WHERE DATE(created_at) >= ?" . $cityFilter);
    $stmt->execute(array_merge([$weekStart], $cityParam));
    $weekRegistrations = $stmt->fetch()['total'];

    // Cadastros deste mês
    $monthStart = date('Y-m-01');
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM clients WHERE DATE(created_at) >= ?" . $cityFilter);
    $stmt->execute(array_merge([$monthStart], $cityParam));
    $monthRegistrations = $stmt->fetch()['total'];

    // Último cadastro (mais recente)
    $stmt = $db->prepare("SELECT cpf, name, city, planId, created_at FROM clients WHERE 1=1" . $cityFilter . " ORDER BY created_at DESC LIMIT 1");
    $stmt->execute($cityParam);
    $lastRegistration = $stmt->fetch();

    // Cadastros por instalador (últimos 30 dias)
    $thirtyDaysAgo = date('Y-m-d', strtotime('-30 days'));
    $stmt = $db->prepare("
        SELECT installer, COUNT(*) as total
        FROM clients
        WHERE DATE(created_at) >= ?" . $cityFilter . "
        GROUP BY installer
        ORDER BY total DESC
    ");
    $stmt->execute(array_merge([$thirtyDaysAgo], $cityParam));
    $byInstaller = $stmt->fetchAll();

    // Cadastros por plano
    $stmt = $db->prepare("SELECT planId as plan, COUNT(*) as total FROM clients WHERE 1=1" . $cityFilter . " GROUP BY planId ORDER BY total DESC");
    $stmt->execute($cityParam);
    $byPlan = $stmt->fetchAll();

    // Últimos cadastros
    $stmt = $db->prepare("SELECT cpf, name, city, planId, created_at FROM clients WHERE 1=1" . $cityFilter . " ORDER BY created_at DESC LIMIT 5");
    $stmt->execute($cityParam);
    $recentRegistrations = $stmt->fetchAll();

    // Cadastros por dia (últimos 7 dias)
    $stmt = $db->prepare("
        SELECT DATE(created_at) as date, COUNT(*) as total
        FROM clients
        WHERE DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)" . $cityFilter . "
        GROUP BY DATE(created_at)
        ORDER BY date
    ");
    $stmt->execute($cityParam);
    $dailyChart = $stmt->fetchAll();

    // Meta mensal - busca da tabela settings ou usa padrão
    $monthlyTarget = 100; // padrão
    try {
        $db->exec("CREATE TABLE IF NOT EXISTS `settings` (
            `key` varchar(100) NOT NULL,
            `value` text NOT NULL,
            `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`key`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $stmt = $db->prepare("SELECT `value` FROM settings WHERE `key` = 'monthly_target'");
        $stmt->execute();
        $row = $stmt->fetch();
        if ($row) {
            $monthlyTarget = intval($row['value']);
        } else {
            $db->prepare("INSERT INTO settings (`key`, `value`) VALUES ('monthly_target', ?)")->execute([$monthlyTarget]);
        }
    } catch (Exception $e) { /* usa padrão */ }

    $metaPercent = $monthlyTarget > 0 ? min(round(($monthRegistrations / $monthlyTarget) * 100), 100) : 0;

    jsonResponse([
        'success' => true,
        'message' => 'Dados carregados com sucesso',
        'data' => [
            'totals' => [
                'clients' => $totalClients,
                'today' => $todayRegistrations,
                'week' => $weekRegistrations,
                'month' => $monthRegistrations
            ],
            'meta' => [
                'target' => $monthlyTarget,
                'current' => $monthRegistrations,
                'percent' => $metaPercent
            ],
            'lastRegistration' => $lastRegistration,
            'byInstaller' => $byInstaller,
            'byPlan' => $byPlan,
            'recentRegistrations' => $recentRegistrations,
            'dailyChart' => $dailyChart
        ]
    ]);

} catch (PDOException $e) {
    jsonResponse(['success' => false, 'message' => 'Erro ao buscar estatísticas'], 500);
}
