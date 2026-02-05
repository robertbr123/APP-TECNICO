<?php
/**
 * Teste de Busca de Clientes
 * Acesse: https://app.ondeline.com.br/api/test-search.php?search=NOME
 */

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Simula autenticação para teste
$_SERVER['HTTP_AUTHORIZATION'] = 'Bearer test';

require_once 'config.php';

$search = $_GET['search'] ?? 'teste';

echo "<pre>";
echo "=== TESTE DE BUSCA DE CLIENTES ===\n\n";

try {
    $db = Database::getInstance()->getConnection();
    echo "1. Conexão com banco: OK\n\n";
    
    // Testa se há clientes
    $stmt = $db->query("SELECT COUNT(*) as total FROM clients");
    $total = $stmt->fetch()['total'];
    echo "2. Total de clientes na tabela: $total\n\n";
    
    if ($total == 0) {
        echo "AVISO: Não há clientes cadastrados!\n";
        echo "</pre>";
        exit;
    }
    
    // Lista alguns clientes
    echo "3. Primeiros 5 clientes:\n";
    $stmt = $db->query("SELECT cpf, name, city, serial FROM clients LIMIT 5");
    $clients = $stmt->fetchAll();
    foreach ($clients as $c) {
        echo "   - {$c['name']} (CPF: {$c['cpf']}) - {$c['city']}\n";
    }
    
    // Testa busca
    echo "\n4. Testando busca por '$search':\n";
    $searchTerm = "%$search%";
    $stmt = $db->prepare("SELECT cpf, name, city, serial FROM clients WHERE name LIKE ? OR cpf LIKE ? LIMIT 10");
    $stmt->execute([$searchTerm, $searchTerm]);
    $results = $stmt->fetchAll();
    
    if (count($results) > 0) {
        foreach ($results as $r) {
            echo "   - {$r['name']} (CPF: {$r['cpf']})\n";
        }
    } else {
        echo "   Nenhum resultado encontrado\n";
    }
    
    // Retorna JSON também
    echo "\n\n5. Resposta JSON que a API deveria retornar:\n";
    echo json_encode([
        'success' => true,
        'data' => $results,
        'total' => count($results)
    ], JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    echo "ERRO: " . $e->getMessage() . "\n";
}

echo "\n</pre>";
