<?php
/**
 * API de Historico/Desempenho do Tecnico
 * Ondeline Tech - App do Tecnico
 */

require_once 'config.php';

$method = $_SERVER['REQUEST_METHOD'];
$userData = requireAuth();

if ($method !== 'GET') {
    jsonResponse(['success' => false, 'message' => 'Metodo nao permitido'], 405);
}

try {
    $db = Database::getInstance()->getConnection();

    $username = $userData['username'];
    $role = $userData['role'];
    $today = date('Y-m-d');
    $weekStart = date('Y-m-d', strtotime('monday this week'));
    $monthStart = date('Y-m-01');
    $prevMonthStart = date('Y-m-01', strtotime('-1 month'));
    $prevMonthEnd = date('Y-m-t', strtotime('-1 month'));

    // Filtro: tecnico ve apenas seus cadastros, admin ve todos
    $installerFilter = '';
    $installerParam = [];
    if ($role === 'tecnico') {
        $installerFilter = ' AND installer = ?';
        $installerParam = [$username];
    }

    // Cadastros hoje
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM clients WHERE DATE(created_at) = ?" . $installerFilter);
    $stmt->execute(array_merge([$today], $installerParam));
    $todayInstallations = (int)$stmt->fetch()['total'];

    // Cadastros esta semana
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM clients WHERE DATE(created_at) >= ?" . $installerFilter);
    $stmt->execute(array_merge([$weekStart], $installerParam));
    $weekInstallations = (int)$stmt->fetch()['total'];

    // Cadastros este mes
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM clients WHERE DATE(created_at) >= ?" . $installerFilter);
    $stmt->execute(array_merge([$monthStart], $installerParam));
    $monthInstallations = (int)$stmt->fetch()['total'];

    // Cadastros mes anterior (para comparacao)
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM clients WHERE DATE(created_at) >= ? AND DATE(created_at) <= ?" . $installerFilter);
    $stmt->execute(array_merge([$prevMonthStart, $prevMonthEnd], $installerParam));
    $prevMonthInstallations = (int)$stmt->fetch()['total'];

    // Cadastros por dia no mes atual (para grafico)
    $stmt = $db->prepare("
        SELECT DATE(created_at) as date, COUNT(*) as total
        FROM clients
        WHERE DATE(created_at) >= ?" . $installerFilter . "
        GROUP BY DATE(created_at)
        ORDER BY date
    ");
    $stmt->execute(array_merge([$monthStart], $installerParam));
    $dailyBreakdown = $stmt->fetchAll();

    // Streak: dias consecutivos com pelo menos 1 cadastro
    $stmt = $db->prepare("
        SELECT DISTINCT DATE(created_at) as date
        FROM clients
        WHERE 1=1" . $installerFilter . "
        ORDER BY date DESC
        LIMIT 60
    ");
    $stmt->execute($installerParam);
    $dates = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $streak = 0;
    $checkDate = date('Y-m-d');
    foreach ($dates as $date) {
        if ($date === $checkDate) {
            $streak++;
            $checkDate = date('Y-m-d', strtotime($checkDate . ' -1 day'));
        } else if ($date === date('Y-m-d', strtotime($checkDate))) {
            // skip duplicates
            continue;
        } else {
            // Se o dia anterior ao checkDate nao esta na lista, verifica se e ontem
            if ($streak === 0 && $date === date('Y-m-d', strtotime('-1 day'))) {
                $streak++;
                $checkDate = date('Y-m-d', strtotime($date . ' -1 day'));
            } else {
                break;
            }
        }
    }

    // Ranking entre tecnicos (baseado em cadastros do mes)
    $ranking = 0;
    $totalTechnicians = 0;
    if ($role === 'tecnico') {
        $stmt = $db->prepare("
            SELECT installer, COUNT(*) as total
            FROM clients
            WHERE DATE(created_at) >= ?
            GROUP BY installer
            ORDER BY total DESC
        ");
        $stmt->execute([$monthStart]);
        $allInstallers = $stmt->fetchAll();
        $totalTechnicians = count($allInstallers);

        foreach ($allInstallers as $index => $inst) {
            if ($inst['installer'] === $username) {
                $ranking = $index + 1;
                break;
            }
        }
        if ($ranking === 0 && $totalTechnicians > 0) {
            $ranking = $totalTechnicians + 1;
            $totalTechnicians++;
        }
    }

    // Meta mensal
    $monthlyGoal = 30;

    jsonResponse([
        'success' => true,
        'data' => [
            'todayInstallations' => $todayInstallations,
            'weekInstallations' => $weekInstallations,
            'monthInstallations' => $monthInstallations,
            'prevMonthInstallations' => $prevMonthInstallations,
            'dailyBreakdown' => $dailyBreakdown,
            'monthlyGoal' => $monthlyGoal,
            'streak' => $streak,
            'ranking' => $ranking,
            'totalTechnicians' => $totalTechnicians,
            'username' => $username,
            'role' => $role
        ]
    ]);

} catch (PDOException $e) {
    jsonResponse(['success' => false, 'message' => 'Erro ao buscar historico', 'error' => $e->getMessage()], 500);
}
