<?php
/**
 * Configurações do Banco de Dados MySQL - cPanel
 * Ondeline Tech - App do Técnico
 */

// Configurações de CORS para permitir requisições do frontend
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json; charset=UTF-8");

// Se for uma requisição OPTIONS (preflight), retorna imediatamente
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// =====================================================
// CONFIGURAÇÕES DO BANCO DE DADOS - ALTERE AQUI
// =====================================================
define('DB_HOST', 'localhost');           // Host do MySQL (geralmente localhost no cPanel)
define('DB_NAME', 'onde2292_erp');   // Nome do banco de dados
define('DB_USER', 'onde2292_erp');       // Usuário do banco (crie no cPanel)
define('DB_PASS', 'Ipx1020!');      // Senha do banco
define('DB_CHARSET', 'utf8mb4');

// =====================================================
// CONFIGURAÇÕES DO JWT PARA AUTENTICAÇÃO
// =====================================================
define('JWT_SECRET', '123E34535ERG5546ondeline_tech_secret_key_2024_altere_isso');
define('JWT_EXPIRATION', 86400 * 7); // 7 dias em segundos

// =====================================================
// CLASSE DE CONEXÃO COM O BANCO
// =====================================================
class Database {
    private static $instance = null;
    private $connection;

    private function __construct() {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            $this->connection = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Erro de conexão com o banco de dados',
                'error' => $e->getMessage()
            ]);
            exit();
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection() {
        return $this->connection;
    }
}

// =====================================================
// FUNÇÕES AUXILIARES
// =====================================================

/**
 * Gera um token JWT simples
 */
function generateToken($userId, $username, $role) {
    $header = base64_encode(json_encode(['alg' => 'HS256', 'typ' => 'JWT']));
    $payload = base64_encode(json_encode([
        'user_id' => $userId,
        'username' => $username,
        'role' => $role,
        'exp' => time() + JWT_EXPIRATION,
        'iat' => time()
    ]));
    $signature = base64_encode(hash_hmac('sha256', "$header.$payload", JWT_SECRET, true));
    return "$header.$payload.$signature";
}

/**
 * Verifica e decodifica um token JWT
 */
function verifyToken($token) {
    $parts = explode('.', $token);
    if (count($parts) !== 3) {
        return false;
    }

    list($header, $payload, $signature) = $parts;
    $validSignature = base64_encode(hash_hmac('sha256', "$header.$payload", JWT_SECRET, true));

    if ($signature !== $validSignature) {
        return false;
    }

    $data = json_decode(base64_decode($payload), true);
    
    if ($data['exp'] < time()) {
        return false;
    }

    return $data;
}

/**
 * Obtém o token do header Authorization
 */
function getBearerToken() {
    $headers = getallheaders();
    if (isset($headers['Authorization'])) {
        if (preg_match('/Bearer\s(\S+)/', $headers['Authorization'], $matches)) {
            return $matches[1];
        }
    }
    return null;
}

/**
 * Verifica se o usuário está autenticado
 */
function requireAuth() {
    $token = getBearerToken();
    if (!$token) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Token não fornecido']);
        exit();
    }

    $userData = verifyToken($token);
    if (!$userData) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Token inválido ou expirado']);
        exit();
    }

    return $userData;
}

/**
 * Resposta JSON padronizada
 */
function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode($data);
    exit();
}

/**
 * Obtém dados do corpo da requisição
 */
function getRequestBody() {
    $json = file_get_contents('php://input');
    return json_decode($json, true) ?? [];
}
