<?php
/**
 * ProjectController - Gerenciamento de Projetos
 * Duralux CRM v1.2 - Sistema de Projetos
 */

require_once 'BaseController.php';

class ProjectController extends BaseController {
    private $table = 'projects';
    
    public function __construct() {
        parent::__construct();
        $this->logActivity('access', 'projects', null, 'Acesso ao módulo de projetos');
    }
    
    /**
     * Listar todos os projetos com filtros
     */
    public function index() {
        try {
            $status = $_GET['status'] ?? '';
            $priority = $_GET['priority'] ?? '';
            $customer_id = $_GET['customer_id'] ?? '';
            $search = $_GET['search'] ?? '';
            $page = (int)($_GET['page'] ?? 1);
            $limit = (int)($_GET['limit'] ?? 10);
            $offset = ($page - 1) * $limit;
            
            $where = ["p.id IS NOT NULL"];
            $params = [];
            
            if (!empty($status)) {
                $where[] = "p.status = :status";
                $params[':status'] = $status;
            }
            
            if (!empty($priority)) {
                $where[] = "p.priority = :priority";
                $params[':priority'] = $priority;
            }
            
            if (!empty($customer_id)) {
                $where[] = "p.customer_id = :customer_id";
                $params[':customer_id'] = $customer_id;
            }
            
            if (!empty($search)) {
                $where[] = "(p.name LIKE :search OR p.description LIKE :search OR c.name LIKE :search)";
                $params[':search'] = "%$search%";
            }
            
            $whereClause = "WHERE " . implode(" AND ", $where);
            
            $sql = "SELECT p.*, c.name as customer_name, u.name as user_name,
                           COUNT(t.id) as total_tasks,
                           COUNT(CASE WHEN t.status = 'completed' THEN 1 END) as completed_tasks
                    FROM {$this->table} p 
                    LEFT JOIN customers c ON p.customer_id = c.id 
                    LEFT JOIN users u ON p.user_id = u.id 
                    LEFT JOIN project_tasks t ON p.id = t.project_id
                    $whereClause 
                    GROUP BY p.id
                    ORDER BY p.created_at DESC 
                    LIMIT :limit OFFSET :offset";
            
            $stmt = $this->db->prepare($sql);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            $projects = $stmt->fetchAll();
            
            // Calcular progresso para cada projeto
            foreach ($projects as &$project) {
                $project['progress'] = $project['total_tasks'] > 0 
                    ? round(($project['completed_tasks'] / $project['total_tasks']) * 100, 1)
                    : 0;
            }
            
            // Total count
            $countSql = "SELECT COUNT(*) FROM {$this->table} p 
                         LEFT JOIN customers c ON p.customer_id = c.id 
                         $whereClause";
            $countStmt = $this->db->prepare($countSql);
            foreach ($params as $key => $value) {
                $countStmt->bindValue($key, $value);
            }
            $countStmt->execute();
            $total = $countStmt->fetchColumn();
            
            $this->jsonResponse([
                'projects' => $projects,
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => $total,
                    'pages' => ceil($total / $limit)
                ]
            ]);
            
        } catch (Exception $e) {
            $this->jsonResponse(['error' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Buscar projeto específico
     */
    public function show($id) {
        try {
            $sql = "SELECT p.*, c.name as customer_name, c.email as customer_email, 
                           u.name as user_name
                    FROM {$this->table} p 
                    LEFT JOIN customers c ON p.customer_id = c.id 
                    LEFT JOIN users u ON p.user_id = u.id 
                    WHERE p.id = :id";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':id' => $id]);
            $project = $stmt->fetch();
            
            if (!$project) {
                $this->jsonResponse(['error' => 'Projeto não encontrado'], 404);
                return;
            }
            
            // Buscar tarefas do projeto
            $tasksStmt = $this->db->prepare("
                SELECT t.*, u.name as assigned_user_name 
                FROM project_tasks t 
                LEFT JOIN users u ON t.assigned_to = u.id
                WHERE t.project_id = :project_id 
                ORDER BY t.priority DESC, t.due_date ASC
            ");
            $tasksStmt->execute([':project_id' => $id]);
            $project['tasks'] = $tasksStmt->fetchAll();
            
            // Calcular estatísticas
            $totalTasks = count($project['tasks']);
            $completedTasks = count(array_filter($project['tasks'], fn($t) => $t['status'] === 'completed'));
            $project['progress'] = $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100, 1) : 0;
            $project['total_tasks'] = $totalTasks;
            $project['completed_tasks'] = $completedTasks;
            
            $this->jsonResponse($project);
            
        } catch (Exception $e) {
            $this->jsonResponse(['error' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Criar novo projeto
     */
    public function store() {
        try {
            $data = $this->getJsonInput();
            
            if (empty($data['name'])) {
                $this->jsonResponse(['error' => 'Nome do projeto é obrigatório'], 400);
                return;
            }
            
            if (empty($data['customer_id'])) {
                $this->jsonResponse(['error' => 'Cliente é obrigatório'], 400);
                return;
            }
            
            // Verificar se cliente existe
            $customerStmt = $this->db->prepare("SELECT id FROM customers WHERE id = :id");
            $customerStmt->execute([':id' => $data['customer_id']]);
            if (!$customerStmt->fetch()) {
                $this->jsonResponse(['error' => 'Cliente não encontrado'], 400);
                return;
            }
            
            $projectData = [
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'customer_id' => $data['customer_id'],
                'status' => $data['status'] ?? 'planning',
                'priority' => $data['priority'] ?? 'medium',
                'budget' => (float)($data['budget'] ?? 0),
                'start_date' => $data['start_date'] ?? null,
                'end_date' => $data['end_date'] ?? null,
                'user_id' => $this->getCurrentUserId(),
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            $columns = implode(', ', array_keys($projectData));
            $placeholders = ':' . implode(', :', array_keys($projectData));
            
            $sql = "INSERT INTO {$this->table} ($columns) VALUES ($placeholders)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute($projectData);
            
            $projectId = $this->db->lastInsertId();
            
            $this->logActivity('create', 'projects', $projectId, "Projeto '{$projectData['name']}' criado");
            
            $this->show($projectId);
            
        } catch (Exception $e) {
            $this->jsonResponse(['error' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Atualizar projeto
     */
    public function update($id) {
        try {
            $data = $this->getJsonInput();
            
            // Verificar se projeto existe
            $stmt = $this->db->prepare("SELECT name FROM {$this->table} WHERE id = :id");
            $stmt->execute([':id' => $id]);
            $currentProject = $stmt->fetch();
            
            if (!$currentProject) {
                $this->jsonResponse(['error' => 'Projeto não encontrado'], 404);
                return;
            }
            
            $updateData = [];
            $allowedFields = ['name', 'description', 'customer_id', 'status', 
                             'priority', 'budget', 'start_date', 'end_date'];
            
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
            
            $setClause = [];
            foreach ($updateData as $key => $value) {
                $setClause[] = "$key = :$key";
            }
            
            $sql = "UPDATE {$this->table} SET " . implode(', ', $setClause) . " WHERE id = :id";
            $updateData[':id'] = $id;
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($updateData);
            
            $this->logActivity('update', 'projects', $id, "Projeto '{$currentProject['name']}' atualizado");
            
            $this->show($id);
            
        } catch (Exception $e) {
            $this->jsonResponse(['error' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Excluir projeto
     */
    public function delete($id) {
        try {
            $stmt = $this->db->prepare("SELECT name FROM {$this->table} WHERE id = :id");
            $stmt->execute([':id' => $id]);
            $project = $stmt->fetch();
            
            if (!$project) {
                $this->jsonResponse(['error' => 'Projeto não encontrado'], 404);
                return;
            }
            
            $this->db->beginTransaction();
            
            // Excluir tarefas do projeto
            $this->db->prepare("DELETE FROM project_tasks WHERE project_id = :id")->execute([':id' => $id]);
            
            // Excluir projeto
            $this->db->prepare("DELETE FROM {$this->table} WHERE id = :id")->execute([':id' => $id]);
            
            $this->db->commit();
            
            $this->logActivity('delete', 'projects', $id, "Projeto '{$project['name']}' excluído");
            
            $this->jsonResponse(['message' => 'Projeto excluído com sucesso']);
            
        } catch (Exception $e) {
            $this->db->rollBack();
            $this->jsonResponse(['error' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Gerenciar tarefas do projeto
     */
    public function manageTasks($projectId) {
        try {
            $method = $_SERVER['REQUEST_METHOD'];
            $data = $this->getJsonInput();
            
            switch ($method) {
                case 'GET':
                    return $this->getProjectTasks($projectId);
                case 'POST':
                    return $this->createTask($projectId, $data);
                case 'PUT':
                    return $this->updateTask($data['task_id'], $data);
                case 'DELETE':
                    return $this->deleteTask($data['task_id']);
            }
            
        } catch (Exception $e) {
            $this->jsonResponse(['error' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Obter tarefas do projeto
     */
    private function getProjectTasks($projectId) {
        $stmt = $this->db->prepare("
            SELECT t.*, u.name as assigned_user_name 
            FROM project_tasks t 
            LEFT JOIN users u ON t.assigned_to = u.id
            WHERE t.project_id = :project_id 
            ORDER BY t.priority DESC, t.due_date ASC
        ");
        $stmt->execute([':project_id' => $projectId]);
        $tasks = $stmt->fetchAll();
        
        $this->jsonResponse(['tasks' => $tasks]);
    }
    
    /**
     * Criar tarefa
     */
    private function createTask($projectId, $data) {
        if (empty($data['title'])) {
            $this->jsonResponse(['error' => 'Título da tarefa é obrigatório'], 400);
            return;
        }
        
        $taskData = [
            'project_id' => $projectId,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'status' => $data['status'] ?? 'pending',
            'priority' => $data['priority'] ?? 'medium',
            'assigned_to' => $data['assigned_to'] ?? null,
            'due_date' => $data['due_date'] ?? null,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        $columns = implode(', ', array_keys($taskData));
        $placeholders = ':' . implode(', :', array_keys($taskData));
        
        $sql = "INSERT INTO project_tasks ($columns) VALUES ($placeholders)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($taskData);
        
        $taskId = $this->db->lastInsertId();
        
        $this->logActivity('create', 'project_tasks', $taskId, "Tarefa '{$taskData['title']}' criada no projeto $projectId");
        
        // Retornar tarefa criada
        $stmt = $this->db->prepare("
            SELECT t.*, u.name as assigned_user_name 
            FROM project_tasks t 
            LEFT JOIN users u ON t.assigned_to = u.id
            WHERE t.id = :id
        ");
        $stmt->execute([':id' => $taskId]);
        $task = $stmt->fetch();
        
        $this->jsonResponse($task);
    }
    
    /**
     * Obter estatísticas de projetos
     */
    public function getStats() {
        try {
            $stats = [];
            
            // Total de projetos
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM {$this->table}");
            $stmt->execute();
            $stats['total_projects'] = $stmt->fetchColumn();
            
            // Por status
            $stmt = $this->db->prepare("SELECT status, COUNT(*) as count FROM {$this->table} GROUP BY status");
            $stmt->execute();
            $statusStats = $stmt->fetchAll();
            $stats['by_status'] = [];
            foreach ($statusStats as $stat) {
                $stats['by_status'][$stat['status']] = $stat['count'];
            }
            
            // Projetos ativos
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM {$this->table} WHERE status IN ('active', 'in_progress')");
            $stmt->execute();
            $stats['active_projects'] = $stmt->fetchColumn();
            
            // Valor total dos projetos
            $stmt = $this->db->prepare("SELECT SUM(budget) FROM {$this->table}");
            $stmt->execute();
            $stats['total_budget'] = (float)$stmt->fetchColumn();
            
            // Projetos por prioridade
            $stmt = $this->db->prepare("SELECT priority, COUNT(*) as count FROM {$this->table} GROUP BY priority");
            $stmt->execute();
            $priorityStats = $stmt->fetchAll();
            $stats['by_priority'] = [];
            foreach ($priorityStats as $stat) {
                $stats['by_priority'][$stat['priority']] = $stat['count'];
            }
            
            $this->jsonResponse($stats);
            
        } catch (Exception $e) {
            $this->jsonResponse(['error' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Obter opções de configuração
     */
    public function getOptions() {
        $options = [
            'status' => [
                'planning' => 'Planejamento',
                'active' => 'Ativo',
                'in_progress' => 'Em Andamento',
                'on_hold' => 'Em Espera',
                'completed' => 'Concluído',
                'cancelled' => 'Cancelado'
            ],
            'priority' => [
                'low' => 'Baixa',
                'medium' => 'Média',
                'high' => 'Alta',
                'urgent' => 'Urgente'
            ],
            'task_status' => [
                'pending' => 'Pendente',
                'in_progress' => 'Em Andamento',
                'completed' => 'Concluído',
                'cancelled' => 'Cancelado'
            ]
        ];
        
        $this->jsonResponse($options);
    }
    
    /**
     * Listar clientes para seleção
     */
    public function getCustomers() {
        try {
            $stmt = $this->db->prepare("SELECT id, name, email FROM customers WHERE active = 1 ORDER BY name");
            $stmt->execute();
            $customers = $stmt->fetchAll();
            
            $this->jsonResponse(['customers' => $customers]);
            
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
                case 'get_projects':
                    $this->index();
                    break;
                    
                case 'get_project':
                    if (!$id) {
                        $this->jsonResponse(['error' => 'ID é obrigatório'], 400);
                        return;
                    }
                    $this->show($id);
                    break;
                    
                case 'create_project':
                    $this->store();
                    break;
                    
                case 'update_project':
                    if (!$id) {
                        $this->jsonResponse(['error' => 'ID é obrigatório'], 400);
                        return;
                    }
                    $this->update($id);
                    break;
                    
                case 'delete_project':
                    if (!$id) {
                        $this->jsonResponse(['error' => 'ID é obrigatório'], 400);
                        return;
                    }
                    $this->delete($id);
                    break;
                    
                case 'manage_tasks':
                    if (!$id) {
                        $this->jsonResponse(['error' => 'Project ID é obrigatório'], 400);
                        return;
                    }
                    $this->manageTasks($id);
                    break;
                    
                case 'get_project_stats':
                    $this->getStats();
                    break;
                    
                case 'get_project_options':
                    $this->getOptions();
                    break;
                    
                case 'get_project_customers':
                    $this->getCustomers();
                    break;
                    
                default:
                    $this->jsonResponse(['error' => 'Ação não encontrada'], 404);
            }
            
        } catch (Exception $e) {
            $this->logActivity('error', 'projects', $id, "Erro na ação '$action': " . $e->getMessage());
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
        return $_SESSION['user_id'] ?? 1;
    }
}