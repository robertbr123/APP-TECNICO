<?php
/**
 * API de Integração TR069
 * 
 * Endpoint para comunicação entre o APP-TECNICO e o sistema TR069 ACS.
 * Permite buscar informações WiFi e alterar configurações remotamente.
 */

// Habilita exibição de erros para debug (remover em produção)
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Captura erros e converte em JSON
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Erro PHP',
        'message' => $errstr,
        'file' => basename($errfile),
        'line' => $errline
    ]);
    exit;
});

// Captura exceções
set_exception_handler(function($e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Exceção',
        'message' => $e->getMessage(),
        'file' => basename($e->getFile()),
        'line' => $e->getLine()
    ]);
    exit;
});

// Captura erros fatais
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Erro Fatal',
            'message' => $error['message'],
            'file' => basename($error['file']),
            'line' => $error['line']
        ]);
    }
});

require_once 'config.php';
require_once 'tr069-config.php';

// Verifica autenticação
$token = null;
$headers = getallheaders();
if (isset($headers['Authorization'])) {
    $authHeader = $headers['Authorization'];
    if (strpos($authHeader, 'Bearer ') === 0) {
        $token = substr($authHeader, 7);
    }
}

// Valida o token (usa a mesma função do sistema)
$userId = null;
$userRole = null;
if ($token) {
    $tokenData = verifyToken($token);
    if ($tokenData) {
        $userId = $tokenData['user_id'] ?? null;
        $userRole = $tokenData['role'] ?? null;
    }
}

// Para ações de alteração, exige autenticação
// Para consultas, permite sem auth (mas logamos quem consultou)

// Pega o método da requisição
$method = $_SERVER['REQUEST_METHOD'];

// Pega a ação da URL ou body
$action = $_GET['action'] ?? null;
if (!$action && $method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? null;
}

// Roteamento de ações
switch ($action) {
    
    // =====================================================
    // HEALTH CHECK - Verifica se o TR069 está disponível
    // =====================================================
    case 'health':
        $result = tr069_health_check();
        echo json_encode([
            'success' => $result['success'] ?? false,
            'tr069_service' => $result['service'] ?? 'Indisponível',
            'tr069_version' => $result['version'] ?? 'N/A',
            'message' => $result['success'] ? 'Serviço TR069 online' : 'Serviço TR069 offline ou inacessível'
        ]);
        break;
    
    // =====================================================
    // GET DEVICE - Busca dispositivo por PPPoE
    // =====================================================
    case 'get_device':
        $pppoe = $_GET['pppoe'] ?? ($input['pppoe'] ?? null);
        
        if (!$pppoe) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => 'Parâmetro pppoe é obrigatório'
            ]);
            break;
        }
        
        $result = tr069_get_device_by_pppoe($pppoe);
        
        // Adiciona informações de status HTTP
        $httpCode = $result['http_code'] ?? 500;
        unset($result['http_code']);
        
        if ($httpCode >= 400) {
            http_response_code($httpCode);
        }
        
        echo json_encode($result);
        break;
    
    // =====================================================
    // GET WIFI - Busca configurações WiFi do dispositivo
    // =====================================================
    case 'get_wifi':
        $pppoe = $_GET['pppoe'] ?? ($input['pppoe'] ?? null);
        
        if (!$pppoe) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => 'Parâmetro pppoe é obrigatório'
            ]);
            break;
        }
        
        $result = tr069_get_wifi($pppoe);
        
        $httpCode = $result['http_code'] ?? 500;
        unset($result['http_code']);
        
        if ($httpCode >= 400) {
            http_response_code($httpCode);
        }
        
        // Log da consulta
        if ($userId) {
            logAuditAction($userId, 'tr069_wifi_consulted', 'Consultou WiFi do cliente', 'device', $pppoe, null, [
                'device_serial' => $result['device_serial'] ?? null,
                'ssid' => $result['wifi']['ssid'] ?? null
            ]);
        }
        
        echo json_encode($result);
        break;
    
    // =====================================================
    // CHANGE WIFI - Altera configurações WiFi
    // =====================================================
    case 'change_wifi':
        // Requer autenticação para alterações
        if (!$userId) {
            http_response_code(401);
            echo json_encode([
                'success' => false,
                'error' => 'Autenticação necessária para alterar configurações'
            ]);
            break;
        }
        
        // Pega os dados do body
        if (!$input) {
            $input = json_decode(file_get_contents('php://input'), true);
        }
        
        $pppoe = $input['pppoe'] ?? null;
        $ssid = $input['ssid'] ?? null;
        $password = $input['password'] ?? null;
        $security_mode = $input['security_mode'] ?? null;
        $encryption = $input['encryption'] ?? null;
        
        if (!$pppoe) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => 'Parâmetro pppoe é obrigatório'
            ]);
            break;
        }
        
        if (!$ssid && !$password && !$security_mode && !$encryption) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => 'Pelo menos um parâmetro deve ser fornecido (ssid, password, security_mode ou encryption)'
            ]);
            break;
        }
        
        // Valida senha mínima
        if ($password && strlen($password) < 8) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => 'Senha WiFi deve ter no mínimo 8 caracteres'
            ]);
            break;
        }
        
        $result = tr069_change_wifi($pppoe, $ssid, $password, $security_mode, $encryption);
        
        $httpCode = $result['http_code'] ?? 500;
        unset($result['http_code']);
        
        if ($httpCode >= 400) {
            http_response_code($httpCode);
        }
        
        // Log da alteração
        $changes = [];
        if ($ssid) $changes['ssid'] = $ssid;
        if ($password) $changes['password'] = '********';
        if ($security_mode) $changes['security_mode'] = $security_mode;
        if ($encryption) $changes['encryption'] = $encryption;
        
        logAuditAction($userId, 'tr069_wifi_changed', 'Alterou configurações WiFi do cliente', 'device', $pppoe, null, [
            'device_serial' => $result['device_serial'] ?? null,
            'changes' => $changes,
            'success' => $result['success'] ?? false
        ]);
        
        echo json_encode($result);
        break;
    
    // =====================================================
    // LIST DEVICES - Lista dispositivos (opcional: online)
    // =====================================================
    case 'list_devices':
        $onlineOnly = isset($_GET['online_only']) && $_GET['online_only'] === 'true';
        $limit = intval($_GET['limit'] ?? 100);
        
        $result = tr069_list_devices($onlineOnly, $limit);
        
        $httpCode = $result['http_code'] ?? 500;
        unset($result['http_code']);
        
        if ($httpCode >= 400) {
            http_response_code($httpCode);
        }
        
        echo json_encode($result);
        break;
    
    // =====================================================
    // AÇÃO NÃO RECONHECIDA
    // =====================================================
    default:
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Ação não especificada ou inválida',
            'available_actions' => [
                'health' => 'Verifica se o TR069 está online',
                'get_device' => 'Busca dispositivo por PPPoE',
                'get_wifi' => 'Busca configurações WiFi',
                'change_wifi' => 'Altera configurações WiFi',
                'list_devices' => 'Lista dispositivos cadastrados'
            ]
        ]);
        break;
}

// =====================================================
// FUNÇÕES AUXILIARES
// =====================================================

/**
 * Registra ação no log de auditoria
 */
function logAuditAction($userId, $actionType, $description, $entityType, $entityId, $entityName = null, $details = null) {
    try {
        $db = Database::getInstance()->getConnection();
        
        $stmt = $db->prepare("
            INSERT INTO audit_logs (user_id, action_type, action_description, entity_type, entity_id, entity_name, details, ip_address, user_agent)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $userId,
            $actionType,
            $description,
            $entityType,
            $entityId,
            $entityName,
            $details ? json_encode($details) : null,
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? null
        ]);
    } catch (Exception $e) {
        // Silencia erros de log para não afetar a operação principal
        error_log("Erro ao registrar auditoria: " . $e->getMessage());
    }
}
