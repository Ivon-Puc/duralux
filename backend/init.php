<?php
/**
 * Arquivo de inicialização do backend
 * Inclui configurações e inicia o banco de dados
 */

// Incluir configurações
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';

// Definir headers de segurança
setSecurityHeaders();

// Permitir CORS para desenvolvimento (remover em produção)
if (DEBUG_MODE) {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, X-CSRF-Token');
    
    // Lidar com requisições OPTIONS (preflight)
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        exit;
    }
}

// Inicializar banco de dados
try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    // Disponibilizar conexão globalmente
    $GLOBALS['db'] = $pdo;
    
} catch (Exception $e) {
    if (DEBUG_MODE) {
        logError("Erro na inicialização do banco: " . $e->getMessage());
        jsonResponse(['error' => 'Erro na conexão com o banco de dados'], 500);
    } else {
        jsonResponse(['error' => 'Erro interno do servidor'], 500);
    }
}

// Função para obter conexão do banco
function getDB() {
    return $GLOBALS['db'];
}

// Função para verificar se usuário está logado
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

// Função para obter usuário atual
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    $db = getDB();
    $stmt = $db->prepare("SELECT id, name, email, role, avatar FROM users WHERE id = ? AND active = 1");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch();
}

// Função para verificar permissões
function hasPermission($required_role = 'user') {
    $user = getCurrentUser();
    if (!$user) {
        return false;
    }
    
    $roles = ['user' => 1, 'admin' => 2];
    $user_level = $roles[$user['role']] ?? 0;
    $required_level = $roles[$required_role] ?? 1;
    
    return $user_level >= $required_level;
}

// Middleware para proteger rotas
function requireAuth($role = 'user') {
    if (!isLoggedIn() || !hasPermission($role)) {
        jsonResponse(['error' => 'Acesso não autorizado'], 401);
    }
}

echo "🚀 Backend Duralux inicializado com sucesso!\n";
echo "📊 Banco de dados SQLite configurado\n";
echo "🔐 Sistema de segurança ativo\n";
echo "💡 Debug mode: " . (DEBUG_MODE ? 'ON' : 'OFF') . "\n";