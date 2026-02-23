<?php
/**
 * API de Notificações In-App
 * Ondeline Tech - App do Técnico
 */

require_once 'config.php';

$method = $_SERVER['REQUEST_METHOD'];
$userData = requireAuth();

try {
    $db = Database::getInstance()->getConnection();

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
    jsonResponse(['success' => false, 'message' => 'Erro no banco de dados', 'error' => $e->getMessage()], 500);
}

function handleGet($db, $userData) {
    $action = $_GET['action'] ?? 'list';

    if ($action === 'unread_count') {
        $stmt = $db->prepare("SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND `read` = 0");
        $stmt->execute([$userData['user_id']]);
        $count = $stmt->fetch()['count'];
        jsonResponse(['success' => true, 'data' => ['count' => intval($count)]]);
    }

    // List notifications
    $limit = intval($_GET['limit'] ?? 30);
    $offset = intval($_GET['offset'] ?? 0);
    $unreadOnly = isset($_GET['unread']) && $_GET['unread'] === '1';

    $where = "WHERE user_id = ?";
    $params = [$userData['user_id']];

    if ($unreadOnly) {
        $where .= " AND `read` = 0";
    }

    $stmt = $db->prepare("SELECT COUNT(*) as total FROM notifications $where");
    $stmt->execute($params);
    $total = $stmt->fetch()['total'];

    $stmt = $db->prepare("SELECT COUNT(*) as unread FROM notifications WHERE user_id = ? AND `read` = 0");
    $stmt->execute([$userData['user_id']]);
    $unreadCount = $stmt->fetch()['unread'];

    $stmt = $db->prepare("SELECT * FROM notifications $where ORDER BY created_at DESC LIMIT $limit OFFSET $offset");
    $stmt->execute($params);
    $notifications = $stmt->fetchAll();

    jsonResponse([
        'success' => true,
        'data' => $notifications,
        'total' => intval($total),
        'unread' => intval($unreadCount)
    ]);
}

function handlePost($db, $userData) {
    $data = getRequestBody();

    // Only admin can create notifications for others
    if ($userData['role'] !== 'admin') {
        jsonResponse(['success' => false, 'message' => 'Sem permissão'], 403);
    }

    if (empty($data['title']) || empty($data['message'])) {
        jsonResponse(['success' => false, 'message' => 'Título e mensagem são obrigatórios'], 400);
    }

    $targetUserId = $data['user_id'] ?? null;

    if ($targetUserId) {
        // Send to specific user
        $stmt = $db->prepare("INSERT INTO notifications (user_id, title, message, type, action_url) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([
            $targetUserId,
            $data['title'],
            $data['message'],
            $data['type'] ?? 'info',
            $data['action_url'] ?? null
        ]);
    } else {
        // Broadcast to all active users
        $stmt = $db->prepare("SELECT id FROM users WHERE active = 1");
        $stmt->execute();
        $users = $stmt->fetchAll();

        $insertStmt = $db->prepare("INSERT INTO notifications (user_id, title, message, type, action_url) VALUES (?, ?, ?, ?, ?)");
        foreach ($users as $user) {
            $insertStmt->execute([
                $user['id'],
                $data['title'],
                $data['message'],
                $data['type'] ?? 'info',
                $data['action_url'] ?? null
            ]);
        }
    }

    jsonResponse(['success' => true, 'message' => 'Notificação enviada com sucesso'], 201);
}

function handlePut($db, $userData) {
    $data = getRequestBody();
    $action = $data['action'] ?? 'mark_read';

    switch ($action) {
        case 'mark_read':
            if (!empty($data['id'])) {
                $stmt = $db->prepare("UPDATE notifications SET `read` = 1 WHERE id = ? AND user_id = ?");
                $stmt->execute([$data['id'], $userData['user_id']]);
            }
            break;

        case 'mark_all_read':
            $stmt = $db->prepare("UPDATE notifications SET `read` = 1 WHERE user_id = ? AND `read` = 0");
            $stmt->execute([$userData['user_id']]);
            break;

        default:
            jsonResponse(['success' => false, 'message' => 'Ação não reconhecida'], 400);
    }

    jsonResponse(['success' => true, 'message' => 'Notificação atualizada']);
}

function handleDelete($db, $userData) {
    $id = $_GET['id'] ?? null;

    if ($id) {
        $stmt = $db->prepare("DELETE FROM notifications WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $userData['user_id']]);
    } else {
        // Delete all read notifications
        $stmt = $db->prepare("DELETE FROM notifications WHERE user_id = ? AND `read` = 1");
        $stmt->execute([$userData['user_id']]);
    }

    jsonResponse(['success' => true, 'message' => 'Notificação(ões) excluída(s)']);
}
