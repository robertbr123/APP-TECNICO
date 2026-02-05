<?php
/**
 * Teste de Conexão e API
 * Acesse: https://seudominio.com/api/test.php
 */

require_once 'config.php';

echo "<h1>🔧 Teste da API - Ondeline Tech</h1>";
echo "<style>body{font-family:Arial,sans-serif;padding:20px;max-width:800px;margin:0 auto;}
.success{color:green;}.error{color:red;}.info{color:blue;}
pre{background:#f5f5f5;padding:15px;border-radius:8px;overflow:auto;}</style>";

// Teste 1: Conexão com banco
echo "<h2>1. Teste de Conexão com Banco</h2>";
try {
    $db = Database::getInstance()->getConnection();
    echo "<p class='success'>✅ Conexão OK - Banco: " . DB_NAME . "</p>";
} catch (Exception $e) {
    echo "<p class='error'>❌ Erro: " . $e->getMessage() . "</p>";
    exit;
}

// Teste 2: Verifica tabela users
echo "<h2>2. Tabela Users</h2>";
try {
    $stmt = $db->query("SELECT * FROM users LIMIT 5");
    $users = $stmt->fetchAll();
    echo "<p class='success'>✅ Tabela users existe - " . count($users) . " usuário(s)</p>";
    echo "<pre>" . print_r($users, true) . "</pre>";
} catch (Exception $e) {
    echo "<p class='error'>❌ Erro: " . $e->getMessage() . "</p>";
}

// Teste 3: Verifica tabela clients
echo "<h2>3. Tabela Clients</h2>";
try {
    $stmt = $db->query("DESCRIBE clients");
    $columns = $stmt->fetchAll();
    echo "<p class='success'>✅ Estrutura da tabela clients:</p>";
    echo "<pre>";
    foreach ($columns as $col) {
        echo $col['Field'] . " - " . $col['Type'] . "\n";
    }
    echo "</pre>";
    
    $stmt = $db->query("SELECT COUNT(*) as total FROM clients");
    $count = $stmt->fetch()['total'];
    echo "<p class='info'>📊 Total de clientes: $count</p>";
} catch (Exception $e) {
    echo "<p class='error'>❌ Erro: " . $e->getMessage() . "</p>";
}

// Teste 4: Verifica tabela client_photos
echo "<h2>4. Tabela Client_Photos</h2>";
try {
    $stmt = $db->query("SHOW TABLES LIKE 'client_photos'");
    if ($stmt->rowCount() > 0) {
        echo "<p class='success'>✅ Tabela client_photos existe</p>";
    } else {
        echo "<p class='info'>ℹ️ Tabela client_photos não existe (será criada automaticamente)</p>";
    }
} catch (Exception $e) {
    echo "<p class='error'>❌ Erro: " . $e->getMessage() . "</p>";
}

// Teste 5: Verifica tabela plans
echo "<h2>5. Tabela Plans</h2>";
try {
    $stmt = $db->query("SELECT * FROM plans");
    $plans = $stmt->fetchAll();
    echo "<p class='success'>✅ " . count($plans) . " plano(s) cadastrado(s)</p>";
    if (count($plans) > 0) {
        echo "<pre>" . print_r($plans, true) . "</pre>";
    }
} catch (Exception $e) {
    echo "<p class='error'>❌ Erro: " . $e->getMessage() . "</p>";
}

// Teste 6: Verifica tabela installers
echo "<h2>6. Tabela Installers</h2>";
try {
    $stmt = $db->query("SELECT * FROM installers");
    $installers = $stmt->fetchAll();
    echo "<p class='success'>✅ " . count($installers) . " instalador(es) cadastrado(s)</p>";
    if (count($installers) > 0) {
        echo "<pre>" . print_r($installers, true) . "</pre>";
    }
} catch (Exception $e) {
    echo "<p class='error'>❌ Erro: " . $e->getMessage() . "</p>";
}

// Teste 7: Verifica pasta uploads
echo "<h2>7. Pasta de Uploads</h2>";
$uploadDir = __DIR__ . '/../uploads/';
if (file_exists($uploadDir)) {
    if (is_writable($uploadDir)) {
        echo "<p class='success'>✅ Pasta uploads existe e tem permissão de escrita</p>";
    } else {
        echo "<p class='error'>❌ Pasta uploads existe mas NÃO tem permissão de escrita</p>";
        echo "<p class='info'>Execute: chmod 755 uploads/</p>";
    }
} else {
    echo "<p class='error'>❌ Pasta uploads NÃO existe</p>";
    echo "<p class='info'>Criando pasta...</p>";
    if (mkdir($uploadDir, 0755, true)) {
        echo "<p class='success'>✅ Pasta criada com sucesso!</p>";
    }
}

// Teste 8: Teste de inserção (opcional)
echo "<h2>8. Teste de Inserção (Cliente de Teste)</h2>";
echo "<p class='info'>⚠️ Para testar inserção, adicione ?insert=1 na URL</p>";

if (isset($_GET['insert'])) {
    try {
        $testCpf = '00000000000';
        
        // Verifica se já existe
        $stmt = $db->prepare("SELECT cpf FROM clients WHERE cpf = ?");
        $stmt->execute([$testCpf]);
        
        if ($stmt->fetch()) {
            echo "<p class='info'>ℹ️ Cliente de teste já existe. Removendo...</p>";
            $db->prepare("DELETE FROM clients WHERE cpf = ?")->execute([$testCpf]);
        }
        
        $stmt = $db->prepare("
            INSERT INTO clients (cpf, name, dob, phone, address, number, complement, city, plan, due_date, installer, observation, registration_date)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $testCpf,
            'Cliente de Teste',
            '1990-01-01',
            '11999999999',
            'Rua Teste',
            '123',
            'Ap 1',
            'São Paulo - SP',
            'Fibra 100MB',
            10,
            'admin',
            'Cliente de teste inserido via API',
            date('Y-m-d')
        ]);
        
        echo "<p class='success'>✅ Cliente de teste inserido com sucesso!</p>";
        
        // Verifica
        $stmt = $db->prepare("SELECT * FROM clients WHERE cpf = ?");
        $stmt->execute([$testCpf]);
        echo "<pre>" . print_r($stmt->fetch(), true) . "</pre>";
        
    } catch (Exception $e) {
        echo "<p class='error'>❌ Erro: " . $e->getMessage() . "</p>";
    }
}

echo "<hr><p>✅ Teste concluído!</p>";
