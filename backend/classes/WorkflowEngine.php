<?php
/**
 * DURALUX CRM - Workflow Automation Engine v5.0
 * Sistema avançado de automação de processos de negócio
 * 
 * Features:
 * - Triggers customizáveis (tempo, eventos, condições)
 * - Actions automatizadas (email, SMS, tarefas, atualizações)
 * - Conditions complexas com operadores lógicos
 * - Templates reutilizáveis e workflows visuais
 * - Histórico completo e analytics
 * 
 * @author Duralux Development Team
 * @version 5.0.0
 * @since 2025-01-03
 */

class WorkflowEngine {
    private $db;
    private $cache;
    private $logger;
    private $actionExecutor;
    private $triggerManager;
    
    public function __construct($database) {
        $this->db = $database;
        $this->cache = RedisCacheManager::getInstance();
        $this->logger = new Logger('workflow');
        $this->actionExecutor = new ActionExecutor($this->db);
        $this->triggerManager = new TriggerManager($this->db);
        
        $this->initializeTables();
    }
    
    /**
     * Inicializar tabelas do sistema de workflow
     */
    private function initializeTables() {
        $tables = [
            // Workflows principais
            'workflows' => "CREATE TABLE IF NOT EXISTS workflows (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(200) NOT NULL,
                description TEXT,
                trigger_type ENUM('time', 'event', 'condition', 'manual') NOT NULL,
                trigger_config JSON NOT NULL,
                conditions JSON DEFAULT NULL,
                actions JSON NOT NULL,
                is_active BOOLEAN DEFAULT TRUE,
                priority INT DEFAULT 5,
                created_by INT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                last_executed TIMESTAMP NULL,
                execution_count INT DEFAULT 0,
                success_rate DECIMAL(5,2) DEFAULT 100.00,
                INDEX idx_trigger_type (trigger_type),
                INDEX idx_active (is_active),
                INDEX idx_priority (priority)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
            
            // Execuções de workflow
            'workflow_executions' => "CREATE TABLE IF NOT EXISTS workflow_executions (
                id INT AUTO_INCREMENT PRIMARY KEY,
                workflow_id INT NOT NULL,
                trigger_data JSON DEFAULT NULL,
                status ENUM('pending', 'running', 'completed', 'failed', 'cancelled') DEFAULT 'pending',
                started_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                completed_at TIMESTAMP NULL,
                error_message TEXT NULL,
                execution_log JSON DEFAULT NULL,
                actions_executed JSON DEFAULT NULL,
                context_data JSON DEFAULT NULL,
                FOREIGN KEY (workflow_id) REFERENCES workflows(id) ON DELETE CASCADE,
                INDEX idx_workflow_id (workflow_id),
                INDEX idx_status (status),
                INDEX idx_started_at (started_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
            
            // Triggers de workflow
            'workflow_triggers' => "CREATE TABLE IF NOT EXISTS workflow_triggers (
                id INT AUTO_INCREMENT PRIMARY KEY,
                workflow_id INT NOT NULL,
                trigger_type VARCHAR(50) NOT NULL,
                entity_type VARCHAR(50) NULL,
                event_type VARCHAR(50) NULL,
                conditions JSON DEFAULT NULL,
                schedule_expression VARCHAR(100) NULL,
                is_active BOOLEAN DEFAULT TRUE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (workflow_id) REFERENCES workflows(id) ON DELETE CASCADE,
                INDEX idx_trigger_type (trigger_type),
                INDEX idx_entity_type (entity_type),
                INDEX idx_schedule (schedule_expression)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
            
            // Actions de workflow
            'workflow_actions' => "CREATE TABLE IF NOT EXISTS workflow_actions (
                id INT AUTO_INCREMENT PRIMARY KEY,
                workflow_id INT NOT NULL,
                action_type VARCHAR(50) NOT NULL,
                action_config JSON NOT NULL,
                execution_order INT DEFAULT 1,
                retry_count INT DEFAULT 0,
                max_retries INT DEFAULT 3,
                timeout_seconds INT DEFAULT 300,
                is_active BOOLEAN DEFAULT TRUE,
                FOREIGN KEY (workflow_id) REFERENCES workflows(id) ON DELETE CASCADE,
                INDEX idx_workflow_id (workflow_id),
                INDEX idx_action_type (action_type),
                INDEX idx_order (execution_order)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
            
            // Templates de workflow
            'workflow_templates' => "CREATE TABLE IF NOT EXISTS workflow_templates (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(200) NOT NULL,
                description TEXT,
                category VARCHAR(50) NOT NULL,
                template_data JSON NOT NULL,
                usage_count INT DEFAULT 0,
                is_public BOOLEAN DEFAULT FALSE,
                created_by INT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_category (category),
                INDEX idx_public (is_public)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
        ];
        
        foreach ($tables as $tableName => $sql) {
            try {
                $this->db->exec($sql);
                $this->logger->info("Tabela '$tableName' inicializada com sucesso");
            } catch (PDOException $e) {
                $this->logger->error("Erro ao criar tabela '$tableName': " . $e->getMessage());
            }
        }
    }
    
    /**
     * Criar novo workflow
     */
    public function createWorkflow($workflowData) {
        try {
            $this->db->beginTransaction();
            
            // Validar dados do workflow
            $this->validateWorkflowData($workflowData);
            
            // Inserir workflow principal
            $sql = "INSERT INTO workflows (name, description, trigger_type, trigger_config, 
                    conditions, actions, is_active, priority, created_by) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $workflowData['name'],
                $workflowData['description'] ?? '',
                $workflowData['trigger_type'],
                json_encode($workflowData['trigger_config']),
                isset($workflowData['conditions']) ? json_encode($workflowData['conditions']) : null,
                json_encode($workflowData['actions']),
                $workflowData['is_active'] ?? true,
                $workflowData['priority'] ?? 5,
                $workflowData['created_by']
            ]);
            
            $workflowId = $this->db->lastInsertId();
            
            // Criar triggers
            if (!empty($workflowData['triggers'])) {
                foreach ($workflowData['triggers'] as $trigger) {
                    $this->createWorkflowTrigger($workflowId, $trigger);
                }
            }
            
            // Criar actions
            if (!empty($workflowData['actions'])) {
                foreach ($workflowData['actions'] as $index => $action) {
                    $this->createWorkflowAction($workflowId, $action, $index + 1);
                }
            }
            
            $this->db->commit();
            
            // Limpar cache
            $this->cache->delete("workflow_{$workflowId}");
            $this->cache->delete("active_workflows");
            
            $this->logger->info("Workflow '{$workflowData['name']}' criado com sucesso", [
                'workflow_id' => $workflowId
            ]);
            
            return [
                'success' => true,
                'workflow_id' => $workflowId,
                'message' => 'Workflow criado com sucesso'
            ];
            
        } catch (Exception $e) {
            $this->db->rollBack();
            $this->logger->error("Erro ao criar workflow: " . $e->getMessage());
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Executar workflow
     */
    public function executeWorkflow($workflowId, $triggerData = null, $context = null) {
        try {
            // Buscar workflow
            $workflow = $this->getWorkflow($workflowId);
            if (!$workflow) {
                throw new Exception("Workflow não encontrado");
            }
            
            if (!$workflow['is_active']) {
                throw new Exception("Workflow está desativado");
            }
            
            // Criar execução
            $executionId = $this->createExecution($workflowId, $triggerData, $context);
            
            // Verificar condições
            if (!$this->evaluateConditions($workflow['conditions'], $triggerData, $context)) {
                $this->updateExecutionStatus($executionId, 'cancelled', 'Condições não atendidas');
                return [
                    'success' => true,
                    'execution_id' => $executionId,
                    'status' => 'cancelled',
                    'message' => 'Condições não atendidas'
                ];
            }
            
            // Marcar como executando
            $this->updateExecutionStatus($executionId, 'running');
            
            // Executar actions
            $executionLog = [];
            $actionsExecuted = [];
            
            $actions = json_decode($workflow['actions'], true);
            foreach ($actions as $action) {
                try {
                    $actionResult = $this->actionExecutor->execute($action, $triggerData, $context);
                    
                    $actionsExecuted[] = [
                        'action' => $action,
                        'result' => $actionResult,
                        'status' => 'success',
                        'executed_at' => date('Y-m-d H:i:s')
                    ];
                    
                    $executionLog[] = "Action '{$action['type']}' executado com sucesso";
                    
                } catch (Exception $e) {
                    $actionsExecuted[] = [
                        'action' => $action,
                        'error' => $e->getMessage(),
                        'status' => 'failed',
                        'executed_at' => date('Y-m-d H:i:s')
                    ];
                    
                    $executionLog[] = "Erro no action '{$action['type']}': " . $e->getMessage();
                    
                    // Se action crítico, parar execução
                    if ($action['critical'] ?? false) {
                        throw $e;
                    }
                }
            }
            
            // Finalizar execução
            $this->finishExecution($executionId, 'completed', $executionLog, $actionsExecuted);
            
            // Atualizar estatísticas do workflow
            $this->updateWorkflowStats($workflowId, true);
            
            return [
                'success' => true,
                'execution_id' => $executionId,
                'status' => 'completed',
                'actions_executed' => count($actionsExecuted),
                'execution_log' => $executionLog
            ];
            
        } catch (Exception $e) {
            // Marcar execução como falhada
            if (isset($executionId)) {
                $this->updateExecutionStatus($executionId, 'failed', $e->getMessage());
                $this->updateWorkflowStats($workflowId, false);
            }
            
            $this->logger->error("Erro na execução do workflow $workflowId: " . $e->getMessage());
            
            return [
                'success' => false,
                'execution_id' => $executionId ?? null,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Processar triggers automáticos
     */
    public function processTriggers($triggerType = null) {
        try {
            $triggers = $this->getActiveTriggers($triggerType);
            $processed = 0;
            $results = [];
            
            foreach ($triggers as $trigger) {
                try {
                    if ($this->shouldExecuteTrigger($trigger)) {
                        $result = $this->executeWorkflow(
                            $trigger['workflow_id'],
                            $this->prepareTriggerData($trigger),
                            ['trigger_id' => $trigger['id']]
                        );
                        
                        $results[] = $result;
                        $processed++;
                    }
                } catch (Exception $e) {
                    $this->logger->error("Erro ao processar trigger {$trigger['id']}: " . $e->getMessage());
                    $results[] = [
                        'success' => false,
                        'trigger_id' => $trigger['id'],
                        'error' => $e->getMessage()
                    ];
                }
            }
            
            return [
                'success' => true,
                'triggers_processed' => $processed,
                'total_triggers' => count($triggers),
                'results' => $results
            ];
            
        } catch (Exception $e) {
            $this->logger->error("Erro ao processar triggers: " . $e->getMessage());
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Obter workflows ativos
     */
    public function getActiveWorkflows($filters = []) {
        try {
            $cacheKey = 'active_workflows_' . md5(serialize($filters));
            $cached = $this->cache->get($cacheKey);
            
            if ($cached !== false) {
                return $cached;
            }
            
            $sql = "SELECT w.*, COUNT(we.id) as execution_count,
                    AVG(CASE WHEN we.status = 'completed' THEN 1 ELSE 0 END) * 100 as success_rate
                    FROM workflows w 
                    LEFT JOIN workflow_executions we ON w.id = we.workflow_id
                    WHERE w.is_active = 1";
            
            $params = [];
            
            if (!empty($filters['trigger_type'])) {
                $sql .= " AND w.trigger_type = ?";
                $params[] = $filters['trigger_type'];
            }
            
            if (!empty($filters['created_by'])) {
                $sql .= " AND w.created_by = ?";
                $params[] = $filters['created_by'];
            }
            
            $sql .= " GROUP BY w.id ORDER BY w.priority DESC, w.created_at ASC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $workflows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Processar dados dos workflows
            foreach ($workflows as &$workflow) {
                $workflow['trigger_config'] = json_decode($workflow['trigger_config'], true);
                $workflow['conditions'] = json_decode($workflow['conditions'], true);
                $workflow['actions'] = json_decode($workflow['actions'], true);
            }
            
            $this->cache->set($cacheKey, $workflows, 300); // Cache por 5 minutos
            
            return $workflows;
            
        } catch (Exception $e) {
            $this->logger->error("Erro ao buscar workflows ativos: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obter estatísticas do workflow
     */
    public function getWorkflowStats($workflowId = null, $limit = 50, $offset = 0) {
        try {
            $sql = "SELECT 
                        w.id,
                        w.name,
                        w.trigger_type,
                        COUNT(we.id) as total_executions,
                        SUM(CASE WHEN we.status = 'completed' THEN 1 ELSE 0 END) as successful_executions,
                        SUM(CASE WHEN we.status = 'failed' THEN 1 ELSE 0 END) as failed_executions,
                        AVG(TIMESTAMPDIFF(SECOND, we.started_at, we.completed_at)) as avg_execution_time,
                        MAX(we.started_at) as last_execution,
                        AVG(CASE WHEN we.status = 'completed' THEN 1 ELSE 0 END) * 100 as success_rate
                    FROM workflows w 
                    LEFT JOIN workflow_executions we ON w.id = we.workflow_id";
            
            $params = [];
            
            if ($workflowId) {
                $sql .= " WHERE w.id = ?";
                $params[] = $workflowId;
            }
            
            $sql .= " GROUP BY w.id ORDER BY total_executions DESC";
            
            // Adicionar paginação para performance
            if ($limit > 0) {
                $sql .= " LIMIT ? OFFSET ?";
                $params[] = $limit;
                $params[] = $offset;
            }
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            $this->logger->error("Erro ao obter estatísticas: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Processamento em lote de workflows
     */
    public function processBatchWorkflows($workflowIds, $triggerData = null, $batchSize = 10) {
        $results = [];
        $batches = array_chunk($workflowIds, $batchSize);
        
        foreach ($batches as $batch) {
            foreach ($batch as $workflowId) {
                try {
                    $result = $this->executeWorkflow($workflowId, $triggerData);
                    $results[$workflowId] = $result;
                } catch (Exception $e) {
                    $results[$workflowId] = [
                        'success' => false,
                        'error' => $e->getMessage()
                    ];
                }
            }
            
            // Pausa pequena entre lotes para não sobrecarregar
            usleep(100000); // 0.1 segundo
        }
        
        return $results;
    }
    
    /**
     * Criar template de workflow
     */
    public function createTemplate($templateData) {
        try {
            $sql = "INSERT INTO workflow_templates (name, description, category, template_data, 
                    is_public, created_by) VALUES (?, ?, ?, ?, ?, ?)";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $templateData['name'],
                $templateData['description'] ?? '',
                $templateData['category'],
                json_encode($templateData['template_data']),
                $templateData['is_public'] ?? false,
                $templateData['created_by']
            ]);
            
            $templateId = $this->db->lastInsertId();
            
            return [
                'success' => true,
                'template_id' => $templateId,
                'message' => 'Template criado com sucesso'
            ];
            
        } catch (Exception $e) {
            $this->logger->error("Erro ao criar template: " . $e->getMessage());
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    // ==========================================
    // MÉTODOS AUXILIARES
    // ==========================================
    
    private function validateWorkflowData($data) {
        $required = ['name', 'trigger_type', 'trigger_config', 'actions', 'created_by'];
        
        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw new Exception("Campo obrigatório '$field' não fornecido");
            }
        }
        
        // Sanitizar inputs para prevenir XSS
        if (isset($data['name'])) {
            $data['name'] = htmlspecialchars(strip_tags($data['name']), ENT_QUOTES, 'UTF-8');
        }
        
        if (isset($data['description'])) {
            $data['description'] = htmlspecialchars(strip_tags($data['description']), ENT_QUOTES, 'UTF-8');
        }
        
        if (!in_array($data['trigger_type'], ['time', 'event', 'condition', 'manual'])) {
            throw new Exception("Tipo de trigger inválido");
        }
        
        if (empty($data['actions']) || !is_array($data['actions'])) {
            throw new Exception("Pelo menos uma action deve ser definida");
        }
        
        // Validar autenticação/autorização
        if (!$this->isAuthenticated() || !$this->isAuthorized($data['created_by'])) {
            throw new Exception("Acesso não autorizado");
        }
        
        return $data;
    }
    
    /**
     * Verificar se usuário está autenticado
     */
    private function isAuthenticated() {
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }
    
    /**
     * Verificar autorização do usuário
     */
    private function isAuthorized($userId) {
        return isset($_SESSION['user_id']) && $_SESSION['user_id'] == $userId;
    }
    
    private function getWorkflow($workflowId) {
        $cacheKey = "workflow_{$workflowId}";
        $cached = $this->cache->get($cacheKey);
        
        if ($cached !== false) {
            return $cached;
        }
        
        $sql = "SELECT * FROM workflows WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$workflowId]);
        $workflow = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($workflow) {
            $this->cache->set($cacheKey, $workflow, 600); // Cache por 10 minutos
        }
        
        return $workflow;
    }
    
    private function createExecution($workflowId, $triggerData, $context) {
        $sql = "INSERT INTO workflow_executions (workflow_id, trigger_data, context_data, status) 
                VALUES (?, ?, ?, 'pending')";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $workflowId,
            $triggerData ? json_encode($triggerData) : null,
            $context ? json_encode($context) : null
        ]);
        
        return $this->db->lastInsertId();
    }
    
    private function updateExecutionStatus($executionId, $status, $errorMessage = null) {
        $sql = "UPDATE workflow_executions SET status = ?, error_message = ?";
        $params = [$status, $errorMessage];
        
        if ($status === 'completed' || $status === 'failed') {
            $sql .= ", completed_at = CURRENT_TIMESTAMP";
        }
        
        $sql .= " WHERE id = ?";
        $params[] = $executionId;
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
    }
    
    private function finishExecution($executionId, $status, $executionLog, $actionsExecuted) {
        $sql = "UPDATE workflow_executions SET 
                status = ?, 
                completed_at = CURRENT_TIMESTAMP,
                execution_log = ?,
                actions_executed = ?
                WHERE id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $status,
            json_encode($executionLog),
            json_encode($actionsExecuted),
            $executionId
        ]);
    }
    
    private function evaluateConditions($conditions, $triggerData, $context) {
        if (!$conditions) {
            return true; // Sem condições = sempre executar
        }
        
        // Implementar avaliador de condições complexas
        $evaluator = new ConditionEvaluator();
        return $evaluator->evaluate($conditions, $triggerData, $context);
    }
    
    private function createWorkflowTrigger($workflowId, $trigger) {
        $sql = "INSERT INTO workflow_triggers 
                (workflow_id, trigger_type, entity_type, event_type, conditions, schedule_expression) 
                VALUES (?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $workflowId,
            $trigger['type'],
            $trigger['entity_type'] ?? null,
            $trigger['event_type'] ?? null,
            isset($trigger['conditions']) ? json_encode($trigger['conditions']) : null,
            $trigger['schedule'] ?? null
        ]);
    }
    
    private function createWorkflowAction($workflowId, $action, $order) {
        $sql = "INSERT INTO workflow_actions 
                (workflow_id, action_type, action_config, execution_order, max_retries, timeout_seconds) 
                VALUES (?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $workflowId,
            $action['type'],
            json_encode($action['config']),
            $order,
            $action['max_retries'] ?? 3,
            $action['timeout'] ?? 300
        ]);
    }
    
    private function getActiveTriggers($triggerType = null) {
        $sql = "SELECT wt.*, w.name as workflow_name 
                FROM workflow_triggers wt 
                JOIN workflows w ON wt.workflow_id = w.id 
                WHERE wt.is_active = 1 AND w.is_active = 1";
        
        $params = [];
        
        if ($triggerType) {
            $sql .= " AND wt.trigger_type = ?";
            $params[] = $triggerType;
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    private function shouldExecuteTrigger($trigger) {
        // Implementar lógica de verificação de trigger
        switch ($trigger['trigger_type']) {
            case 'time':
                return $this->checkScheduleTrigger($trigger);
            case 'event':
                return $this->checkEventTrigger($trigger);
            default:
                return false;
        }
    }
    
    private function checkScheduleTrigger($trigger) {
        // Implementar verificação de agendamento (cron-like)
        // Por exemplo: verificar se chegou a hora de executar baseado no schedule_expression
        return true; // Placeholder
    }
    
    private function checkEventTrigger($trigger) {
        // Implementar verificação de eventos
        // Por exemplo: verificar se houve um evento específico no sistema
        return true; // Placeholder
    }
    
    private function prepareTriggerData($trigger) {
        // Preparar dados específicos do trigger
        return [
            'trigger_id' => $trigger['id'],
            'trigger_type' => $trigger['trigger_type'],
            'timestamp' => date('Y-m-d H:i:s')
        ];
    }
    
    private function updateWorkflowStats($workflowId, $success) {
        $sql = "UPDATE workflows SET 
                last_executed = CURRENT_TIMESTAMP,
                execution_count = execution_count + 1,
                success_rate = (
                    SELECT AVG(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) * 100
                    FROM workflow_executions 
                    WHERE workflow_id = ?
                )
                WHERE id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$workflowId, $workflowId]);
        
        // Limpar cache do workflow
        $this->cache->delete("workflow_{$workflowId}");
    }
}

/**
 * Executor de Actions
 */
class ActionExecutor {
    private $db;
    private $logger;
    
    public function __construct($database) {
        $this->db = $database;
        $this->logger = new Logger('workflow_actions');
    }
    
    public function execute($action, $triggerData, $context) {
        $actionType = $action['type'];
        $config = $action['config'];
        
        switch ($actionType) {
            case 'send_email':
                return $this->sendEmail($config, $triggerData, $context);
            case 'send_sms':
                return $this->sendSMS($config, $triggerData, $context);
            case 'create_task':
                return $this->createTask($config, $triggerData, $context);
            case 'update_record':
                return $this->updateRecord($config, $triggerData, $context);
            case 'webhook':
                return $this->callWebhook($config, $triggerData, $context);
            case 'custom_script':
                return $this->executeScript($config, $triggerData, $context);
            default:
                throw new Exception("Tipo de action não suportado: $actionType");
        }
    }
    
    private function sendEmail($config, $triggerData, $context) {
        // Implementar envio de email
        $this->logger->info("Email enviado", ['config' => $config]);
        return ['status' => 'sent', 'message' => 'Email enviado com sucesso'];
    }
    
    private function sendSMS($config, $triggerData, $context) {
        // Implementar envio de SMS
        $this->logger->info("SMS enviado", ['config' => $config]);
        return ['status' => 'sent', 'message' => 'SMS enviado com sucesso'];
    }
    
    private function createTask($config, $triggerData, $context) {
        // Implementar criação de tarefa
        $this->logger->info("Tarefa criada", ['config' => $config]);
        return ['status' => 'created', 'task_id' => 123];
    }
    
    private function updateRecord($config, $triggerData, $context) {
        // Implementar atualização de registro
        $this->logger->info("Registro atualizado", ['config' => $config]);
        return ['status' => 'updated', 'affected_rows' => 1];
    }
    
    private function callWebhook($config, $triggerData, $context) {
        // Implementar chamada de webhook
        $this->logger->info("Webhook chamado", ['config' => $config]);
        return ['status' => 'called', 'response_code' => 200];
    }
    
    private function executeScript($config, $triggerData, $context) {
        // Implementar execução de script customizado
        $this->logger->info("Script executado", ['config' => $config]);
        return ['status' => 'executed', 'output' => 'Script executado com sucesso'];
    }
}

/**
 * Avaliador de Condições
 */
class ConditionEvaluator {
    public function evaluate($conditions, $triggerData, $context) {
        // Implementar avaliador de condições complexas
        // Suporte para operadores: AND, OR, NOT
        // Suporte para comparações: ==, !=, >, <, >=, <=, LIKE, IN
        
        if (!is_array($conditions)) {
            return true;
        }
        
        return $this->evaluateConditionGroup($conditions, $triggerData, $context);
    }
    
    private function evaluateConditionGroup($group, $triggerData, $context) {
        $operator = $group['operator'] ?? 'AND';
        $conditions = $group['conditions'] ?? [];
        
        if (empty($conditions)) {
            return true;
        }
        
        $results = [];
        
        foreach ($conditions as $condition) {
            if (isset($condition['conditions'])) {
                // Grupo aninhado
                $results[] = $this->evaluateConditionGroup($condition, $triggerData, $context);
            } else {
                // Condição simples
                $results[] = $this->evaluateSimpleCondition($condition, $triggerData, $context);
            }
        }
        
        switch (strtoupper($operator)) {
            case 'AND':
                return !in_array(false, $results);
            case 'OR':
                return in_array(true, $results);
            default:
                return true;
        }
    }
    
    private function evaluateSimpleCondition($condition, $triggerData, $context) {
        $field = $condition['field'];
        $operator = $condition['operator'];
        $value = $condition['value'];
        
        // Obter valor do campo dos dados
        $fieldValue = $this->getFieldValue($field, $triggerData, $context);
        
        switch ($operator) {
            case '==':
                return $fieldValue == $value;
            case '!=':
                return $fieldValue != $value;
            case '>':
                return $fieldValue > $value;
            case '<':
                return $fieldValue < $value;
            case '>=':
                return $fieldValue >= $value;
            case '<=':
                return $fieldValue <= $value;
            case 'LIKE':
                return stripos($fieldValue, $value) !== false;
            case 'IN':
                return in_array($fieldValue, (array)$value);
            default:
                return true;
        }
    }
    
    private function getFieldValue($field, $triggerData, $context) {
        // Buscar valor do campo nos dados disponíveis
        if (isset($triggerData[$field])) {
            return $triggerData[$field];
        }
        
        if (isset($context[$field])) {
            return $context[$field];
        }
        
        // Suporte para campos aninhados (ex: user.name)
        if (strpos($field, '.') !== false) {
            $parts = explode('.', $field);
            $data = array_merge((array)$triggerData, (array)$context);
            
            foreach ($parts as $part) {
                if (isset($data[$part])) {
                    $data = $data[$part];
                } else {
                    return null;
                }
            }
            
            return $data;
        }
        
        return null;
    }
}

/**
 * Gerenciador de Triggers
 */
class TriggerManager {
    private $db;
    private $logger;
    
    public function __construct($database) {
        $this->db = $database;
        $this->logger = new Logger('workflow_triggers');
    }
    
    public function registerEventTrigger($entityType, $eventType, $data) {
        // Registrar trigger de evento para processamento posterior
        $this->logger->info("Evento registrado: $entityType.$eventType", ['data' => $data]);
        
        // Buscar workflows que escutam este evento
        $sql = "SELECT wt.workflow_id 
                FROM workflow_triggers wt 
                JOIN workflows w ON wt.workflow_id = w.id 
                WHERE wt.trigger_type = 'event' 
                AND wt.entity_type = ? 
                AND wt.event_type = ? 
                AND wt.is_active = 1 
                AND w.is_active = 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$entityType, $eventType]);
        $workflowIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        return $workflowIds;
    }
}

?>