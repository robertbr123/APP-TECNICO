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

    // Busca cidade do técnico para filtro
    $cityFilter = '';
    $cityParam = [];
    if ($userData['role'] === 'tecnico') {
        $cityStmt = $db->prepare("SELECT city FROM users WHERE id = ?");
        $cityStmt->execute([$userData['user_id']]);
        $userCity = $cityStmt->fetch()['city'] ?? null;
        if ($userCity) {
            $cityFilter = " AND city LIKE ?";
            $cityParam = ["%$userCity%"];
        }
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

    jsonResponse([
        'success' => true,
        'data' => [
            'totals' => [
                'clients' => $totalClients,
                'today' => $todayRegistrations,
                'week' => $weekRegistrations,
                'month' => $monthRegistrations
            ],
            'lastRegistration' => $lastRegistration,
            'byInstaller' => $byInstaller,
            'byPlan' => $byPlan,
            'recentRegistrations' => $recentRegistrations,
            'dailyChart' => $dailyChart
        ]
    ]);

} catch (PDOException $e) {
    jsonResponse(['success' => false, 'message' => 'Erro ao buscar estatísticas', 'error' => $e->getMessage()], 500);
}
