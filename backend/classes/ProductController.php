<?php
/**
 * Controller de Produtos/Serviços - CRUD Completo
 * Gerenciamento de produtos e serviços do sistema
 */

require_once 'BaseController.php';

class ProductController extends BaseController {
    
    /**
     * Listar produtos com busca, filtros e paginação
     * GET /products
     */
    public function index($params = []) {
        requireAuth(); // Middleware de autenticação
        
        $request_data = $this->getRequestData();
        
        // Parâmetros de busca e filtros
        $search = $request_data['search'] ?? '';
        $category = $request_data['category'] ?? '';
        $active = $request_data['active'] ?? '';
        $min_price = $request_data['min_price'] ?? '';
        $max_price = $request_data['max_price'] ?? '';
        $in_stock = $request_data['in_stock'] ?? '';
        $sort = $request_data['sort'] ?? 'created_at';
        $order = $request_data['order'] ?? 'DESC';
        $page = intval($request_data['page'] ?? 1);
        $limit = intval($request_data['limit'] ?? 10);
        
        // Construir query base
        $where_conditions = ['1 = 1'];
        $query_params = [];
        
        // Filtro de busca (nome ou descrição)
        if (!empty($search)) {
            $where_conditions[] = "(name LIKE ? OR description LIKE ? OR sku LIKE ?)";
            $query_params[] = "%$search%";
            $query_params[] = "%$search%";
            $query_params[] = "%$search%";
        }
        
        // Filtro por categoria
        if (!empty($category)) {
            $where_conditions[] = "category LIKE ?";
            $query_params[] = "%$category%";
        }
        
        // Filtro por status ativo
        if ($active !== '') {
            $where_conditions[] = "active = ?";
            $query_params[] = intval($active);
        }
        
        // Filtro por faixa de preço
        if (!empty($min_price)) {
            $where_conditions[] = "price >= ?";
            $query_params[] = floatval($min_price);
        }
        
        if (!empty($max_price)) {
            $where_conditions[] = "price <= ?";
            $query_params[] = floatval($max_price);
        }
        
        // Filtro por estoque
        if ($in_stock !== '') {
            if ($in_stock === '1') {
                $where_conditions[] = "stock > 0";
            } else {
                $where_conditions[] = "stock <= 0";
            }
        }
        
        // Validar campo de ordenação
        $allowed_sorts = ['name', 'price', 'category', 'stock', 'created_at', 'updated_at'];
        if (!in_array($sort, $allowed_sorts)) {
            $sort = 'created_at';
        }
        
        // Validar direção da ordenação
        $order = strtoupper($order) === 'ASC' ? 'ASC' : 'DESC';
        
        // Query final
        $where_clause = implode(' AND ', $where_conditions);
        $query = "SELECT id, name, description, price, category, image, stock, sku, active, 
                         created_at, updated_at 
                  FROM products 
                  WHERE $where_clause 
                  ORDER BY $sort $order";
        
        // Executar paginação
        $result = $this->paginate($query, $query_params, $page, $limit);
        
        // Formatar dados para exibição
        foreach ($result['data'] as &$product) {
            $product['price_formatted'] = 'R$ ' . number_format($product['price'], 2, ',', '.');
            $product['created_at_formatted'] = date('d/m/Y H:i', strtotime($product['created_at']));
            $product['updated_at_formatted'] = date('d/m/Y H:i', strtotime($product['updated_at']));
            $product['active_text'] = $product['active'] ? 'Ativo' : 'Inativo';
            $product['stock_status'] = $product['stock'] > 0 ? 'Em Estoque' : 'Sem Estoque';
            $product['stock_level'] = $this->getStockLevel($product['stock']);
        }
        
        // Estatísticas adicionais
        $stats_query = "SELECT 
                          COUNT(*) as total,
                          COUNT(CASE WHEN active = 1 THEN 1 END) as active_count,
                          COUNT(CASE WHEN active = 0 THEN 1 END) as inactive_count,
                          COUNT(CASE WHEN stock > 0 THEN 1 END) as in_stock_count,
                          COUNT(CASE WHEN stock <= 0 THEN 1 END) as out_of_stock_count,
                          COUNT(DISTINCT category) as categories_count,
                          AVG(price) as avg_price,
                          SUM(stock) as total_stock
                        FROM products";
        
        $stats_stmt = $this->db->prepare($stats_query);
        $stats_stmt->execute();
        $stats = $stats_stmt->fetch();
        
        // Formatar estatísticas
        $stats['avg_price_formatted'] = 'R$ ' . number_format($stats['avg_price'], 2, ',', '.');
        
        // Buscar categorias disponíveis
        $categories_query = "SELECT DISTINCT category FROM products WHERE category IS NOT NULL AND category != '' ORDER BY category";
        $categories_stmt = $this->db->prepare($categories_query);
        $categories_stmt->execute();
        $categories = $categories_stmt->fetchAll(PDO::FETCH_COLUMN);
        
        $this->successResponse('Produtos listados com sucesso', [
            'products' => $result['data'],
            'pagination' => $result['pagination'],
            'filters' => [
                'search' => $search,
                'category' => $category,
                'active' => $active,
                'min_price' => $min_price,
                'max_price' => $max_price,
                'in_stock' => $in_stock,
                'sort' => $sort,
                'order' => $order
            ],
            'statistics' => $stats,
            'categories' => $categories
        ]);
    }
    
    /**
     * Buscar produto específico por ID
     * GET /products/{id}
     */
    public function show($params = []) {
        requireAuth();
        
        $id = intval($params['id'] ?? 0);
        
        if ($id <= 0) {
            $this->errorResponse('ID do produto inválido');
        }
        
        $product = $this->findById('products', $id);
        
        // Buscar pedidos que incluem este produto (últimos 5)
        $orders_query = "SELECT o.id, o.total, o.status, o.created_at, c.name as customer_name,
                               oi.quantity, oi.unit_price, oi.total as item_total
                        FROM orders o
                        JOIN order_items oi ON o.id = oi.order_id  
                        JOIN customers c ON o.customer_id = c.id
                        WHERE oi.product_id = ? 
                        ORDER BY o.created_at DESC 
                        LIMIT 5";
        
        $orders_stmt = $this->db->prepare($orders_query);
        $orders_stmt->execute([$id]);
        $orders = $orders_stmt->fetchAll();
        
        // Estatísticas do produto
        $product_stats_query = "SELECT 
                                  SUM(oi.quantity) as total_sold,
                                  COUNT(DISTINCT oi.order_id) as total_orders,
                                  SUM(oi.total) as total_revenue
                                FROM order_items oi 
                                WHERE oi.product_id = ?";
        
        $product_stats_stmt = $this->db->prepare($product_stats_query);
        $product_stats_stmt->execute([$id]);
        $product_stats = $product_stats_stmt->fetch();
        
        // Formatar dados
        foreach ($orders as &$order) {
            $order['total_formatted'] = 'R$ ' . number_format($order['total'], 2, ',', '.');
            $order['item_total_formatted'] = 'R$ ' . number_format($order['item_total'], 2, ',', '.');
            $order['unit_price_formatted'] = 'R$ ' . number_format($order['unit_price'], 2, ',', '.');
            $order['created_at_formatted'] = date('d/m/Y', strtotime($order['created_at']));
            $order['status_text'] = $this->getOrderStatusText($order['status']);
        }
        
        $product['price_formatted'] = 'R$ ' . number_format($product['price'], 2, ',', '.');
        $product['created_at_formatted'] = date('d/m/Y H:i', strtotime($product['created_at']));
        $product['updated_at_formatted'] = date('d/m/Y H:i', strtotime($product['updated_at']));
        $product['stock_level'] = $this->getStockLevel($product['stock']);
        $product['recent_orders'] = $orders;
        $product['statistics'] = [
            'total_sold' => $product_stats['total_sold'] ?: 0,
            'total_orders' => $product_stats['total_orders'] ?: 0,
            'total_revenue' => $product_stats['total_revenue'] ?: 0,
            'total_revenue_formatted' => 'R$ ' . number_format($product_stats['total_revenue'] ?: 0, 2, ',', '.')
        ];
        
        $this->successResponse('Produto encontrado', $product);
    }
    
    /**
     * Criar novo produto
     * POST /products
     */
    public function store($params = []) {
        requireAuth();
        
        $data = $this->getRequestData();
        
        // Validar campos obrigatórios
        $this->validateRequired($data, ['name', 'price']);
        
        // Sanitizar dados
        $data = $this->sanitizeData($data, [
            'name', 'description', 'price', 'category', 'image', 'stock', 'sku'
        ]);
        
        // Validações específicas
        $errors = [];
        
        // Validar nome
        if (strlen($data['name']) < 2) {
            $errors['name'] = 'Nome deve ter pelo menos 2 caracteres';
        }
        
        // Validar preço
        $price = floatval($data['price']);
        if ($price < 0) {
            $errors['price'] = 'Preço deve ser maior ou igual a zero';
        }
        
        // Validar estoque
        $stock = intval($data['stock'] ?? 0);
        if ($stock < 0) {
            $errors['stock'] = 'Estoque deve ser maior ou igual a zero';
        }
        
        // Validar SKU (se fornecido)
        if (!empty($data['sku'])) {
            if ($this->exists('products', 'sku', $data['sku'])) {
                $errors['sku'] = 'Este SKU já está cadastrado para outro produto';
            }
        }
        
        // Validar categoria
        if (!empty($data['category']) && strlen($data['category']) > 100) {
            $errors['category'] = 'Categoria deve ter no máximo 100 caracteres';
        }
        
        if (!empty($errors)) {
            $this->errorResponse('Dados inválidos', $errors);
        }
        
        try {
            // Gerar SKU automático se não fornecido
            if (empty($data['sku'])) {
                $data['sku'] = $this->generateSKU($data['name']);
            }
            
            // Inserir produto
            $stmt = $this->db->prepare("
                INSERT INTO products (name, description, price, category, image, stock, sku, active, created_at, updated_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
            ");
            
            $stmt->execute([
                $data['name'],
                $data['description'] ?? null,
                $price,
                $data['category'] ?? null,
                $data['image'] ?? null,
                $stock,
                $data['sku']
            ]);
            
            $product_id = $this->db->lastInsertId();
            
            // Log da criação
            $this->logActivity('product_created', 'products', $product_id, [
                'name' => $data['name'],
                'price' => $price,
                'sku' => $data['sku']
            ]);
            
            // Buscar produto criado para retornar
            $created_product = $this->findById('products', $product_id);
            $created_product['price_formatted'] = 'R$ ' . number_format($created_product['price'], 2, ',', '.');
            
            $this->successResponse('Produto cadastrado com sucesso', $created_product, 201);
            
        } catch (Exception $e) {
            logError("Erro ao criar produto: " . $e->getMessage());
            $this->errorResponse('Erro interno do servidor', null, 500);
        }
    }
    
    /**
     * Atualizar produto existente
     * PUT /products/{id}
     */
    public function update($params = []) {
        requireAuth();
        
        $id = intval($params['id'] ?? 0);
        
        if ($id <= 0) {
            $this->errorResponse('ID do produto inválido');
        }
        
        // Verificar se produto existe
        $existing_product = $this->findById('products', $id);
        
        $data = $this->getRequestData();
        
        // Sanitizar dados
        $data = $this->sanitizeData($data, [
            'name', 'description', 'price', 'category', 'image', 'stock', 'sku', 'active'
        ]);
        
        // Validações
        $errors = [];
        
        if (isset($data['name']) && strlen($data['name']) < 2) {
            $errors['name'] = 'Nome deve ter pelo menos 2 caracteres';
        }
        
        if (isset($data['price'])) {
            $price = floatval($data['price']);
            if ($price < 0) {
                $errors['price'] = 'Preço deve ser maior ou igual a zero';
            }
            $data['price'] = $price;
        }
        
        if (isset($data['stock'])) {
            $stock = intval($data['stock']);
            if ($stock < 0) {
                $errors['stock'] = 'Estoque deve ser maior ou igual a zero';
            }
            $data['stock'] = $stock;
        }
        
        if (isset($data['sku']) && !empty($data['sku'])) {
            if ($this->exists('products', 'sku', $data['sku'], $id)) {
                $errors['sku'] = 'Este SKU já está cadastrado para outro produto';
            }
        }
        
        if (isset($data['category']) && strlen($data['category']) > 100) {
            $errors['category'] = 'Categoria deve ter no máximo 100 caracteres';
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
            
            $allowed_fields = ['name', 'description', 'price', 'category', 'image', 'stock', 'sku', 'active'];
            
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
            
            $sql = "UPDATE products SET " . implode(', ', $update_fields) . " WHERE id = ?";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($update_params);
            
            // Log da atualização
            $this->logActivity('product_updated', 'products', $id, $data);
            
            // Buscar produto atualizado
            $updated_product = $this->findById('products', $id);
            $updated_product['price_formatted'] = 'R$ ' . number_format($updated_product['price'], 2, ',', '.');
            
            $this->successResponse('Produto atualizado com sucesso', $updated_product);
            
        } catch (Exception $e) {
            logError("Erro ao atualizar produto: " . $e->getMessage());
            $this->errorResponse('Erro interno do servidor', null, 500);
        }
    }
    
    /**
     * Excluir produto (soft delete)
     * DELETE /products/{id}
     */
    public function delete($params = []) {
        requireAuth();
        
        $id = intval($params['id'] ?? 0);
        
        if ($id <= 0) {
            $this->errorResponse('ID do produto inválido');
        }
        
        // Verificar se produto existe
        $product = $this->findById('products', $id);
        
        // Verificar se produto tem pedidos
        $orders_stmt = $this->db->prepare("SELECT COUNT(*) FROM order_items WHERE product_id = ?");
        $orders_stmt->execute([$id]);
        $orders_count = $orders_stmt->fetchColumn();
        
        if ($orders_count > 0) {
            $this->errorResponse("Não é possível excluir produto que possui $orders_count venda(s). Desative-o ao invés de excluir.");
        }
        
        try {
            // Soft delete - marcar como inativo
            $stmt = $this->db->prepare("UPDATE products SET active = 0, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
            $stmt->execute([$id]);
            
            // Log da exclusão
            $this->logActivity('product_deleted', 'products', $id, [
                'name' => $product['name'],
                'sku' => $product['sku']
            ]);
            
            $this->successResponse('Produto removido com sucesso');
            
        } catch (Exception $e) {
            logError("Erro ao excluir produto: " . $e->getMessage());
            $this->errorResponse('Erro interno do servidor', null, 500);
        }
    }
    
    /**
     * Método auxiliar para gerar SKU automático
     */
    private function generateSKU($name) {
        // Pegar primeiras 3 letras do nome + timestamp
        $prefix = strtoupper(substr(preg_replace('/[^a-zA-Z]/', '', $name), 0, 3));
        $suffix = substr(time(), -6); // Últimos 6 dígitos do timestamp
        
        $sku = $prefix . $suffix;
        
        // Verificar se SKU já existe, se sim, adicionar número sequencial
        $counter = 1;
        while ($this->exists('products', 'sku', $sku)) {
            $sku = $prefix . $suffix . str_pad($counter, 2, '0', STR_PAD_LEFT);
            $counter++;
        }
        
        return $sku;
    }
    
    /**
     * Método auxiliar para determinar nível de estoque
     */
    private function getStockLevel($stock) {
        if ($stock <= 0) {
            return ['level' => 'empty', 'text' => 'Sem Estoque', 'color' => 'danger'];
        } elseif ($stock <= 10) {
            return ['level' => 'low', 'text' => 'Estoque Baixo', 'color' => 'warning'];
        } elseif ($stock <= 50) {
            return ['level' => 'medium', 'text' => 'Estoque Médio', 'color' => 'info'];
        } else {
            return ['level' => 'high', 'text' => 'Estoque Alto', 'color' => 'success'];
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