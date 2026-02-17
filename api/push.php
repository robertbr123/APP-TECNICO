<?php
/**
 * API de Push Notifications
 * Gerencia subscriptions e envio de notificações push
 */

require_once 'config.php';
require_once 'Logger.php';

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
// GET - Listar subscriptions (admin)
// =====================================================
if ($method === 'GET') {
    $userData = requireAuth();
    if ($userData['role'] !== 'admin') {
        jsonResponse(['success' => false, 'message' => 'Acesso negado'], 403);
    }
    handleList();
}

jsonResponse(['success' => false, 'message' => 'Metodo nao permitido'], 405);

// =====================================================
// Handlers
// =====================================================

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
    $url = $data['url'] ?? '/dashboard.html';
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
            jsonResponse(['success' => false, 'message' => 'Nenhuma subscription encontrada']);
        }

        $payload = json_encode([
            'title' => $title,
            'body' => $body,
            'url' => $url
        ]);

        $sent = 0;
        $failed = 0;
        $expired = [];

        foreach ($subscriptions as $sub) {
            $result = sendPushNotification($sub['endpoint'], $sub['p256dh'], $sub['auth'], $payload);

            if ($result === true) {
                $sent++;
            } elseif ($result === 'expired') {
                $expired[] = $sub['id'];
                $failed++;
            } else {
                $failed++;
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
            'sent' => $sent,
            'failed' => $failed
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

        jsonResponse(['success' => true, 'data' => $subs, 'count' => count($subs)]);

    } catch (PDOException $e) {
        Logger::logException($e, ['context' => 'push list']);
        jsonResponse(['success' => false, 'message' => 'Erro ao listar subscriptions'], 500);
    }
}

// =====================================================
// Envio de Push via Web Push Protocol (sem Composer)
// Implementação completa com encriptação RFC 8291
// =====================================================

function sendPushNotification($endpoint, $p256dh, $auth, $payload) {
    // Monta headers para Web Push com VAPID
    $vapidHeaders = generateVapidHeaders($endpoint);

    if (!$vapidHeaders) {
        Logger::warning("Falha ao gerar VAPID headers");
        return false;
    }

    // Encripta o payload usando as chaves do cliente
    $encrypted = encryptPayload($payload, $p256dh, $auth);
    
    if (!$encrypted) {
        Logger::warning("Falha ao encriptar payload");
        return false;
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
        CURLOPT_TIMEOUT => 10,
        CURLOPT_SSL_VERIFYPEER => true
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($httpCode >= 200 && $httpCode < 300) {
        return true;
    } elseif ($httpCode === 404 || $httpCode === 410) {
        return 'expired'; // Subscription expirada
    }

    Logger::warning("Push failed", [
        'httpCode' => $httpCode, 
        'endpoint' => substr($endpoint, 0, 80),
        'response' => substr($response, 0, 200),
        'curlError' => $curlError
    ]);
    return false;
}

/**
 * Encripta o payload usando RFC 8291 (aes128gcm)
 */
function encryptPayload($payload, $userPublicKey, $userAuthToken) {
    // Decodifica as chaves do cliente
    $userPublicKeyBytes = base64_decode(strtr($userPublicKey, '-_', '+/'));
    $userAuthTokenBytes = base64_decode(strtr($userAuthToken, '-_', '+/'));

    if (strlen($userPublicKeyBytes) !== 65 || strlen($userAuthTokenBytes) !== 16) {
        Logger::warning("Chaves do cliente inválidas", [
            'pubKeyLen' => strlen($userPublicKeyBytes),
            'authLen' => strlen($userAuthTokenBytes)
        ]);
        return null;
    }

    // Gera par de chaves ECDH do servidor
    $serverKey = openssl_pkey_new([
        'curve_name' => 'prime256v1',
        'private_key_type' => OPENSSL_KEYTYPE_EC
    ]);

    if (!$serverKey) {
        Logger::error("Falha ao gerar chave ECDH do servidor");
        return null;
    }

    $serverKeyDetails = openssl_pkey_get_details($serverKey);
    $serverPublicKeyBytes = chr(0x04) . str_pad($serverKeyDetails['ec']['x'], 32, "\x00", STR_PAD_LEFT) 
                                      . str_pad($serverKeyDetails['ec']['y'], 32, "\x00", STR_PAD_LEFT);

    // Extrai coordenadas da chave pública do cliente
    $userX = substr($userPublicKeyBytes, 1, 32);
    $userY = substr($userPublicKeyBytes, 33, 32);

    // Cria chave pública do cliente no formato PEM para ECDH
    $userPubKeyPem = createECPublicKeyPem($userX, $userY);
    if (!$userPubKeyPem) {
        return null;
    }

    // Deriva o shared secret via ECDH
    $sharedSecret = '';
    if (!openssl_pkey_derive($sharedSecret, $serverKey, openssl_pkey_get_public($userPubKeyPem))) {
        Logger::error("Falha no ECDH derive");
        return null;
    }

    // Gera salt aleatório (16 bytes)
    $salt = random_bytes(16);

    // Deriva as chaves usando HKDF (RFC 8291)
    // IKM = ECDH(server_private, user_public)
    // PRK = HKDF-Extract(auth_secret, IKM)
    $ikm = $sharedSecret;
    $prk = hash_hmac('sha256', $ikm, $userAuthTokenBytes, true);

    // info = "WebPush: info\x00" || user_public || server_public
    $keyInfo = "WebPush: info\x00" . $userPublicKeyBytes . $serverPublicKeyBytes;
    $ikm2 = hkdfExpand($prk, $keyInfo, 32);

    // Deriva CEK e nonce
    $prk2 = hash_hmac('sha256', $ikm2, $salt, true);
    
    $cekInfo = "Content-Encoding: aes128gcm\x00";
    $cek = hkdfExpand($prk2, $cekInfo, 16);
    
    $nonceInfo = "Content-Encoding: nonce\x00";
    $nonce = hkdfExpand($prk2, $nonceInfo, 12);

    // Adiciona padding ao payload (1 byte de delimitador + padding)
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
        Logger::error("Falha na encriptação AES-GCM");
        return null;
    }

    // Monta o header do aes128gcm: salt (16) + rs (4) + idlen (1) + keyid (65)
    $rs = pack('N', 4096); // record size
    $idlen = chr(65); // length of public key
    
    $header = $salt . $rs . $idlen . $serverPublicKeyBytes;
    $body = $ciphertext . $tag;

    return [
        'ciphertext' => $header . $body,
        'serverPublicKey' => base64_encode($serverPublicKeyBytes)
    ];
}

/**
 * HKDF-Expand (RFC 5869)
 */
function hkdfExpand($prk, $info, $length) {
    $t = '';
    $output = '';
    $counter = 1;
    
    while (strlen($output) < $length) {
        $t = hash_hmac('sha256', $t . $info . chr($counter), $prk, true);
        $output .= $t;
        $counter++;
    }
    
    return substr($output, 0, $length);
}

/**
 * Cria uma chave pública EC no formato PEM
 */
function createECPublicKeyPem($x, $y) {
    // OID para P-256 (prime256v1/secp256r1)
    $oid = "\x06\x08\x2a\x86\x48\xce\x3d\x03\x01\x07";
    $algo = "\x06\x07\x2a\x86\x48\xce\x3d\x02\x01";
    
    // Public key point (uncompressed)
    $point = "\x04" . $x . $y;
    
    // BitString wrapper
    $bitString = "\x03" . chr(strlen($point) + 1) . "\x00" . $point;
    
    // AlgorithmIdentifier
    $algId = "\x30" . chr(strlen($algo) + strlen($oid)) . $algo . $oid;
    
    // SubjectPublicKeyInfo
    $spki = "\x30" . chr(strlen($algId) + strlen($bitString)) . $algId . $bitString;
    
    $pem = "-----BEGIN PUBLIC KEY-----\n" .
           chunk_split(base64_encode($spki), 64) .
           "-----END PUBLIC KEY-----\n";
    
    return $pem;
}

function generateVapidHeaders($endpoint) {
    $publicKey = VAPID_PUBLIC_KEY;
    $privateKey = VAPID_PRIVATE_KEY;

    if ($publicKey === 'COLE_SUA_CHAVE_PUBLICA_AQUI') {
        return null; // Chaves nao configuradas
    }

    $audience = parse_url($endpoint, PHP_URL_SCHEME) . '://' . parse_url($endpoint, PHP_URL_HOST);

    // JWT Header
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

    // Decode private key
    $privateKeyDer = base64_decode(strtr($privateKey, '-_', '+/'));

    // Cria a chave privada PEM
    $pem = "-----BEGIN EC PRIVATE KEY-----\n" .
        chunk_split(base64_encode(
            "\x30\x77\x02\x01\x01\x04\x20" .
            $privateKeyDer .
            "\xa0\x0a\x06\x08\x2a\x86\x48\xce\x3d\x03\x01\x07\xa1\x44\x03\x42\x00\x04" .
            base64_decode(strtr($publicKey, '-_', '+/'))
        ), 64) .
        "-----END EC PRIVATE KEY-----\n";

    $key = openssl_pkey_get_private($pem);
    if (!$key) {
        Logger::error("Falha ao carregar chave VAPID privada");
        return null;
    }

    openssl_sign($signingInput, $signature, $key, OPENSSL_ALGO_SHA256);

    // Converte DER signature para raw r||s (64 bytes)
    $rawSig = derToRaw($signature);
    $signatureB64 = rtrim(strtr(base64_encode($rawSig), '+/', '-_'), '=');

    return [
        'token' => $signingInput . '.' . $signatureB64,
        'publicKey' => $publicKey
    ];
}

function derToRaw($der) {
    $pos = 0;
    $pos += 2; // Skip SEQUENCE tag + length
    $pos += 1; // INTEGER tag
    $rLen = ord($der[$pos++]);
    $r = substr($der, $pos, $rLen);
    $pos += $rLen;
    $pos += 1; // INTEGER tag
    $sLen = ord($der[$pos++]);
    $s = substr($der, $pos, $sLen);

    // Pad to 32 bytes each
    $r = str_pad(ltrim($r, "\x00"), 32, "\x00", STR_PAD_LEFT);
    $s = str_pad(ltrim($s, "\x00"), 32, "\x00", STR_PAD_LEFT);

    return $r . $s;
}
