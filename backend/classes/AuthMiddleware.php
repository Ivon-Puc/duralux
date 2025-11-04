<?php
/**
 * Middleware de Proteção e Validação de Rotas
 */

class AuthMiddleware {
    
    /**
     * Verificar se requisição precisa de autenticação
     */
    public static function requireAuth($required_role = 'user') {
        if (!isLoggedIn()) {
            http_response_code(401);
            echo json_encode([
                'success' => false,
                'message' => 'Acesso não autorizado - Login necessário',
                'redirect' => '/auth/login'
            ]);
            exit;
        }
        
        if (!hasPermission($required_role)) {
            http_response_code(403);
            echo json_encode([
                'success' => false,
                'message' => 'Acesso negado - Permissão insuficiente',
                'required_role' => $required_role
            ]);
            exit;
        }
        
        return true;
    }
    
    /**
     * Validar token CSRF
     */
    public static function validateCSRF() {
        $token = null;
        
        // Verificar no header
        if (isset($_SERVER['HTTP_X_CSRF_TOKEN'])) {
            $token = $_SERVER['HTTP_X_CSRF_TOKEN'];
        }
        // Verificar no POST
        elseif (isset($_POST['csrf_token'])) {
            $token = $_POST['csrf_token'];
        }
        // Verificar no JSON
        else {
            $input = json_decode(file_get_contents('php://input'), true);
            $token = $input['csrf_token'] ?? null;
        }
        
        if (!$token || !validateCSRFToken($token)) {
            http_response_code(403);
            echo json_encode([
                'success' => false,
                'message' => 'Token CSRF inválido ou ausente'
            ]);
            exit;
        }
        
        return true;
    }
    
    /**
     * Rate limiting básico
     */
    public static function rateLimit($max_requests = 60, $window_minutes = 1) {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $cache_key = 'rate_limit_' . md5($ip);
        
        // Usar sessão como cache simples (em produção, usar Redis/Memcached)
        if (!isset($_SESSION['rate_limits'])) {
            $_SESSION['rate_limits'] = [];
        }
        
        $now = time();
        $window_start = $now - ($window_minutes * 60);
        
        // Limpar requests antigos
        $_SESSION['rate_limits'] = array_filter(
            $_SESSION['rate_limits'], 
            function($timestamp) use ($window_start) {
                return $timestamp > $window_start;
            }
        );
        
        // Verificar limite
        $current_requests = count($_SESSION['rate_limits']);
        
        if ($current_requests >= $max_requests) {
            http_response_code(429);
            echo json_encode([
                'success' => false,
                'message' => 'Muitas requisições. Tente novamente em ' . $window_minutes . ' minuto(s)',
                'retry_after' => $window_minutes * 60
            ]);
            exit;
        }
        
        // Registrar requisição atual
        $_SESSION['rate_limits'][] = $now;
        
        return true;
    }
    
    /**
     * Validar dados de entrada (sanitização extra)
     */
    public static function validateInput($data) {
        if (is_array($data)) {
            array_walk_recursive($data, function(&$value) {
                if (is_string($value)) {
                    // Remover tags HTML perigosas
                    $value = strip_tags($value);
                    
                    // Detectar possíveis payloads maliciosos
                    $malicious_patterns = [
                        '/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/mi',
                        '/javascript:/i',
                        '/on\w+\s*=/i',
                        '/\bselect\b.*\bfrom\b/i',
                        '/\bunion\b.*\bselect\b/i',
                        '/\binsert\b.*\binto\b/i',
                        '/\bupdate\b.*\bset\b/i',
                        '/\bdelete\b.*\bfrom\b/i'
                    ];
                    
                    foreach ($malicious_patterns as $pattern) {
                        if (preg_match($pattern, $value)) {
                            http_response_code(400);
                            echo json_encode([
                                'success' => false,
                                'message' => 'Dados de entrada contêm conteúdo suspeito'
                            ]);
                            exit;
                        }
                    }
                }
            });
        }
        
        return $data;
    }
    
    /**
     * Log de tentativas suspeitas
     */
    public static function logSuspiciousActivity($activity, $details = null) {
        $log_data = [
            'timestamp' => date('Y-m-d H:i:s'),
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'activity' => $activity,
            'details' => $details,
            'user_id' => $_SESSION['user_id'] ?? null
        ];
        
        $log_line = json_encode($log_data) . "\n";
        file_put_contents(__DIR__ . '/../logs/security.log', $log_line, FILE_APPEND | LOCK_EX);
    }
}

/**
 * Aplicar middlewares globais baseado na rota
 */
function applyMiddleware($method, $uri) {
    // Rate limiting para todas as rotas
    AuthMiddleware::rateLimit(100, 1); // 100 req/min
    
    // Rotas que precisam de autenticação
    $protected_routes = [
        '/customers', '/products', '/orders', '/dashboard'
    ];
    
    foreach ($protected_routes as $protected) {
        if (strpos($uri, $protected) === 0) {
            AuthMiddleware::requireAuth();
            
            // CSRF para métodos não-GET
            if (in_array($method, ['POST', 'PUT', 'DELETE'])) {
                AuthMiddleware::validateCSRF();
            }
            break;
        }
    }
    
    // Rotas que precisam de admin
    $admin_routes = ['/dashboard/admin', '/users'];
    
    foreach ($admin_routes as $admin_route) {
        if (strpos($uri, $admin_route) === 0) {
            AuthMiddleware::requireAuth('admin');
            break;
        }
    }
    
    // Validar entrada para todas as rotas
    if (in_array($method, ['POST', 'PUT'])) {
        $input_data = json_decode(file_get_contents('php://input'), true) ?: [];
        $input_data = array_merge($_POST, $input_data);
        AuthMiddleware::validateInput($input_data);
    }
}
?>