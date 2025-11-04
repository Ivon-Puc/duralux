<?php
/**
 * Teste do Banco de Dados e Inserção de Dados de Exemplo
 * Execute este arquivo para verificar se o banco está funcionando
 */

require_once __DIR__ . '/init.php';

echo "<h2>🔍 Testando Sistema Duralux</h2>";

try {
    $db = getDB();
    
    // Testar conexão
    echo "<h3>✅ Conexão com SQLite estabelecida</h3>";
    
    // Verificar tabelas
    $tables = ['users', 'customers', 'products', 'orders', 'order_items'];
    echo "<h3>📋 Verificando Tabelas:</h3><ul>";
    
    foreach ($tables as $table) {
        $stmt = $db->prepare("SELECT name FROM sqlite_master WHERE type='table' AND name=?");
        $stmt->execute([$table]);
        if ($stmt->fetch()) {
            echo "<li>✅ Tabela '$table' existe</li>";
        } else {
            echo "<li>❌ Tabela '$table' não encontrada</li>";
        }
    }
    echo "</ul>";
    
    // Verificar usuário admin
    echo "<h3>👤 Verificando Usuário Admin:</h3>";
    $stmt = $db->prepare("SELECT id, name, email, role FROM users WHERE role = 'admin' LIMIT 1");
    $stmt->execute();
    $admin = $stmt->fetch();
    
    if ($admin) {
        echo "<p>✅ Admin encontrado: <strong>{$admin['name']}</strong> ({$admin['email']})</p>";
        echo "<p>🔑 <strong>Login:</strong> admin@duralux.com</p>";
        echo "<p>🔐 <strong>Senha:</strong> admin123</p>";
    } else {
        echo "<p>❌ Usuário admin não encontrado</p>";
    }
    
    // Inserir dados de exemplo
    insertSampleData($db);
    
    // Estatísticas
    showStats($db);
    
} catch (Exception $e) {
    echo "<p style='color:red'>❌ Erro: " . htmlspecialchars($e->getMessage()) . "</p>";
}

function insertSampleData($db) {
    echo "<h3>📊 Inserindo Dados de Exemplo:</h3>";
    
    // Clientes de exemplo
    $customers_data = [
        ['João Silva', 'joao@email.com', '(11) 99999-1111', 'Rua A, 123', 'São Paulo'],
        ['Maria Santos', 'maria@email.com', '(11) 99999-2222', 'Rua B, 456', 'Belo Horizonte'],
        ['Pedro Costa', 'pedro@email.com', '(21) 99999-3333', 'Rua C, 789', 'Rio de Janeiro'],
    ];
    
    foreach ($customers_data as $customer) {
        $stmt = $db->prepare("SELECT COUNT(*) FROM customers WHERE email = ?");
        $stmt->execute([$customer[1]]);
        
        if ($stmt->fetchColumn() == 0) {
            $stmt = $db->prepare("INSERT INTO customers (name, email, phone, address, city) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute($customer);
            echo "<p>✅ Cliente adicionado: {$customer[0]}</p>";
        }
    }
    
    // Produtos de exemplo
    $products_data = [
        ['Produto A', 'Descrição do produto A', 99.90, 'Categoria 1', 100],
        ['Serviço B', 'Descrição do serviço B', 149.50, 'Serviços', 0],
        ['Produto C', 'Descrição do produto C', 79.90, 'Categoria 2', 50],
    ];
    
    foreach ($products_data as $product) {
        $stmt = $db->prepare("SELECT COUNT(*) FROM products WHERE name = ?");
        $stmt->execute([$product[0]]);
        
        if ($stmt->fetchColumn() == 0) {
            $stmt = $db->prepare("INSERT INTO products (name, description, price, category, stock) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute($product);
            echo "<p>✅ Produto adicionado: {$product[0]} - R$ " . number_format($product[2], 2, ',', '.') . "</p>";
        }
    }
}

function showStats($db) {
    echo "<h3>📈 Estatísticas do Sistema:</h3>";
    
    // Total de usuários
    $stmt = $db->query("SELECT COUNT(*) as total FROM users");
    $users = $stmt->fetch()['total'];
    
    // Total de clientes
    $stmt = $db->query("SELECT COUNT(*) as total FROM customers");
    $customers = $stmt->fetch()['total'];
    
    // Total de produtos
    $stmt = $db->query("SELECT COUNT(*) as total FROM products");
    $products = $stmt->fetch()['total'];
    
    // Total de pedidos
    $stmt = $db->query("SELECT COUNT(*) as total FROM orders");
    $orders = $stmt->fetch()['total'];
    
    echo "<div style='display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin: 20px 0;'>";
    echo "<div style='background: #e3f2fd; padding: 15px; border-radius: 8px; text-align: center;'>";
    echo "<h4>👥 Usuários</h4><h2>$users</h2></div>";
    
    echo "<div style='background: #f3e5f5; padding: 15px; border-radius: 8px; text-align: center;'>";
    echo "<h4>🤝 Clientes</h4><h2>$customers</h2></div>";
    
    echo "<div style='background: #e8f5e8; padding: 15px; border-radius: 8px; text-align: center;'>";
    echo "<h4>📦 Produtos</h4><h2>$products</h2></div>";
    
    echo "<div style='background: #fff3e0; padding: 15px; border-radius: 8px; text-align: center;'>";
    echo "<h4>🛒 Pedidos</h4><h2>$orders</h2></div>";
    echo "</div>";
    
    // Lista de clientes
    if ($customers > 0) {
        echo "<h3>👥 Clientes Cadastrados:</h3>";
        $stmt = $db->query("SELECT name, email, city, created_at FROM customers ORDER BY created_at DESC LIMIT 5");
        echo "<table border='1' style='width: 100%; border-collapse: collapse;'>";
        echo "<tr style='background: #f5f5f5;'><th>Nome</th><th>Email</th><th>Cidade</th><th>Cadastrado em</th></tr>";
        
        while ($row = $stmt->fetch()) {
            echo "<tr>";
            echo "<td>{$row['name']}</td>";
            echo "<td>{$row['email']}</td>";
            echo "<td>{$row['city']}</td>";
            echo "<td>" . date('d/m/Y H:i', strtotime($row['created_at'])) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // Lista de produtos
    if ($products > 0) {
        echo "<h3>📦 Produtos Cadastrados:</h3>";
        $stmt = $db->query("SELECT name, price, category, stock FROM products ORDER BY created_at DESC LIMIT 5");
        echo "<table border='1' style='width: 100%; border-collapse: collapse;'>";
        echo "<tr style='background: #f5f5f5;'><th>Produto</th><th>Preço</th><th>Categoria</th><th>Estoque</th></tr>";
        
        while ($row = $stmt->fetch()) {
            echo "<tr>";
            echo "<td>{$row['name']}</td>";
            echo "<td>R$ " . number_format($row['price'], 2, ',', '.') . "</td>";
            echo "<td>{$row['category']}</td>";
            echo "<td>{$row['stock']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
}

echo "<hr>";
echo "<h3>🎯 Próximos Passos:</h3>";
echo "<ol>";
echo "<li>✅ Banco de dados SQLite configurado</li>";
echo "<li>🔄 Implementar sistema de login</li>";
echo "<li>🔄 Conectar frontend ao backend</li>";
echo "<li>🔄 Implementar CRUD de clientes</li>";
echo "<li>🔄 Implementar CRUD de produtos</li>";
echo "</ol>";

echo "<p><strong>🌐 Acesso ao sistema:</strong> <a href='../duralux-admin/index.html'>Ir para Dashboard</a></p>";
?>