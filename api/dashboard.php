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
    
    // Total de clientes
    $stmt = $db->query("SELECT COUNT(*) as total FROM clients");
    $totalClients = $stmt->fetch()['total'];

    // Cadastros de hoje
    $today = date('Y-m-d');
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM clients WHERE registration_date = ?");
    $stmt->execute([$today]);
    $todayRegistrations = $stmt->fetch()['total'];

    // Cadastros desta semana
    $weekStart = date('Y-m-d', strtotime('monday this week'));
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM clients WHERE registration_date >= ?");
    $stmt->execute([$weekStart]);
    $weekRegistrations = $stmt->fetch()['total'];

    // Cadastros deste mês
    $monthStart = date('Y-m-01');
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM clients WHERE registration_date >= ?");
    $stmt->execute([$monthStart]);
    $monthRegistrations = $stmt->fetch()['total'];

    // Cadastros por instalador (últimos 30 dias)
    $thirtyDaysAgo = date('Y-m-d', strtotime('-30 days'));
    $stmt = $db->prepare("
        SELECT installer, COUNT(*) as total 
        FROM clients 
        WHERE registration_date >= ?
        GROUP BY installer 
        ORDER BY total DESC
    ");
    $stmt->execute([$thirtyDaysAgo]);
    $byInstaller = $stmt->fetchAll();

    // Cadastros por plano
    $stmt = $db->query("SELECT plan, COUNT(*) as total FROM clients GROUP BY plan ORDER BY total DESC");
    $byPlan = $stmt->fetchAll();

    // Últimos cadastros
    $stmt = $db->query("SELECT cpf, name, city, plan, registration_date FROM clients ORDER BY registration_date DESC LIMIT 5");
    $recentRegistrations = $stmt->fetchAll();

    // Cadastros por dia (últimos 7 dias)
    $stmt = $db->prepare("
        SELECT registration_date as date, COUNT(*) as total 
        FROM clients 
        WHERE registration_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
        GROUP BY registration_date 
        ORDER BY registration_date
    ");
    $stmt->execute();
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
            'byInstaller' => $byInstaller,
            'byPlan' => $byPlan,
            'recentRegistrations' => $recentRegistrations,
            'dailyChart' => $dailyChart
        ]
    ]);

} catch (PDOException $e) {
    jsonResponse(['success' => false, 'message' => 'Erro ao buscar estatísticas', 'error' => $e->getMessage()], 500);
}
