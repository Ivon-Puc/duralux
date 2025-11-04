<?php
/**
 * Teste Simples da API
 */

// Debug PHP errors
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json; charset=utf-8');

try {
    // Teste básico
    $response = [
        'success' => true,
        'message' => 'API funcionando!',
        'server_time' => date('Y-m-d H:i:s'),
        'php_version' => phpversion(),
        'extensions' => [
            'pdo' => extension_loaded('pdo'),
            'pdo_sqlite' => extension_loaded('pdo_sqlite'),
            'pdo_mysql' => extension_loaded('pdo_mysql')
        ]
    ];
    
    // Testa SQLite
    try {
        $db_path = __DIR__ . '/../database/duralux.db';
        $db_dir = dirname($db_path);
        
        if (!is_dir($db_dir)) {
            mkdir($db_dir, 0777, true);
            $response['db_dir_created'] = true;
        }
        
        $pdo = new PDO('sqlite:' . $db_path);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Teste de query simples
        $stmt = $pdo->query("SELECT 'Hello SQLite' as test");
        $result = $stmt->fetch();
        
        $response['database'] = [
            'status' => 'connected',
            'type' => 'sqlite',
            'path' => $db_path,
            'test_query' => $result['test']
        ];
        
        // Cria tabela de teste se não existir
        $pdo->exec("CREATE TABLE IF NOT EXISTS test_table (id INTEGER PRIMARY KEY, name TEXT)");
        $pdo->exec("INSERT OR IGNORE INTO test_table (id, name) VALUES (1, 'Test Data')");
        
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM test_table");
        $count = $stmt->fetch();
        $response['database']['test_records'] = $count['count'];
        
    } catch (Exception $e) {
        $response['database'] = [
            'status' => 'error',
            'message' => $e->getMessage()
        ];
    }
    
    echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ], JSON_PRETTY_PRINT);
}
?>