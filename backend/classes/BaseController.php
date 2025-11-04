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

    /**
     * Registra atividade do usuário
     */
    protected function logActivity($action, $details = null) {
        if (!isset($_SESSION['user_id'])) {
            return;
        }

        try {
            $stmt = $this->db->prepare("
                INSERT INTO activity_logs (user_id, action, details, ip_address, user_agent, created_at) 
                VALUES (?, ?, ?, ?, ?, datetime('now'))
            ");
            
            $stmt->execute([
                $_SESSION['user_id'],
                $action,
                $details ? json_encode($details) : null,
                $_SERVER['REMOTE_ADDR'] ?? '',
                $_SERVER['HTTP_USER_AGENT'] ?? ''
            ]);
        } catch (Exception $e) {
            error_log("Erro ao registrar atividade: " . $e->getMessage());
        }
    }

    /**
     * Obtém estatísticas gerais do dashboard
     */
    public function getDashboardStats() {
        try {
            // Estatísticas de clientes
            $customersStmt = $this->db->query("SELECT COUNT(*) as total FROM customers");
            $totalCustomers = $customersStmt->fetch(PDO::FETCH_ASSOC)['total'];

            // Estatísticas de produtos
            $productsStmt = $this->db->query("SELECT COUNT(*) as total FROM products");
            $totalProducts = $productsStmt->fetch(PDO::FETCH_ASSOC)['total'];

            // Estatísticas de pedidos (simuladas com customers como base)
            $ordersStmt = $this->db->query("
                SELECT 
                    COUNT(*) as total_invoices,
                    COUNT(CASE WHEN created_at > date('now', '-30 days') THEN 1 END) as pending_invoices,
                    0 as total_leads,
                    0 as converted_leads,
                    0 as total_projects,
                    0 as active_projects
                FROM customers
            ");
            $orderStats = $ordersStmt->fetch(PDO::FETCH_ASSOC);

            // Simula alguns valores para demonstração
            $pendingAmount = rand(5000, 50000);
            $conversionRate = rand(35, 65);
            $conversionValue = rand(2000, 10000);

            $stats = [
                'total_customers' => $totalCustomers,
                'total_products' => $totalProducts,
                'total_invoices' => $orderStats['total_invoices'],
                'pending_invoices' => max(1, round($orderStats['total_invoices'] * 0.3)),
                'pending_amount' => $pendingAmount,
                'total_leads' => rand(50, 200),
                'converted_leads' => rand(20, 100),
                'total_projects' => rand(15, 50),
                'active_projects' => rand(5, 25),
                'conversion_rate' => $conversionRate,
                'conversion_value' => $conversionValue
            ];

            $this->successResponse("Estatísticas carregadas com sucesso", $stats);

        } catch (Exception $e) {
            error_log("Erro ao obter estatísticas do dashboard: " . $e->getMessage());
            $this->errorResponse("Erro ao carregar estatísticas");
        }
    }

    /**
     * Obtém dados de receita para gráficos
     */
    public function getRevenueData($period = 'month') {
        try {
            // Para demonstração, geramos dados simulados
            // Em um sistema real, estes viriam da tabela de pedidos/vendas
            $revenueData = [
                'awaiting' => rand(5000, 15000),
                'completed' => rand(15000, 35000),
                'rejected' => rand(2000, 8000),
                'revenue' => rand(30000, 70000),
                'chart_data' => []
            ];

            // Gera dados para o gráfico dos últimos 12 meses
            for ($i = 11; $i >= 0; $i--) {
                $date = date('Y-m', strtotime("-$i months"));
                $revenueData['chart_data'][] = [
                    'period' => $date,
                    'value' => rand(10000, 50000)
                ];
            }

            $this->successResponse("Dados de receita carregados com sucesso", $revenueData);

        } catch (Exception $e) {
            error_log("Erro ao obter dados de receita: " . $e->getMessage());
            $this->errorResponse("Erro ao carregar dados de receita");
        }
    }

    /**
     * Obtém análises de leads
     */
    public function getLeadsAnalytics() {
        try {
            // Dados simulados para leads
            $leadsData = [
                'new_leads' => rand(20, 50),
                'qualified_leads' => rand(15, 30),
                'converted_leads' => rand(5, 20),
                'lost_leads' => rand(5, 15),
                'conversion_rate' => rand(25, 45),
                'avg_deal_size' => rand(5000, 25000)
            ];

            $this->successResponse("Análises de leads carregadas com sucesso", $leadsData);

        } catch (Exception $e) {
            error_log("Erro ao obter análises de leads: " . $e->getMessage());
            $this->errorResponse("Erro ao carregar análises de leads");
        }
    }

    /**
     * Obtém análises de projetos
     */
    public function getProjectsAnalytics() {
        try {
            // Dados simulados para projetos
            $projectsData = [
                'active_projects' => rand(10, 30),
                'completed_projects' => rand(50, 150),
                'on_hold_projects' => rand(2, 10),
                'cancelled_projects' => rand(1, 5),
                'avg_project_value' => rand(15000, 50000),
                'total_project_value' => rand(500000, 2000000)
            ];

            $this->successResponse("Análises de projetos carregadas com sucesso", $projectsData);

        } catch (Exception $e) {
            error_log("Erro ao obter análises de projetos: " . $e->getMessage());
            $this->errorResponse("Erro ao carregar análises de projetos");
        }
    }

    /**
     * Obtém atividades recentes
     */
    public function getRecentActivities($limit = 10) {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    action,
                    details,
                    created_at,
                    'system' as type
                FROM activity_logs 
                ORDER BY created_at DESC 
                LIMIT ?
            ");
            
            $stmt->execute([$limit]);
            $activities = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Processa atividades para exibição
            $processedActivities = [];
            foreach ($activities as $activity) {
                $processedActivities[] = [
                    'title' => $this->formatActivityTitle($activity['action'], $activity['details']),
                    'type' => $this->getActivityType($activity['action']),
                    'created_at' => $activity['created_at']
                ];
            }

            $this->successResponse("Atividades carregadas com sucesso", $processedActivities);

        } catch (Exception $e) {
            error_log("Erro ao obter atividades recentes: " . $e->getMessage());
            $this->errorResponse("Erro ao carregar atividades");
        }
    }

    /**
     * Formata título da atividade para exibição
     */
    private function formatActivityTitle($action, $details) {
        $titles = [
            'customer_created' => 'Novo cliente criado',
            'customer_updated' => 'Cliente atualizado',
            'customer_deleted' => 'Cliente removido',
            'product_created' => 'Novo produto criado',
            'product_updated' => 'Produto atualizado',
            'product_deleted' => 'Produto removido',
            'user_login' => 'Login realizado',
            'user_logout' => 'Logout realizado'
        ];

        return $titles[$action] ?? ucfirst(str_replace('_', ' ', $action));
    }

    /**
     * Obtém tipo da atividade
     */
    private function getActivityType($action) {
        if (strpos($action, 'customer') !== false) return 'customer';
        if (strpos($action, 'product') !== false) return 'product';
        if (strpos($action, 'order') !== false) return 'order';
        if (strpos($action, 'payment') !== false) return 'payment';
        if (strpos($action, 'lead') !== false) return 'lead';
        if (strpos($action, 'project') !== false) return 'project';
        
        return 'system';
    }
}