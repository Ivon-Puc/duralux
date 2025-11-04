<?php
/**
 * Configuração do Banco de Dados SQLite
 * Para MVP - Duralux CRM
 */

class Database {
    private $pdo;
    private $db_path;
    
    public function __construct() {
        $this->db_path = __DIR__ . '/../database/duralux.db';
        $this->connect();
        $this->createTables();
    }
    
    private function connect() {
        try {
            // Criar diretório do banco se não existir
            $db_dir = dirname($this->db_path);
            if (!is_dir($db_dir)) {
                mkdir($db_dir, 0777, true);
            }
            
            $this->pdo = new PDO('sqlite:' . $this->db_path);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            
            echo "✅ Conexão SQLite estabelecida com sucesso!\n";
        } catch(PDOException $e) {
            die("❌ Erro na conexão: " . $e->getMessage());
        }
    }
    
    public function getConnection() {
        return $this->pdo;
    }
    
    private function createTables() {
        $sql_users = "CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name VARCHAR(100) NOT NULL,
            email VARCHAR(150) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            role VARCHAR(20) DEFAULT 'user',
            avatar VARCHAR(255),
            active INTEGER DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )";
        
        $sql_customers = "CREATE TABLE IF NOT EXISTS customers (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name VARCHAR(100) NOT NULL,
            email VARCHAR(150),
            phone VARCHAR(20),
            address TEXT,
            city VARCHAR(100),
            state VARCHAR(50),
            zipcode VARCHAR(10),
            notes TEXT,
            active INTEGER DEFAULT 1,
            user_id INTEGER,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id)
        )";
        
        $sql_products = "CREATE TABLE IF NOT EXISTS products (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name VARCHAR(150) NOT NULL,
            description TEXT,
            price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            category VARCHAR(100),
            image VARCHAR(255),
            stock INTEGER DEFAULT 0,
            sku VARCHAR(50),
            active INTEGER DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )";
        
        $sql_orders = "CREATE TABLE IF NOT EXISTS orders (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            customer_id INTEGER NOT NULL,
            total DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            status VARCHAR(20) DEFAULT 'pending',
            notes TEXT,
            user_id INTEGER,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (customer_id) REFERENCES customers(id),
            FOREIGN KEY (user_id) REFERENCES users(id)
        )";
        
        $sql_order_items = "CREATE TABLE IF NOT EXISTS order_items (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            order_id INTEGER NOT NULL,
            product_id INTEGER NOT NULL,
            quantity INTEGER NOT NULL DEFAULT 1,
            unit_price DECIMAL(10,2) NOT NULL,
            total DECIMAL(10,2) NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
            FOREIGN KEY (product_id) REFERENCES products(id)
        )";
        
        try {
            $this->pdo->exec($sql_users);
            $this->pdo->exec($sql_customers);
            $this->pdo->exec($sql_products);
            $this->pdo->exec($sql_orders);
            $this->pdo->exec($sql_order_items);
            
            echo "✅ Tabelas criadas/verificadas com sucesso!\n";
            
            // Inserir usuário admin padrão se não existir
            $this->createDefaultAdmin();
            
        } catch(PDOException $e) {
            die("❌ Erro ao criar tabelas: " . $e->getMessage());
        }
    }
    
    private function createDefaultAdmin() {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM users WHERE role = 'admin'");
        $stmt->execute();
        
        if ($stmt->fetchColumn() == 0) {
            $password = password_hash('admin123', PASSWORD_DEFAULT);
            $stmt = $this->pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
            $stmt->execute(['Administrador', 'admin@duralux.com', $password, 'admin']);
            echo "✅ Usuário admin criado: admin@duralux.com / admin123\n";
        }
    }
}