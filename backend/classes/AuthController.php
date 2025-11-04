<?php
/**
 * Controller de Autenticação - Login, Logout, Registro
 */

require_once 'BaseController.php';

class AuthController extends BaseController {
    
    /**
     * Login do usuário
     * POST /auth/login
     */
    public function login($params = []) {
        $data = $this->getRequestData();
        
        // Validar campos obrigatórios
        $this->validateRequired($data, ['email', 'password']);
        
        $email = trim($data['email']);
        $password = $data['password'];
        
        // Buscar usuário
        $stmt = $this->db->prepare("SELECT * FROM users WHERE email = ? AND active = 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if (!$user || !password_verify($password, $user['password'])) {
            $this->logActivity('login_failed', 'users', null, ['email' => $email]);
            $this->errorResponse('Email ou senha inválidos', null, 401);
        }
        
        // Criar sessão
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['login_time'] = time();
        
        // Gerar token CSRF
        $csrf_token = generateCSRFToken();
        
        // Log de sucesso
        $this->logActivity('login_success', 'users', $user['id']);
        
        // Atualizar último login
        $stmt = $this->db->prepare("UPDATE users SET updated_at = CURRENT_TIMESTAMP WHERE id = ?");
        $stmt->execute([$user['id']]);
        
        $this->successResponse('Login realizado com sucesso', [
            'user' => [
                'id' => $user['id'],
                'name' => $user['name'],
                'email' => $user['email'],
                'role' => $user['role'],
                'avatar' => $user['avatar']
            ],
            'csrf_token' => $csrf_token
        ]);
    }
    
    /**
     * Logout do usuário
     * POST /auth/logout
     */
    public function logout($params = []) {
        $user_id = $_SESSION['user_id'] ?? null;
        
        if ($user_id) {
            $this->logActivity('logout', 'users', $user_id);
        }
        
        // Destruir sessão
        session_destroy();
        
        $this->successResponse('Logout realizado com sucesso');
    }
    
    /**
     * Registro de novo usuário
     * POST /auth/register
     */
    public function register($params = []) {
        $data = $this->getRequestData();
        
        // Validar campos obrigatórios
        $this->validateRequired($data, ['name', 'email', 'password', 'password_confirm']);
        
        $data = $this->sanitizeData($data, ['name', 'email', 'password', 'password_confirm']);
        
        // Validações específicas
        $errors = [];
        
        // Validar email
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Email inválido';
        } elseif ($this->exists('users', 'email', $data['email'])) {
            $errors['email'] = 'Este email já está cadastrado';
        }
        
        // Validar senha
        if (strlen($data['password']) < PASSWORD_MIN_LENGTH) {
            $errors['password'] = 'Senha deve ter no mínimo ' . PASSWORD_MIN_LENGTH . ' caracteres';
        }
        
        if ($data['password'] !== $data['password_confirm']) {
            $errors['password_confirm'] = 'Confirmação de senha não confere';
        }
        
        if (!empty($errors)) {
            $this->errorResponse('Dados inválidos', $errors);
        }
        
        try {
            // Hash da senha
            $password_hash = password_hash($data['password'], PASSWORD_DEFAULT);
            
            // Inserir usuário
            $stmt = $this->db->prepare("
                INSERT INTO users (name, email, password, role, created_at) 
                VALUES (?, ?, ?, 'user', CURRENT_TIMESTAMP)
            ");
            
            $stmt->execute([
                $data['name'],
                $data['email'],
                $password_hash
            ]);
            
            $user_id = $this->db->lastInsertId();
            
            // Log da criação
            $this->logActivity('user_created', 'users', $user_id, [
                'name' => $data['name'],
                'email' => $data['email']
            ]);
            
            $this->successResponse('Usuário cadastrado com sucesso', [
                'user_id' => $user_id
            ], 201);
            
        } catch (Exception $e) {
            logError("Erro ao criar usuário: " . $e->getMessage());
            $this->errorResponse('Erro interno do servidor', null, 500);
        }
    }
    
    /**
     * Obter dados do usuário atual
     * GET /auth/me
     */
    public function me($params = []) {
        if (!isLoggedIn()) {
            $this->errorResponse('Usuário não autenticado', null, 401);
        }
        
        $user = getCurrentUser();
        
        if (!$user) {
            $this->errorResponse('Sessão inválida', null, 401);
        }
        
        $this->successResponse('Dados do usuário', [
            'user' => $user,
            'session_time' => $_SESSION['login_time'] ?? null,
            'csrf_token' => generateCSRFToken()
        ]);
    }
    
    /**
     * Recuperação de senha
     * POST /auth/forgot
     */
    public function forgotPassword($params = []) {
        $data = $this->getRequestData();
        
        $this->validateRequired($data, ['email']);
        
        $email = trim($data['email']);
        
        // Verificar se usuário existe
        $stmt = $this->db->prepare("SELECT id, name FROM users WHERE email = ? AND active = 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if (!$user) {
            // Por segurança, sempre retornar sucesso (não revelar se email existe)
            $this->successResponse('Se o email estiver cadastrado, você receberá as instruções');
        }
        
        // Gerar token de recuperação
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
        
        // Salvar token (criar tabela se não existir)
        $create_tokens_table = "CREATE TABLE IF NOT EXISTS password_reset_tokens (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            token VARCHAR(64) NOT NULL,
            expires_at DATETIME NOT NULL,
            used INTEGER DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id)
        )";
        
        $this->db->exec($create_tokens_table);
        
        $stmt = $this->db->prepare("
            INSERT INTO password_reset_tokens (user_id, token, expires_at) 
            VALUES (?, ?, ?)
        ");
        $stmt->execute([$user['id'], $token, $expires]);
        
        // Log da tentativa
        $this->logActivity('password_reset_requested', 'users', $user['id']);
        
        // TODO: Enviar email com o token
        // Por enquanto, apenas log para desenvolvimento
        logError("Token de recuperação para {$email}: {$token}");
        
        $this->successResponse('Se o email estiver cadastrado, você receberá as instruções');
    }
}
?>