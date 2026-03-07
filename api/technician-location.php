<?php
/**
 * API de Localização de Técnicos
 * Ondeline Tech - App do Técnico
 * 
 * Rastreia localização em tempo real dos técnicos para:
 * - Auto-atribuição de OS ao técnico mais próximo
 * - Visualização no mapa pelo admin
 */

require_once 'config.php';

$method = $_SERVER['REQUEST_METHOD'];
$userData = requireAuth();

try {
    $db = Database::getInstance()->getConnection();

    // Auto-create table if not exists
    $db->exec("
        CREATE TABLE IF NOT EXISTS `technician_locations` (
            `user_id` int(11) NOT NULL,
            `latitude` decimal(10, 8) NOT NULL,
            `longitude` decimal(11, 8) NOT NULL,
            `accuracy` decimal(10, 2) DEFAULT NULL,
            `heading` decimal(5, 2) DEFAULT NULL,
            `speed` decimal(6, 2) DEFAULT NULL,
            `is_active` tinyint(1) DEFAULT 1,
            `last_activity` varchar(50) DEFAULT NULL,
            `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`user_id`),
            KEY `idx_location` (`latitude`, `longitude`),
            KEY `idx_active` (`is_active`, `updated_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    switch ($method) {
        case 'GET':
            handleGet($db, $userData);
            break;
        case 'POST':
            handlePost($db, $userData);
            break;
        default:
            jsonResponse(['success' => false, 'message' => 'Método não permitido'], 405);
    }
} catch (PDOException $e) {
    Logger::error('Erro DB technician-location', ['error' => $e->getMessage()]);
    jsonResponse(['success' => false, 'message' => 'Erro no banco de dados'], 500);
}

/**
 * GET - Lista técnicos ativos ou busca o mais próximo
 */
function handleGet($db, $userData) {
    $action = $_GET['action'] ?? 'list';

    // Lista todos os técnicos ativos (apenas admin)
    if ($action === 'list') {
        if ($userData['role'] !== 'admin') {
            jsonResponse(['success' => false, 'message' => 'Acesso negado'], 403);
        }

        // Técnicos ativos nas últimas 2 horas
        $stmt = $db->prepare("
            SELECT 
                tl.user_id,
                u.full_name,
                u.username,
                u.photo,
                tl.latitude,
                tl.longitude,
                tl.accuracy,
                tl.heading,
                tl.speed,
                tl.is_active,
                tl.last_activity,
                tl.updated_at,
                TIMESTAMPDIFF(MINUTE, tl.updated_at, NOW()) as minutes_ago
            FROM technician_locations tl
            JOIN users u ON u.id = tl.user_id
            WHERE u.active = 1
            AND u.role IN ('user', 'tecnico')
            AND tl.updated_at > DATE_SUB(NOW(), INTERVAL 2 HOUR)
            ORDER BY tl.updated_at DESC
        ");
        $stmt->execute();
        $technicians = $stmt->fetchAll();

        jsonResponse([
            'success' => true,
            'message' => 'Técnicos ativos carregados',
            'data' => $technicians,
            'total' => count($technicians)
        ]);
    }

    // Sugere técnico mais próximo de um cliente (apenas admin)
    if ($action === 'suggest') {
        if ($userData['role'] !== 'admin') {
            jsonResponse(['success' => false, 'message' => 'Acesso negado'], 403);
        }

        $clientCpf = $_GET['client_cpf'] ?? null;
        $clientLat = isset($_GET['latitude']) ? (float)$_GET['latitude'] : null;
        $clientLon = isset($_GET['longitude']) ? (float)$_GET['longitude'] : null;

        // Se não tem coords diretas, busca do cliente
        if ((!$clientLat || !$clientLon) && $clientCpf) {
            $cleanCpf = preg_replace('/\D/', '', $clientCpf);
            $stmt = $db->prepare("SELECT latitude, longitude FROM clients WHERE cpf = ?");
            $stmt->execute([$cleanCpf]);
            $client = $stmt->fetch();
            
            if ($client && $client['latitude'] && $client['longitude']) {
                $clientLat = (float)$client['latitude'];
                $clientLon = (float)$client['longitude'];
            }
        }

        if (!$clientLat || !$clientLon) {
            jsonResponse([
                'success' => false, 
                'message' => 'Localização do cliente não disponível',
                'data' => []
            ]);
        }

        // Busca técnicos ativos com distância calculada (Fórmula Haversine)
        $stmt = $db->prepare("
            SELECT 
                tl.user_id,
                u.full_name,
                u.username,
                u.photo,
                tl.latitude,
                tl.longitude,
                tl.accuracy,
                tl.is_active,
                tl.last_activity,
                tl.updated_at,
                TIMESTAMPDIFF(MINUTE, tl.updated_at, NOW()) as minutes_ago,
                (
                    6371 * acos(
                        cos(radians(?)) * 
                        cos(radians(tl.latitude)) * 
                        cos(radians(tl.longitude) - radians(?)) + 
                        sin(radians(?)) * 
                        sin(radians(tl.latitude))
                    )
                ) AS distance_km
            FROM technician_locations tl
            JOIN users u ON u.id = tl.user_id
            WHERE u.active = 1
            AND u.role IN ('user', 'tecnico')
            AND tl.updated_at > DATE_SUB(NOW(), INTERVAL 2 HOUR)
            ORDER BY distance_km ASC
            LIMIT 5
        ");
        $stmt->execute([$clientLat, $clientLon, $clientLat]);
        $suggestions = $stmt->fetchAll();

        // Formata distância
        foreach ($suggestions as &$tech) {
            $tech['distance_km'] = round($tech['distance_km'], 2);
            $tech['distance_text'] = $tech['distance_km'] < 1 
                ? round($tech['distance_km'] * 1000) . 'm'
                : $tech['distance_km'] . 'km';
        }

        jsonResponse([
            'success' => true,
            'message' => count($suggestions) > 0 ? 'Técnicos encontrados' : 'Nenhum técnico disponível próximo',
            'data' => $suggestions,
            'client_location' => [
                'latitude' => $clientLat,
                'longitude' => $clientLon
            ]
        ]);
    }

    // Minha localização atual
    if ($action === 'my') {
        $stmt = $db->prepare("
            SELECT latitude, longitude, accuracy, is_active, last_activity, updated_at
            FROM technician_locations
            WHERE user_id = ?
        ");
        $stmt->execute([$userData['user_id']]);
        $location = $stmt->fetch();

        jsonResponse([
            'success' => true,
            'data' => $location ?: null
        ]);
    }

    jsonResponse(['success' => false, 'message' => 'Ação inválida'], 400);
}

/**
 * POST - Atualiza localização do técnico
 */
function handlePost($db, $userData) {
    $data = getRequestBody();
    $action = $data['action'] ?? 'update';

    if ($action === 'update') {
        $latitude = isset($data['latitude']) ? (float)$data['latitude'] : null;
        $longitude = isset($data['longitude']) ? (float)$data['longitude'] : null;
        $accuracy = isset($data['accuracy']) ? (float)$data['accuracy'] : null;
        $heading = isset($data['heading']) ? (float)$data['heading'] : null;
        $speed = isset($data['speed']) ? (float)$data['speed'] : null;
        $lastActivity = $data['last_activity'] ?? 'app_active';

        if (!$latitude || !$longitude) {
            jsonResponse(['success' => false, 'message' => 'Latitude e longitude são obrigatórios'], 400);
        }

        // Validação básica de coordenadas
        if ($latitude < -90 || $latitude > 90 || $longitude < -180 || $longitude > 180) {
            jsonResponse(['success' => false, 'message' => 'Coordenadas inválidas'], 400);
        }

        // Upsert (insert ou update)
        $stmt = $db->prepare("
            INSERT INTO technician_locations 
                (user_id, latitude, longitude, accuracy, heading, speed, is_active, last_activity, updated_at)
            VALUES 
                (?, ?, ?, ?, ?, ?, 1, ?, NOW())
            ON DUPLICATE KEY UPDATE
                latitude = VALUES(latitude),
                longitude = VALUES(longitude),
                accuracy = VALUES(accuracy),
                heading = VALUES(heading),
                speed = VALUES(speed),
                is_active = 1,
                last_activity = VALUES(last_activity),
                updated_at = NOW()
        ");
        $stmt->execute([
            $userData['user_id'],
            $latitude,
            $longitude,
            $accuracy,
            $heading,
            $speed,
            $lastActivity
        ]);

        Logger::debug('Localização atualizada', [
            'user_id' => $userData['user_id'],
            'lat' => $latitude,
            'lng' => $longitude
        ]);

        jsonResponse(['success' => true, 'message' => 'Localização atualizada']);
    }

    // Marca como inativo (saindo do app)
    if ($action === 'inactive') {
        $stmt = $db->prepare("
            UPDATE technician_locations 
            SET is_active = 0, last_activity = 'app_closed'
            WHERE user_id = ?
        ");
        $stmt->execute([$userData['user_id']]);

        jsonResponse(['success' => true, 'message' => 'Status atualizado para inativo']);
    }

    jsonResponse(['success' => false, 'message' => 'Ação inválida'], 400);
}
