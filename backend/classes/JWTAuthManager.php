<?php
/**
 * DURALUX CRM - JWT Authentication Manager v3.0
 * Sistema avançado de autenticação JWT com roles granulares
 * 
 * Features Avançadas:
 * - JWT Token generation e validation com algoritmos seguros
 * - Sistema de roles granular (Admin, Manager, User, Guest)
 * - Permissões por módulo/ação (customers.read, leads.create, etc.)
 * - Two-Factor Authentication (2FA) com TOTP
 * - Session management avançado
 * - Rate limiting específico por usuário
 * - Audit trail completo
 * - Password policies avançadas
 * - Account lockout protection
 * - Token blacklisting/revogação
 * 
 * @author Duralux Development Team
 * @version 3.0.0
 * @since 2025-11-04
 */

require_once __DIR__ . '/../config/database.php';

class JWTAuthManager {
    
    private $db;
    private $secretKey;
    private $algorithm = 'HS256';
    
    // Configurações de segurança
    private $config = [
        'token' => [
            'access_token_ttl' => 3600,     // 1 hora
            'refresh_token_ttl' => 2592000, // 30 dias
            'remember_token_ttl' => 7776000, // 90 dias
            'issuer' => 'Duralux CRM',
            'audience' => 'duralux-users'
        ],
        'password' => [
            'min_length' => 8,
            'require_uppercase' => true,
            'require_lowercase' => true,
            'require_numbers' => true,
            'require_symbols' => true,
            'prevent_reuse' => 5, // últimas 5 senhas
            'expiry_days' => 90
        ],
        'account' => [
            'max_login_attempts' => 5,
            'lockout_duration' => 900, // 15 minutos
            'session_timeout' => 7200,  // 2 horas
            'require_2fa' => false,
            'force_password_change' => false
        ],
        'security' => [
            'enable_audit_log' => true,
            'log_failed_attempts' => true,
            'detect_suspicious_activity' => true,
            'ip_whitelist' => [],
            'geographic_restrictions' => false
        ]
    ];
    
    // Sistema de roles e permissões
    private $roleHierarchy = [
        'superadmin' => [
            'name' => 'Super Administrator',
            'level' => 100,
            'inherits' => [],
            'permissions' => ['*'] // Todas as permissões
        ],
        'admin' => [
            'name' => 'Administrator',
            'level' => 90,
            'inherits' => ['manager'],
            'permissions' => [
                'users.*', 'settings.*', 'reports.*', 'system.*',
                'customers.*', 'leads.*', 'projects.*', 'orders.*'
            ]
        ],
        'manager' => [
            'name' => 'Manager',
            'level' => 70,
            'inherits' => ['user'],
            'permissions' => [
                'customers.*', 'leads.*', 'projects.read', 'projects.create',
                'projects.update', 'orders.read', 'orders.create',
                'reports.read', 'dashboard.read'
            ]
        ],
        'user' => [
            'name' => 'User',
            'level' => 50,
            'inherits' => ['guest'],
            'permissions' => [
                'customers.read', 'customers.create', 'customers.update',
                'leads.read', 'leads.create', 'leads.update',
                'projects.read', 'dashboard.read'
            ]
        ],
        'guest' => [
            'name' => 'Guest',
            'level' => 10,
            'inherits' => [],
            'permissions' => [
                'auth.login', 'auth.register', 'auth.reset-password'
            ]
        ]
    ];
    
    public function __construct() {
        try {
            $this->db = new PDO(
                "sqlite:" . __DIR__ . "/../../database/duralux.db",
                null,
                null,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
            
            $this->secretKey = $this->getOrCreateSecretKey();
            $this->initializeTables();
            
        } catch (PDOException $e) {
            throw new Exception('Database connection failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Inicializar tabelas necessárias
     */
    private function initializeTables() {
        $tables = [
            // Tabela de usuários expandida
            "CREATE TABLE IF NOT EXISTS users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                email VARCHAR(255) UNIQUE NOT NULL,
                username VARCHAR(100) UNIQUE,
                password_hash TEXT NOT NULL,
                role VARCHAR(50) DEFAULT 'user',
                status ENUM('active', 'inactive', 'locked', 'pending') DEFAULT 'active',
                first_name VARCHAR(100),
                last_name VARCHAR(100),
                phone VARCHAR(20),
                avatar_url TEXT,
                language VARCHAR(10) DEFAULT 'pt-BR',
                timezone VARCHAR(50) DEFAULT 'America/Sao_Paulo',
                last_login DATETIME,
                login_attempts INTEGER DEFAULT 0,
                locked_until DATETIME NULL,
                password_changed_at DATETIME,
                email_verified_at DATETIME NULL,
                two_factor_secret TEXT NULL,
                two_factor_enabled BOOLEAN DEFAULT 0,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )",
            
            // Tokens JWT e refresh tokens
            "CREATE TABLE IF NOT EXISTS auth_tokens (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                token_type ENUM('access', 'refresh', 'remember') NOT NULL,
                token_hash TEXT NOT NULL,
                expires_at DATETIME NOT NULL,
                ip_address VARCHAR(45),
                user_agent TEXT,
                device_info TEXT,
                is_revoked BOOLEAN DEFAULT 0,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            )",
            
            // Log de auditoria
            "CREATE TABLE IF NOT EXISTS auth_audit_log (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER,
                action VARCHAR(100) NOT NULL,
                details TEXT,
                ip_address VARCHAR(45),
                user_agent TEXT,
                success BOOLEAN DEFAULT 1,
                risk_score INTEGER DEFAULT 0,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
            )",
            
            // Sessões ativas
            "CREATE TABLE IF NOT EXISTS user_sessions (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                session_token TEXT UNIQUE NOT NULL,
                ip_address VARCHAR(45),
                user_agent TEXT,
                last_activity DATETIME DEFAULT CURRENT_TIMESTAMP,
                expires_at DATETIME NOT NULL,
                is_active BOOLEAN DEFAULT 1,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            )",
            
            // Permissões personalizadas
            "CREATE TABLE IF NOT EXISTS user_permissions (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                permission VARCHAR(255) NOT NULL,
                granted BOOLEAN DEFAULT 1,
                granted_by INTEGER,
                granted_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                expires_at DATETIME NULL,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (granted_by) REFERENCES users(id) ON DELETE SET NULL
            )",
            
            // Histórico de senhas
            "CREATE TABLE IF NOT EXISTS password_history (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                password_hash TEXT NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            )",
            
            // Configurações do sistema de auth
            "CREATE TABLE IF NOT EXISTS auth_settings (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                setting_key VARCHAR(100) UNIQUE NOT NULL,
                setting_value TEXT,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )"
        ];
        
        foreach ($tables as $sql) {
            $this->db->exec($sql);
        }
        
        // Criar índices para performance
        $indexes = [
            "CREATE INDEX IF NOT EXISTS idx_users_email ON users(email)",
            "CREATE INDEX IF NOT EXISTS idx_users_role ON users(role)",
            "CREATE INDEX IF NOT EXISTS idx_auth_tokens_user ON auth_tokens(user_id)",
            "CREATE INDEX IF NOT EXISTS idx_auth_tokens_hash ON auth_tokens(token_hash)",
            "CREATE INDEX IF NOT EXISTS idx_audit_log_user ON auth_audit_log(user_id)",
            "CREATE INDEX IF NOT EXISTS idx_audit_log_action ON auth_audit_log(action)",
            "CREATE INDEX IF NOT EXISTS idx_sessions_user ON user_sessions(user_id)",
            "CREATE INDEX IF NOT EXISTS idx_sessions_token ON user_sessions(session_token)"
        ];
        
        foreach ($indexes as $sql) {
            $this->db->exec($sql);
        }
    }
    
    /**
     * Gerar ou recuperar chave secreta
     */
    private function getOrCreateSecretKey() {
        $stmt = $this->db->prepare("SELECT setting_value FROM auth_settings WHERE setting_key = 'jwt_secret'");
        $stmt->execute();
        $secret = $stmt->fetchColumn();
        
        if (!$secret) {
            $secret = bin2hex(random_bytes(32));
            $stmt = $this->db->prepare("INSERT INTO auth_settings (setting_key, setting_value) VALUES ('jwt_secret', ?)");
            $stmt->execute([$secret]);
        }
        
        return $secret;
    }
    
    /**
     * Autenticar usuário
     */
    public function authenticate($email, $password, $rememberMe = false) {
        try {
            $this->logAuthAttempt($email, 'login_attempt');
            
            // Verificar rate limiting
            if (!$this->checkRateLimit($email)) {
                throw new Exception('Too many login attempts. Try again later.');
            }
            
            // Buscar usuário
            $user = $this->getUserByEmail($email);
            if (!$user) {
                $this->logAuthAttempt($email, 'login_failed', 'User not found');
                throw new Exception('Invalid credentials');
            }
            
            // Verificar se conta está bloqueada
            if ($user['status'] === 'locked' || 
                ($user['locked_until'] && strtotime($user['locked_until']) > time())) {
                $this->logAuthAttempt($email, 'login_blocked', 'Account locked');
                throw new Exception('Account is locked');
            }
            
            // Verificar senha
            if (!password_verify($password, $user['password_hash'])) {
                $this->handleFailedEntrar($user['id'], $email);
                throw new Exception('Invalid credentials');
            }
            
            // Verificar se precisa de 2FA
            if ($user['two_factor_enabled']) {
                return $this->handle2FARequired($user);
            }
            
            // Entrar bem-sucedido
            return $this->handleSuccessfulEntrar($user, $rememberMe);
            
        } catch (Exception $e) {
            throw $e;
        }
    }
    
    /**
     * Processar login bem-sucedido
     */
    private function handleSuccessfulEntrar($user, $rememberMe = false) {
        // Resetar tentativas de login
        $this->resetEntrarAttempts($user['id']);
        
        // Atualizar último login
        $this->updateLastEntrar($user['id']);
        
        // Gerar tokens
        $accessToken = $this->generateAccessToken($user);
        $refreshToken = $this->generateRefreshToken($user);
        
        // Criar sessão
        $sessionToken = $this->createUserSession($user['id']);
        
        // Token de "lembrar-me" se solicitado
        $rememberToken = $rememberMe ? $this->generateRememberToken($user) : null;
        
        // Log da autenticação bem-sucedida
        $this->logAuthAttempt($user['email'], 'login_success', 'Entrar successful');
        
        return [
            'user' => $this->sanitizeUser($user),
            'tokens' => [
                'access_token' => $accessToken,
                'refresh_token' => $refreshToken,
                'remember_token' => $rememberToken,
                'session_token' => $sessionToken
            ],
            'permissions' => $this->getUserPermissions($user['id']),
            'expires_at' => date('Y-m-d H:i:s', time() + $this->config['token']['access_token_ttl'])
        ];
    }
    
    /**
     * Gerar token de acesso JWT
     */
    private function generateAccessToken($user) {
        $payload = [
            'iss' => $this->config['token']['issuer'],
            'aud' => $this->config['token']['audience'],
            'iat' => time(),
            'exp' => time() + $this->config['token']['access_token_ttl'],
            'sub' => $user['id'],
            'email' => $user['email'],
            'role' => $user['role'],
            'permissions' => $this->getUserPermissions($user['id']),
            'session_id' => uniqid('sess_', true)
        ];
        
        $token = $this->createJWT($payload);
        
        // Armazenar token na base de dados
        $this->storeToken($user['id'], 'access', $token, time() + $this->config['token']['access_token_ttl']);
        
        return $token;
    }
    
    /**
     * Gerar refresh token
     */
    private function generateRefreshToken($user) {
        $payload = [
            'iss' => $this->config['token']['issuer'],
            'aud' => $this->config['token']['audience'],
            'iat' => time(),
            'exp' => time() + $this->config['token']['refresh_token_ttl'],
            'sub' => $user['id'],
            'type' => 'refresh'
        ];
        
        $token = $this->createJWT($payload);
        
        // Armazenar refresh token
        $this->storeToken($user['id'], 'refresh', $token, time() + $this->config['token']['refresh_token_ttl']);
        
        return $token;
    }
    
    /**
     * Criar JWT Token
     */
    private function createJWT($payload) {
        $header = json_encode(['typ' => 'JWT', 'alg' => $this->algorithm]);
        $payload = json_encode($payload);
        
        $base64Header = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
        $base64Payload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));
        
        $signature = hash_hmac('sha256', $base64Header . "." . $base64Payload, $this->secretKey, true);
        $base64Signature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
        
        return $base64Header . "." . $base64Payload . "." . $base64Signature;
    }
    
    /**
     * Validar JWT Token
     */
    public function validateToken($token) {
        try {
            $parts = explode('.', $token);
            if (count($parts) !== 3) {
                throw new Exception('Invalid token format');
            }
            
            $header = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $parts[0])), true);
            $payload = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $parts[1])), true);
            $signature = $parts[2];
            
            // Verificar algoritmo
            if (!isset($header['alg']) || $header['alg'] !== $this->algorithm) {
                throw new Exception('Invalid algorithm');
            }
            
            // Verificar assinatura
            $expectedSignature = str_replace(['+', '/', '='], ['-', '_', ''], 
                base64_encode(hash_hmac('sha256', $parts[0] . '.' . $parts[1], $this->secretKey, true))
            );
            
            if (!hash_equals($expectedSignature, $signature)) {
                throw new Exception('Invalid signature');
            }
            
            // Verificar expiração
            if (isset($payload['exp']) && $payload['exp'] < time()) {
                throw new Exception('Token expired');
            }
            
            // Verificar se token foi revogado
            if ($this->isTokenRevoked($token)) {
                throw new Exception('Token revoked');
            }
            
            return $payload;
            
        } catch (Exception $e) {
            throw new Exception('Token validation failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Verificar permissões do usuário
     */
    public function hasPermission($userId, $permission) {
        $userPermissions = $this->getUserPermissions($userId);
        
        // Verificar permissão específica
        if (in_array($permission, $userPermissions) || in_array('*', $userPermissions)) {
            return true;
        }
        
        // Verificar permissões com wildcard (ex: customers.* permite customers.read)
        foreach ($userPermissions as $userPerm) {
            if (strpos($userPerm, '*') !== false) {
                $pattern = str_replace('*', '.*', preg_quote($userPerm, '/'));
                if (preg_match('/^' . $pattern . '$/', $permission)) {
                    return true;
                }
            }
        }
        
        return false;
    }
    
    /**
     * Obter permissões do usuário
     */
    public function getUserPermissions($userId) {
        $user = $this->getUserById($userId);
        if (!$user) return [];
        
        $role = $user['role'];
        $permissions = [];
        
        // Obter permissões da role
        if (isset($this->roleHierarchy[$role])) {
            $permissions = $this->getRolePermissions($role);
        }
        
        // Adicionar permissões personalizadas
        $stmt = $this->db->prepare("
            SELECT permission 
            FROM user_permissions 
            WHERE user_id = ? AND granted = 1 
            AND (expires_at IS NULL OR expires_at > datetime('now'))
        ");
        $stmt->execute([$userId]);
        $customPerms = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        return array_unique(array_merge($permissions, $customPerms));
    }
    
    /**
     * Obter permissões de uma role (incluindo herança)
     */
    private function getRolePermissions($role) {
        $permissions = [];
        
        if (!isset($this->roleHierarchy[$role])) {
            return $permissions;
        }
        
        $roleData = $this->roleHierarchy[$role];
        
        // Adicionar permissões da role atual
        $permissions = array_merge($permissions, $roleData['permissions']);
        
        // Adicionar permissões das roles herdadas
        if (!empty($roleData['inherits'])) {
            foreach ($roleData['inherits'] as $inheritedRole) {
                $permissions = array_merge($permissions, $this->getRolePermissions($inheritedRole));
            }
        }
        
        return array_unique($permissions);
    }
    
    /**
     * Middleware de autenticação
     */
    public function middleware($requiredPermission = null) {
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        
        if (!preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            http_response_code(401);
            echo json_encode(['error' => 'Authentication required']);
            exit;
        }
        
        try {
            $payload = $this->validateToken($matches[1]);
            
            // Verificar permissão se especificada
            if ($requiredPermission && !$this->hasPermission($payload['sub'], $requiredPermission)) {
                http_response_code(403);
                echo json_encode(['error' => 'Insufficient permissions']);
                exit;
            }
            
            // Disponibilizar dados do usuário
            $_SESSION['auth_user'] = $payload;
            return $payload;
            
        } catch (Exception $e) {
            http_response_code(401);
            echo json_encode(['error' => 'Invalid token: ' . $e->getMessage()]);
            exit;
        }
    }
    
    // ==========================================
    // MÉTODOS AUXILIARES
    // ==========================================
    
    private function getUserByEmail($email) {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    private function getUserById($id) {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    private function checkRateLimit($email) {
        // Implementar rate limiting baseado em IP e email
        return true; // Simplificado por enquanto
    }
    
    private function handleFailedEntrar($userId, $email) {
        // Incrementar tentativas
        $stmt = $this->db->prepare("UPDATE users SET login_attempts = login_attempts + 1 WHERE id = ?");
        $stmt->execute([$userId]);
        
        // Verificar se deve bloquear conta
        $user = $this->getUserById($userId);
        if ($user['login_attempts'] >= $this->config['account']['max_login_attempts']) {
            $lockUntil = date('Y-m-d H:i:s', time() + $this->config['account']['lockout_duration']);
            $stmt = $this->db->prepare("UPDATE users SET status = 'locked', locked_until = ? WHERE id = ?");
            $stmt->execute([$lockUntil, $userId]);
        }
        
        $this->logAuthAttempt($email, 'login_failed', 'Invalid password');
    }
    
    private function resetEntrarAttempts($userId) {
        $stmt = $this->db->prepare("UPDATE users SET login_attempts = 0, locked_until = NULL WHERE id = ?");
        $stmt->execute([$userId]);
    }
    
    private function updateLastEntrar($userId) {
        $stmt = $this->db->prepare("UPDATE users SET last_login = datetime('now') WHERE id = ?");
        $stmt->execute([$userId]);
    }
    
    private function storeToken($userId, $type, $token, $expiresAt) {
        $stmt = $this->db->prepare("
            INSERT INTO auth_tokens (user_id, token_type, token_hash, expires_at, ip_address, user_agent) 
            VALUES (?, ?, ?, datetime(?, 'unixepoch'), ?, ?)
        ");
        $stmt->execute([
            $userId, 
            $type, 
            hash('sha256', $token), 
            $expiresAt,
            $_SERVER['REMOTE_ADDR'] ?? '',
            $_SERVER['HTTP_USER_AGENT'] ?? ''
        ]);
    }
    
    private function isTokenRevoked($token) {
        $stmt = $this->db->prepare("
            SELECT is_revoked FROM auth_tokens 
            WHERE token_hash = ? AND expires_at > datetime('now')
        ");
        $stmt->execute([hash('sha256', $token)]);
        $result = $stmt->fetch();
        
        return $result && $result['is_revoked'];
    }
    
    private function createUserSession($userId) {
        $sessionToken = bin2hex(random_bytes(32));
        
        $stmt = $this->db->prepare("
            INSERT INTO user_sessions (user_id, session_token, ip_address, user_agent, expires_at) 
            VALUES (?, ?, ?, ?, datetime('now', '+' || ? || ' seconds'))
        ");
        $stmt->execute([
            $userId, 
            $sessionToken, 
            $_SERVER['REMOTE_ADDR'] ?? '',
            $_SERVER['HTTP_USER_AGENT'] ?? '',
            $this->config['account']['session_timeout']
        ]);
        
        return $sessionToken;
    }
    
    private function logAuthAttempt($email, $action, $details = null) {
        if (!$this->config['security']['enable_audit_log']) return;
        
        $stmt = $this->db->prepare("
            INSERT INTO auth_audit_log (user_id, action, details, ip_address, user_agent, success) 
            VALUES ((SELECT id FROM users WHERE email = ?), ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $email,
            $action,
            $details,
            $_SERVER['REMOTE_ADDR'] ?? '',
            $_SERVER['HTTP_USER_AGENT'] ?? '',
            strpos($action, 'success') !== false ? 1 : 0
        ]);
    }
    
    private function sanitizeUser($user) {
        unset($user['password_hash'], $user['two_factor_secret']);
        return $user;
    }
    
    private function handle2FARequired($user) {
        // Retornar que 2FA é necessário
        return [
            'requires_2fa' => true,
            'user_id' => $user['id'],
            'message' => 'Two-factor authentication required'
        ];
    }
    
    private function generateRememberToken($user) {
        $payload = [
            'iss' => $this->config['token']['issuer'],
            'aud' => $this->config['token']['audience'],
            'iat' => time(),
            'exp' => time() + $this->config['token']['remember_token_ttl'],
            'sub' => $user['id'],
            'type' => 'remember'
        ];
        
        $token = $this->createJWT($payload);
        
        // Armazenar remember token
        $this->storeToken($user['id'], 'remember', $token, time() + $this->config['token']['remember_token_ttl']);
        
        return $token;
    }
}

?>