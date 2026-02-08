<?php
/**
 * Logger - Sistema de Logs Estruturados
 * Ondeline Tech - App do Técnico
 * 
 * Substitui o error_log() espalhado por logs estruturados
 * com níveis, contexto e rotação de arquivos
 */

class Logger {
    // Níveis de log
    const DEBUG = 'DEBUG';
    const INFO = 'INFO';
    const WARNING = 'WARNING';
    const ERROR = 'ERROR';
    const CRITICAL = 'CRITICAL';

    // Configurações
    private static $logDir = __DIR__ . '/../logs';
    private static $maxFileSize = 10 * 1024 * 1024; // 10MB
    private static $maxFiles = 5;
    private static $enabled = true;

    /**
     * Inicializa o diretório de logs
     */
    private static function init() {
        if (!is_dir(self::$logDir)) {
            mkdir(self::$logDir, 0755, true);
        }
    }

    /**
     * Log de debug
     */
    public static function debug($message, $context = []) {
        self::log(self::DEBUG, $message, $context);
    }

    /**
     * Log de info
     */
    public static function info($message, $context = []) {
        self::log(self::INFO, $message, $context);
    }

    /**
     * Log de warning
     */
    public static function warning($message, $context = []) {
        self::log(self::WARNING, $message, $context);
    }

    /**
     * Log de erro
     */
    public static function error($message, $context = []) {
        self::log(self::ERROR, $message, $context);
    }

    /**
     * Log crítico
     */
    public static function critical($message, $context = []) {
        self::log(self::CRITICAL, $message, $context);
    }

    /**
     * Log genérico
     */
    private static function log($level, $message, $context = []) {
        if (!self::$enabled) return;

        self::init();

        $timestamp = date('Y-m-d H:i:s');
        $file = self::getLogFile($level);
        
        // Monta o log
        $logEntry = [
            'timestamp' => $timestamp,
            'level' => $level,
            'message' => $message,
            'context' => $context,
            'request_id' => self::getRequestId(),
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'method' => $_SERVER['REQUEST_METHOD'] ?? 'CLI',
            'uri' => $_SERVER['REQUEST_URI'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ];

        // Adiciona stack trace para erros
        if (in_array($level, [self::ERROR, self::CRITICAL])) {
            $logEntry['trace'] = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5);
        }

        // Converte para JSON para facilitar parsing
        $line = json_encode($logEntry, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";

        // Rotação se necessário
        self::rotateIfNeeded($file);

        // Escreve no arquivo
        file_put_contents($file, $line, FILE_APPEND | LOCK_EX);
    }

    /**
     * Obtém o arquivo de log baseado no nível
     */
    private static function getLogFile($level) {
        $date = date('Y-m-d');
        
        // Erros críticos e erros vão para arquivo separado
        if (in_array($level, [self::ERROR, self::CRITICAL])) {
            return self::$logDir . "/error-{$date}.log";
        }
        
        return self::$logDir . "/app-{$date}.log";
    }

    /**
     * Rotaciona arquivos de log se necessário
     */
    private static function rotateIfNeeded($file) {
        if (!file_exists($file)) return;
        
        if (filesize($file) > self::$maxFileSize) {
            $info = pathinfo($file);
            $base = $info['dirname'] . '/' . $info['filename'];
            $ext = isset($info['extension']) ? '.' . $info['extension'] : '';

            // Remove arquivo mais antigo
            $oldest = $base . '.' . self::$maxFiles . $ext;
            if (file_exists($oldest)) {
                unlink($oldest);
            }

            // Rotaciona arquivos
            for ($i = self::$maxFiles - 1; $i >= 1; $i--) {
                $old = $base . '.' . $i . $ext;
                $new = $base . '.' . ($i + 1) . $ext;
                if (file_exists($old)) {
                    rename($old, $new);
                }
            }

            // Move arquivo atual
            rename($file, $base . '.1' . $ext);
        }
    }

    /**
     * Gera ID único para a requisição
     */
    private static function getRequestId() {
        static $requestId = null;
        
        if ($requestId === null) {
            $requestId = uniqid('req_', true);
        }
        
        return $requestId;
    }

    /**
     * Log de requisição API
     */
    public static function logRequest($endpoint, $method, $data = []) {
        // Remove dados sensíveis
        $sanitized = self::sanitizeData($data);
        
        self::info("API Request: {$method} {$endpoint}", [
            'endpoint' => $endpoint,
            'method' => $method,
            'data' => $sanitized
        ]);
    }

    /**
     * Log de resposta API
     */
    public static function logResponse($endpoint, $statusCode, $data = []) {
        $level = $statusCode >= 400 ? self::ERROR : self::INFO;
        
        self::log($level, "API Response: {$endpoint} - {$statusCode}", [
            'endpoint' => $endpoint,
            'status_code' => $statusCode,
            'response' => $data
        ]);
    }

    /**
     * Log de exceção
     */
    public static function logException(Exception $e, $context = []) {
        self::error($e->getMessage(), [
            'exception' => get_class($e),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString(),
            'context' => $context
        ]);
    }

    /**
     * Log de operação do banco
     */
    public static function logDatabase($operation, $table, $affectedRows = null, $duration = null) {
        self::debug("Database: {$operation} on {$table}", [
            'operation' => $operation,
            'table' => $table,
            'affected_rows' => $affectedRows,
            'duration_ms' => $duration
        ]);
    }

    /**
     * Log de auditoria de segurança
     */
    public static function logSecurity($event, $details = []) {
        self::warning("Security: {$event}", [
            'event' => $event,
            'details' => $details,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ]);
    }

    /**
     * Sanitiza dados removendo informações sensíveis
     */
    private static function sanitizeData($data) {
        $sensitiveFields = ['password', 'senha', 'token', 'secret', 'credit_card', 'cvv'];
        
        if (is_array($data)) {
            $sanitized = [];
            foreach ($data as $key => $value) {
                if (in_array(strtolower($key), $sensitiveFields)) {
                    $sanitized[$key] = '***REDACTED***';
                } elseif (is_array($value)) {
                    $sanitized[$key] = self::sanitizeData($value);
                } else {
                    $sanitized[$key] = $value;
                }
            }
            return $sanitized;
        }
        
        return $data;
    }

    /**
     * Limpa logs antigos
     */
    public static function cleanOldLogs($daysToKeep = 30) {
        self::init();
        
        $now = time();
        $files = glob(self::$logDir . '/*.log*');
        
        foreach ($files as $file) {
            if (is_file($file)) {
                $fileTime = filemtime($file);
                if ($now - $fileTime > ($daysToKeep * 24 * 60 * 60)) {
                    unlink($file);
                }
            }
        }
    }

    /**
     * Busca logs (útil para debug)
     */
    public static function search($level = null, $startDate = null, $endDate = null, $limit = 100) {
        self::init();
        
        $logs = [];
        $files = glob(self::$logDir . '/*.log');
        
        foreach ($files as $file) {
            $handle = fopen($file, 'r');
            if ($handle) {
                while (($line = fgets($handle)) !== false && count($logs) < $limit) {
                    $entry = json_decode($line, true);
                    if ($entry) {
                        if ($level && $entry['level'] !== $level) continue;
                        if ($startDate && $entry['timestamp'] < $startDate) continue;
                        if ($endDate && $entry['timestamp'] > $endDate) continue;
                        
                        $logs[] = $entry;
                    }
                }
                fclose($handle);
            }
        }
        
        return $logs;
    }
}

// Helper functions para facilitar uso
if (!function_exists('log_debug')) {
    function log_debug($message, $context = []) {
        Logger::debug($message, $context);
    }
}

if (!function_exists('log_info')) {
    function log_info($message, $context = []) {
        Logger::info($message, $context);
    }
}

if (!function_exists('log_warning')) {
    function log_warning($message, $context = []) {
        Logger::warning($message, $context);
    }
}

if (!function_exists('log_error')) {
    function log_error($message, $context = []) {
        Logger::error($message, $context);
    }
}

if (!function_exists('log_critical')) {
    function log_critical($message, $context = []) {
        Logger::critical($message, $context);
    }
}
