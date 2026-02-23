<?php
/**
 * API de Push Notifications
 * Gerencia subscriptions e envio de notificações push
 */

require_once 'config.php';
require_once 'Logger.php';

// Handler de erros para capturar erros fatais
set_error_handler(function($severity, $message, $file, $line) {
    Logger::error("PHP Error", [
        'severity' => $severity,
        'message' => $message,
        'file' => $file,
        'line' => $line
    ]);
    // Converte erros em exceções
    throw new ErrorException($message, 0, $severity, $file, $line);
});

// Handler de exceções não capturadas
set_exception_handler(function($e) {
    Logger::error("Uncaught Exception", [
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
    jsonResponse(['success' => false, 'message' => 'Erro interno do servidor'], 500);
});

$method = $_SERVER['REQUEST_METHOD'];

// =====================================================
// POST - Subscribe ou Send
// =====================================================
if ($method === 'POST') {
    $userData = requireAuth();
    $data = getRequestBody();
    $action = $data['action'] ?? '';

    if ($action === 'subscribe') {
        handleSubscribe($userData, $data);
    } elseif ($action === 'send') {
        if ($userData['role'] !== 'admin') {
            jsonResponse(['success' => false, 'message' => 'Apenas admins podem enviar notificacoes'], 403);
        }
        handleSend($data);
    } else {
        jsonResponse(['success' => false, 'message' => 'Acao invalida. Use: subscribe ou send'], 400);
    }
}

// =====================================================
// DELETE - Unsubscribe
// =====================================================
if ($method === 'DELETE') {
    $userData = requireAuth();
    handleUnsubscribe($userData);
}

// =====================================================
// GET - Listar subscriptions (admin) ou Test (debug)
// =====================================================
if ($method === 'GET') {
    $userData = requireAuth();
    if ($userData['role'] !== 'admin') {
        jsonResponse(['success' => false, 'message' => 'Acesso negado'], 403);
    }
    
    // Endpoint de debug: /api/push.php?test=1
    if (isset($_GET['test'])) {
        handleTest();
    }
    
    handleList();
}

jsonResponse(['success' => false, 'message' => 'Metodo nao permitido'], 405);

// =====================================================
// Handlers
// =====================================================

/**
 * Testa as configurações de push
 */
function handleTest() {
    $tests = [
        'php_version' => PHP_VERSION,
        'openssl_loaded' => extension_loaded('openssl'),
        'curl_loaded' => extension_loaded('curl'),
        'openssl_pkey_new' => function_exists('openssl_pkey_new'),
        'openssl_pkey_derive' => function_exists('openssl_pkey_derive'),
        'aes_128_gcm' => in_array('aes-128-gcm', openssl_get_cipher_methods()),
        'vapid_public_key_len' => strlen(VAPID_PUBLIC_KEY),
        'vapid_private_key_len' => strlen(VAPID_PRIVATE_KEY),
    ];
    
    // Testa decodificação das chaves
    $pubKeyBytes = base64UrlDecode(VAPID_PUBLIC_KEY);
    $privKeyBytes = base64UrlDecode(VAPID_PRIVATE_KEY);
    
    $tests['vapid_public_key_decoded_len'] = strlen($pubKeyBytes);
    $tests['vapid_private_key_decoded_len'] = strlen($privKeyBytes);
    $tests['vapid_public_key_starts_with_04'] = ord($pubKeyBytes[0]) === 0x04;
    
    // Testa criação de chave EC
    $serverKey = @openssl_pkey_new([
        'curve_name' => 'prime256v1',
        'private_key_type' => OPENSSL_KEYTYPE_EC
    ]);
    $tests['ec_key_generation'] = $serverKey !== false;
    
    if ($serverKey) {
        $details = openssl_pkey_get_details($serverKey);
        $tests['ec_key_has_x'] = isset($details['ec']['x']);
        $tests['ec_key_has_y'] = isset($details['ec']['y']);
    }
    
    // Testa criação do PEM da chave privada VAPID
    // A chave pública já vem com 0x04 no início (formato uncompressed)
    $privateKeyDer = 
        "\x30\x77" .
        "\x02\x01\x01" .
        "\x04\x20" . $privKeyBytes .
        "\xa0\x0a" .
        "\x06\x08\x2a\x86\x48\xce\x3d\x03\x01\x07" .
        "\xa1\x44" .
        "\x03\x42\x00" .
        $pubKeyBytes; // Já inclui o 0x04
        
    $pem = "-----BEGIN EC PRIVATE KEY-----\n" .
           chunk_split(base64_encode($privateKeyDer), 64, "\n") .
           "-----END EC PRIVATE KEY-----";
    
    $vapidKey = @openssl_pkey_get_private($pem);
    $tests['vapid_key_load'] = $vapidKey !== false;
    
    if (!$vapidKey) {
        $tests['vapid_key_error'] = openssl_error_string();
    }
    
    jsonResponse([
        'success' => true,
        'message' => 'Testes executados com sucesso',
        'data' => [
            'tests' => $tests,
            'all_passed' => !in_array(false, $tests, true)
        ]
    ]);
}

function handleSubscribe($userData, $data) {
    $subscription = $data['subscription'] ?? null;

    if (!$subscription || empty($subscription['endpoint']) || empty($subscription['keys'])) {
        jsonResponse(['success' => false, 'message' => 'Subscription invalida'], 400);
    }

    try {
        $db = Database::getInstance()->getConnection();

        // Cria tabela se nao existir
        $db->exec("
            CREATE TABLE IF NOT EXISTS `push_subscriptions` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `user_id` int(11) NOT NULL,
                `endpoint` text NOT NULL,
                `p256dh` varchar(255) NOT NULL,
                `auth` varchar(255) NOT NULL,
                `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                KEY `idx_user_id` (`user_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        // Remove subscription antiga do mesmo endpoint
        $stmt = $db->prepare("DELETE FROM push_subscriptions WHERE user_id = ? AND endpoint = ?");
        $stmt->execute([$userData['user_id'], $subscription['endpoint']]);

        // Insere nova subscription
        $stmt = $db->prepare("INSERT INTO push_subscriptions (user_id, endpoint, p256dh, auth) VALUES (?, ?, ?, ?)");
        $stmt->execute([
            $userData['user_id'],
            $subscription['endpoint'],
            $subscription['keys']['p256dh'],
            $subscription['keys']['auth']
        ]);

        Logger::info("Push subscription registrada", ['user_id' => $userData['user_id']]);
        jsonResponse(['success' => true, 'message' => 'Subscription registrada']);

    } catch (PDOException $e) {
        Logger::logException($e, ['context' => 'push subscribe']);
        jsonResponse(['success' => false, 'message' => 'Erro ao salvar subscription'], 500);
    }
}

function handleUnsubscribe($userData) {
    try {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("DELETE FROM push_subscriptions WHERE user_id = ?");
        $stmt->execute([$userData['user_id']]);

        Logger::info("Push subscription removida", ['user_id' => $userData['user_id']]);
        jsonResponse(['success' => true, 'message' => 'Subscription removida']);

    } catch (PDOException $e) {
        Logger::logException($e, ['context' => 'push unsubscribe']);
        jsonResponse(['success' => false, 'message' => 'Erro ao remover subscription'], 500);
    }
}

function handleSend($data) {
    $title = $data['title'] ?? 'Ondeline Tech';
    $body = $data['body'] ?? 'Nova notificacao';
    $url = $data['url'] ?? '/dashboard.php';
    $targetUserId = $data['user_id'] ?? null; // null = enviar para todos

    try {
        $db = Database::getInstance()->getConnection();

        // Busca subscriptions
        if ($targetUserId) {
            $stmt = $db->prepare("SELECT * FROM push_subscriptions WHERE user_id = ?");
            $stmt->execute([$targetUserId]);
        } else {
            $stmt = $db->query("SELECT * FROM push_subscriptions");
        }
        $subscriptions = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($subscriptions)) {
            jsonResponse(['success' => false, 'message' => 'Nenhuma subscription encontrada'], 404);
        }

        $payload = json_encode([
            'title' => $title,
            'body' => $body,
            'url' => $url
        ], JSON_UNESCAPED_UNICODE);

        $sent = 0;
        $failed = 0;
        $expired = [];
        $errors = [];

        foreach ($subscriptions as $sub) {
            try {
                $result = sendPushNotification($sub['endpoint'], $sub['p256dh'], $sub['auth'], $payload);

                if ($result === true) {
                    $sent++;
                } elseif ($result === 'expired') {
                    $expired[] = $sub['id'];
                    $failed++;
                } else {
                    $failed++;
                }
            } catch (Exception $e) {
                $failed++;
                $errors[] = $e->getMessage();
                Logger::error("Erro ao enviar push", ['error' => $e->getMessage(), 'sub_id' => $sub['id']]);
            }
        }

        // Remove subscriptions expiradas
        if (!empty($expired)) {
            $placeholders = implode(',', array_fill(0, count($expired), '?'));
            $db->prepare("DELETE FROM push_subscriptions WHERE id IN ($placeholders)")->execute($expired);
        }

        Logger::info("Push notifications enviadas", ['sent' => $sent, 'failed' => $failed, 'expired' => count($expired)]);
        jsonResponse([
            'success' => true,
            'message' => "$sent enviadas, $failed falharam",
            'data' => [
                'sent' => $sent,
                'failed' => $failed
            ]
        ]);

    } catch (Exception $e) {
        Logger::logException($e, ['context' => 'push send']);
        jsonResponse(['success' => false, 'message' => 'Erro ao enviar notificacoes'], 500);
    }
}

function handleList() {
    try {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->query("
            SELECT ps.id, ps.user_id, u.username, u.full_name, ps.created_at
            FROM push_subscriptions ps
            JOIN users u ON u.id = ps.user_id
            ORDER BY ps.created_at DESC
        ");
        $subs = $stmt->fetchAll(PDO::FETCH_ASSOC);

        jsonResponse(['success' => true, 'message' => 'Dados carregados com sucesso', 'data' => $subs, 'count' => count($subs)]);

    } catch (PDOException $e) {
        Logger::logException($e, ['context' => 'push list']);
        jsonResponse(['success' => false, 'message' => 'Erro ao listar subscriptions'], 500);
    }
}

// =====================================================
// Envio de Push via Web Push Protocol
// Implementação RFC 8291 com aes128gcm
// =====================================================

function sendPushNotification($endpoint, $p256dh, $auth, $payload) {
    try {
        // Log para debug
        Logger::info("Iniciando envio push", [
            'endpoint' => substr($endpoint, 0, 60) . '...',
            'p256dh_len' => strlen($p256dh),
            'auth_len' => strlen($auth)
        ]);

        // Monta headers para Web Push com VAPID
        $vapidHeaders = generateVapidHeaders($endpoint);

        if (!$vapidHeaders) {
            Logger::warning("Falha ao gerar VAPID headers");
            return false;
        }

        // Encripta o payload usando as chaves do cliente
        $encrypted = encryptPayload($payload, $p256dh, $auth);
        
        if (!$encrypted) {
            Logger::warning("Falha ao encriptar payload - tentando envio simplificado");
            // Fallback: tenta envio sem payload (notificação vazia que o SW pode preencher)
            return sendEmptyPush($endpoint, $vapidHeaders);
        }

        $ch = curl_init($endpoint);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $encrypted['ciphertext'],
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/octet-stream',
                'Content-Encoding: aes128gcm',
                'Content-Length: ' . strlen($encrypted['ciphertext']),
                'TTL: 86400',
                'Urgency: normal',
                'Authorization: vapid t=' . $vapidHeaders['token'] . ', k=' . $vapidHeaders['publicKey'],
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        Logger::info("Resposta do push", [
            'httpCode' => $httpCode,
            'response' => substr($response, 0, 100),
            'curlError' => $curlError
        ]);

        if ($httpCode >= 200 && $httpCode < 300) {
            return true;
        } elseif ($httpCode === 404 || $httpCode === 410) {
            return 'expired'; // Subscription expirada
        }

        return false;
        
    } catch (Exception $e) {
        Logger::error("Exceção em sendPushNotification", ['error' => $e->getMessage()]);
        return false;
    } catch (Error $e) {
        Logger::error("Erro fatal em sendPushNotification", ['error' => $e->getMessage()]);
        return false;
    }
}

/**
 * Envia push sem payload (fallback)
 */
function sendEmptyPush($endpoint, $vapidHeaders) {
    $ch = curl_init($endpoint);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => '',
        CURLOPT_HTTPHEADER => [
            'Content-Length: 0',
            'TTL: 86400',
            'Urgency: normal',
            'Authorization: vapid t=' . $vapidHeaders['token'] . ', k=' . $vapidHeaders['publicKey'],
        ],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_SSL_VERIFYPEER => false
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    Logger::info("Resposta push vazio", ['httpCode' => $httpCode]);

    return ($httpCode >= 200 && $httpCode < 300);
}

/**
 * Encripta o payload usando RFC 8291 (aes128gcm)
 */
function encryptPayload($payload, $userPublicKey, $userAuthToken) {
    // Verifica se as funções necessárias existem
    if (!function_exists('openssl_pkey_new') || !function_exists('openssl_pkey_derive')) {
        Logger::error("OpenSSL functions not available");
        return null;
    }

    // Decodifica as chaves do cliente (base64url -> bytes)
    $userPublicKeyBytes = base64UrlDecode($userPublicKey);
    $userAuthTokenBytes = base64UrlDecode($userAuthToken);

    Logger::info("Chaves decodificadas", [
        'pubKeyLen' => strlen($userPublicKeyBytes),
        'authLen' => strlen($userAuthTokenBytes)
    ]);

    // Valida tamanhos
    if (strlen($userPublicKeyBytes) !== 65) {
        Logger::warning("Chave pública inválida", ['len' => strlen($userPublicKeyBytes)]);
        return null;
    }
    if (strlen($userAuthTokenBytes) !== 16) {
        Logger::warning("Auth token inválido", ['len' => strlen($userAuthTokenBytes)]);
        return null;
    }

    // Gera par de chaves ECDH do servidor
    $serverKey = @openssl_pkey_new([
        'curve_name' => 'prime256v1',
        'private_key_type' => OPENSSL_KEYTYPE_EC
    ]);

    if (!$serverKey) {
        Logger::error("Falha ao gerar chave ECDH: " . openssl_error_string());
        return null;
    }

    $serverKeyDetails = openssl_pkey_get_details($serverKey);
    if (!isset($serverKeyDetails['ec']['x']) || !isset($serverKeyDetails['ec']['y'])) {
        Logger::error("Detalhes da chave EC não disponíveis");
        return null;
    }

    // Monta chave pública do servidor (formato uncompressed: 0x04 || x || y)
    $serverX = str_pad($serverKeyDetails['ec']['x'], 32, "\x00", STR_PAD_LEFT);
    $serverY = str_pad($serverKeyDetails['ec']['y'], 32, "\x00", STR_PAD_LEFT);
    $serverPublicKeyBytes = "\x04" . $serverX . $serverY;

    // Extrai coordenadas da chave pública do cliente
    $userX = substr($userPublicKeyBytes, 1, 32);
    $userY = substr($userPublicKeyBytes, 33, 32);

    // Cria chave pública do cliente no formato PEM
    $userPubKeyPem = createECPublicKeyPem($userX, $userY);
    if (!$userPubKeyPem) {
        Logger::error("Falha ao criar PEM da chave do cliente");
        return null;
    }

    $userPubKeyResource = @openssl_pkey_get_public($userPubKeyPem);
    if (!$userPubKeyResource) {
        Logger::error("Falha ao carregar chave pública: " . openssl_error_string());
        return null;
    }

    // Deriva o shared secret via ECDH
    $sharedSecret = '';
    $deriveResult = @openssl_pkey_derive($sharedSecret, $serverKey, $userPubKeyResource);
    if (!$deriveResult || empty($sharedSecret)) {
        Logger::error("Falha no ECDH derive: " . openssl_error_string());
        return null;
    }

    Logger::info("ECDH shared secret derivado", ['len' => strlen($sharedSecret)]);

    // Gera salt aleatório (16 bytes)
    $salt = random_bytes(16);

    // === HKDF conforme RFC 8291 ===
    
    // Passo 1: PRK = HKDF-Extract(auth_secret, shared_secret)
    $prk = hash_hmac('sha256', $sharedSecret, $userAuthTokenBytes, true);

    // Passo 2: IKM = HKDF-Expand(PRK, "WebPush: info\x00" || ua_public || as_public, 32)
    $keyInfo = "WebPush: info\x00" . $userPublicKeyBytes . $serverPublicKeyBytes;
    $ikm = hkdfExpand($prk, $keyInfo, 32);

    // Passo 3: PRK2 = HKDF-Extract(salt, IKM)
    $prk2 = hash_hmac('sha256', $ikm, $salt, true);

    // Passo 4: CEK = HKDF-Expand(PRK2, "Content-Encoding: aes128gcm\x00", 16)
    $cek = hkdfExpand($prk2, "Content-Encoding: aes128gcm\x00", 16);

    // Passo 5: Nonce = HKDF-Expand(PRK2, "Content-Encoding: nonce\x00", 12)
    $nonce = hkdfExpand($prk2, "Content-Encoding: nonce\x00", 12);

    // Adiciona padding delimiter ao payload
    $paddedPayload = $payload . "\x02";

    // Encripta com AES-128-GCM
    $tag = '';
    $ciphertext = openssl_encrypt(
        $paddedPayload,
        'aes-128-gcm',
        $cek,
        OPENSSL_RAW_DATA,
        $nonce,
        $tag,
        '',
        16
    );

    if ($ciphertext === false) {
        Logger::error("Falha na encriptação AES-GCM: " . openssl_error_string());
        return null;
    }

    // Monta o header aes128gcm: salt(16) + rs(4) + idlen(1) + keyid(65)
    $rs = pack('N', 4096); // record size
    $idlen = chr(65); // length of public key (65 bytes)
    
    $header = $salt . $rs . $idlen . $serverPublicKeyBytes;
    $body = $ciphertext . $tag;

    Logger::info("Payload encriptado com sucesso", [
        'headerLen' => strlen($header),
        'bodyLen' => strlen($body)
    ]);

    return [
        'ciphertext' => $header . $body,
        'serverPublicKey' => base64_encode($serverPublicKeyBytes)
    ];
}

/**
 * Decodifica base64url para bytes
 */
function base64UrlDecode($input) {
    $remainder = strlen($input) % 4;
    if ($remainder) {
        $input .= str_repeat('=', 4 - $remainder);
    }
    return base64_decode(strtr($input, '-_', '+/'));
}

/**
 * HKDF-Expand (RFC 5869)
 */
function hkdfExpand($prk, $info, $length) {
    $hash = '';
    $output = '';
    $counter = 1;
    
    while (strlen($output) < $length) {
        $hash = hash_hmac('sha256', $hash . $info . chr($counter), $prk, true);
        $output .= $hash;
        $counter++;
    }
    
    return substr($output, 0, $length);
}

/**
 * Cria uma chave pública EC P-256 no formato PEM
 */
function createECPublicKeyPem($x, $y) {
    // Estrutura ASN.1 para SubjectPublicKeyInfo com EC P-256
    // SEQUENCE {
    //   SEQUENCE {
    //     OID ecPublicKey (1.2.840.10045.2.1)
    //     OID prime256v1 (1.2.840.10045.3.1.7)
    //   }
    //   BIT STRING (public key point)
    // }
    
    $ecPublicKeyOid = "\x06\x07\x2a\x86\x48\xce\x3d\x02\x01"; // 1.2.840.10045.2.1
    $prime256v1Oid = "\x06\x08\x2a\x86\x48\xce\x3d\x03\x01\x07"; // 1.2.840.10045.3.1.7
    
    // AlgorithmIdentifier
    $algIdContent = $ecPublicKeyOid . $prime256v1Oid;
    $algId = "\x30" . chr(strlen($algIdContent)) . $algIdContent;
    
    // Public key point (uncompressed: 0x04 || x || y)
    $point = "\x04" . $x . $y;
    
    // BIT STRING (unused bits = 0)
    $bitString = "\x03" . chr(strlen($point) + 1) . "\x00" . $point;
    
    // SubjectPublicKeyInfo
    $spkiContent = $algId . $bitString;
    $spki = "\x30" . chr(strlen($spkiContent)) . $spkiContent;
    
    $pem = "-----BEGIN PUBLIC KEY-----\n" .
           chunk_split(base64_encode($spki), 64, "\n") .
           "-----END PUBLIC KEY-----";
    
    return $pem;
}

function generateVapidHeaders($endpoint) {
    $publicKey = VAPID_PUBLIC_KEY;
    $privateKey = VAPID_PRIVATE_KEY;

    if ($publicKey === 'COLE_SUA_CHAVE_PUBLICA_AQUI') {
        Logger::warning("Chaves VAPID não configuradas");
        return null;
    }

    $audience = parse_url($endpoint, PHP_URL_SCHEME) . '://' . parse_url($endpoint, PHP_URL_HOST);

    // JWT Header (ES256 = ECDSA com SHA-256)
    $header = json_encode(['typ' => 'JWT', 'alg' => 'ES256']);

    // JWT Payload
    $payload = json_encode([
        'aud' => $audience,
        'exp' => time() + 86400,
        'sub' => VAPID_SUBJECT
    ]);

    $headerB64 = rtrim(strtr(base64_encode($header), '+/', '-_'), '=');
    $payloadB64 = rtrim(strtr(base64_encode($payload), '+/', '-_'), '=');
    $signingInput = $headerB64 . '.' . $payloadB64;

    // Decodifica chave privada (base64url)
    $privateKeyBytes = base64UrlDecode($privateKey);
    $publicKeyBytes = base64UrlDecode($publicKey);

    Logger::info("VAPID keys", [
        'privateKeyLen' => strlen($privateKeyBytes),
        'publicKeyLen' => strlen($publicKeyBytes),
        'publicKeyStartsWith04' => ord($publicKeyBytes[0]) === 0x04
    ]);

    // Valida tamanhos
    if (strlen($privateKeyBytes) !== 32) {
        Logger::error("Chave privada VAPID deve ter 32 bytes", ['len' => strlen($privateKeyBytes)]);
        return null;
    }
    if (strlen($publicKeyBytes) !== 65) {
        Logger::error("Chave pública VAPID deve ter 65 bytes", ['len' => strlen($publicKeyBytes)]);
        return null;
    }

    // Cria a chave privada EC no formato PEM (SEC1/RFC 5915)
    // EC PRIVATE KEY structure:
    // SEQUENCE {
    //   INTEGER 1 (version)
    //   OCTET STRING (private key - 32 bytes)
    //   [0] OID prime256v1
    //   [1] BIT STRING (public key - 65 bytes)
    // }
    
    $privateKeyDer = 
        "\x30\x77" .                                          // SEQUENCE, 119 bytes
        "\x02\x01\x01" .                                      // INTEGER 1 (version)
        "\x04\x20" . $privateKeyBytes .                       // OCTET STRING, 32 bytes (private key)
        "\xa0\x0a" .                                          // [0] context tag, 10 bytes
        "\x06\x08\x2a\x86\x48\xce\x3d\x03\x01\x07" .          // OID prime256v1
        "\xa1\x44" .                                          // [1] context tag, 68 bytes
        "\x03\x42\x00" .                                      // BIT STRING, 66 bytes, 0 unused bits
        $publicKeyBytes;                                      // Public key já inclui 0x04

    $pem = "-----BEGIN EC PRIVATE KEY-----\n" .
           chunk_split(base64_encode($privateKeyDer), 64, "\n") .
           "-----END EC PRIVATE KEY-----";

    $key = @openssl_pkey_get_private($pem);
    if (!$key) {
        Logger::error("Falha ao carregar chave VAPID privada: " . openssl_error_string());
        return null;
    }

    // Assina com ECDSA SHA-256 (ES256)
    $signature = '';
    $signResult = @openssl_sign($signingInput, $signature, $key, OPENSSL_ALGO_SHA256);
    
    if (!$signResult || empty($signature)) {
        Logger::error("Falha ao assinar JWT VAPID: " . openssl_error_string());
        return null;
    }

    // Converte DER signature para raw r||s (64 bytes) - formato JWS
    $rawSig = derSignatureToRaw($signature);
    if (!$rawSig) {
        Logger::error("Falha ao converter assinatura DER");
        return null;
    }
    
    $signatureB64 = rtrim(strtr(base64_encode($rawSig), '+/', '-_'), '=');

    Logger::info("VAPID token gerado com sucesso");

    return [
        'token' => $signingInput . '.' . $signatureB64,
        'publicKey' => $publicKey
    ];
}

/**
 * Converte assinatura ECDSA de DER para formato raw (r||s)
 * Formato DER: SEQUENCE { INTEGER r, INTEGER s }
 */
function derSignatureToRaw($der) {
    if (strlen($der) < 8) return null;
    
    $pos = 0;
    
    // SEQUENCE tag (0x30)
    if (ord($der[$pos++]) !== 0x30) return null;
    
    // Length
    $seqLen = ord($der[$pos++]);
    if ($seqLen & 0x80) {
        // Long form length (não esperado para assinaturas EC)
        $numLenBytes = $seqLen & 0x7f;
        $pos += $numLenBytes;
    }
    
    // First INTEGER (r)
    if (ord($der[$pos++]) !== 0x02) return null;
    $rLen = ord($der[$pos++]);
    $r = substr($der, $pos, $rLen);
    $pos += $rLen;
    
    // Second INTEGER (s)
    if (ord($der[$pos++]) !== 0x02) return null;
    $sLen = ord($der[$pos++]);
    $s = substr($der, $pos, $sLen);
    
    // Remove leading zeros e pad para 32 bytes
    $r = ltrim($r, "\x00");
    $s = ltrim($s, "\x00");
    $r = str_pad($r, 32, "\x00", STR_PAD_LEFT);
    $s = str_pad($s, 32, "\x00", STR_PAD_LEFT);
    
    return $r . $s;
}
