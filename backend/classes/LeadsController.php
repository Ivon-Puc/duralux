<?php
/**
 * LeadsController - Gerenciamento de Leads e Pipeline de Vendas
 * Duralux CRM v5.0 - Sistema de Leads Completo
 */

require_once 'BaseController.php';

class LeadsController extends BaseController {
    private $table = 'leads';
    
    public function __construct($database = null) {
        parent::__construct();
        if ($database) {
            $this->db = $database;
        }
        $this->ensureTables();
    }
    
    /**
     * Garantir que as tabelas existem
     */
    private function ensureTables() {
        try {
            // Verificar se a tabela existe e tem estrutura adequada
            $sql = "CREATE TABLE IF NOT EXISTS leads (
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
                assigned_to INTEGER,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                converted_at DATETIME,
                tags TEXT,
                notes TEXT,
                address TEXT,
                city TEXT,
                country TEXT DEFAULT 'Brasil',
                lead_score INTEGER DEFAULT 0,
                last_contact DATETIME,
                next_followup DATETIME
            )";
            
            $this->db->exec($sql);
            
            // Inserir dados de exemplo se não existirem
            $count = $this->db->query("SELECT COUNT(*) FROM leads")->fetchColumn();
            if ($count == 0) {
                $this->insertDemoData();
            }
        } catch (Exception $e) {
            error_log("Erro ao criar tabelas de leads: " . $e->getMessage());
        }
    }
    
    /**
     * Inserir dados de demonstração
     */
    private function insertDemoData() {
        $demoLeads = [
            [
                'name' => 'João Silva',
                'email' => 'joao.silva@techcorp.com',
                'phone' => '(11) 99999-1111',
                'company' => 'TechCorp Solutions',
                'position' => 'Gerente de TI',
                'source' => 'website',
                'status' => 'new',
                'priority' => 'high',
                'description' => 'Interessado em soluções de CRM para empresa de médio porte com 150+ colaboradores.',
                'value' => 25000.00,
                'city' => 'São Paulo',
                'lead_score' => 85
            ],
            [
                'name' => 'Maria Santos',
                'email' => 'maria.santos@innovate.com',
                'phone' => '(11) 88888-2222',
                'company' => 'Innovate Startup',
                'position' => 'CEO',
                'source' => 'referral',
                'status' => 'contacted',
                'priority' => 'high',
                'description' => 'Startup em crescimento buscando sistema integrado para gestão de vendas e marketing.',
                'value' => 15000.00,
                'city' => 'São Paulo',
                'lead_score' => 92
            ],
            [
                'name' => 'Pedro Oliveira',
                'email' => 'pedro@comercialmax.com',
                'phone' => '(21) 77777-3333',
                'company' => 'Comercial Max',
                'position' => 'Diretor Comercial',
                'source' => 'social_media',
                'status' => 'qualified',
                'priority' => 'medium',
                'description' => 'Rede de lojas precisando de automação para processos de vendas em múltiplas filiais.',
                'value' => 35000.00,
                'city' => 'Rio de Janeiro',
                'lead_score' => 78
            ],
            [
                'name' => 'Ana Costa',
                'email' => 'ana.costa@metalurgica.com',
                'phone' => '(11) 66666-4444',
                'company' => 'Metalúrgica Industrial',
                'position' => 'Gerente de Projetos',
                'source' => 'trade_show',
                'status' => 'proposal',
                'priority' => 'high',
                'description' => 'Implementação de CRM industrial para gestão de contratos e projetos complexos.',
                'value' => 75000.00,
                'city' => 'Guarulhos',
                'lead_score' => 88
            ],
            [
                'name' => 'Carlos Ferreira',
                'email' => 'carlos@consultoria.com',
                'phone' => '(85) 55555-5555',
                'company' => 'CF Consultoria',
                'position' => 'Sócio-proprietário',
                'source' => 'cold_call',
                'status' => 'converted',
                'priority' => 'medium',
                'description' => 'Consultoria especializada convertida - implementação de CRM para gestão de clientes.',
                'value' => 18000.00,
                'city' => 'Fortaleza',
                'lead_score' => 95
            ],
            [
                'name' => 'Luciana Mendes',
                'email' => 'luciana@ecommerce.com',
                'phone' => '(31) 44444-6666',
                'company' => 'E-commerce Plus',
                'position' => 'Gerente de Marketing',
                'source' => 'email_campaign',
                'status' => 'nurturing',
                'priority' => 'medium',
                'description' => 'E-commerce em expansão interessado em integração com plataformas de venda online.',
                'value' => 22000.00,
                'city' => 'Belo Horizonte',
                'lead_score' => 65
            ]
        ];
        
        $sql = "INSERT INTO leads (name, email, phone, company, position, source, status, priority, description, value, city, lead_score) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->db->prepare($sql);
        
        foreach ($demoLeads as $lead) {
            try {
                $stmt->execute([
                    $lead['name'],
                    $lead['email'],
                    $lead['phone'],
                    $lead['company'],
                    $lead['position'],
                    $lead['source'],
                    $lead['status'],
                    $lead['priority'],
                    $lead['description'],
                    $lead['value'],
                    $lead['city'],
                    $lead['lead_score']
                ]);
            } catch (Exception $e) {
                error_log("Erro ao inserir lead demo: " . $e->getMessage());
            }
        }
    }
    
    public function __construct() {
        parent::__construct();
        $this->logActivity('access', 'leads', null, 'Acesso ao módulo de leads');
    }
    
    /**
     * Listar todos os leads com filtros avançados
     */
    public function index() {
        try {
            // Parâmetros de filtros
            $status = $_GET['status'] ?? '';
            $pipeline = $_GET['pipeline'] ?? '';
            $source = $_GET['source'] ?? '';
            $search = $_GET['search'] ?? '';
            $page = (int)($_GET['page'] ?? 1);
            $limit = (int)($_GET['limit'] ?? 10);
            $offset = ($page - 1) * $limit;
            
            // Base da query com JOIN para usuário responsável
            $where = ["l.id IS NOT NULL"];
            $params = [];
            
            // Aplicar filtros
            if (!empty($status)) {
                $where[] = "l.status = :status";
                $params[':status'] = $status;
            }
            
            if (!empty($pipeline)) {
                $where[] = "l.pipeline_stage = :pipeline";
                $params[':pipeline'] = $pipeline;
            }
            
            if (!empty($source)) {
                $where[] = "l.source = :source";
                $params[':source'] = $source;
            }
            
            if (!empty($search)) {
                $where[] = "(l.name LIKE :search OR l.email LIKE :search OR l.company LIKE :search OR l.phone LIKE :search)";
                $params[':search'] = "%$search%";
            }
            
            $whereClause = "WHERE " . implode(" AND ", $where);
            
            // Query principal com dados do usuário responsável
            $sql = "SELECT l.*, u.name as user_name 
                    FROM {$this->table} l 
                    LEFT JOIN users u ON l.user_id = u.id 
                    $whereClause 
                    ORDER BY l.created_at DESC 
                    LIMIT :limit OFFSET :offset";
            
            $stmt = $this->db->prepare($sql);
            
            // Bind dos parâmetros
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            
            $stmt->execute();
            $leads = $stmt->fetchAll();
            
            // Contar total de registros
            $countSql = "SELECT COUNT(*) FROM {$this->table} l $whereClause";
            $countStmt = $this->db->prepare($countSql);
            
            foreach ($params as $key => $value) {
                $countStmt->bindValue($key, $value);
            }
            
            $countStmt->execute();
            $total = $countStmt->fetchColumn();
            
            // Estatísticas do pipeline
            $stats = $this->getPipelineStats();
            
            $this->jsonResponse([
                'leads' => $leads,
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => $total,
                    'pages' => ceil($total / $limit)
                ],
                'stats' => $stats
            ]);
            
        } catch (Exception $e) {
            $this->jsonResponse(['error' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Buscar um lead específico
     */
    public function show($id) {
        try {
            $sql = "SELECT l.*, u.name as user_name, c.name as customer_name 
                    FROM {$this->table} l 
                    LEFT JOIN users u ON l.user_id = u.id 
                    LEFT JOIN customers c ON l.customer_id = c.id 
                    WHERE l.id = :id";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':id' => $id]);
            $lead = $stmt->fetch();
            
            if ($lead) {
                $this->jsonResponse($lead);
            } else {
                $this->jsonResponse(['error' => 'Lead não encontrado'], 404);
            }
            
        } catch (Exception $e) {
            $this->jsonResponse(['error' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Criar novo lead
     */
    public function store() {
        try {
            $data = $this->getJsonInput();
            
            // Validações
            if (empty($data['name'])) {
                $this->jsonResponse(['error' => 'Nome é obrigatório'], 400);
                return;
            }
            
            if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                $this->jsonResponse(['error' => 'Email inválido'], 400);
                return;
            }
            
            // Verificar se email já existe (se fornecido)
            if (!empty($data['email'])) {
                $stmt = $this->db->prepare("SELECT id FROM {$this->table} WHERE email = :email");
                $stmt->execute([':email' => $data['email']]);
                if ($stmt->fetch()) {
                    $this->jsonResponse(['error' => 'Email já existe no sistema'], 400);
                    return;
                }
            }
            
            // Dados do novo lead
            $leadData = [
                'name' => $data['name'],
                'email' => $data['email'] ?? null,
                'phone' => $data['phone'] ?? null,
                'company' => $data['company'] ?? null,
                'position' => $data['position'] ?? null,
                'source' => $data['source'] ?? 'website',
                'status' => $data['status'] ?? 'new',
                'pipeline_stage' => $data['pipeline_stage'] ?? 'prospect',
                'value' => (float)($data['value'] ?? 0),
                'probability' => (int)($data['probability'] ?? 25),
                'notes' => $data['notes'] ?? null,
                'next_contact_date' => $data['next_contact_date'] ?? null,
                'user_id' => $this->getCurrentUserId(),
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            $columns = implode(', ', array_keys($leadData));
            $placeholders = ':' . implode(', :', array_keys($leadData));
            
            $sql = "INSERT INTO {$this->table} ($columns) VALUES ($placeholders)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute($leadData);
            
            $leadId = $this->db->lastInsertId();
            
            // Log da atividade
            $this->logActivity('create', 'leads', $leadId, "Lead '{$leadData['name']}' criado");
            
            // Buscar o lead criado
            $this->show($leadId);
            
        } catch (Exception $e) {
            $this->jsonResponse(['error' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Atualizar lead existente
     */
    public function update($id) {
        try {
            $data = $this->getJsonInput();
            
            // Verificar se lead existe
            $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE id = :id");
            $stmt->execute([':id' => $id]);
            $currentLead = $stmt->fetch();
            
            if (!$currentLead) {
                $this->jsonResponse(['error' => 'Lead não encontrado'], 404);
                return;
            }
            
            // Validações
            if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                $this->jsonResponse(['error' => 'Email inválido'], 400);
                return;
            }
            
            // Verificar email duplicado (exceto o próprio lead)
            if (!empty($data['email']) && $data['email'] != $currentLead['email']) {
                $stmt = $this->db->prepare("SELECT id FROM {$this->table} WHERE email = :email AND id != :id");
                $stmt->execute([':email' => $data['email'], ':id' => $id]);
                if ($stmt->fetch()) {
                    $this->jsonResponse(['error' => 'Email já existe no sistema'], 400);
                    return;
                }
            }
            
            // Preparar dados para atualização
            $updateData = [];
            $allowedFields = ['name', 'email', 'phone', 'company', 'position', 'source', 
                             'status', 'pipeline_stage', 'value', 'probability', 'notes', 'next_contact_date'];
            
            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    $updateData[$field] = $data[$field];
                }
            }
            
            if (empty($updateData)) {
                $this->jsonResponse(['error' => 'Nenhum campo válido para atualizar'], 400);
                return;
            }
            
            $updateData['updated_at'] = date('Y-m-d H:i:s');
            
            // Construir query de update
            $setClause = [];
            foreach ($updateData as $key => $value) {
                $setClause[] = "$key = :$key";
            }
            
            $sql = "UPDATE {$this->table} SET " . implode(', ', $setClause) . " WHERE id = :id";
            $updateData[':id'] = $id;
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($updateData);
            
            // Log da atividade
            $this->logActivity('update', 'leads', $id, "Lead '{$currentLead['name']}' atualizado");
            
            // Retornar lead atualizado
            $this->show($id);
            
        } catch (Exception $e) {
            $this->jsonResponse(['error' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Excluir lead
     */
    public function delete($id) {
        try {
            // Verificar se lead existe
            $stmt = $this->db->prepare("SELECT name FROM {$this->table} WHERE id = :id");
            $stmt->execute([':id' => $id]);
            $lead = $stmt->fetch();
            
            if (!$lead) {
                $this->jsonResponse(['error' => 'Lead não encontrado'], 404);
                return;
            }
            
            // Excluir lead
            $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE id = :id");
            $stmt->execute([':id' => $id]);
            
            // Log da atividade
            $this->logActivity('delete', 'leads', $id, "Lead '{$lead['name']}' excluído");
            
            $this->jsonResponse(['message' => 'Lead excluído com sucesso']);
            
        } catch (Exception $e) {
            $this->jsonResponse(['error' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Converter lead em cliente
     */
    public function convertToCustomer($id) {
        try {
            $this->db->beginTransaction();
            
            // Buscar dados do lead
            $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE id = :id");
            $stmt->execute([':id' => $id]);
            $lead = $stmt->fetch();
            
            if (!$lead) {
                $this->jsonResponse(['error' => 'Lead não encontrado'], 404);
                return;
            }
            
            if ($lead['converted']) {
                $this->jsonResponse(['error' => 'Lead já foi convertido'], 400);
                return;
            }
            
            // Criar cliente baseado no lead
            $customerData = [
                'name' => $lead['name'],
                'email' => $lead['email'],
                'phone' => $lead['phone'],
                'notes' => $lead['notes'] . "\n\nConvertido do lead em " . date('d/m/Y H:i'),
                'user_id' => $lead['user_id'],
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            $columns = implode(', ', array_keys($customerData));
            $placeholders = ':' . implode(', :', array_keys($customerData));
            
            $sql = "INSERT INTO customers ($columns) VALUES ($placeholders)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute($customerData);
            
            $customerId = $this->db->lastInsertId();
            
            // Marcar lead como convertido
            $stmt = $this->db->prepare("UPDATE {$this->table} SET converted = 1, customer_id = :customer_id, converted_at = :converted_at, status = 'converted' WHERE id = :id");
            $stmt->execute([
                ':customer_id' => $customerId,
                ':converted_at' => date('Y-m-d H:i:s'),
                ':id' => $id
            ]);
            
            $this->db->commit();
            
            // Log da atividade
            $this->logActivity('convert', 'leads', $id, "Lead '{$lead['name']}' convertido em cliente (ID: $customerId)");
            
            $this->jsonResponse([
                'message' => 'Lead convertido em cliente com sucesso',
                'customer_id' => $customerId
            ]);
            
        } catch (Exception $e) {
            $this->db->rollBack();
            $this->jsonResponse(['error' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Obter estatísticas do pipeline
     */
    private function getPipelineStats() {
        try {
            // Estatísticas gerais
            $stats = [
                'total_leads' => 0,
                'converted_leads' => 0,
                'conversion_rate' => 0,
                'total_value' => 0,
                'by_status' => [],
                'by_pipeline' => [],
                'by_source' => []
            ];
            
            // Total de leads
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM {$this->table}");
            $stmt->execute();
            $stats['total_leads'] = $stmt->fetchColumn();
            
            // Leads convertidos
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM {$this->table} WHERE converted = 1");
            $stmt->execute();
            $stats['converted_leads'] = $stmt->fetchColumn();
            
            // Taxa de conversão
            if ($stats['total_leads'] > 0) {
                $stats['conversion_rate'] = round(($stats['converted_leads'] / $stats['total_leads']) * 100, 2);
            }
            
            // Valor total do pipeline
            $stmt = $this->db->prepare("SELECT SUM(value) FROM {$this->table} WHERE converted = 0");
            $stmt->execute();
            $stats['total_value'] = (float)$stmt->fetchColumn();
            
            // Por status
            $stmt = $this->db->prepare("SELECT status, COUNT(*) as count FROM {$this->table} GROUP BY status");
            $stmt->execute();
            while ($row = $stmt->fetch()) {
                $stats['by_status'][$row['status']] = $row['count'];
            }
            
            // Por pipeline
            $stmt = $this->db->prepare("SELECT pipeline_stage, COUNT(*) as count FROM {$this->table} GROUP BY pipeline_stage");
            $stmt->execute();
            while ($row = $stmt->fetch()) {
                $stats['by_pipeline'][$row['pipeline_stage']] = $row['count'];
            }
            
            // Por fonte
            $stmt = $this->db->prepare("SELECT source, COUNT(*) as count FROM {$this->table} GROUP BY source");
            $stmt->execute();
            while ($row = $stmt->fetch()) {
                $stats['by_source'][$row['source']] = $row['count'];
            }
            
            return $stats;
            
        } catch (Exception $e) {
            return [];
        }
    }
    
    /**
     * Obter opções de configuração
     */
    public function getOptions() {
        $options = [
            'status' => [
                'new' => 'Novo',
                'contacted' => 'Contatado',
                'qualified' => 'Qualificado',
                'proposal' => 'Proposta Enviada',
                'negotiation' => 'Negociação',
                'converted' => 'Convertido',
                'lost' => 'Perdido'
            ],
            'pipeline_stages' => [
                'prospect' => 'Prospecção',
                'qualification' => 'Qualificação',
                'proposal' => 'Proposta',
                'negotiation' => 'Negociação',
                'closing' => 'Fechamento',
                'won' => 'Ganho',
                'lost' => 'Perdido'
            ],
            'sources' => [
                'website' => 'Site',
                'referral' => 'Indicação',
                'social_media' => 'Redes Sociais',
                'email_campaign' => 'Campanha Email',
                'cold_call' => 'Ligação Fria',
                'event' => 'Evento',
                'partner' => 'Parceiro',
                'other' => 'Outros'
            ]
        ];
        
        $this->jsonResponse($options);
    }
    
    /**
     * Dashboard do pipeline
     */
    public function pipeline() {
        try {
            $stats = $this->getPipelineStats();
            
            // Leads por etapa do pipeline
            $sql = "SELECT pipeline_stage, COUNT(*) as count, SUM(value) as total_value 
                    FROM {$this->table} 
                    WHERE converted = 0 
                    GROUP BY pipeline_stage 
                    ORDER BY 
                        CASE pipeline_stage
                            WHEN 'prospect' THEN 1
                            WHEN 'qualification' THEN 2
                            WHEN 'proposal' THEN 3
                            WHEN 'negotiation' THEN 4
                            WHEN 'closing' THEN 5
                            ELSE 6
                        END";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $pipeline = $stmt->fetchAll();
            
            // Leads recentes (últimos 7 dias)
            $sql = "SELECT l.*, u.name as user_name 
                    FROM {$this->table} l 
                    LEFT JOIN users u ON l.user_id = u.id 
                    WHERE l.created_at >= date('now', '-7 days') 
                    ORDER BY l.created_at DESC 
                    LIMIT 10";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $recent_leads = $stmt->fetchAll();
            
            $this->jsonResponse([
                'stats' => $stats,
                'pipeline' => $pipeline,
                'recent_leads' => $recent_leads
            ]);
            
        } catch (Exception $e) {
            $this->jsonResponse(['error' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Método principal para lidar com requisições
     */
    public function handleRequest() {
        $data = json_decode(file_get_contents('php://input'), true) ?: [];
        $action = $data['action'] ?? $_GET['action'] ?? '';
        $id = $data['id'] ?? $_GET['id'] ?? null;
        
        try {
            switch ($action) {
                case 'get_leads':
                    $this->index();
                    break;
                    
                case 'get_lead':
                    if (!$id) {
                        $this->jsonResponse(['error' => 'ID é obrigatório'], 400);
                        return;
                    }
                    $this->show($id);
                    break;
                    
                case 'create_lead':
                    $this->store();
                    break;
                    
                case 'update_lead':
                    if (!$id) {
                        $this->jsonResponse(['error' => 'ID é obrigatório'], 400);
                        return;
                    }
                    $this->update($id);
                    break;
                    
                case 'delete_lead':
                    if (!$id) {
                        $this->jsonResponse(['error' => 'ID é obrigatório'], 400);
                        return;
                    }
                    $this->delete($id);
                    break;
                    
                case 'convert_lead':
                    if (!$id) {
                        $this->jsonResponse(['error' => 'ID é obrigatório'], 400);
                        return;
                    }
                    $this->convertToCustomer($id);
                    break;
                    
                case 'get_leads_options':
                    $this->getOptions();
                    break;
                    
                case 'get_pipeline_stats':
                    $this->pipeline();
                    break;
                    
                case 'search_leads':
                    $this->index(); // Usa o mesmo método com filtros
                    break;
                    
                default:
                    $this->jsonResponse(['error' => 'Ação não encontrada'], 404);
            }
            
        } catch (Exception $e) {
            $this->logActivity('error', 'leads', $id, "Erro na ação '$action': " . $e->getMessage());
            $this->jsonResponse(['error' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Resposta JSON padronizada
     */
    private function jsonResponse($data, $status = 200) {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }
    
    /**
     * Obter dados JSON da entrada
     */
    private function getJsonInput() {
        return json_decode(file_get_contents('php://input'), true) ?: [];
    }
    
    /**
     * Obter ID do usuário atual
     */
    private function getCurrentUserId() {
        return $_SESSION['user_id'] ?? 1; // Default para usuário 1 se não estiver logado
    }
}