<?php
/**
 * DURALUX CRM - API de Leads v5.0
 * Endpoint específico para gerenciamento de leads
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Tratar requisições OPTIONS (CORS preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

try {
    // Inicializar banco SQLite
    $dbPath = __DIR__ . '/data/duralux.db';
    $db = new PDO('sqlite:' . $dbPath);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Criar tabela se não existir
    $createTable = "CREATE TABLE IF NOT EXISTS leads (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        email TEXT NOT NULL UNIQUE,
        phone TEXT,
        company TEXT,
        position TEXT,
        source TEXT DEFAULT 'website',
        status TEXT DEFAULT 'new',
        priority TEXT DEFAULT 'medium',
        description TEXT,
        value DECIMAL(10,2) DEFAULT 0.00,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        city TEXT,
        lead_score INTEGER DEFAULT 0
    )";
    
    $db->exec($createTable);
    
    // Verificar se há dados, se não inserir dados demo
    $count = $db->query("SELECT COUNT(*) FROM leads")->fetchColumn();
    if ($count == 0) {
        $demoLeads = [
            ['João Silva', 'joao.silva@techcorp.com', '(11) 99999-1111', 'TechCorp Solutions', 'Gerente de TI', 'website', 'new', 'high', 'Interessado em CRM para empresa média', 25000.00, 'São Paulo', 85],
            ['Maria Santos', 'maria.santos@innovate.com', '(11) 88888-2222', 'Innovate Startup', 'CEO', 'referral', 'contacted', 'high', 'Startup buscando sistema integrado', 15000.00, 'São Paulo', 92],
            ['Pedro Oliveira', 'pedro@comercialmax.com', '(21) 77777-3333', 'Comercial Max', 'Diretor Comercial', 'social_media', 'qualified', 'medium', 'Rede de lojas com automação', 35000.00, 'Rio de Janeiro', 78],
            ['Ana Costa', 'ana.costa@metalurgica.com', '(11) 66666-4444', 'Metalúrgica Industrial', 'Gerente de Projetos', 'trade_show', 'proposal', 'high', 'CRM industrial para projetos', 75000.00, 'Guarulhos', 88],
            ['Carlos Ferreira', 'carlos@consultoria.com', '(85) 55555-5555', 'CF Consultoria', 'Sócio', 'cold_call', 'converted', 'medium', 'Consultoria já convertida', 18000.00, 'Fortaleza', 95]
        ];
        
        $insertSql = "INSERT INTO leads (name, email, phone, company, position, source, status, priority, description, value, city, lead_score) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $db->prepare($insertSql);
        
        foreach ($demoLeads as $lead) {
            $stmt->execute($lead);
        }
    }
    
    // Processar requisição
    $method = $_SERVER['REQUEST_METHOD'];
    $action = $_GET['action'] ?? 'list';
    
    switch ($action) {
        case 'list':
            $page = max(1, (int)($_GET['page'] ?? 1));
            $limit = min(100, max(1, (int)($_GET['limit'] ?? 10)));
            $offset = ($page - 1) * $limit;
            
            // Filtros
            $where = [];
            $params = [];
            
            if (!empty($_GET['status'])) {
                $where[] = "status = ?";
                $params[] = $_GET['status'];
            }
            
            if (!empty($_GET['search'])) {
                $where[] = "(name LIKE ? OR email LIKE ? OR company LIKE ?)";
                $search = '%' . $_GET['search'] . '%';
                $params[] = $search;
                $params[] = $search;
                $params[] = $search;
            }
            
            $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';
            
            // Buscar leads
            $sql = "SELECT * FROM leads $whereClause ORDER BY created_at DESC LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;
            
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $leads = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Contar total
            $countSql = "SELECT COUNT(*) FROM leads $whereClause";
            $countStmt = $db->prepare($countSql);
            $countStmt->execute(array_slice($params, 0, -2));
            $total = $countStmt->fetchColumn();
            
            echo json_encode([
                'success' => true,
                'leads' => $leads,
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => (int)$total,
                    'pages' => ceil($total / $limit)
                ]
            ]);
            break;
            
        case 'get':
            $id = (int)($_GET['id'] ?? 0);
            if (!$id) {
                throw new Exception('ID não informado');
            }
            
            $stmt = $db->prepare("SELECT * FROM leads WHERE id = ?");
            $stmt->execute([$id]);
            $lead = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$lead) {
                throw new Exception('Lead não encontrado');
            }
            
            echo json_encode(['success' => true, 'lead' => $lead]);
            break;
            
        case 'create':
            if ($method !== 'POST') {
                throw new Exception('Método não permitido');
            }
            
            $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
            
            if (empty($input['name']) || empty($input['email'])) {
                throw new Exception('Nome e email são obrigatórios');
            }
            
            // Sanitizar
            $name = htmlspecialchars(strip_tags($input['name']), ENT_QUOTES, 'UTF-8');
            $email = filter_var($input['email'], FILTER_SANITIZE_EMAIL);
            
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception('Email inválido');
            }
            
            $sql = "INSERT INTO leads (name, email, phone, company, position, source, status, priority, description, value, city) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $db->prepare($sql);
            $stmt->execute([
                $name,
                $email,
                $input['phone'] ?? '',
                $input['company'] ?? '',
                $input['position'] ?? '',
                $input['source'] ?? 'website',
                $input['status'] ?? 'new',
                $input['priority'] ?? 'medium',
                $input['description'] ?? '',
                (float)($input['value'] ?? 0),
                $input['city'] ?? ''
            ]);
            
            $leadId = $db->lastInsertId();
            
            echo json_encode([
                'success' => true,
                'lead_id' => $leadId,
                'message' => 'Lead criado com sucesso!'
            ]);
            break;
            
        case 'update':
            $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
            $id = (int)($input['id'] ?? $_GET['id'] ?? 0);
            
            if (!$id) {
                throw new Exception('ID não informado');
            }
            
            // Verificar se existe
            $checkStmt = $db->prepare("SELECT id FROM leads WHERE id = ?");
            $checkStmt->execute([$id]);
            if (!$checkStmt->fetch()) {
                throw new Exception('Lead não encontrado');
            }
            
            $fields = [];
            $values = [];
            $allowedFields = ['name', 'email', 'phone', 'company', 'position', 'source', 'status', 'priority', 'description', 'value', 'city'];
            
            foreach ($allowedFields as $field) {
                if (isset($input[$field])) {
                    $fields[] = "$field = ?";
                    $values[] = $input[$field];
                }
            }
            
            if (empty($fields)) {
                throw new Exception('Nenhum campo para atualizar');
            }
            
            $fields[] = "updated_at = CURRENT_TIMESTAMP";
            $values[] = $id;
            
            $sql = "UPDATE leads SET " . implode(', ', $fields) . " WHERE id = ?";
            $stmt = $db->prepare($sql);
            $stmt->execute($values);
            
            echo json_encode([
                'success' => true,
                'message' => 'Lead atualizado com sucesso!'
            ]);
            break;
            
        case 'delete':
            $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
            $id = (int)($input['id'] ?? $_GET['id'] ?? 0);
            
            if (!$id) {
                throw new Exception('ID não informado');
            }
            
            $stmt = $db->prepare("DELETE FROM leads WHERE id = ?");
            $stmt->execute([$id]);
            
            if ($stmt->rowCount() > 0) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Lead excluído com sucesso!'
                ]);
            } else {
                throw new Exception('Lead não encontrado');
            }
            break;
            
        case 'stats':
            // Estatísticas gerais
            $stats = [];
            
            $stats['total'] = (int)$db->query("SELECT COUNT(*) FROM leads")->fetchColumn();
            
            // Por status
            $statusQuery = $db->query("SELECT status, COUNT(*) as count FROM leads GROUP BY status");
            $stats['by_status'] = [];
            while ($row = $statusQuery->fetch(PDO::FETCH_ASSOC)) {
                $stats['by_status'][$row['status']] = (int)$row['count'];
            }
            
            // Por prioridade
            $priorityQuery = $db->query("SELECT priority, COUNT(*) as count FROM leads GROUP BY priority");
            $stats['by_priority'] = [];
            while ($row = $priorityQuery->fetch(PDO::FETCH_ASSOC)) {
                $stats['by_priority'][$row['priority']] = (int)$row['count'];
            }
            
            // Valor total
            $stats['total_value'] = (float)($db->query("SELECT SUM(value) FROM leads")->fetchColumn() ?? 0);
            $stats['converted_value'] = (float)($db->query("SELECT SUM(value) FROM leads WHERE status = 'converted'")->fetchColumn() ?? 0);
            
            // Taxa de conversão
            $converted = (int)($stats['by_status']['converted'] ?? 0);
            $stats['conversion_rate'] = $stats['total'] > 0 ? round(($converted / $stats['total']) * 100, 2) : 0;
            
            echo json_encode(['success' => true, 'stats' => $stats]);
            break;
            
        default:
            throw new Exception('Ação não reconhecida: ' . $action);
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>