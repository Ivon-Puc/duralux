<?php
/**
 * Duralux CRM - OrderController
 * 
 * Sistema completo de gestão de pedidos e faturas
 * Funcionalidades: CRUD de pedidos, itens, faturas, pagamentos
 * 
 * @version 1.3.0
 * @author Ivon Martins
 * @created 2025-01-03
 */

require_once __DIR__ . '/BaseController.php';

class OrderController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->requireAuth(); // Proteção de autenticação
    }

    /**
     * Handle action-based requests
     */
    public function handleRequest()
    {
        $action = $this->getRequestData()['action'] ?? '';
        
        switch ($action) {
            case 'get_orders':
                return $this->index();
            case 'get_order':
                return $this->view();
            case 'create_order':
                return $this->create();
            case 'update_order':
                return $this->update();
            case 'delete_order':
                return $this->delete();
            case 'get_order_stats':
                return $this->statistics();
            case 'generate_invoice':
                return $this->generateInvoice();
            default:
                $this->jsonResponse(['success' => false, 'message' => 'Ação não reconhecida'], 400);
        }
    }

    /**
     * Lista todos os pedidos com filtros avançados
     */
    public function index()
    {
        try {
            // Parâmetros de busca e paginação
            $search = $_GET['search'] ?? '';
            $status = $_GET['status'] ?? '';
            $payment_status = $_GET['payment_status'] ?? '';
            $customer_id = $_GET['customer_id'] ?? '';
            $start_date = $_GET['start_date'] ?? '';
            $end_date = $_GET['end_date'] ?? '';
            $page = max(1, intval($_GET['page'] ?? 1));
            $limit = min(100, max(10, intval($_GET['limit'] ?? 20)));
            $offset = ($page - 1) * $limit;

            // Construção da query base
            $whereConditions = ['1=1'];
            $params = [];

            // Filtro por busca (número do pedido ou cliente)
            if (!empty($search)) {
                $whereConditions[] = "(o.order_number LIKE ? OR c.name LIKE ? OR c.email LIKE ?)";
                $params[] = "%$search%";
                $params[] = "%$search%";
                $params[] = "%$search%";
            }

            // Filtro por status do pedido
            if (!empty($status)) {
                $whereConditions[] = "o.status = ?";
                $params[] = $status;
            }

            // Filtro por status de pagamento
            if (!empty($payment_status)) {
                $whereConditions[] = "o.payment_status = ?";
                $params[] = $payment_status;
            }

            // Filtro por cliente
            if (!empty($customer_id)) {
                $whereConditions[] = "o.customer_id = ?";
                $params[] = $customer_id;
            }

            // Filtro por data
            if (!empty($start_date)) {
                $whereConditions[] = "DATE(o.created_at) >= ?";
                $params[] = $start_date;
            }

            if (!empty($end_date)) {
                $whereConditions[] = "DATE(o.created_at) <= ?";
                $params[] = $end_date;
            }

            $whereClause = implode(' AND ', $whereConditions);

            // Query principal com JOINs
            $sql = "
                SELECT 
                    o.*,
                    c.name as customer_name,
                    c.email as customer_email,
                    c.phone as customer_phone,
                    COUNT(oi.id) as total_items,
                    COALESCE(SUM(oi.quantity * oi.price), 0) as calculated_total
                FROM orders o
                LEFT JOIN customers c ON o.customer_id = c.id
                LEFT JOIN order_items oi ON o.id = oi.order_id
                WHERE $whereClause
                GROUP BY o.id
                ORDER BY o.created_at DESC
                LIMIT ? OFFSET ?
            ";

            $params[] = $limit;
            $params[] = $offset;

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Query para total de registros
            $countSql = "
                SELECT COUNT(DISTINCT o.id) as total
                FROM orders o
                LEFT JOIN customers c ON o.customer_id = c.id
                WHERE $whereClause
            ";
            
            $countParams = array_slice($params, 0, -2); // Remove limit e offset
            $countStmt = $this->db->prepare($countSql);
            $countStmt->execute($countParams);
            $totalRecords = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];

            // Formata dados dos pedidos
            foreach ($orders as &$order) {
                $order['total_amount'] = number_format((float)$order['total_amount'], 2, '.', '');
                $order['calculated_total'] = number_format((float)$order['calculated_total'], 2, '.', '');
                $order['created_at_formatted'] = date('d/m/Y H:i', strtotime($order['created_at']));
                
                // Carrega itens do pedido
                $order['items'] = $this->getOrderItems($order['id']);
            }

            $this->jsonResponse([
                'success' => true,
                'data' => $orders,
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $limit,
                    'total' => (int)$totalRecords,
                    'total_pages' => ceil($totalRecords / $limit),
                    'has_more' => ($page * $limit) < $totalRecords
                ]
            ]);

        } catch (Exception $e) {
            $this->logActivity('orders', 'index_error', null, "Erro ao listar pedidos: " . $e->getMessage());
            $this->jsonResponse(['success' => false, 'message' => 'Erro interno do servidor'], 500);
        }
    }

    /**
     * Cria um novo pedido
     */
    public function create()
    {
        try {
            $data = $this->getJsonInput();

            // Validação obrigatória
            $required = ['customer_id', 'items'];
            foreach ($required as $field) {
                if (!isset($data[$field]) || empty($data[$field])) {
                    $this->jsonResponse(['success' => false, 'message' => "Campo $field é obrigatório"], 400);
                    return;
                }
            }

            // Validação do cliente
            if (!$this->customerExists($data['customer_id'])) {
                $this->jsonResponse(['success' => false, 'message' => 'Cliente não encontrado'], 404);
                return;
            }

            // Validação dos itens
            if (!is_array($data['items']) || empty($data['items'])) {
                $this->jsonResponse(['success' => false, 'message' => 'Pelo menos um item é obrigatório'], 400);
                return;
            }

            // Geração do número do pedido
            $orderNumber = $this->generateOrderNumber();

            // Início da transação
            $this->db->beginTransaction();

            try {
                // Calcula total dos itens
                $totalAmount = 0;
                $validatedItems = [];

                foreach ($data['items'] as $item) {
                    if (!isset($item['product_id'], $item['quantity'], $item['price'])) {
                        throw new Exception("Item inválido: product_id, quantity e price são obrigatórios");
                    }

                    $quantity = max(1, (int)$item['quantity']);
                    $price = max(0, (float)$item['price']);
                    $itemTotal = $quantity * $price;
                    $totalAmount += $itemTotal;

                    $validatedItems[] = [
                        'product_id' => (int)$item['product_id'],
                        'quantity' => $quantity,
                        'price' => $price,
                        'total' => $itemTotal,
                        'description' => $item['description'] ?? ''
                    ];
                }

                // Insere o pedido
                $sql = "
                    INSERT INTO orders (
                        order_number, customer_id, status, payment_status,
                        total_amount, notes, created_at, updated_at
                    ) VALUES (?, ?, ?, ?, ?, ?, datetime('now'), datetime('now'))
                ";

                $stmt = $this->db->prepare($sql);
                $stmt->execute([
                    $orderNumber,
                    $data['customer_id'],
                    $data['status'] ?? 'pending',
                    $data['payment_status'] ?? 'unpaid',
                    $totalAmount,
                    $data['notes'] ?? ''
                ]);

                $orderId = $this->db->lastInsertId();

                // Insere os itens do pedido
                foreach ($validatedItems as $item) {
                    $itemSql = "
                        INSERT INTO order_items (
                            order_id, product_id, quantity, price, total, description, created_at
                        ) VALUES (?, ?, ?, ?, ?, ?, datetime('now'))
                    ";
                    
                    $itemStmt = $this->db->prepare($itemSql);
                    $itemStmt->execute([
                        $orderId,
                        $item['product_id'],
                        $item['quantity'],
                        $item['price'],
                        $item['total'],
                        $item['description']
                    ]);
                }

                $this->db->commit();

                // Carrega o pedido criado
                $order = $this->getOrderById($orderId);

                $this->logActivity('orders', 'create', $orderId, "Pedido $orderNumber criado com sucesso");

                $this->jsonResponse([
                    'success' => true,
                    'message' => 'Pedido criado com sucesso',
                    'data' => $order
                ], 201);

            } catch (Exception $e) {
                $this->db->rollback();
                throw $e;
            }

        } catch (Exception $e) {
            $this->logActivity('orders', 'create_error', null, "Erro ao criar pedido: " . $e->getMessage());
            $this->jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Visualiza um pedido específico
     */
    public function view()
    {
        try {
            $id = $this->getUrlParam(2);
            
            if (!$id) {
                $this->jsonResponse(['success' => false, 'message' => 'ID do pedido não fornecido'], 400);
                return;
            }

            $order = $this->getOrderById($id);

            if (!$order) {
                $this->jsonResponse(['success' => false, 'message' => 'Pedido não encontrado'], 404);
                return;
            }

            $this->jsonResponse(['success' => true, 'data' => $order]);

        } catch (Exception $e) {
            $this->logActivity('orders', 'view_error', $id ?? null, "Erro ao visualizar pedido: " . $e->getMessage());
            $this->jsonResponse(['success' => false, 'message' => 'Erro interno do servidor'], 500);
        }
    }

    /**
     * Atualiza um pedido
     */
    public function update()
    {
        try {
            $id = $this->getUrlParam(2);
            $data = $this->getJsonInput();

            if (!$id) {
                $this->jsonResponse(['success' => false, 'message' => 'ID do pedido não fornecido'], 400);
                return;
            }

            // Verifica se o pedido existe
            $existingOrder = $this->getOrderById($id);
            if (!$existingOrder) {
                $this->jsonResponse(['success' => false, 'message' => 'Pedido não encontrado'], 404);
                return;
            }

            // Campos atualizáveis
            $allowedFields = ['status', 'payment_status', 'notes'];
            $updateFields = [];
            $params = [];

            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    $updateFields[] = "$field = ?";
                    $params[] = $data[$field];
                }
            }

            if (empty($updateFields)) {
                $this->jsonResponse(['success' => false, 'message' => 'Nenhum campo válido para atualizar'], 400);
                return;
            }

            // Início da transação
            $this->db->beginTransaction();

            try {
                // Atualiza o pedido
                $updateFields[] = "updated_at = datetime('now')";
                $params[] = $id;

                $sql = "UPDATE orders SET " . implode(', ', $updateFields) . " WHERE id = ?";
                $stmt = $this->db->prepare($sql);
                $stmt->execute($params);

                // Atualiza itens se fornecidos
                if (isset($data['items']) && is_array($data['items'])) {
                    // Remove itens existentes
                    $deleteItemsSql = "DELETE FROM order_items WHERE order_id = ?";
                    $deleteStmt = $this->db->prepare($deleteItemsSql);
                    $deleteStmt->execute([$id]);

                    // Recalcula total
                    $totalAmount = 0;

                    // Insere novos itens
                    foreach ($data['items'] as $item) {
                        if (!isset($item['product_id'], $item['quantity'], $item['price'])) {
                            throw new Exception("Item inválido: product_id, quantity e price são obrigatórios");
                        }

                        $quantity = max(1, (int)$item['quantity']);
                        $price = max(0, (float)$item['price']);
                        $itemTotal = $quantity * $price;
                        $totalAmount += $itemTotal;

                        $itemSql = "
                            INSERT INTO order_items (
                                order_id, product_id, quantity, price, total, description, created_at
                            ) VALUES (?, ?, ?, ?, ?, ?, datetime('now'))
                        ";
                        
                        $itemStmt = $this->db->prepare($itemSql);
                        $itemStmt->execute([
                            $id,
                            $item['product_id'],
                            $quantity,
                            $price,
                            $itemTotal,
                            $item['description'] ?? ''
                        ]);
                    }

                    // Atualiza total do pedido
                    $updateTotalSql = "UPDATE orders SET total_amount = ? WHERE id = ?";
                    $updateTotalStmt = $this->db->prepare($updateTotalSql);
                    $updateTotalStmt->execute([$totalAmount, $id]);
                }

                $this->db->commit();

                // Carrega pedido atualizado
                $updatedOrder = $this->getOrderById($id);

                $this->logActivity('orders', 'update', $id, "Pedido {$existingOrder['order_number']} atualizado");

                $this->jsonResponse([
                    'success' => true,
                    'message' => 'Pedido atualizado com sucesso',
                    'data' => $updatedOrder
                ]);

            } catch (Exception $e) {
                $this->db->rollback();
                throw $e;
            }

        } catch (Exception $e) {
            $this->logActivity('orders', 'update_error', $id ?? null, "Erro ao atualizar pedido: " . $e->getMessage());
            $this->jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Exclui um pedido
     */
    public function delete()
    {
        try {
            $id = $this->getUrlParam(2);

            if (!$id) {
                $this->jsonResponse(['success' => false, 'message' => 'ID do pedido não fornecido'], 400);
                return;
            }

            // Verifica se o pedido existe
            $order = $this->getOrderById($id);
            if (!$order) {
                $this->jsonResponse(['success' => false, 'message' => 'Pedido não encontrado'], 404);
                return;
            }

            // Início da transação
            $this->db->beginTransaction();

            try {
                // Remove itens do pedido
                $deleteItemsSql = "DELETE FROM order_items WHERE order_id = ?";
                $deleteItemsStmt = $this->db->prepare($deleteItemsSql);
                $deleteItemsStmt->execute([$id]);

                // Remove o pedido
                $deleteOrderSql = "DELETE FROM orders WHERE id = ?";
                $deleteOrderStmt = $this->db->prepare($deleteOrderSql);
                $deleteOrderStmt->execute([$id]);

                $this->db->commit();

                $this->logActivity('orders', 'delete', $id, "Pedido {$order['order_number']} excluído");

                $this->jsonResponse([
                    'success' => true,
                    'message' => 'Pedido excluído com sucesso'
                ]);

            } catch (Exception $e) {
                $this->db->rollback();
                throw $e;
            }

        } catch (Exception $e) {
            $this->logActivity('orders', 'delete_error', $id ?? null, "Erro ao excluir pedido: " . $e->getMessage());
            $this->jsonResponse(['success' => false, 'message' => 'Erro interno do servidor'], 500);
        }
    }

    /**
     * Estatísticas de pedidos
     */
    public function statistics()
    {
        try {
            // Pedidos por status
            $statusSql = "
                SELECT 
                    status,
                    COUNT(*) as count,
                    COALESCE(SUM(total_amount), 0) as total_value
                FROM orders 
                GROUP BY status
            ";
            $statusStmt = $this->db->prepare($statusSql);
            $statusStmt->execute();
            $statusStats = $statusStmt->fetchAll(PDO::FETCH_ASSOC);

            // Pedidos por status de pagamento
            $paymentSql = "
                SELECT 
                    payment_status,
                    COUNT(*) as count,
                    COALESCE(SUM(total_amount), 0) as total_value
                FROM orders 
                GROUP BY payment_status
            ";
            $paymentStmt = $this->db->prepare($paymentSql);
            $paymentStmt->execute();
            $paymentStats = $paymentStmt->fetchAll(PDO::FETCH_ASSOC);

            // Estatísticas gerais
            $generalSql = "
                SELECT 
                    COUNT(*) as total_orders,
                    COALESCE(SUM(total_amount), 0) as total_revenue,
                    COALESCE(AVG(total_amount), 0) as average_order_value,
                    COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_orders,
                    COUNT(CASE WHEN payment_status = 'paid' THEN 1 END) as paid_orders
                FROM orders
            ";
            $generalStmt = $this->db->prepare($generalSql);
            $generalStmt->execute();
            $generalStats = $generalStmt->fetch(PDO::FETCH_ASSOC);

            // Vendas por mês (últimos 12 meses)
            $monthlySql = "
                SELECT 
                    strftime('%Y-%m', created_at) as month,
                    COUNT(*) as orders_count,
                    COALESCE(SUM(total_amount), 0) as total_value
                FROM orders 
                WHERE created_at >= datetime('now', '-12 months')
                GROUP BY strftime('%Y-%m', created_at)
                ORDER BY month DESC
            ";
            $monthlyStmt = $this->db->prepare($monthlySql);
            $monthlyStmt->execute();
            $monthlyStats = $monthlyStmt->fetchAll(PDO::FETCH_ASSOC);

            // Top produtos mais vendidos
            $topProductsSql = "
                SELECT 
                    p.name,
                    SUM(oi.quantity) as total_quantity,
                    SUM(oi.total) as total_revenue,
                    COUNT(DISTINCT oi.order_id) as orders_count
                FROM order_items oi
                LEFT JOIN products p ON oi.product_id = p.id
                GROUP BY oi.product_id
                ORDER BY total_quantity DESC
                LIMIT 10
            ";
            $topProductsStmt = $this->db->prepare($topProductsSql);
            $topProductsStmt->execute();
            $topProducts = $topProductsStmt->fetchAll(PDO::FETCH_ASSOC);

            $this->jsonResponse([
                'success' => true,
                'data' => [
                    'general' => $generalStats,
                    'by_status' => $statusStats,
                    'by_payment_status' => $paymentStats,
                    'monthly_sales' => $monthlyStats,
                    'top_products' => $topProducts
                ]
            ]);

        } catch (Exception $e) {
            $this->logActivity('orders', 'statistics_error', null, "Erro ao buscar estatísticas: " . $e->getMessage());
            $this->jsonResponse(['success' => false, 'message' => 'Erro interno do servidor'], 500);
        }
    }

    /**
     * Gera fatura para um pedido
     */
    public function generateInvoice()
    {
        try {
            $id = $this->getUrlParam(2);

            if (!$id) {
                $this->jsonResponse(['success' => false, 'message' => 'ID do pedido não fornecido'], 400);
                return;
            }

            $order = $this->getOrderById($id);
            if (!$order) {
                $this->jsonResponse(['success' => false, 'message' => 'Pedido não encontrado'], 404);
                return;
            }

            // Gera número da fatura
            $invoiceNumber = $this->generateInvoiceNumber();

            // Dados da fatura
            $invoice = [
                'invoice_number' => $invoiceNumber,
                'order_id' => $id,
                'order_number' => $order['order_number'],
                'customer' => [
                    'name' => $order['customer_name'],
                    'email' => $order['customer_email'],
                    'phone' => $order['customer_phone']
                ],
                'items' => $order['items'],
                'total_amount' => $order['total_amount'],
                'status' => 'generated',
                'generated_at' => date('Y-m-d H:i:s'),
                'due_date' => date('Y-m-d', strtotime('+30 days'))
            ];

            $this->logActivity('orders', 'invoice_generated', $id, "Fatura $invoiceNumber gerada para pedido {$order['order_number']}");

            $this->jsonResponse([
                'success' => true,
                'message' => 'Fatura gerada com sucesso',
                'data' => $invoice
            ]);

        } catch (Exception $e) {
            $this->logActivity('orders', 'invoice_error', $id ?? null, "Erro ao gerar fatura: " . $e->getMessage());
            $this->jsonResponse(['success' => false, 'message' => 'Erro interno do servidor'], 500);
        }
    }

    // ==================== MÉTODOS AUXILIARES ====================

    /**
     * Busca pedido por ID com todos os relacionamentos
     */
    private function getOrderById($id)
    {
        $sql = "
            SELECT 
                o.*,
                c.name as customer_name,
                c.email as customer_email,
                c.phone as customer_phone,
                c.address as customer_address
            FROM orders o
            LEFT JOIN customers c ON o.customer_id = c.id
            WHERE o.id = ?
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($order) {
            $order['total_amount'] = number_format((float)$order['total_amount'], 2, '.', '');
            $order['created_at_formatted'] = date('d/m/Y H:i', strtotime($order['created_at']));
            $order['items'] = $this->getOrderItems($id);
        }

        return $order;
    }

    /**
     * Busca itens de um pedido
     */
    private function getOrderItems($orderId)
    {
        $sql = "
            SELECT 
                oi.*,
                p.name as product_name,
                p.sku as product_sku
            FROM order_items oi
            LEFT JOIN products p ON oi.product_id = p.id
            WHERE oi.order_id = ?
            ORDER BY oi.id
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$orderId]);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($items as &$item) {
            $item['price'] = number_format((float)$item['price'], 2, '.', '');
            $item['total'] = number_format((float)$item['total'], 2, '.', '');
        }

        return $items;
    }

    /**
     * Verifica se cliente existe
     */
    private function customerExists($customerId)
    {
        $sql = "SELECT COUNT(*) FROM customers WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$customerId]);
        return $stmt->fetchColumn() > 0;
    }

    /**
     * Gera número único do pedido
     */
    private function generateOrderNumber()
    {
        $prefix = 'ORD';
        $date = date('Ymd');
        
        // Busca último número do dia
        $sql = "SELECT MAX(CAST(SUBSTR(order_number, 12) AS INTEGER)) as last_num 
                FROM orders 
                WHERE order_number LIKE ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(["$prefix$date%"]);
        $lastNum = $stmt->fetchColumn() ?: 0;
        
        $nextNum = $lastNum + 1;
        return $prefix . $date . str_pad($nextNum, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Gera número único da fatura
     */
    private function generateInvoiceNumber()
    {
        $prefix = 'INV';
        $date = date('Ymd');
        $random = rand(1000, 9999);
        return $prefix . $date . $random;
    }
}