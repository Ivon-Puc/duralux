<?php
/**
 * Configuração de Banco de Dados MySQL para APIs
 * Duralux CRM
 */

// Configurações MySQL para produção
define('DB_HOST', 'localhost');
define('DB_NAME', 'duralux_crm');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_PORT', 3306);
define('DB_CHARSET', 'utf8mb4');

// Configurações SQLite para desenvolvimento/teste
define('SQLITE_DB_PATH', __DIR__ . '/../database/duralux.db');

/**
 * Função para obter conexão PDO
 * Tenta MySQL primeiro, depois SQLite
 */
function getDatabaseConnection() {
    try {
        // Tenta conexão MySQL primeiro
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET
        ]);
        
        return $pdo;
        
    } catch (PDOException $e) {
        // Se MySQL falhar, usa SQLite
        try {
            // Cria diretório se não existir
            $db_dir = dirname(SQLITE_DB_PATH);
            if (!is_dir($db_dir)) {
                mkdir($db_dir, 0777, true);
            }
            
            $pdo = new PDO('sqlite:' . SQLITE_DB_PATH);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            
            // Cria tabelas básicas se necessário
            createBasicTables($pdo);
            
            return $pdo;
            
        } catch (PDOException $sqlite_e) {
            throw new Exception("Erro ao conectar com MySQL e SQLite: MySQL=" . $e->getMessage() . ", SQLite=" . $sqlite_e->getMessage());
        }
    }
}

/**
 * Cria tabelas básicas para SQLite
 */
function createBasicTables($pdo) {
    $tables = [
        'leads' => "CREATE TABLE IF NOT EXISTS leads (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name VARCHAR(100) NOT NULL,
            email VARCHAR(150),
            phone VARCHAR(20),
            company VARCHAR(100),
            status VARCHAR(20) DEFAULT 'novo',
            source VARCHAR(50) DEFAULT 'website',
            value DECIMAL(10,2) DEFAULT 0.00,
            notes TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )",
        
        'projects' => "CREATE TABLE IF NOT EXISTS projects (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name VARCHAR(200) NOT NULL,
            description TEXT,
            status VARCHAR(20) DEFAULT 'em_andamento',
            budget DECIMAL(10,2) DEFAULT 0.00,
            start_date DATE,
            prazo_entrega DATE,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )",
        
        'customers' => "CREATE TABLE IF NOT EXISTS customers (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name VARCHAR(100) NOT NULL,
            email VARCHAR(150),
            phone VARCHAR(20),
            company VARCHAR(100),
            active INTEGER DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )",
        
        'vendas' => "CREATE TABLE IF NOT EXISTS vendas (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            customer_id INTEGER,
            valor DECIMAL(10,2) NOT NULL,
            status VARCHAR(20) DEFAULT 'fechada',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (customer_id) REFERENCES customers(id)
        )"
    ];
    
    foreach ($tables as $table_name => $sql) {
        try {
            $pdo->exec($sql);
        } catch (PDOException $e) {
            error_log("Erro ao criar tabela $table_name: " . $e->getMessage());
        }
    }
    
    // Insere dados de exemplo se as tabelas estiverem vazias
    insertSampleData($pdo);
}

/**
 * Insere dados de exemplo para demonstração
 */
function insertSampleData($pdo) {
    try {
        // Verifica se já existem dados
        $stmt = $pdo->query("SELECT COUNT(*) FROM leads");
        if ($stmt->fetchColumn() == 0) {
            
            // Dados de exemplo para leads
            $sample_leads = [
                ['João Silva', 'joao@email.com', '(11) 99999-1111', 'Tech Corp', 'novo', 'website', 5000],
                ['Maria Santos', 'maria@email.com', '(11) 99999-2222', 'Marketing Plus', 'qualificado', 'indicacao', 8500],
                ['Pedro Costa', 'pedro@email.com', '(11) 99999-3333', 'Design Studio', 'convertido', 'google_ads', 12000],
                ['Ana Oliveira', 'ana@email.com', '(11) 99999-4444', 'Consultoria ABC', 'novo', 'facebook', 3200],
                ['Carlos Lima', 'carlos@email.com', '(11) 99999-5555', 'Startup XYZ', 'qualificado', 'website', 15000]
            ];
            
            $stmt = $pdo->prepare("INSERT INTO leads (name, email, phone, company, status, source, value) VALUES (?, ?, ?, ?, ?, ?, ?)");
            foreach ($sample_leads as $lead) {
                $stmt->execute($lead);
            }
            
            // Dados de exemplo para projetos
            $sample_projects = [
                ['Website Corporativo', 'Desenvolvimento de site institucional', 'em_andamento', 25000, '2024-10-01', '2024-12-15'],
                ['Sistema CRM', 'Implementação de sistema CRM personalizado', 'em_andamento', 45000, '2024-09-15', '2024-11-30'],
                ['E-commerce', 'Loja virtual completa', 'concluido', 35000, '2024-08-01', '2024-10-30'],
                ['App Mobile', 'Aplicativo para iOS e Android', 'em_andamento', 65000, '2024-10-15', '2025-01-30']
            ];
            
            $stmt = $pdo->prepare("INSERT INTO projects (name, description, status, budget, start_date, prazo_entrega) VALUES (?, ?, ?, ?, ?, ?)");
            foreach ($sample_projects as $project) {
                $stmt->execute($project);
            }
            
            // Dados de exemplo para clientes
            $sample_customers = [
                ['Tech Corp LTDA', 'contato@techcorp.com', '(11) 3333-1111', 'Tech Corp'],
                ['Marketing Plus S/A', 'vendas@marketingplus.com', '(11) 3333-2222', 'Marketing Plus'],
                ['Design Studio ME', 'projeto@designstudio.com', '(11) 3333-3333', 'Design Studio'],
                ['Consultoria ABC', 'info@consultoriaabc.com', '(11) 3333-4444', 'Consultoria ABC']
            ];
            
            $stmt = $pdo->prepare("INSERT INTO customers (name, email, phone, company) VALUES (?, ?, ?, ?)");
            foreach ($sample_customers as $customer) {
                $stmt->execute($customer);
            }
            
            // Dados de exemplo para vendas
            $sample_sales = [
                [1, 25000, 'fechada'],
                [2, 45000, 'fechada'],
                [3, 35000, 'fechada'],
                [1, 15000, 'fechada'],
                [2, 22000, 'fechada']
            ];
            
            $stmt = $pdo->prepare("INSERT INTO vendas (customer_id, valor, status) VALUES (?, ?, ?)");
            foreach ($sample_sales as $sale) {
                $stmt->execute($sale);
            }
        }
        
    } catch (PDOException $e) {
        error_log("Erro ao inserir dados de exemplo: " . $e->getMessage());
    }
}

/**
 * Função utilitária para teste de conexão
 */
function testDatabaseConnection() {
    try {
        $pdo = getDatabaseConnection();
        return [
            'success' => true,
            'message' => 'Conexão estabelecida com sucesso',
            'driver' => $pdo->getAttribute(PDO::ATTR_DRIVER_NAME)
        ];
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => $e->getMessage()
        ];
    }
}
?>