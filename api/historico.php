<?php
/**
 * API de Historico/Desempenho do Tecnico
 * Ondeline Tech - App do Tecnico
 */

require_once 'config.php';
require_once 'Logger.php';

$method = $_SERVER['REQUEST_METHOD'];
$userData = requireAuth();

if ($method !== 'GET') {
    jsonResponse(['success' => false, 'message' => 'Metodo nao permitido'], 405);
}

$action = $_GET['action'] ?? 'stats';

// Ranking/Leaderboard - apenas admin
if ($action === 'leaderboard') {
    if ($userData['role'] !== 'admin') {
        jsonResponse(['success' => false, 'message' => 'Acesso negado'], 403);
    }

    try {
        $db = Database::getInstance()->getConnection();
        $monthStart = date('Y-m-01');

        // Ranking de cadastros (clients) por tecnico no mes
        $stmt = $db->prepare("
            SELECT
                c.installer as username,
                COALESCE(u.full_name, c.installer) as full_name,
                u.photo,
                COUNT(*) as total_cadastros
            FROM clients c
            LEFT JOIN users u ON u.username = c.installer
            WHERE DATE(c.created_at) >= ?
            AND c.installer IS NOT NULL AND c.installer != ''
            GROUP BY c.installer
            ORDER BY total_cadastros DESC
        ");
        $stmt->execute([$monthStart]);
        $cadastrosRanking = $stmt->fetchAll();

        // Ranking de checklists concluidos por tecnico no mes
        $checklistRanking = [];
        try {
            $stmt = $db->prepare("
                SELECT
                    ic.technician_id,
                    COALESCE(u.full_name, u.username) as full_name,
                    u.username,
                    u.photo,
                    COUNT(*) as total_checklists
                FROM installation_checklists ic
                JOIN users u ON u.id = ic.technician_id
                WHERE ic.status = 'completed'
                AND DATE(ic.created_at) >= ?
                GROUP BY ic.technician_id
                ORDER BY total_checklists DESC
            ");
            $stmt->execute([$monthStart]);
            $checklistRanking = $stmt->fetchAll();
        } catch (Exception $e) { /* tabela pode nao existir */ }

        $months = ['Janeiro','Fevereiro','Marco','Abril','Maio','Junho','Julho','Agosto','Setembro','Outubro','Novembro','Dezembro'];
        $monthLabel = $months[(int)date('m') - 1] . ' ' . date('Y');

        jsonResponse([
            'success' => true,
            'data' => [
                'month' => $monthLabel,
                'cadastros' => $cadastrosRanking,
                'checklists' => $checklistRanking
            ]
        ]);
    } catch (PDOException $e) {
        jsonResponse(['success' => false, 'message' => 'Erro ao buscar ranking'], 500);
    }
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
    
    // Log para debug
    Logger::debug("Historico request", ['user' => $username, 'role' => $role, 'weekStart' => $weekStart]);

    // Cadastros hoje
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM clients WHERE DATE(created_at) = ?" . $installerFilter);
    $stmt->execute(array_merge([$today], $installerParam));
    $todayInstallations = (int)$stmt->fetch()['total'];

    // Cadastros esta semana
    $weekSql = "SELECT COUNT(*) as total FROM clients WHERE DATE(created_at) >= ?" . $installerFilter;
    $weekParams = array_merge([$weekStart], $installerParam);
    Logger::debug("Historico week query", ['sql' => $weekSql, 'params' => $weekParams]);
    $stmt = $db->prepare($weekSql);
    $stmt->execute($weekParams);
    $weekInstallations = (int)$stmt->fetch()['total'];
    Logger::debug("Historico week result", ['count' => $weekInstallations]);

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

    // Meta mensal individual do usuario (ou fallback global)
    $monthlyGoal = 10; // padrao
    try {
        // Primeiro tenta buscar meta individual do usuario
        $stmtMeta = $db->prepare("SELECT monthly_goal FROM users WHERE id = ?");
        $stmtMeta->execute([$userData['user_id']]);
        $userMeta = $stmtMeta->fetch();
        if ($userMeta && !empty($userMeta['monthly_goal'])) {
            $monthlyGoal = intval($userMeta['monthly_goal']);
        } else {
            // Se nao tem meta individual, busca a global da tabela settings
            $db->exec("CREATE TABLE IF NOT EXISTS `settings` (
                `key` varchar(100) NOT NULL,
                `value` text NOT NULL,
                PRIMARY KEY (`key`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            
            $stmtGlobal = $db->prepare("SELECT `value` FROM settings WHERE `key` = 'monthly_target'");
            $stmtGlobal->execute();
            $globalMeta = $stmtGlobal->fetch();
            if ($globalMeta) {
                $monthlyGoal = intval($globalMeta['value']);
            }
        }
        
        // Tenta adicionar coluna monthly_goal na tabela users se nao existir
        $db->exec("ALTER TABLE users ADD COLUMN monthly_goal INT DEFAULT NULL");
    } catch (Exception $e) { /* usa padrao */ }

    jsonResponse([
        'success' => true,
        'message' => 'Dados carregados com sucesso',
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
    jsonResponse(['success' => false, 'message' => 'Erro ao buscar historico'], 500);
}
