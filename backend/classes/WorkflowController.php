<?php
/**
 * DURALUX CRM - Workflow Controller v5.0
 * Controller para gerenciamento de workflows e automações
 * 
 * @author Duralux Development Team
 * @version 5.0.0
 * @since 2025-01-03
 */

require_once 'WorkflowEngine.php';

class WorkflowController {
    private $db;
    private $workflowEngine;
    private $auth;
    
    public function __construct($database) {
        $this->db = $database;
        $this->workflowEngine = new WorkflowEngine($database);
        $this->auth = new AuthController($database);
    }
    
    /**
     * Processar requisições do controller
     */
    public function handleRequest() {
        try {
            // Verificar autenticação
            if (!$this->auth->isAuthenticated()) {
                return $this->jsonResponse(['error' => 'Não autenticado'], 401);
            }
            
            $action = $_GET['action'] ?? $_POST['action'] ?? '';
            $method = $_SERVER['REQUEST_METHOD'];
            
            switch ($action) {
                // Workflows
                case 'create_workflow':
                    return $this->createWorkflow();
                case 'get_workflows':
                    return $this->getWorkflows();
                case 'get_workflow':
                    return $this->getWorkflow();
                case 'update_workflow':
                    return $this->updateWorkflow();
                case 'delete_workflow':
                    return $this->deleteWorkflow();
                case 'toggle_workflow':
                    return $this->toggleWorkflow();
                
                // Execuções
                case 'execute_workflow':
                    return $this->executeWorkflow();
                case 'get_executions':
                    return $this->getExecutions();
                case 'get_execution_details':
                    return $this->getExecutionDetails();
                
                // Templates
                case 'create_template':
                    return $this->createTemplate();
                case 'get_templates':
                    return $this->getTemplates();
                case 'use_template':
                    return $this->useTemplate();
                
                // Painel de Controle e estatísticas
                case 'get_dashboard':
                    return $this->getPainel de Controle();
                case 'get_workflow_stats':
                    return $this->getWorkflowStats();
                
                // Triggers
                case 'process_triggers':
                    return $this->processTriggers();
                case 'test_trigger':
                    return $this->testTrigger();
                
                default:
                    return $this->jsonResponse(['error' => 'Ação não encontrada'], 404);
            }
            
        } catch (Exception $e) {
            error_log("Erro no WorkflowController: " . $e->getMessage());
            return $this->jsonResponse(['error' => 'Erro interno do servidor'], 500);
        }
    }
    
    /**
     * Criar novo workflow
     */
    private function createWorkflow() {
        try {
            $data = $this->getJsonInput();
            
            // Validar dados
            $requiredFields = ['name', 'trigger_type', 'trigger_config', 'actions'];
            foreach ($requiredFields as $field) {
                if (!isset($data[$field])) {
                    return $this->jsonResponse(['error' => "Campo '$field' é obrigatório"], 400);
                }
            }
            
            // Adicionar dados do usuário
            $user = $this->auth->getCurrentUser();
            $data['created_by'] = $user['id'];
            
            $result = $this->workflowEngine->createWorkflow($data);
            
            if ($result['success']) {
                return $this->jsonResponse([
                    'success' => true,
                    'data' => $result,
                    'message' => 'Workflow criado com sucesso'
                ]);
            } else {
                return $this->jsonResponse(['error' => $result['error']], 400);
            }
            
        } catch (Exception $e) {
            return $this->jsonResponse(['error' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Obter lista de workflows
     */
    private function getWorkflows() {
        try {
            $filters = [];
            
            if (isset($_GET['trigger_type'])) {
                $filters['trigger_type'] = $_GET['trigger_type'];
            }
            
            if (isset($_GET['created_by'])) {
                $filters['created_by'] = $_GET['created_by'];
            }
            
            $workflows = $this->workflowEngine->getActiveWorkflows($filters);
            
            return $this->jsonResponse([
                'success' => true,
                'data' => $workflows,
                'count' => count($workflows)
            ]);
            
        } catch (Exception $e) {
            return $this->jsonResponse(['error' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Obter workflow específico
     */
    private function getWorkflow() {
        try {
            $workflowId = $_GET['workflow_id'] ?? null;
            
            if (!$workflowId) {
                return $this->jsonResponse(['error' => 'ID do workflow é obrigatório'], 400);
            }
            
            $sql = "SELECT w.*, 
                    COUNT(we.id) as total_executions,
                    SUM(CASE WHEN we.status = 'completed' THEN 1 ELSE 0 END) as successful_executions,
                    MAX(we.started_at) as last_execution
                    FROM workflows w 
                    LEFT JOIN workflow_executions we ON w.id = we.workflow_id 
                    WHERE w.id = ? 
                    GROUP BY w.id";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$workflowId]);
            $workflow = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$workflow) {
                return $this->jsonResponse(['error' => 'Workflow não encontrado'], 404);
            }
            
            // Decodificar campos JSON
            $workflow['trigger_config'] = json_decode($workflow['trigger_config'], true);
            $workflow['conditions'] = json_decode($workflow['conditions'], true);
            $workflow['actions'] = json_decode($workflow['actions'], true);
            
            return $this->jsonResponse([
                'success' => true,
                'data' => $workflow
            ]);
            
        } catch (Exception $e) {
            return $this->jsonResponse(['error' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Atualizar workflow
     */
    private function updateWorkflow() {
        try {
            $workflowId = $_GET['workflow_id'] ?? $_POST['workflow_id'] ?? null;
            
            if (!$workflowId) {
                return $this->jsonResponse(['error' => 'ID do workflow é obrigatório'], 400);
            }
            
            $data = $this->getJsonInput();
            
            // Campos que podem ser atualizados
            $updateFields = [];
            $params = [];
            
            if (isset($data['name'])) {
                $updateFields[] = 'name = ?';
                $params[] = $data['name'];
            }
            
            if (isset($data['description'])) {
                $updateFields[] = 'description = ?';
                $params[] = $data['description'];
            }
            
            if (isset($data['trigger_config'])) {
                $updateFields[] = 'trigger_config = ?';
                $params[] = json_encode($data['trigger_config']);
            }
            
            if (isset($data['conditions'])) {
                $updateFields[] = 'conditions = ?';
                $params[] = json_encode($data['conditions']);
            }
            
            if (isset($data['actions'])) {
                $updateFields[] = 'actions = ?';
                $params[] = json_encode($data['actions']);
            }
            
            if (isset($data['priority'])) {
                $updateFields[] = 'priority = ?';
                $params[] = $data['priority'];
            }
            
            if (empty($updateFields)) {
                return $this->jsonResponse(['error' => 'Nenhum campo para atualizar'], 400);
            }
            
            $updateFields[] = 'updated_at = CURRENT_TIMESTAMP';
            $params[] = $workflowId;
            
            $sql = "UPDATE workflows SET " . implode(', ', $updateFields) . " WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            return $this->jsonResponse([
                'success' => true,
                'message' => 'Workflow atualizado com sucesso'
            ]);
            
        } catch (Exception $e) {
            return $this->jsonResponse(['error' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Deletar workflow
     */
    private function deleteWorkflow() {
        try {
            $workflowId = $_GET['workflow_id'] ?? $_POST['workflow_id'] ?? null;
            
            if (!$workflowId) {
                return $this->jsonResponse(['error' => 'ID do workflow é obrigatório'], 400);
            }
            
            // Verificar se o usuário tem permissão
            $user = $this->auth->getCurrentUser();
            
            $sql = "SELECT created_by FROM workflows WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$workflowId]);
            $workflow = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$workflow) {
                return $this->jsonResponse(['error' => 'Workflow não encontrado'], 404);
            }
            
            if ($workflow['created_by'] != $user['id'] && $user['role'] != 'admin') {
                return $this->jsonResponse(['error' => 'Sem permissão para deletar'], 403);
            }
            
            // Deletar workflow (cascade vai remover execuções, triggers e actions)
            $sql = "DELETE FROM workflows WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$workflowId]);
            
            return $this->jsonResponse([
                'success' => true,
                'message' => 'Workflow deletado com sucesso'
            ]);
            
        } catch (Exception $e) {
            return $this->jsonResponse(['error' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Ativar/desativar workflow
     */
    private function toggleWorkflow() {
        try {
            $workflowId = $_GET['workflow_id'] ?? $_POST['workflow_id'] ?? null;
            $active = $_POST['active'] ?? true;
            
            if (!$workflowId) {
                return $this->jsonResponse(['error' => 'ID do workflow é obrigatório'], 400);
            }
            
            $sql = "UPDATE workflows SET is_active = ? WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$active ? 1 : 0, $workflowId]);
            
            return $this->jsonResponse([
                'success' => true,
                'message' => 'Status do workflow atualizado'
            ]);
            
        } catch (Exception $e) {
            return $this->jsonResponse(['error' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Executar workflow manualmente
     */
    private function executeWorkflow() {
        try {
            $workflowId = $_POST['workflow_id'] ?? null;
            $triggerData = $_POST['trigger_data'] ?? null;
            
            if (!$workflowId) {
                return $this->jsonResponse(['error' => 'ID do workflow é obrigatório'], 400);
            }
            
            $user = $this->auth->getCurrentUser();
            $context = [
                'executed_by' => $user['id'],
                'execution_type' => 'manual'
            ];
            
            $result = $this->workflowEngine->executeWorkflow($workflowId, $triggerData, $context);
            
            return $this->jsonResponse($result);
            
        } catch (Exception $e) {
            return $this->jsonResponse(['error' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Obter execuções de workflow
     */
    private function getExecutions() {
        try {
            $workflowId = $_GET['workflow_id'] ?? null;
            $limit = $_GET['limit'] ?? 50;
            $offset = $_GET['offset'] ?? 0;
            
            $sql = "SELECT we.*, w.name as workflow_name 
                    FROM workflow_executions we 
                    JOIN workflows w ON we.workflow_id = w.id";
            
            $params = [];
            
            if ($workflowId) {
                $sql .= " WHERE we.workflow_id = ?";
                $params[] = $workflowId;
            }
            
            $sql .= " ORDER BY we.started_at DESC LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $executions = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Decodificar campos JSON
            foreach ($executions as &$execution) {
                $execution['trigger_data'] = json_decode($execution['trigger_data'], true);
                $execution['context_data'] = json_decode($execution['context_data'], true);
                $execution['execution_log'] = json_decode($execution['execution_log'], true);
                $execution['actions_executed'] = json_decode($execution['actions_executed'], true);
            }
            
            return $this->jsonResponse([
                'success' => true,
                'data' => $executions,
                'count' => count($executions)
            ]);
            
        } catch (Exception $e) {
            return $this->jsonResponse(['error' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Obter detalhes de execução
     */
    private function getExecutionDetails() {
        try {
            $executionId = $_GET['execution_id'] ?? null;
            
            if (!$executionId) {
                return $this->jsonResponse(['error' => 'ID da execução é obrigatório'], 400);
            }
            
            $sql = "SELECT we.*, w.name as workflow_name, w.trigger_type, w.actions as workflow_actions
                    FROM workflow_executions we 
                    JOIN workflows w ON we.workflow_id = w.id 
                    WHERE we.id = ?";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$executionId]);
            $execution = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$execution) {
                return $this->jsonResponse(['error' => 'Execução não encontrada'], 404);
            }
            
            // Decodificar campos JSON
            $execution['trigger_data'] = json_decode($execution['trigger_data'], true);
            $execution['context_data'] = json_decode($execution['context_data'], true);
            $execution['execution_log'] = json_decode($execution['execution_log'], true);
            $execution['actions_executed'] = json_decode($execution['actions_executed'], true);
            $execution['workflow_actions'] = json_decode($execution['workflow_actions'], true);
            
            return $this->jsonResponse([
                'success' => true,
                'data' => $execution
            ]);
            
        } catch (Exception $e) {
            return $this->jsonResponse(['error' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Criar template de workflow
     */
    private function createTemplate() {
        try {
            $data = $this->getJsonInput();
            
            // Validar dados
            if (!isset($data['name']) || !isset($data['template_data'])) {
                return $this->jsonResponse(['error' => 'Nome e dados do template são obrigatórios'], 400);
            }
            
            $user = $this->auth->getCurrentUser();
            $data['created_by'] = $user['id'];
            
            $result = $this->workflowEngine->createTemplate($data);
            
            return $this->jsonResponse($result);
            
        } catch (Exception $e) {
            return $this->jsonResponse(['error' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Obter templates
     */
    private function getTemplates() {
        try {
            $category = $_GET['category'] ?? null;
            $publicOnly = $_GET['public_only'] ?? false;
            
            $sql = "SELECT * FROM workflow_templates WHERE 1=1";
            $params = [];
            
            if ($category) {
                $sql .= " AND category = ?";
                $params[] = $category;
            }
            
            if ($publicOnly) {
                $sql .= " AND is_public = 1";
            }
            
            $sql .= " ORDER BY usage_count DESC, created_at DESC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $templates = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Decodificar template_data
            foreach ($templates as &$template) {
                $template['template_data'] = json_decode($template['template_data'], true);
            }
            
            return $this->jsonResponse([
                'success' => true,
                'data' => $templates,
                'count' => count($templates)
            ]);
            
        } catch (Exception $e) {
            return $this->jsonResponse(['error' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Usar template para criar workflow
     */
    private function useTemplate() {
        try {
            $templateId = $_POST['template_id'] ?? null;
            $customData = $_POST['custom_data'] ?? [];
            
            if (!$templateId) {
                return $this->jsonResponse(['error' => 'ID do template é obrigatório'], 400);
            }
            
            // Buscar template
            $sql = "SELECT * FROM workflow_templates WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$templateId]);
            $template = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$template) {
                return $this->jsonResponse(['error' => 'Template não encontrado'], 404);
            }
            
            $templateData = json_decode($template['template_data'], true);
            
            // Mesclar dados customizados
            $workflowData = array_merge($templateData, $customData);
            
            // Adicionar usuário atual
            $user = $this->auth->getCurrentUser();
            $workflowData['created_by'] = $user['id'];
            
            // Criar workflow baseado no template
            $result = $this->workflowEngine->createWorkflow($workflowData);
            
            if ($result['success']) {
                // Incrementar contador de uso do template
                $sql = "UPDATE workflow_templates SET usage_count = usage_count + 1 WHERE id = ?";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([$templateId]);
            }
            
            return $this->jsonResponse($result);
            
        } catch (Exception $e) {
            return $this->jsonResponse(['error' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Painel de Controle de workflows
     */
    private function getPainel de Controle() {
        try {
            // Estatísticas gerais
            $sql = "SELECT 
                        COUNT(*) as total_workflows,
                        SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active_workflows,
                        AVG(execution_count) as avg_executions,
                        AVG(success_rate) as avg_success_rate
                    FROM workflows";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $stats = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Execuções recentes
            $sql = "SELECT we.*, w.name as workflow_name 
                    FROM workflow_executions we 
                    JOIN workflows w ON we.workflow_id = w.id 
                    ORDER BY we.started_at DESC 
                    LIMIT 10";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $recentExecutions = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Workflows mais ativos
            $sql = "SELECT w.*, 
                    COUNT(we.id) as recent_executions,
                    AVG(CASE WHEN we.status = 'completed' THEN 1 ELSE 0 END) * 100 as success_rate
                    FROM workflows w 
                    LEFT JOIN workflow_executions we ON w.id = we.workflow_id 
                        AND we.started_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                    WHERE w.is_active = 1
                    GROUP BY w.id 
                    ORDER BY recent_executions DESC 
                    LIMIT 5";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $topWorkflows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Execuções por dia (últimos 30 dias)
            $sql = "SELECT 
                        DATE(started_at) as date,
                        COUNT(*) as total_executions,
                        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as successful_executions,
                        SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed_executions
                    FROM workflow_executions 
                    WHERE started_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                    GROUP BY DATE(started_at) 
                    ORDER BY date ASC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $executionTrends = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return $this->jsonResponse([
                'success' => true,
                'data' => [
                    'stats' => $stats,
                    'recent_executions' => $recentExecutions,
                    'top_workflows' => $topWorkflows,
                    'execution_trends' => $executionTrends
                ]
            ]);
            
        } catch (Exception $e) {
            return $this->jsonResponse(['error' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Estatísticas de workflow
     */
    private function getWorkflowStats() {
        try {
            $workflowId = $_GET['workflow_id'] ?? null;
            
            $stats = $this->workflowEngine->getWorkflowStats($workflowId);
            
            return $this->jsonResponse([
                'success' => true,
                'data' => $stats
            ]);
            
        } catch (Exception $e) {
            return $this->jsonResponse(['error' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Processar triggers automáticos
     */
    private function processTriggers() {
        try {
            $triggerType = $_GET['trigger_type'] ?? null;
            
            $result = $this->workflowEngine->processTriggers($triggerType);
            
            return $this->jsonResponse($result);
            
        } catch (Exception $e) {
            return $this->jsonResponse(['error' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Testar trigger
     */
    private function testTrigger() {
        try {
            $workflowId = $_POST['workflow_id'] ?? null;
            $testData = $_POST['test_data'] ?? [];
            
            if (!$workflowId) {
                return $this->jsonResponse(['error' => 'ID do workflow é obrigatório'], 400);
            }
            
            $user = $this->auth->getCurrentUser();
            $context = [
                'executed_by' => $user['id'],
                'execution_type' => 'test'
            ];
            
            $result = $this->workflowEngine->executeWorkflow($workflowId, $testData, $context);
            
            return $this->jsonResponse($result);
            
        } catch (Exception $e) {
            return $this->jsonResponse(['error' => $e->getMessage()], 500);
        }
    }
    
    // ==========================================
    // MÉTODOS AUXILIARES
    // ==========================================
    
    private function getJsonInput() {
        $input = file_get_contents('php://input');
        return json_decode($input, true) ?: [];
    }
    
    private function jsonResponse($data, $statusCode = 200) {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }
}

?>