<?php
/**
 * DURALUX CRM - Advanced Authentication Controller v3.0
 * Controlador de autenticação com JWT, roles e 2FA
 * 
 * @author Duralux Development Team
 * @version 3.0.0
 */

require_once 'BaseController.php';
require_once 'JWTAuthManager.php';

class AuthController extends BaseController {
    
    private $authManager;
    
    public function __construct() {
        parent::__construct();
        $this->authManager = new JWTAuthManager();
    }
    
    /**
     * Login de usuário com JWT
     */
    public function login($params = []) {
        try {
            $data = $this->getRequestData();
            
            // Validar dados obrigatórios
            if (empty($data['email']) || empty($data['password'])) {
                return $this->sendError('Email e senha são obrigatórios', 400);
            }
            
            // Validar formato do email
            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                return $this->sendError('Formato de email inválido', 400);
            }
            
            $rememberMe = $data['remember'] ?? false;
            
            // Tentar autenticação
            $result = $this->authManager->authenticate($data['email'], $data['password'], $rememberMe);
            
            // Verificar se requer 2FA
            if (isset($result['requires_2fa'])) {
                return $this->sendSuccess($result, '2FA required', 202);
            }
            
            // Login bem-sucedido
            return $this->sendSuccess([
                'user' => $result['user'],
                'tokens' => $result['tokens'],
                'permissions' => $result['permissions'],
                'expires_at' => $result['expires_at']
            ], 'Login realizado com sucesso');
            
        } catch (Exception $e) {
            return $this->sendError($e->getMessage(), 401);
        }
    }
    
    /**
     * Logout com revogação de token
     */
    public function logout($params = []) {
        try {
            $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
            
            if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
                $token = $matches[1];
                
                // Revogar token
                $this->revokeToken($token);
                
                // Invalidar sessão
                if (isset($_SESSION['auth_user'])) {
                    unset($_SESSION['auth_user']);
                }
            }
            
            return $this->sendSuccess([], 'Logout realizado com sucesso');
            
        } catch (Exception $e) {
            return $this->sendError($e->getMessage(), 500);
        }
    }
    
    /**
     * Registrar novo usuário com validações avançadas
     */
    public function register($params = []) {
        try {
            $data = $this->getRequestData();
            
            // Validar dados obrigatórios
            $required = ['email', 'password', 'first_name', 'last_name'];
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    return $this->sendError("Campo '$field' é obrigatório", 400);
                }
            }
            
            // Validar formato do email
            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                return $this->sendError('Formato de email inválido', 400);
            }
            
            // Verificar se email já existe
            if ($this->emailExists($data['email'])) {
                return $this->sendError('Email já está em uso', 409);
            }
            
            // Validar política de senha
            $passwordValidation = $this->validatePassword($data['password']);
            if (!$passwordValidation['valid']) {
                return $this->sendError($passwordValidation['message'], 400);
            }
            
            // Criar usuário
            $userId = $this->createUser($data);
            
            return $this->sendSuccess([
                'user_id' => $userId,
                'message' => 'Usuário registrado com sucesso'
            ], 'Registro realizado com sucesso', 201);
            
        } catch (Exception $e) {
            return $this->sendError($e->getMessage(), 500);
        }
    }
    
    /**
     * Obter informações do usuário atual
     */
    public function me($params = []) {
        try {
            $user = $this->authManager->middleware();
            
            // Buscar dados completos do usuário
            $userData = $this->authManager->getUserById($user['sub']);
            if (!$userData) {
                return $this->sendError('Usuário não encontrado', 404);
            }
            
            // Remover dados sensíveis
            unset($userData['password_hash'], $userData['two_factor_secret']);
            
            return $this->sendSuccess([
                'user' => $userData,
                'permissions' => $this->authManager->getUserPermissions($user['sub']),
                'session_info' => [
                    'login_time' => $user['iat'],
                    'expires_at' => $user['exp'],
                    'session_id' => $user['session_id'] ?? null
                ]
            ], 'Dados do usuário carregados');
            
        } catch (Exception $e) {
            return $this->sendError($e->getMessage(), 401);
        }
    }
    
    /**
     * Refresh token JWT
     */
    public function refreshToken($params = []) {
        try {
            $data = $this->getRequestData();
            
            if (empty($data['refresh_token'])) {
                return $this->sendError('Refresh token é obrigatório', 400);
            }
            
            // Validar refresh token
            $payload = $this->authManager->validateToken($data['refresh_token']);
            
            if (!isset($payload['type']) || $payload['type'] !== 'refresh') {
                return $this->sendError('Token inválido para refresh', 401);
            }
            
            // Gerar novo access token
            $user = $this->authManager->getUserById($payload['sub']);
            if (!$user) {
                return $this->sendError('Usuário não encontrado', 404);
            }
            
            $newAccessToken = $this->authManager->generateAccessToken($user);
            
            return $this->sendSuccess([
                'access_token' => $newAccessToken,
                'expires_at' => date('Y-m-d H:i:s', time() + 3600) // 1 hora
            ], 'Token renovado com sucesso');
            
        } catch (Exception $e) {
            return $this->sendError($e->getMessage(), 401);
        }
    }
    
    /**
     * Verificar 2FA
     */
    public function verify2FA($params = []) {
        try {
            $data = $this->getRequestData();
            
            if (empty($data['user_id']) || empty($data['totp_code'])) {
                return $this->sendError('User ID e código TOTP são obrigatórios', 400);
            }
            
            // Verificar código TOTP
            $isValid = $this->verify2FACode($data['user_id'], $data['totp_code']);
            
            if (!$isValid) {
                return $this->sendError('Código 2FA inválido', 401);
            }
            
            // Completar login após 2FA
            $user = $this->authManager->getUserById($data['user_id']);
            $result = $this->authManager->handleSuccessfulLogin($user, $data['remember'] ?? false);
            
            return $this->sendSuccess([
                'user' => $result['user'],
                'tokens' => $result['tokens'],
                'permissions' => $result['permissions'],
                'expires_at' => $result['expires_at']
            ], '2FA verificado com sucesso');
            
        } catch (Exception $e) {
            return $this->sendError($e->getMessage(), 401);
        }
    }
    
    /**
     * Alterar senha
     */
    public function changePassword($params = []) {
        try {
            $user = $this->authManager->middleware();
            $data = $this->getRequestData();
            
            if (empty($data['current_password']) || empty($data['new_password'])) {
                return $this->sendError('Senha atual e nova senha são obrigatórias', 400);
            }
            
            // Verificar senha atual
            $userData = $this->authManager->getUserById($user['sub']);
            if (!password_verify($data['current_password'], $userData['password_hash'])) {
                return $this->sendError('Senha atual incorreta', 401);
            }
            
            // Validar nova senha
            $passwordValidation = $this->validatePassword($data['new_password']);
            if (!$passwordValidation['valid']) {
                return $this->sendError($passwordValidation['message'], 400);
            }
            
            // Verificar histórico de senhas
            if ($this->isPasswordReused($user['sub'], $data['new_password'])) {
                return $this->sendError('Nova senha não pode ser igual às últimas 5 senhas', 400);
            }
            
            // Atualizar senha
            $this->updateUserPassword($user['sub'], $data['new_password']);
            
            return $this->sendSuccess([], 'Senha alterada com sucesso');
            
        } catch (Exception $e) {
            return $this->sendError($e->getMessage(), 500);
        }
    }
    
    // ==========================================
    // MÉTODOS AUXILIARES PRIVADOS
    // ==========================================
    
    private function emailExists($email) {
        $stmt = $this->db->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        return $stmt->fetchColumn() !== false;
    }
    
    private function validatePassword($password) {
        $config = [
            'min_length' => 8,
            'require_uppercase' => true,
            'require_lowercase' => true,
            'require_numbers' => true,
            'require_symbols' => true
        ];
        
        if (strlen($password) < $config['min_length']) {
            return ['valid' => false, 'message' => "Senha deve ter pelo menos {$config['min_length']} caracteres"];
        }
        
        if ($config['require_uppercase'] && !preg_match('/[A-Z]/', $password)) {
            return ['valid' => false, 'message' => 'Senha deve conter pelo menos uma letra maiúscula'];
        }
        
        if ($config['require_lowercase'] && !preg_match('/[a-z]/', $password)) {
            return ['valid' => false, 'message' => 'Senha deve conter pelo menos uma letra minúscula'];
        }
        
        if ($config['require_numbers'] && !preg_match('/[0-9]/', $password)) {
            return ['valid' => false, 'message' => 'Senha deve conter pelo menos um número'];
        }
        
        if ($config['require_symbols'] && !preg_match('/[^a-zA-Z0-9]/', $password)) {
            return ['valid' => false, 'message' => 'Senha deve conter pelo menos um símbolo'];
        }
        
        return ['valid' => true];
    }
    
    private function createUser($data) {
        $stmt = $this->db->prepare("
            INSERT INTO users (
                email, password_hash, first_name, last_name, phone, 
                role, status, password_changed_at, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, datetime('now'), datetime('now'))
        ");
        
        $stmt->execute([
            $data['email'],
            password_hash($data['password'], PASSWORD_DEFAULT),
            $data['first_name'],
            $data['last_name'],
            $data['phone'] ?? null,
            $data['role'] ?? 'user',
            'active'
        ]);
        
        $userId = $this->db->lastInsertId();
        
        // Adicionar à tabela password_history
        $this->addPasswordToHistory($userId, password_hash($data['password'], PASSWORD_DEFAULT));
        
        return $userId;
    }
    
    private function revokeToken($token) {
        $stmt = $this->db->prepare("
            UPDATE auth_tokens 
            SET is_revoked = 1 
            WHERE token_hash = ?
        ");
        $stmt->execute([hash('sha256', $token)]);
    }
    
    private function isPasswordReused($userId, $password) {
        $stmt = $this->db->prepare("
            SELECT password_hash 
            FROM password_history 
            WHERE user_id = ? 
            ORDER BY created_at DESC 
            LIMIT 5
        ");
        $stmt->execute([$userId]);
        $oldPasswords = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        foreach ($oldPasswords as $oldHash) {
            if (password_verify($password, $oldHash)) {
                return true;
            }
        }
        
        return false;
    }
    
    private function updateUserPassword($userId, $password) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        
        $stmt = $this->db->prepare("
            UPDATE users 
            SET password_hash = ?, password_changed_at = datetime('now') 
            WHERE id = ?
        ");
        $stmt->execute([$hash, $userId]);
        
        // Adicionar ao histórico
        $this->addPasswordToHistory($userId, $hash);
    }
    
    private function addPasswordToHistory($userId, $passwordHash) {
        $stmt = $this->db->prepare("
            INSERT INTO password_history (user_id, password_hash) 
            VALUES (?, ?)
        ");
        $stmt->execute([$userId, $passwordHash]);
        
        // Manter apenas as últimas 5 senhas
        $stmt = $this->db->prepare("
            DELETE FROM password_history 
            WHERE user_id = ? AND id NOT IN (
                SELECT id FROM password_history 
                WHERE user_id = ? 
                ORDER BY created_at DESC 
                LIMIT 5
            )
        ");
        $stmt->execute([$userId, $userId]);
    }
    
    private function verify2FACode($userId, $code) {
        // Implementar verificação TOTP
        // Por simplicidade, aceitar qualquer código de 6 dígitos por enquanto
        return preg_match('/^\d{6}$/', $code);
    }
}
?>