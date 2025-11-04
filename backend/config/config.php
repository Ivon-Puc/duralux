<?php
/**
 * Configurações Gerais - Duralux CRM
 */

// Configurações da Aplicação
define('APP_NAME', 'Duralux CRM');
define('APP_VERSION', '1.0.0');
define('APP_URL', 'http://localhost');

// Configurações de Sessão
define('SESSION_LIFETIME', 3600); // 1 hora
define('SESSION_NAME', 'DURALUX_SESSION');

// Configurações de Upload
define('UPLOAD_MAX_SIZE', 5242880); // 5MB
define('UPLOAD_ALLOWED_TYPES', ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx']);
define('UPLOAD_PATH', __DIR__ . '/../uploads/');

// Configurações de Segurança
define('CSRF_TOKEN_LENGTH', 32);
define('PASSWORD_MIN_LENGTH', 6);

// Configurações de Email (para recuperação de senha)
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', ''); // Configure seu email
define('SMTP_PASSWORD', ''); // Configure sua senha de app
define('SMTP_FROM_EMAIL', 'noreply@duralux.com');
define('SMTP_FROM_NAME', 'Duralux CRM');

// Configurações de Desenvolvimento
define('DEBUG_MODE', true);
define('LOG_ERRORS', true);

// Headers de Segurança
function setSecurityHeaders() {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('X-XSS-Protection: 1; mode=block');
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
}

// Função para log de erros
function logError($message, $file = '', $line = '') {
    if (LOG_ERRORS) {
        $timestamp = date('Y-m-d H:i:s');
        $log_message = "[$timestamp] $message";
        if ($file && $line) {
            $log_message .= " in $file on line $line";
        }
        error_log($log_message . PHP_EOL, 3, __DIR__ . '/../logs/error.log');
    }
}

// Função para sanitizar dados
function sanitize($data) {
    if (is_array($data)) {
        foreach ($data as $key => $value) {
            $data[$key] = sanitize($value);
        }
    } else {
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    }
    return $data;
}

// Função para gerar token CSRF
function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(CSRF_TOKEN_LENGTH));
    }
    return $_SESSION['csrf_token'];
}

// Função para validar token CSRF
function validateCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Função para resposta JSON
function jsonResponse($data, $status_code = 200) {
    http_response_code($status_code);
    header('Content-Type: application/json');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// Autoloader simples
spl_autoload_register(function($class) {
    $file = __DIR__ . '/../classes/' . $class . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

// Inicializar sessão se não estiver ativa
if (session_status() === PHP_SESSION_NONE) {
    session_name(SESSION_NAME);
    session_start();
    
    // Regenerar ID da sessão periodicamente por segurança
    if (!isset($_SESSION['last_regeneration'])) {
        session_regenerate_id(true);
        $_SESSION['last_regeneration'] = time();
    } elseif (time() - $_SESSION['last_regeneration'] > 1800) { // 30 minutos
        session_regenerate_id(true);
        $_SESSION['last_regeneration'] = time();
    }
}

// Criar diretórios necessários se não existirem
$directories = [
    __DIR__ . '/../logs',
    __DIR__ . '/../uploads',
    __DIR__ . '/../database'
];

foreach ($directories as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
}