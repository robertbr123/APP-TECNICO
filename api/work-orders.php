<?php
/**
 * API de Ordens de Serviço (OS)
 * Ondeline Tech - App do Técnico
 */

require_once 'config.php';

$method = $_SERVER['REQUEST_METHOD'];
$userData = requireAuth();

try {
    $db = Database::getInstance()->getConnection();

    // Auto-create table if not exists
    $db->exec("
        CREATE TABLE IF NOT EXISTS `work_orders` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `order_number` varchar(20) NOT NULL,
            `client_cpf` varchar(11) DEFAULT NULL,
            `client_name` varchar(255) NOT NULL,
            `type` enum('installation','repair','maintenance','migration','removal','other') DEFAULT 'repair',
            `priority` enum('low','medium','high','urgent') DEFAULT 'medium',
            `status` enum('open','assigned','in_progress','completed','cancelled') DEFAULT 'open',
            `assigned_to` int(11) DEFAULT NULL,
            `assigned_name` varchar(150) DEFAULT NULL,
            `description` text NOT NULL,
            `resolution` text DEFAULT NULL,
            `scheduled_date` date DEFAULT NULL,
            `scheduled_time` time DEFAULT NULL,
            `started_at` timestamp NULL DEFAULT NULL,
            `completed_at` timestamp NULL DEFAULT NULL,
            `latitude` decimal(10,8) DEFAULT NULL,
            `longitude` decimal(11,8) DEFAULT NULL,
            `created_by` int(11) NOT NULL,
            `created_by_name` varchar(150) DEFAULT NULL,
            `resolution_photos` text DEFAULT NULL,
            `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
            `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `order_number` (`order_number`),
            KEY `client_cpf` (`client_cpf`),
            KEY `assigned_to` (`assigned_to`),
            KEY `status` (`status`),
            KEY `priority` (`priority`),
            KEY `scheduled_date` (`scheduled_date`),
            KEY `created_at` (`created_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    // Add resolution_photos column if missing
    try {
        $db->exec("ALTER TABLE work_orders ADD COLUMN `resolution_photos` text DEFAULT NULL AFTER `resolution`");
    } catch (PDOException $e) { /* column already exists */ }

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
    jsonResponse(['success' => false, 'message' => 'Erro no banco de dados'], 500);
}

function generateOrderNumber($db) {
    $prefix = 'OS-' . date('Ymd') . '-';
    $stmt = $db->prepare("SELECT order_number FROM work_orders WHERE order_number LIKE ? ORDER BY id DESC LIMIT 1");
    $stmt->execute([$prefix . '%']);
    $last = $stmt->fetch();

    if ($last) {
        $lastNum = intval(substr($last['order_number'], -4));
        return $prefix . str_pad($lastNum + 1, 4, '0', STR_PAD_LEFT);
    }
    return $prefix . '0001';
}

function handleGet($db, $userData) {
    $action = $_GET['action'] ?? 'list';

    if ($action === 'get' && isset($_GET['id'])) {
        $stmt = $db->prepare("SELECT * FROM work_orders WHERE id = ?");
        $stmt->execute([$_GET['id']]);
        $order = $stmt->fetch();

        if (!$order) {
            jsonResponse(['success' => false, 'message' => 'OS não encontrada'], 404);
        }
        jsonResponse(['success' => true, 'message' => 'Dados carregados com sucesso', 'data' => $order]);
    }

    if ($action === 'stats') {
        $cityFilter = '';
        $cityParam = [];
        if ($userData['role'] !== 'admin') {
            $cityFilter = " AND assigned_to = ?";
            $cityParam = [$userData['user_id']];
        }

        $stats = [];
        foreach (['open', 'assigned', 'in_progress', 'completed', 'cancelled'] as $status) {
            $stmt = $db->prepare("SELECT COUNT(*) as total FROM work_orders WHERE status = ?" . $cityFilter);
            $stmt->execute(array_merge([$status], $cityParam));
            $stats[$status] = $stmt->fetch()['total'];
        }

        // Hoje
        $stmt = $db->prepare("SELECT COUNT(*) as total FROM work_orders WHERE DATE(created_at) = CURDATE()" . $cityFilter);
        $stmt->execute($cityParam);
        $stats['today'] = $stmt->fetch()['total'];

        jsonResponse(['success' => true, 'message' => 'Dados carregados com sucesso', 'data' => $stats]);
    }

    // List with filters
    $status = $_GET['status'] ?? null;
    $priority = $_GET['priority'] ?? null;
    $assignedTo = $_GET['assigned_to'] ?? null;
    $search = $_GET['search'] ?? null;
    $limit = intval($_GET['limit'] ?? 50);
    $offset = intval($_GET['offset'] ?? 0);

    $where = "WHERE 1=1";
    $params = [];

    // Technicians only see their assigned orders
    if ($userData['role'] !== 'admin') {
        $where .= " AND (assigned_to = ? OR created_by = ?)";
        $params[] = $userData['user_id'];
        $params[] = $userData['user_id'];
    }

    if ($status) {
        $where .= " AND status = ?";
        $params[] = $status;
    }
    if ($priority) {
        $where .= " AND priority = ?";
        $params[] = $priority;
    }
    if ($assignedTo) {
        $where .= " AND assigned_to = ?";
        $params[] = $assignedTo;
    }
    if ($search) {
        $where .= " AND (client_name LIKE ? OR order_number LIKE ? OR description LIKE ? OR client_cpf LIKE ?)";
        $searchTerm = "%$search%";
        $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
    }

    $stmt = $db->prepare("SELECT COUNT(*) as total FROM work_orders $where");
    $stmt->execute($params);
    $total = $stmt->fetch()['total'];

    $stmt = $db->prepare("SELECT * FROM work_orders $where ORDER BY
        CASE priority
            WHEN 'urgent' THEN 1
            WHEN 'high' THEN 2
            WHEN 'medium' THEN 3
            WHEN 'low' THEN 4
        END,
        created_at DESC
        LIMIT $limit OFFSET $offset");
    $stmt->execute($params);
    $orders = $stmt->fetchAll();

    jsonResponse([
        'success' => true,
        'message' => 'Dados carregados com sucesso',
        'data' => $orders,
        'total' => $total,
        'limit' => $limit,
        'offset' => $offset
    ]);
}

function handlePost($db, $userData) {
    $data = getRequestBody();
    $action = $data['action'] ?? 'create';

    if ($action === 'create') {
        // Only admin can create OS
        if ($userData['role'] !== 'admin') {
            jsonResponse(['success' => false, 'message' => 'Apenas administradores podem criar OS'], 403);
        }

        if (empty($data['client_name']) || empty($data['description'])) {
            jsonResponse(['success' => false, 'message' => 'Nome do cliente e descrição são obrigatórios'], 400);
        }

        $orderNumber = generateOrderNumber($db);

        $initialStatus = !empty($data['assigned_to']) ? 'assigned' : 'open';

        $stmt = $db->prepare("
            INSERT INTO work_orders (order_number, client_cpf, client_name, type, priority, status, assigned_to, assigned_name, description, scheduled_date, scheduled_time, created_by, created_by_name)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $orderNumber,
            $data['client_cpf'] ?? null,
            $data['client_name'],
            $data['type'] ?? 'repair',
            $data['priority'] ?? 'medium',
            $initialStatus,
            $data['assigned_to'] ?? null,
            $data['assigned_name'] ?? null,
            $data['description'],
            $data['scheduled_date'] ?? null,
            $data['scheduled_time'] ?? null,
            $userData['user_id'],
            $userData['username']
        ]);

        $orderId = $db->lastInsertId();

        // Create notification for assigned user
        if (!empty($data['assigned_to'])) {
            $notifStmt = $db->prepare("
                INSERT INTO notifications (user_id, title, message, type, action_url)
                VALUES (?, ?, ?, 'info', ?)
            ");
            $notifStmt->execute([
                $data['assigned_to'],
                'Nova OS Atribuída',
                "OS $orderNumber - " . $data['client_name'] . ": " . substr($data['description'], 0, 100),
                "ordens.php?id=$orderId"
            ]);
        }

        jsonResponse([
            'success' => true,
            'message' => 'OS criada com sucesso',
            'data' => [
                'id' => $orderId,
                'order_number' => $orderNumber
            ]
        ], 201);
    }
}

function handlePut($db, $userData) {
    $data = getRequestBody();

    if (empty($data['id'])) {
        jsonResponse(['success' => false, 'message' => 'ID da OS é obrigatório'], 400);
    }

    $action = $data['action'] ?? 'update';

    // Fetch current order
    $stmt = $db->prepare("SELECT * FROM work_orders WHERE id = ?");
    $stmt->execute([$data['id']]);
    $order = $stmt->fetch();

    if (!$order) {
        jsonResponse(['success' => false, 'message' => 'OS não encontrada'], 404);
    }

    switch ($action) {
        case 'assign':
            if (empty($data['assigned_to'])) {
                jsonResponse(['success' => false, 'message' => 'ID do técnico é obrigatório'], 400);
            }
            $stmt = $db->prepare("UPDATE work_orders SET assigned_to = ?, assigned_name = ?, status = 'assigned', updated_at = NOW() WHERE id = ?");
            $stmt->execute([$data['assigned_to'], $data['assigned_name'] ?? null, $data['id']]);

            // Notify assigned user
            $notifStmt = $db->prepare("INSERT INTO notifications (user_id, title, message, type, action_url) VALUES (?, ?, ?, 'info', ?)");
            $notifStmt->execute([
                $data['assigned_to'],
                'OS Atribuída a Você',
                "OS {$order['order_number']} - {$order['client_name']}",
                "ordens.php?id={$data['id']}"
            ]);
            break;

        case 'start':
            $stmt = $db->prepare("UPDATE work_orders SET status = 'in_progress', started_at = NOW(), latitude = ?, longitude = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$data['latitude'] ?? null, $data['longitude'] ?? null, $data['id']]);
            break;

        case 'complete':
            if (empty($data['resolution'])) {
                jsonResponse(['success' => false, 'message' => 'Resolução é obrigatória para concluir'], 400);
            }

            // Save resolution photos as files
            $photoUrls = [];
            if (!empty($data['photos']) && is_array($data['photos'])) {
                $uploadDir = __DIR__ . '/../uploads/os/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                foreach ($data['photos'] as $idx => $base64) {
                    if (preg_match('/^data:image\/(jpeg|png|webp|jpg);base64,/', $base64, $matches)) {
                        $ext = $matches[1] === 'jpeg' ? 'jpg' : $matches[1];
                        $imageData = base64_decode(preg_replace('/^data:image\/\w+;base64,/', '', $base64));
                        $filename = 'os_' . $data['id'] . '_' . time() . '_' . $idx . '.' . $ext;
                        if (file_put_contents($uploadDir . $filename, $imageData)) {
                            $photoUrls[] = 'uploads/os/' . $filename;
                        }
                    }
                }
            }

            $photosJson = !empty($photoUrls) ? json_encode($photoUrls) : null;
            $stmt = $db->prepare("UPDATE work_orders SET status = 'completed', resolution = ?, resolution_photos = ?, completed_at = NOW(), updated_at = NOW() WHERE id = ?");
            $stmt->execute([$data['resolution'], $photosJson, $data['id']]);

            // Notify creator
            if ($order['created_by'] != $userData['user_id']) {
                $notifStmt = $db->prepare("INSERT INTO notifications (user_id, title, message, type, action_url) VALUES (?, ?, ?, 'success', ?)");
                $notifStmt->execute([
                    $order['created_by'],
                    'OS Concluída',
                    "OS {$order['order_number']} foi concluída por {$userData['username']}",
                    "ordens.php?id={$data['id']}"
                ]);
            }
            break;

        case 'cancel':
            $stmt = $db->prepare("UPDATE work_orders SET status = 'cancelled', resolution = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$data['resolution'] ?? 'Cancelada', $data['id']]);
            break;

        case 'update':
            $fields = [];
            $values = [];
            $allowed = ['client_name', 'client_cpf', 'type', 'priority', 'description', 'scheduled_date', 'scheduled_time', 'assigned_to', 'assigned_name'];

            foreach ($allowed as $field) {
                if (isset($data[$field])) {
                    $fields[] = "$field = ?";
                    $values[] = $data[$field];
                }
            }

            if (empty($fields)) {
                jsonResponse(['success' => false, 'message' => 'Nenhum campo para atualizar'], 400);
            }

            $fields[] = "updated_at = NOW()";
            $values[] = $data['id'];

            $stmt = $db->prepare("UPDATE work_orders SET " . implode(', ', $fields) . " WHERE id = ?");
            $stmt->execute($values);
            break;

        default:
            jsonResponse(['success' => false, 'message' => 'Ação não reconhecida'], 400);
    }

    jsonResponse(['success' => true, 'message' => 'OS atualizada com sucesso']);
}

function handleDelete($db, $userData) {
    $id = $_GET['id'] ?? null;

    if (!$id) {
        jsonResponse(['success' => false, 'message' => 'ID da OS é obrigatório'], 400);
    }

    // Only admin or creator can delete
    $stmt = $db->prepare("SELECT created_by FROM work_orders WHERE id = ?");
    $stmt->execute([$id]);
    $order = $stmt->fetch();

    if (!$order) {
        jsonResponse(['success' => false, 'message' => 'OS não encontrada'], 404);
    }

    if ($userData['role'] !== 'admin' && $order['created_by'] != $userData['user_id']) {
        jsonResponse(['success' => false, 'message' => 'Sem permissão para excluir esta OS'], 403);
    }

    $stmt = $db->prepare("DELETE FROM work_orders WHERE id = ?");
    $stmt->execute([$id]);

    jsonResponse(['success' => true, 'message' => 'OS excluída com sucesso']);
}
