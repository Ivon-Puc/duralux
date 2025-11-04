<?php
/**
 * DURALUX CRM - Enhanced Auth Middleware v3.0
 * Middleware avançado para autenticação e controle de acesso
 * 
 * Features:
 * - Verificação JWT automática
 * - Sistema de roles granular
 * - Rate limiting por usuário
 * - Audit trail completo
 * - Detecção de atividade suspeita
 * - IP whitelisting/blacklisting
 * - Session timeout automático
 * 
 * @author Duralux Development Team
 * @version 3.0.0
 */

require_once 'JWTAuthManager.php';

class EnhancedAuthMiddleware {
    
    private $authManager;
    private $config;
    
    public function __construct() {
        $this->authManager = new JWTAuthManager();
        
        $this->config = [
            'session_timeout' => 7200, // 2 horas
            'max_concurrent_sessions' => 5,
            'detect_suspicious_activity' => true,
            'ip_tracking' => true,
            'device_tracking' => true,
            'geo_blocking' => false,
            'rate_limiting' => [
                'enabled' => true,
                'requests_per_minute' => 60,
                'requests_per_hour' => 1000
            ]
        ];
    }
    
    /**
     * Middleware principal de autenticação
     */
    public static function handle($requiredPermission = null, $options = []) {
        $middleware = new self();
        return $middleware->process($requiredPermission, $options);
    }
    
    /**
     * Processar middleware de autenticação
     */
    public function process($requiredPermission = null, $options = []) {
        try {
            // 1. Verificar rate limiting
            if (!$this->checkRateLimit()) {
                $this->respondWithError('Rate limit exceeded', 429);
            }
            
            // 2. Verificar IP whitelist/blacklist
            if (!$this->checkIPAccess()) {
                $this->respondWithError('Access denied from this IP', 403);
            }
            
            // 3. Extrair e validar token JWT
            $token = $this->extractToken();
            if (!$token) {
                $this->respondWithError('Authentication token required', 401);
            }
            
            // 4. Validar token JWT
            $payload = $this->authManager->validateToken($token);
            
            // 5. Verificar se usuário ainda está ativo
            $user = $this->verifyUserStatus($payload['sub']);
            if (!$user) {
                $this->respondWithError('User account is inactive', 401);
            }
            
            // 6. Verificar timeout de sessão
            if (!$this->checkSessionTimeout($payload)) {
                $this->respondWithError('Session expired', 401);
            }
            
            // 7. Verificar permissão específica se requerida
            if ($requiredPermission && !$this->authManager->hasPermission($payload['sub'], $requiredPermission)) {
                $this->logSecurityEvent('insufficient_permissions', $payload['sub'], [
                    'required' => $requiredPermission,
                    'endpoint' => $_SERVER['REQUEST_URI']
                ]);
                $this->respondWithError('Insufficient permissions', 403);
            }
            
            // 8. Detectar atividade suspeita
            if ($this->config['detect_suspicious_activity']) {
                $this->detectSuspiciousActivity($payload);
            }
            
            // 9. Atualizar atividade da sessão
            $this->updateSessionActivity($payload['sub']);
            
            // 10. Disponibilizar dados do usuário para a aplicação
            $_SESSION['auth_user'] = $payload;
            $_SESSION['user_permissions'] = $this->authManager->getUserPermissions($payload['sub']);
            
            return $payload;
            
        } catch (Exception $e) {
            $this->logSecurityEvent('auth_error', null, ['error' => $e->getMessage()]);
            $this->respondWithError('Authentication failed: ' . $e->getMessage(), 401);
        }
    }
    
    /**
     * Middleware específico para roles
     */
    public static function requireRole($roles) {
        return function() use ($roles) {
            $middleware = new EnhancedAuthMiddleware();
            $user = $middleware->process();
            
            $userRoles = is_array($roles) ? $roles : [$roles];
            
            if (!in_array($user['role'], $userRoles)) {
                $middleware->respondWithError('Insufficient role privileges', 403);
            }
            
            return $user;
        };
    }
    
    /**
     * Middleware para verificar múltiplas permissões
     */
    public static function requirePermissions($permissions, $requireAll = true) {
        return function() use ($permissions, $requireAll) {
            $middleware = new EnhancedAuthMiddleware();
            $user = $middleware->process();
            
            $userPermissions = $middleware->authManager->getUserPermissions($user['sub']);
            $requiredPerms = is_array($permissions) ? $permissions : [$permissions];
            
            $hasPermissions = [];
            foreach ($requiredPerms as $perm) {
                $hasPermissions[] = in_array($perm, $userPermissions) || in_array('*', $userPermissions);
            }
            
            if ($requireAll) {
                // Requer todas as permissões
                if (in_array(false, $hasPermissions)) {
                    $middleware->respondWithError('Missing required permissions', 403);
                }
            } else {
                // Requer pelo menos uma permissão
                if (!in_array(true, $hasPermissions)) {
                    $middleware->respondWithError('No valid permissions found', 403);
                }
            }
            
            return $user;
        };
    }
    
    /**
     * Extrair token JWT do header Authorization
     */
    private function extractToken() {
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        
        if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            return $matches[1];
        }
        
        // Verificar em cookie como fallback
        if (isset($_COOKIE['auth_token'])) {
            return $_COOKIE['auth_token'];
        }
        
        return null;
    }
    
    /**
     * Verificar rate limiting
     */
    private function checkRateLimit() {
        if (!$this->config['rate_limiting']['enabled']) {
            return true;
        }
        
        $clientId = $this->getClientIdentifier();
        $currentTime = time();
        
        // Implementar rate limiting simples baseado em arquivo/sessão
        // Em produção, usar Redis ou Memcached
        $rateLimitFile = sys_get_temp_dir() . "/duralux_rate_limit_" . md5($clientId);
        
        if (file_exists($rateLimitFile)) {
            $data = json_decode(file_get_contents($rateLimitFile), true);
            
            // Limpar requests antigas (mais de 1 minuto)
            $data['requests'] = array_filter($data['requests'], function($timestamp) use ($currentTime) {
                return ($currentTime - $timestamp) < 60;
            });
            
            // Verificar limite
            if (count($data['requests']) >= $this->config['rate_limiting']['requests_per_minute']) {
                return false;
            }
        } else {
            $data = ['requests' => []];
        }
        
        // Adicionar esta requisição
        $data['requests'][] = $currentTime;
        file_put_contents($rateLimitFile, json_encode($data));
        
        return true;
    }
    
    /**
     * Verificar acesso por IP
     */
    private function checkIPAccess() {
        $clientIP = $this->getClientIP();
        
        // Verificar blacklist (implementar conforme necessário)
        $blacklistedIPs = []; // Carregar de configuração
        
        if (in_array($clientIP, $blacklistedIPs)) {
            return false;
        }
        
        // Verificar whitelist se configurado
        $whitelistedIPs = []; // Carregar de configuração
        
        if (!empty($whitelistedIPs) && !in_array($clientIP, $whitelistedIPs)) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Verificar status do usuário
     */
    private function verifyUserStatus($userId) {
        try {
            $pdo = new PDO("sqlite:" . __DIR__ . "/../../database/duralux.db");
            
            $stmt = $pdo->prepare("
                SELECT status, locked_until 
                FROM users 
                WHERE id = ?
            ");
            $stmt->execute([$userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user) return false;
            
            // Verificar se conta está ativa
            if ($user['status'] !== 'active') {
                return false;
            }
            
            // Verificar se conta está bloqueada temporariamente
            if ($user['locked_until'] && strtotime($user['locked_until']) > time()) {
                return false;
            }
            
            return true;
            
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Verificar timeout de sessão
     */
    private function checkSessionTimeout($payload) {
        $sessionStart = $payload['iat'] ?? 0;
        $currentTime = time();
        
        return ($currentTime - $sessionStart) < $this->config['session_timeout'];
    }
    
    /**
     * Detectar atividade suspeita
     */
    private function detectSuspiciousActivity($payload) {
        $userId = $payload['sub'];
        $currentIP = $this->getClientIP();
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        try {
            $pdo = new PDO("sqlite:" . __DIR__ . "/../../database/duralux.db");
            
            // Verificar mudança de IP
            $stmt = $pdo->prepare("
                SELECT ip_address, user_agent 
                FROM auth_audit_log 
                WHERE user_id = ? AND action = 'login_success' 
                ORDER BY created_at DESC 
                LIMIT 1
            ");
            $stmt->execute([$userId]);
            $lastEntrar = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $riskScore = 0;
            $riskFactors = [];
            
            // Mudança de IP
            if ($lastEntrar && $lastEntrar['ip_address'] !== $currentIP) {
                $riskScore += 30;
                $riskFactors[] = 'IP change detected';
            }
            
            // Mudança de User Agent
            if ($lastEntrar && $lastEntrar['user_agent'] !== $userAgent) {
                $riskScore += 20;
                $riskFactors[] = 'Device/browser change detected';
            }
            
            // Horário incomum (fora do horário comercial)
            $hour = (int)date('H');
            if ($hour < 6 || $hour > 22) {
                $riskScore += 10;
                $riskFactors[] = 'Access outside business hours';
            }
            
            // Log de atividade suspeita se score alto
            if ($riskScore >= 50) {
                $this->logSecurityEvent('suspicious_activity', $userId, [
                    'risk_score' => $riskScore,
                    'factors' => $riskFactors,
                    'ip_address' => $currentIP,
                    'user_agent' => $userAgent
                ]);
                
                // Opcionalmente, requerer re-autenticação ou 2FA
                // $this->requireReAuthentication();
            }
            
        } catch (Exception $e) {
            // Log erro silenciosamente
        }
    }
    
    /**
     * Atualizar atividade da sessão
     */
    private function updateSessionActivity($userId) {
        try {
            $pdo = new PDO("sqlite:" . __DIR__ . "/../../database/duralux.db");
            
            $stmt = $pdo->prepare("
                UPDATE user_sessions 
                SET last_activity = datetime('now') 
                WHERE user_id = ? AND is_active = 1
            ");
            $stmt->execute([$userId]);
            
        } catch (Exception $e) {
            // Log erro silenciosamente
        }
    }
    
    /**
     * Log de eventos de segurança
     */
    private function logSecurityEvent($event, $userId, $details = []) {
        try {
            $pdo = new PDO("sqlite:" . __DIR__ . "/../../database/duralux.db");
            
            $stmt = $pdo->prepare("
                INSERT INTO auth_audit_log (
                    user_id, action, details, ip_address, user_agent, 
                    risk_score, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, datetime('now'))
            ");
            
            $stmt->execute([
                $userId,
                $event,
                json_encode($details),
                $this->getClientIP(),
                $_SERVER['HTTP_USER_AGENT'] ?? '',
                $details['risk_score'] ?? 0
            ]);
            
        } catch (Exception $e) {
            error_log("Failed to log security event: " . $e->getMessage());
        }
    }
    
    /**
     * Responder com erro e finalizar execução
     */
    private function respondWithError($message, $code = 401) {
        http_response_code($code);
        header('Content-Type: application/json');
        
        echo json_encode([
            'error' => true,
            'message' => $message,
            'code' => $code,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        
        exit;
    }
    
    /**
     * Obter IP do cliente
     */
    private function getClientIP() {
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return $_SERVER['HTTP_X_FORWARDED_FOR'];
        } elseif (!empty($_SERVER['HTTP_X_REAL_IP'])) {
            return $_SERVER['HTTP_X_REAL_IP'];
        } else {
            return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        }
    }
    
    /**
     * Obter identificador do cliente
     */
    private function getClientIdentifier() {
        return md5($this->getClientIP() . ($_SERVER['HTTP_USER_AGENT'] ?? ''));
    }
    
    /**
     * Verificar se endpoint é público
     */
    public static function isPublicEndpoint($path) {
        $publicEndpoints = [
            '/api/v1/auth/login',
            '/api/v1/auth/register',
            '/api/v1/auth/forgot-password',
            '/api/v1/docs',
            '/api/v1/health'
        ];
        
        foreach ($publicEndpoints as $endpoint) {
            if (strpos($path, $endpoint) === 0) {
                return true;
            }
        }
        
        return false;
    }
}

/**
 * Funções auxiliares para compatibilidade
 */
class AuthMiddleware extends EnhancedAuthMiddleware {
    
    /**
     * Método estático simples para compatibilidade
     */
    public static function handle() {
        return parent::handle();
    }
    
    /**
     * Verificar se usuário tem permissão específica
     */
    public static function checkPermission($permission) {
        $user = self::handle($permission);
        return $user !== null;
    }
    
    /**
     * Verificar se usuário tem uma das roles especificadas
     */
    public static function checkRole($roles) {
        $middleware = new self();
        $user = $middleware->process();
        
        $allowedRoles = is_array($roles) ? $roles : [$roles];
        return in_array($user['role'], $allowedRoles);
    }
}

?>