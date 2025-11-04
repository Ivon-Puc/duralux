<?php
/**
 * Controller Base - Funcionalidades comuns para todos os controllers
 */

abstract class BaseController {
    protected $db;
    
    public function __construct() {
        $this->db = getDB();
    }
    
    /**
     * Obter dados da requisição JSON
     */
    protected function getJsonData() {
        $input = file_get_contents('php://input');
        return json_decode($input, true) ?: [];
    }
    
    /**
     * Obter dados da requisição (POST/GET/PUT/DELETE)
     */
    protected function getRequestData() {
        $method = $_SERVER['REQUEST_METHOD'];
        
        switch ($method) {
            case 'GET':
                return $_GET;
            case 'POST':
                return array_merge($_POST, $this->getJsonData());
            case 'PUT':
            case 'DELETE':
                return $this->getJsonData();
            default:
                return [];
        }
    }
    
    /**
     * Validar dados obrigatórios
     */
    protected function validateRequired($data, $required_fields) {
        $errors = [];
        
        foreach ($required_fields as $field) {
            if (!isset($data[$field]) || empty(trim($data[$field]))) {
                $errors[$field] = "Campo '$field' é obrigatório";
            }
        }
        
        if (!empty($errors)) {
            jsonResponse(['errors' => $errors], 400);
        }
        
        return true;
    }
    
    /**
     * Sanitizar dados de entrada
     */
    protected function sanitizeData($data, $allowed_fields = null) {
        $sanitized = [];
        
        foreach ($data as $key => $value) {
            // Se allowed_fields está definido, só permitir campos da lista
            if ($allowed_fields && !in_array($key, $allowed_fields)) {
                continue;
            }
            
            $sanitized[$key] = sanitize($value);
        }
        
        return $sanitized;
    }
    
    /**
     * Paginar resultados
     */
    protected function paginate($query, $params = [], $page = 1, $limit = 10) {
        $page = max(1, intval($page));
        $limit = max(1, min(100, intval($limit))); // Máximo 100 por página
        $offset = ($page - 1) * $limit;
        
        // Query para contar total de registros
        $count_query = "SELECT COUNT(*) as total FROM (" . $query . ") as count_table";
        $count_stmt = $this->db->prepare($count_query);
        $count_stmt->execute($params);
        $total = $count_stmt->fetch()['total'];
        
        // Query com paginação
        $paginated_query = $query . " LIMIT $limit OFFSET $offset";
        $stmt = $this->db->prepare($paginated_query);
        $stmt->execute($params);
        $data = $stmt->fetchAll();
        
        return [
            'data' => $data,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $limit,
                'total' => $total,
                'total_pages' => ceil($total / $limit),
                'has_next' => $page < ceil($total / $limit),
                'has_prev' => $page > 1
            ]
        ];
    }
    
    /**
     * Buscar registro por ID
     */
    protected function findById($table, $id, $fields = '*') {
        $stmt = $this->db->prepare("SELECT $fields FROM $table WHERE id = ? AND active = 1");
        $stmt->execute([$id]);
        $result = $stmt->fetch();
        
        if (!$result) {
            jsonResponse(['error' => 'Registro não encontrado'], 404);
        }
        
        return $result;
    }
    
    /**
     * Verificar se registro existe
     */
    protected function exists($table, $field, $value, $exclude_id = null) {
        $sql = "SELECT COUNT(*) FROM $table WHERE $field = ?";
        $params = [$value];
        
        if ($exclude_id) {
            $sql .= " AND id != ?";
            $params[] = $exclude_id;
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchColumn() > 0;
    }
    
    /**
     * Log de atividades (para auditoria)
     */
    protected function logActivity($action, $table, $record_id, $details = null) {
        $user = getCurrentUser();
        $user_id = $user ? $user['id'] : null;
        
        // Criar tabela de logs se não existir
        $create_logs_table = "CREATE TABLE IF NOT EXISTS activity_logs (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER,
            action VARCHAR(50) NOT NULL,
            table_name VARCHAR(50) NOT NULL,
            record_id INTEGER,
            details TEXT,
            ip_address VARCHAR(45),
            user_agent TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id)
        )";
        
        $this->db->exec($create_logs_table);
        
        // Inserir log
        $stmt = $this->db->prepare("
            INSERT INTO activity_logs (user_id, action, table_name, record_id, details, ip_address, user_agent) 
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $user_id,
            $action,
            $table,
            $record_id,
            $details ? json_encode($details) : null,
            $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ]);
    }
    
    /**
     * Resposta de sucesso padronizada
     */
    protected function successResponse($message, $data = null, $status_code = 200) {
        $response = ['success' => true, 'message' => $message];
        
        if ($data !== null) {
            $response['data'] = $data;
        }
        
        jsonResponse($response, $status_code);
    }
    
    /**
     * Resposta de erro padronizada
     */
    protected function errorResponse($message, $errors = null, $status_code = 400) {
        $response = ['success' => false, 'message' => $message];
        
        if ($errors !== null) {
            $response['errors'] = $errors;
        }
        
        jsonResponse($response, $status_code);
    }
}