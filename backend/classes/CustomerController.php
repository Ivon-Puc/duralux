<?php
/**
 * Controller de Clientes - CRUD Completo
 * Gerenciamento de clientes do sistema
 */

require_once 'BaseController.php';

class CustomerController extends BaseController {
    
    /**
     * Listar clientes com busca, filtros e paginação
     * GET /customers
     */
    public function index($params = []) {
        requireAuth(); // Middleware de autenticação
        
        $request_data = $this->getRequestData();
        
        // Parâmetros de busca e filtros
        $search = $request_data['search'] ?? '';
        $city = $request_data['city'] ?? '';
        $active = $request_data['active'] ?? '';
        $sort = $request_data['sort'] ?? 'created_at';
        $order = $request_data['order'] ?? 'DESC';
        $page = intval($request_data['page'] ?? 1);
        $limit = intval($request_data['limit'] ?? 10);
        
        // Construir query base
        $where_conditions = ['1 = 1'];
        $query_params = [];
        
        // Filtro de busca (nome ou email)
        if (!empty($search)) {
            $where_conditions[] = "(name LIKE ? OR email LIKE ?)";
            $query_params[] = "%$search%";
            $query_params[] = "%$search%";
        }
        
        // Filtro por cidade
        if (!empty($city)) {
            $where_conditions[] = "city LIKE ?";
            $query_params[] = "%$city%";
        }
        
        // Filtro por status ativo
        if ($active !== '') {
            $where_conditions[] = "active = ?";
            $query_params[] = intval($active);
        }
        
        // Validar campo de ordenação
        $allowed_sorts = ['name', 'email', 'city', 'created_at', 'updated_at'];
        if (!in_array($sort, $allowed_sorts)) {
            $sort = 'created_at';
        }
        
        // Validar direção da ordenação
        $order = strtoupper($order) === 'ASC' ? 'ASC' : 'DESC';
        
        // Query final
        $where_clause = implode(' AND ', $where_conditions);
        $query = "SELECT id, name, email, phone, address, city, state, zipcode, active, 
                         created_at, updated_at 
                  FROM customers 
                  WHERE $where_clause 
                  ORDER BY $sort $order";
        
        // Executar paginação
        $result = $this->paginate($query, $query_params, $page, $limit);
        
        // Formatar datas para exibição
        foreach ($result['data'] as &$customer) {
            $customer['created_at_formatted'] = date('d/m/Y H:i', strtotime($customer['created_at']));
            $customer['updated_at_formatted'] = date('d/m/Y H:i', strtotime($customer['updated_at']));
            $customer['active_text'] = $customer['active'] ? 'Ativo' : 'Inativo';
        }
        
        // Estatísticas adicionais
        $stats_query = "SELECT 
                          COUNT(*) as total,
                          COUNT(CASE WHEN active = 1 THEN 1 END) as active_count,
                          COUNT(CASE WHEN active = 0 THEN 1 END) as inactive_count,
                          COUNT(DISTINCT city) as cities_count
                        FROM customers";
        
        $stats_stmt = $this->db->prepare($stats_query);
        $stats_stmt->execute();
        $stats = $stats_stmt->fetch();
        
        $this->successResponse('Clientes listados com sucesso', [
            'customers' => $result['data'],
            'pagination' => $result['pagination'],
            'filters' => [
                'search' => $search,
                'city' => $city,
                'active' => $active,
                'sort' => $sort,
                'order' => $order
            ],
            'statistics' => $stats
        ]);
    }
    
    /**
     * Buscar cliente específico por ID
     * GET /customers/{id}
     */
    public function show($params = []) {
        requireAuth();
        
        $id = intval($params['id'] ?? 0);
        
        if ($id <= 0) {
            $this->errorResponse('ID do cliente inválido');
        }
        
        $customer = $this->findById('customers', $id);
        
        // Buscar pedidos do cliente (últimos 5)
        $orders_query = "SELECT id, total, status, created_at 
                        FROM orders 
                        WHERE customer_id = ? 
                        ORDER BY created_at DESC 
                        LIMIT 5";
        
        $orders_stmt = $this->db->prepare($orders_query);
        $orders_stmt->execute([$id]);
        $orders = $orders_stmt->fetchAll();
        
        // Formatar dados
        foreach ($orders as &$order) {
            $order['total_formatted'] = 'R$ ' . number_format($order['total'], 2, ',', '.');
            $order['created_at_formatted'] = date('d/m/Y', strtotime($order['created_at']));
            $order['status_text'] = $this->getOrderStatusText($order['status']);
        }
        
        $customer['created_at_formatted'] = date('d/m/Y H:i', strtotime($customer['created_at']));
        $customer['updated_at_formatted'] = date('d/m/Y H:i', strtotime($customer['updated_at']));
        $customer['recent_orders'] = $orders;
        
        $this->successResponse('Cliente encontrado', $customer);
    }
    
    /**
     * Criar novo cliente
     * POST /customers
     */
    public function store($params = []) {
        requireAuth();
        
        $data = $this->getRequestData();
        
        // Validar campos obrigatórios
        $this->validateRequired($data, ['name', 'email']);
        
        // Sanitizar dados
        $data = $this->sanitizeData($data, [
            'name', 'email', 'phone', 'address', 'city', 'state', 'zipcode', 'notes'
        ]);
        
        // Validações específicas
        $errors = [];
        
        // Validar email
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Email inválido';
        } elseif ($this->exists('customers', 'email', $data['email'])) {
            $errors['email'] = 'Este email já está cadastrado para outro cliente';
        }
        
        // Validar nome
        if (strlen($data['name']) < 2) {
            $errors['name'] = 'Nome deve ter pelo menos 2 caracteres';
        }
        
        // Validar telefone (se fornecido)
        if (!empty($data['phone'])) {
            $phone = preg_replace('/[^0-9]/', '', $data['phone']);
            if (strlen($phone) < 10) {
                $errors['phone'] = 'Telefone deve ter pelo menos 10 dígitos';
            }
        }
        
        // Validar CEP (se fornecido)
        if (!empty($data['zipcode'])) {
            $zipcode = preg_replace('/[^0-9]/', '', $data['zipcode']);
            if (strlen($zipcode) !== 8) {
                $errors['zipcode'] = 'CEP deve ter 8 dígitos';
            }
        }
        
        if (!empty($errors)) {
            $this->errorResponse('Dados inválidos', $errors);
        }
        
        try {
            // Inserir cliente
            $stmt = $this->db->prepare("
                INSERT INTO customers (name, email, phone, address, city, state, zipcode, notes, user_id, created_at, updated_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
            ");
            
            $user = getCurrentUser();
            
            $stmt->execute([
                $data['name'],
                $data['email'],
                $data['phone'] ?? null,
                $data['address'] ?? null,
                $data['city'] ?? null,
                $data['state'] ?? null,
                $data['zipcode'] ?? null,
                $data['notes'] ?? null,
                $user['id']
            ]);
            
            $customer_id = $this->db->lastInsertId();
            
            // Log da criação
            $this->logActivity('customer_created', 'customers', $customer_id, [
                'name' => $data['name'],
                'email' => $data['email']
            ]);
            
            // Buscar cliente criado para retornar
            $created_customer = $this->findById('customers', $customer_id);
            
            $this->successResponse('Cliente cadastrado com sucesso', $created_customer, 201);
            
        } catch (Exception $e) {
            logError("Erro ao criar cliente: " . $e->getMessage());
            $this->errorResponse('Erro interno do servidor', null, 500);
        }
    }
    
    /**
     * Atualizar cliente existente
     * PUT /customers/{id}
     */
    public function update($params = []) {
        requireAuth();
        
        $id = intval($params['id'] ?? 0);
        
        if ($id <= 0) {
            $this->errorResponse('ID do cliente inválido');
        }
        
        // Verificar se cliente existe
        $existing_customer = $this->findById('customers', $id);
        
        $data = $this->getRequestData();
        
        // Sanitizar dados
        $data = $this->sanitizeData($data, [
            'name', 'email', 'phone', 'address', 'city', 'state', 'zipcode', 'notes', 'active'
        ]);
        
        // Validações
        $errors = [];
        
        if (isset($data['email'])) {
            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                $errors['email'] = 'Email inválido';
            } elseif ($this->exists('customers', 'email', $data['email'], $id)) {
                $errors['email'] = 'Este email já está cadastrado para outro cliente';
            }
        }
        
        if (isset($data['name']) && strlen($data['name']) < 2) {
            $errors['name'] = 'Nome deve ter pelo menos 2 caracteres';
        }
        
        if (!empty($data['phone'])) {
            $phone = preg_replace('/[^0-9]/', '', $data['phone']);
            if (strlen($phone) < 10) {
                $errors['phone'] = 'Telefone deve ter pelo menos 10 dígitos';
            }
        }
        
        if (!empty($data['zipcode'])) {
            $zipcode = preg_replace('/[^0-9]/', '', $data['zipcode']);
            if (strlen($zipcode) !== 8) {
                $errors['zipcode'] = 'CEP deve ter 8 dígitos';
            }
        }
        
        if (isset($data['active'])) {
            $data['active'] = intval($data['active']);
        }
        
        if (!empty($errors)) {
            $this->errorResponse('Dados inválidos', $errors);
        }
        
        try {
            // Construir query de update dinâmica
            $update_fields = [];
            $update_params = [];
            
            $allowed_fields = ['name', 'email', 'phone', 'address', 'city', 'state', 'zipcode', 'notes', 'active'];
            
            foreach ($allowed_fields as $field) {
                if (array_key_exists($field, $data)) {
                    $update_fields[] = "$field = ?";
                    $update_params[] = $data[$field];
                }
            }
            
            if (empty($update_fields)) {
                $this->errorResponse('Nenhum campo para atualizar foi fornecido');
            }
            
            // Adicionar updated_at
            $update_fields[] = "updated_at = CURRENT_TIMESTAMP";
            $update_params[] = $id;
            
            $sql = "UPDATE customers SET " . implode(', ', $update_fields) . " WHERE id = ?";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($update_params);
            
            // Log da atualização
            $this->logActivity('customer_updated', 'customers', $id, $data);
            
            // Buscar cliente atualizado
            $updated_customer = $this->findById('customers', $id);
            
            $this->successResponse('Cliente atualizado com sucesso', $updated_customer);
            
        } catch (Exception $e) {
            logError("Erro ao atualizar cliente: " . $e->getMessage());
            $this->errorResponse('Erro interno do servidor', null, 500);
        }
    }
    
    /**
     * Excluir cliente (soft delete)
     * DELETE /customers/{id}
     */
    public function delete($params = []) {
        requireAuth();
        
        $id = intval($params['id'] ?? 0);
        
        if ($id <= 0) {
            $this->errorResponse('ID do cliente inválido');
        }
        
        // Verificar se cliente existe
        $customer = $this->findById('customers', $id);
        
        // Verificar se cliente tem pedidos
        $orders_stmt = $this->db->prepare("SELECT COUNT(*) FROM orders WHERE customer_id = ?");
        $orders_stmt->execute([$id]);
        $orders_count = $orders_stmt->fetchColumn();
        
        if ($orders_count > 0) {
            $this->errorResponse("Não é possível excluir cliente que possui $orders_count pedido(s). Desative-o ao invés de excluir.");
        }
        
        try {
            // Soft delete - marcar como inativo
            $stmt = $this->db->prepare("UPDATE customers SET active = 0, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
            $stmt->execute([$id]);
            
            // Log da exclusão
            $this->logActivity('customer_deleted', 'customers', $id, [
                'name' => $customer['name'],
                'email' => $customer['email']
            ]);
            
            $this->successResponse('Cliente removido com sucesso');
            
        } catch (Exception $e) {
            logError("Erro ao excluir cliente: " . $e->getMessage());
            $this->errorResponse('Erro interno do servidor', null, 500);
        }
    }
    
    /**
     * Método auxiliar para traduzir status dos pedidos
     */
    private function getOrderStatusText($status) {
        $statuses = [
            'pending' => 'Pendente',
            'confirmed' => 'Confirmado',
            'processing' => 'Processando',
            'shipped' => 'Enviado',
            'delivered' => 'Entregue',
            'cancelled' => 'Cancelado'
        ];
        
        return $statuses[$status] ?? ucfirst($status);
    }
}
?>