<?php
/**
 * API de Importação de Clientes via Planilha Excel/CSV
 * Ondeline Tech - App do Técnico
 *
 * POST: Importa um array de clientes enviado como JSON
 *  - Apenas admin
 *  - Pula CPFs já cadastrados (informa motivo)
 *  - Retorna resumo: total, importados, não-importados com motivo
 */

require_once 'config.php';
require_once 'Logger.php';
require_once 'Validator.php';

$method = $_SERVER['REQUEST_METHOD'];
$userData = requireAuth();

// Apenas admin
if ($userData['role'] !== 'admin') {
    jsonResponse(['success' => false, 'message' => 'Apenas administradores podem importar clientes'], 403);
}

if ($method !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Método não permitido'], 405);
}

try {
    $db = Database::getInstance()->getConnection();

    $json = file_get_contents('php://input');
    $body = json_decode($json, true);

    if (!$body || !isset($body['clientes']) || !is_array($body['clientes'])) {
        jsonResponse(['success' => false, 'message' => 'Dados inválidos. Envie { "clientes": [...] }'], 400);
    }

    $clientes  = $body['clientes'];
    $total     = count($clientes);
    $importados = 0;
    $ignorados  = [];

    // Statement reutilizável para inserção
    $insertStmt = $db->prepare("
        INSERT INTO clients (
            cpf, name, phone, birthDate, state, city, neighborhood,
            address, number, complement, planId, pppoe, password,
            dueDay, observation, latitude, longitude, location_accuracy,
            installer, status, active, created_at
        ) VALUES (
            ?, ?, ?, ?, ?, ?, ?,
            ?, ?, ?, ?, ?, ?,
            ?, ?, NULL, NULL, NULL,
            ?, ?, ?, NOW()
        )
    ");

    // Statement para verificar duplicata
    $checkStmt = $db->prepare("SELECT cpf FROM clients WHERE cpf = ?");

    foreach ($clientes as $index => $c) {
        $linhaNum  = $index + 2; // linha do Excel (cabeçalho = linha 1)
        $nomeLabel = !empty($c['nome']) ? trim($c['nome']) : "Linha $linhaNum";

        // ── 1. CPF obrigatório ───────────────────────────────────────
        if (empty($c['cpf'])) {
            $ignorados[] = ['nome' => $nomeLabel, 'cpf' => '-', 'motivo' => 'CPF não informado'];
            continue;
        }

        $cpfValidation = Validator::validateCPF((string)$c['cpf']);
        if (!$cpfValidation['valid']) {
            $ignorados[] = [
                'nome'  => $nomeLabel,
                'cpf'   => $c['cpf'],
                'motivo' => 'CPF inválido: ' . $cpfValidation['message']
            ];
            continue;
        }
        $cpf = $cpfValidation['clean'];

        // ── 2. Nome obrigatório ─────────────────────────────────────
        if (empty($c['nome'])) {
            $ignorados[] = ['nome' => "Linha $linhaNum", 'cpf' => Validator::formatCPF($cpf), 'motivo' => 'Nome não informado'];
            continue;
        }

        // ── 3. Verificar duplicata ──────────────────────────────────
        $checkStmt->execute([$cpf]);
        if ($checkStmt->fetch()) {
            $ignorados[] = [
                'nome'  => $nomeLabel,
                'cpf'   => Validator::formatCPF($cpf),
                'motivo' => 'CPF já cadastrado no sistema'
            ];
            continue;
        }

        // ── 4. Preparar campos ──────────────────────────────────────
        $name  = Validator::sanitizeString($c['nome'], ['maxLength' => 150]);
        $phone = isset($c['telefones']) ? trim($c['telefones']) : '';

        // Data de nascimento — suporta DD/MM/YYYY, YYYY-MM-DD e serial do Excel
        $birthDate = null;
        if (!empty($c['dataNasc'])) {
            $ds = trim((string)$c['dataNasc']);
            if (is_numeric($ds)) {
                // Serial de data do Excel (dias desde 30/12/1899)
                $ts = ($ds - 25569) * 86400;
                $birthDate = date('Y-m-d', $ts);
            } elseif (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $ds, $m)) {
                $birthDate = sprintf('%04d-%02d-%02d', $m[3], $m[2], $m[1]);
            } elseif (preg_match('/^\d{4}-\d{2}-\d{2}$/', $ds)) {
                $birthDate = $ds;
            }
            // Valida que a data é real
            if ($birthDate && !checkdate(
                (int)substr($birthDate, 5, 2),
                (int)substr($birthDate, 8, 2),
                (int)substr($birthDate, 0, 4)
            )) {
                $birthDate = null;
            }
        }

        // Endereço — tenta separar logradouro e número
        $addressFull = !empty($c['endereco']) ? trim($c['endereco']) : 'Não informado';
        $address     = $addressFull;
        $number      = 'S/N';
        // Padrão: "Rua das Flores, 123" ou "Av. Santos 456"
        if (preg_match('/^(.+?)[\s,]+(\d+[A-Za-z]?)\s*$/', $addressFull, $m)) {
            $address = trim($m[1]);
            $number  = $m[2];
        }

        $planId    = !empty($c['planoid'])    ? (int)$c['planoid']    : 6;
        $dueDay    = !empty($c['vencimento']) ? (int)$c['vencimento'] : 10;
        $pppoe     = !empty($c['login'])  ? trim($c['login'])  : ($cpf . '@ondeline');
        $password  = !empty($c['senha'])  ? trim($c['senha'])  : '123';
        $state     = !empty($c['uf'])     ? strtoupper(trim($c['uf'])) : '';
        $city      = !empty($c['cidade']) ? trim($c['cidade']) : '';
        $neighborhood = !empty($c['bairro'])      ? trim($c['bairro'])      : '';
        $complement   = !empty($c['complemento']) ? trim($c['complemento']) : '';
        $cep          = !empty($c['cep'])          ? preg_replace('/\D/', '', $c['cep']) : '';

        // ── 5. Inserir ──────────────────────────────────────────────
        try {
            $insertStmt->execute([
                $cpf, $name, $phone, $birthDate, $state, $city, $neighborhood,
                $address, $number, $complement, $planId, $pppoe, $password,
                $dueDay, '',
                $userData['username'], 'ativo', 1
            ]);

            $importados++;

            // Auditoria (opcional, não bloqueia)
            try {
                $auditStmt = $db->prepare("
                    INSERT INTO audit_logs
                        (user_id, username, action_type, action_description,
                         entity_type, entity_id, entity_name, details, ip_address, user_agent)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $auditStmt->execute([
                    $userData['user_id'] ?? null,
                    $userData['username'] ?? 'admin',
                    'client_created',
                    'Cliente importado via planilha',
                    'client', $cpf, $name,
                    json_encode(['planId' => $planId, 'city' => $city, 'via' => 'import']),
                    $_SERVER['REMOTE_ADDR']      ?? null,
                    $_SERVER['HTTP_USER_AGENT']  ?? null
                ]);
            } catch (Exception $e) {
                // Ignora falha na auditoria
            }

        } catch (PDOException $e) {
            $ignorados[] = [
                'nome'  => $name,
                'cpf'   => Validator::formatCPF($cpf),
                'motivo' => 'Erro ao salvar: ' . $e->getMessage()
            ];
        }
    }

    Logger::info('Importação de clientes concluída', [
        'total'      => $total,
        'importados' => $importados,
        'ignorados'  => count($ignorados),
        'admin'      => $userData['username']
    ]);

    jsonResponse([
        'success'   => true,
        'message'   => 'Importação concluída',
        'resultado' => [
            'total'         => $total,
            'importados'    => $importados,
            'nao_importados' => count($ignorados),
            'detalhes'      => $ignorados
        ]
    ]);

} catch (PDOException $e) {
    Logger::logException($e, ['context' => 'import-clients']);
    jsonResponse(['success' => false, 'message' => 'Erro no banco de dados'], 500);
} catch (Exception $e) {
    Logger::logException($e);
    jsonResponse(['success' => false, 'message' => 'Erro interno do servidor'], 500);
}
