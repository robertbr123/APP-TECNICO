<?php
/**
 * Configurações de Integração com o Sistema TR069 (TR06)
 * 
 * Este arquivo contém as configurações para conectar o APP-TECNICO
 * com o sistema TR069 ACS para gerenciamento remoto de equipamentos.
 */

// =====================================================
// CONFIGURAÇÕES DO TR069 ACS - ALTERE AQUI
// =====================================================

// URL base do servidor TR069 (TR06)
// Exemplo: http://192.168.1.100:7557 ou https://tr069.seuprovedor.com.br
define('TR069_API_URL', 'http://localhost:7557/api/v1');

// Chave de API para autenticação no TR069
// IMPORTANTE: Use a mesma chave que está no arquivo .env do TR06
define('TR069_API_KEY', 'dgdkgjnd975348957039ferng59684');

// Timeout em segundos para requisições ao TR069
define('TR069_TIMEOUT', 30);

// =====================================================
// FUNÇÕES DE COMUNICAÇÃO COM O TR069
// =====================================================

/**
 * Faz uma requisição GET para a API do TR069
 */
function tr069_get($endpoint, $params = []) {
    $url = TR069_API_URL . $endpoint;
    
    if (!empty($params)) {
        $url .= '?' . http_build_query($params);
    }
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => TR069_TIMEOUT,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . TR069_API_KEY,
            'Content-Type: application/json',
            'Accept: application/json'
        ],
        CURLOPT_SSL_VERIFYPEER => false, // Desabilitar em desenvolvimento, habilitar em produção
        CURLOPT_SSL_VERIFYHOST => false
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        return [
            'success' => false,
            'error' => 'Erro de conexão com TR069',
            'message' => $error,
            'http_code' => 0
        ];
    }
    
    $data = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        return [
            'success' => false,
            'error' => 'Resposta inválida do TR069',
            'message' => 'Não foi possível decodificar a resposta JSON',
            'http_code' => $httpCode,
            'raw_response' => $response
        ];
    }
    
    $data['http_code'] = $httpCode;
    return $data;
}

/**
 * Faz uma requisição POST para a API do TR069
 */
function tr069_post($endpoint, $data = []) {
    $url = TR069_API_URL . $endpoint;
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => TR069_TIMEOUT,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . TR069_API_KEY,
            'Content-Type: application/json',
            'Accept: application/json'
        ],
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        return [
            'success' => false,
            'error' => 'Erro de conexão com TR069',
            'message' => $error,
            'http_code' => 0
        ];
    }
    
    $result = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        return [
            'success' => false,
            'error' => 'Resposta inválida do TR069',
            'message' => 'Não foi possível decodificar a resposta JSON',
            'http_code' => $httpCode,
            'raw_response' => $response
        ];
    }
    
    $result['http_code'] = $httpCode;
    return $result;
}

/**
 * Verifica se o serviço TR069 está disponível
 */
function tr069_health_check() {
    return tr069_get('/health');
}

/**
 * Busca dispositivo pelo usuário PPPoE
 */
function tr069_get_device_by_pppoe($pppoe_username) {
    // URL encode do username
    $pppoe_encoded = urlencode($pppoe_username);
    return tr069_get("/device/by-pppoe/{$pppoe_encoded}");
}

/**
 * Busca configurações WiFi de um dispositivo
 */
function tr069_get_wifi($pppoe_username) {
    return tr069_post('/wifi/get', [
        'pppoe_username' => $pppoe_username
    ]);
}

/**
 * Altera configurações WiFi de um dispositivo
 */
function tr069_change_wifi($pppoe_username, $ssid = null, $password = null, $security_mode = null, $encryption = null) {
    $data = [
        'pppoe_username' => $pppoe_username
    ];
    
    if ($ssid !== null) {
        $data['ssid'] = $ssid;
    }
    if ($password !== null) {
        $data['password'] = $password;
    }
    if ($security_mode !== null) {
        $data['security_mode'] = $security_mode;
    }
    if ($encryption !== null) {
        $data['encryption'] = $encryption;
    }
    
    return tr069_post('/wifi/change', $data);
}

/**
 * Lista dispositivos (opcional: filtrar por status online)
 */
function tr069_list_devices($online_only = false, $limit = 100) {
    $params = ['limit' => $limit];
    if ($online_only) {
        $params['online_only'] = 'true';
    }
    return tr069_get('/devices', $params);
}
