<?php
/**
 * Teste de Conexão com o Banco de Dados
 * Acesse: https://app.ondeline.com.br/api/test-connection.php
 */

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");

echo "<pre>";
echo "=== TESTE DE CONEXÃO ===\n\n";

// Teste 1: Verificar se o arquivo config existe
echo "1. Verificando config.php... ";
if (file_exists('config.php')) {
    echo "OK\n";
} else {
    echo "ERRO - Arquivo não encontrado\n";
    exit;
}

// Teste 2: Incluir configurações
echo "2. Carregando configurações... ";
try {
    require_once 'config.php';
    echo "OK\n";
} catch (Exception $e) {
    echo "ERRO: " . $e->getMessage() . "\n";
    exit;
}

// Teste 3: Conectar ao banco
echo "3. Conectando ao banco de dados... ";
try {
    $db = Database::getInstance()->getConnection();
    echo "OK\n";
} catch (Exception $e) {
    echo "ERRO: " . $e->getMessage() . "\n";
    exit;
}

// Teste 4: Verificar tabela clients
echo "4. Verificando tabela 'clients'... ";
try {
    $stmt = $db->query("SHOW TABLES LIKE 'clients'");
    if ($stmt->rowCount() > 0) {
        echo "OK\n";
    } else {
        echo "ERRO - Tabela não existe\n";
    }
} catch (Exception $e) {
    echo "ERRO: " . $e->getMessage() . "\n";
}

// Teste 5: Contar registros
echo "5. Contando registros em 'clients'... ";
try {
    $stmt = $db->query("SELECT COUNT(*) as total FROM clients");
    $result = $stmt->fetch();
    echo $result['total'] . " registros\n";
} catch (Exception $e) {
    echo "ERRO: " . $e->getMessage() . "\n";
}

// Teste 6: Verificar estrutura da tabela
echo "6. Estrutura da tabela 'clients':\n";
try {
    $stmt = $db->query("DESCRIBE clients");
    $columns = $stmt->fetchAll();
    foreach ($columns as $col) {
        echo "   - {$col['Field']} ({$col['Type']})\n";
    }
} catch (Exception $e) {
    echo "   ERRO: " . $e->getMessage() . "\n";
}

// Teste 7: Verificar tabela users
echo "\n7. Verificando tabela 'users'... ";
try {
    $stmt = $db->query("SELECT COUNT(*) as total FROM users");
    $result = $stmt->fetch();
    echo $result['total'] . " usuários\n";
} catch (Exception $e) {
    echo "ERRO: " . $e->getMessage() . "\n";
}

echo "\n=== FIM DOS TESTES ===\n";
echo "</pre>";
