<?php
/**
 * Proxy para API SGP - Ondeline
 * Consulta status online/offline e dados do cliente via SGP
 */

require_once 'config.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Método não permitido'], 405);
}

$data = getRequestBody();

// =====================================================
// Ação: buscar dados do cliente pelo CPF
// =====================================================
if (isset($data['action']) && $data['action'] === 'buscar_cliente') {
    $cpf = $data['cpf'] ?? null;
    
    if (!$cpf) {
        jsonResponse(['success' => false, 'message' => 'CPF é obrigatório'], 400);
    }
    
    // Limpa o CPF
    $cpf = preg_replace('/\D/', '', $cpf);
    
    $result = callSgpApi('https://ondeline.sgp.tsmx.com.br/api/ura/clientes/', [
        'app' => 'bot',
        'token' => '8e6523a9-2c7e-43de-888b-555da380a8fd',
        'cpfcnpj' => $cpf
    ]);
    
    if ($result === false) {
        jsonResponse(['success' => false, 'message' => 'Erro ao consultar API SGP'], 500);
    }
    
    $decoded = json_decode($result, true);
    
    if (!$decoded || !isset($decoded['clientes']) || empty($decoded['clientes'])) {
        jsonResponse(['success' => false, 'message' => 'Cliente não encontrado no SGP', 'raw' => $decoded]);
    }
    
    jsonResponse(['success' => true, 'data' => $decoded]);
}

// =====================================================
// Ação: verificar status de acesso (online/offline)
// =====================================================
if (isset($data['action']) && $data['action'] === 'verificar_acesso') {
    $contrato = $data['contrato'] ?? null;
    
    if (!$contrato) {
        jsonResponse(['success' => false, 'message' => 'Número do contrato é obrigatório'], 400);
    }
    
    $result = callSgpApi('https://ondeline.sgp.tsmx.com.br/api/ura/verificaacesso/', [
        'app' => 'bot',
        'token' => '8e6523a9-2c7e-43de-888b-555da380a8fd',
        'contrato' => (string)$contrato
    ]);
    
    if ($result === false) {
        jsonResponse(['success' => false, 'message' => 'Erro ao verificar acesso no SGP'], 500);
    }
    
    $decoded = json_decode($result, true);
    
    jsonResponse(['success' => true, 'data' => $decoded]);
}

// =====================================================
// Ação: salvar contrato e MAC no banco de dados
// =====================================================
if (isset($data['action']) && $data['action'] === 'salvar_contrato') {
    $cpf = $data['cpf'] ?? null;
    $contrato = $data['contrato'] ?? null;
    $mac = $data['mac'] ?? null;
    
    if (!$cpf) {
        jsonResponse(['success' => false, 'message' => 'CPF é obrigatório'], 400);
    }
    
    $cpf = preg_replace('/\D/', '', $cpf);
    
    try {
        $db = Database::getInstance()->getConnection();
        
        $updateFields = [];
        $params = [];
        
        if ($contrato !== null) {
            $updateFields[] = "contrato = ?";
            $params[] = $contrato;
        }
        
        if ($mac !== null) {
            $updateFields[] = "serial = ?";
            $params[] = $mac;
        }
        
        if (empty($updateFields)) {
            jsonResponse(['success' => false, 'message' => 'Nenhum dado para atualizar'], 400);
        }
        
        $params[] = $cpf;
        $sql = "UPDATE clients SET " . implode(', ', $updateFields) . " WHERE cpf = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        
        if ($stmt->rowCount() === 0) {
            jsonResponse(['success' => false, 'message' => 'Cliente não encontrado no banco local'], 404);
        }
        
        jsonResponse(['success' => true, 'message' => 'Contrato e MAC salvos com sucesso']);
        
    } catch (PDOException $e) {
        jsonResponse(['success' => false, 'message' => 'Erro no banco de dados', 'error' => $e->getMessage()], 500);
    }
}

jsonResponse(['success' => false, 'message' => 'Ação não especificada. Use: buscar_cliente, verificar_acesso ou salvar_contrato'], 400);

// =====================================================
// Função para chamar a API SGP via cURL
// =====================================================
function callSgpApi($url, $headers) {
    $ch = curl_init();
    
    // Monta os headers HTTP
    $httpHeaders = [];
    foreach ($headers as $key => $value) {
        $httpHeaders[] = "$key: $value";
    }
    
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => $httpHeaders,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_POSTFIELDS => '' // POST sem body, dados vão nos headers
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        error_log("SGP API Error: " . $error);
        return false;
    }
    
    error_log("SGP API Response (HTTP $httpCode): " . substr($response, 0, 500));
    
    return $response;
}
