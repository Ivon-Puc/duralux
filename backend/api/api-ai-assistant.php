<?php
/**
 * DURALUX CRM - API AI Assistant v8.0
 * Endpoints para integração com o assistente de IA
 * 
 * @version 8.0
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../config/database.php';
require_once '../classes/DuraluxAIAssistant.php';

class AIAssistantAPI {
    private $db;
    private $ai;
    
    public function __construct() {
        try {
            $this->db = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
                ]
            );
            
            $this->ai = new DuraluxAIAssistant($this->db);
            
        } catch (Exception $e) {
            $this->sendError("Erro de conexão: " . $e->getMessage(), 500);
        }
    }
    
    public function handleRequest() {
        $method = $_SERVER['REQUEST_METHOD'];
        $path = $_GET['endpoint'] ?? '';
        
        try {
            switch ($path) {
                case 'chat':
                    if ($method === 'POST') {
                        $this->handleChatMessage();
                    } else {
                        $this->sendError("Método não permitido", 405);
                    }
                    break;
                    
                case 'insights':
                    if ($method === 'GET') {
                        $this->getInsights();
                    } else {
                        $this->sendError("Método não permitido", 405);
                    }
                    break;
                    
                case 'predictions':
                    if ($method === 'GET') {
                        $this->getPredictions();
                    } elseif ($method === 'POST') {
                        $this->generatePrediction();
                    } else {
                        $this->sendError("Método não permitido", 405);
                    }
                    break;
                    
                case 'conversation-history':
                    if ($method === 'GET') {
                        $this->getConversationHistory();
                    } else {
                        $this->sendError("Método não permitido", 405);
                    }
                    break;
                    
                case 'auto-insights':
                    if ($method === 'POST') {
                        $this->generateAutoInsights();
                    } else {
                        $this->sendError("Método não permitido", 405);
                    }
                    break;
                    
                case 'user-preferences':
                    if ($method === 'GET') {
                        $this->getUserPreferences();
                    } elseif ($method === 'POST') {
                        $this->updateUserPreferences();
                    } else {
                        $this->sendError("Método não permitido", 405);
                    }
                    break;
                    
                case 'smart-suggestions':
                    if ($method === 'GET') {
                        $this->getSmartSuggestions();
                    } else {
                        $this->sendError("Método não permitido", 405);
                    }
                    break;
                    
                case 'ai-status':
                    if ($method === 'GET') {
                        $this->getAIStatus();
                    } else {
                        $this->sendError("Método não permitido", 405);
                    }
                    break;
                    
                default:
                    $this->sendError("Endpoint não encontrado: $path", 404);
            }
            
        } catch (Exception $e) {
            error_log("Erro na API AI: " . $e->getMessage());
            $this->sendError("Erro interno do servidor", 500);
        }
    }
    
    /**
     * Processa mensagem do chatbot
     */
    private function handleChatMessage() {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($input['message']) || empty(trim($input['message']))) {
            $this->sendError("Mensagem é obrigatória", 400);
            return;
        }
        
        $message = trim($input['message']);
        $session_id = $input['session_id'] ?? null;
        
        $response = $this->ai->processChatMessage($message, $session_id);
        
        $this->sendSuccess($response);
    }
    
    /**
     * Obtém insights recentes
     */
    private function getInsights() {
        $limit = (int)($_GET['limit'] ?? 10);
        $category = $_GET['category'] ?? null;
        
        try {
            $sql = "SELECT * FROM ai_insights WHERE status != 'dismissed'";
            $params = [];
            
            if ($category) {
                $sql .= " AND category = ?";
                $params[] = $category;
            }
            
            $sql .= " ORDER BY 
                CASE priority
                    WHEN 'critical' THEN 1
                    WHEN 'high' THEN 2
                    WHEN 'medium' THEN 3
                    WHEN 'low' THEN 4
                END,
                created_at DESC
            LIMIT ?";
            
            $params[] = $limit;
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $insights = $stmt->fetchAll();
            
            // Decodifica campos JSON
            foreach ($insights as &$insight) {
                $insight['action_suggestions'] = json_decode($insight['action_suggestions'] ?? '[]', true);
                $insight['data'] = json_decode($insight['data'] ?? '{}', true);
                $insight['created_at_formatted'] = date('d/m/Y H:i', strtotime($insight['created_at']));
            }
            
            $this->sendSuccess([
                'insights' => $insights,
                'total' => count($insights)
            ]);
            
        } catch (Exception $e) {
            error_log("Erro ao buscar insights: " . $e->getMessage());
            $this->sendError("Erro ao buscar insights", 500);
        }
    }
    
    /**
     * Obtém predições
     */
    private function getPredictions() {
        $entity_type = $_GET['entity_type'] ?? null;
        $entity_id = $_GET['entity_id'] ?? null;
        $limit = (int)($_GET['limit'] ?? 20);
        
        try {
            $sql = "SELECT * FROM ai_predictions WHERE 1=1";
            $params = [];
            
            if ($entity_type) {
                $sql .= " AND entity_type = ?";
                $params[] = $entity_type;
            }
            
            if ($entity_id) {
                $sql .= " AND entity_id = ?";
                $params[] = $entity_id;
            }
            
            $sql .= " ORDER BY created_at DESC LIMIT ?";
            $params[] = $limit;
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $predictions = $stmt->fetchAll();
            
            // Formata dados
            foreach ($predictions as &$prediction) {
                $prediction['features_used'] = json_decode($prediction['features_used'] ?? '[]', true);
                $prediction['created_at_formatted'] = date('d/m/Y H:i', strtotime($prediction['created_at']));
                $prediction['prediction_value_formatted'] = number_format($prediction['prediction_value'], 2, ',', '.');
            }
            
            $this->sendSuccess([
                'predictions' => $predictions,
                'total' => count($predictions)
            ]);
            
        } catch (Exception $e) {
            error_log("Erro ao buscar predições: " . $e->getMessage());
            $this->sendError("Erro ao buscar predições", 500);
        }
    }
    
    /**
     * Gera nova predição
     */
    private function generatePrediction() {
        $input = json_decode(file_get_contents('php://input'), true);
        
        $entity_type = $input['entity_type'] ?? null;
        $entity_id = $input['entity_id'] ?? null;
        
        if (!$entity_type) {
            $this->sendError("Tipo de entidade é obrigatório", 400);
            return;
        }
        
        $prediction = $this->ai->generatePredictions($entity_type, $entity_id);
        
        if ($prediction) {
            $this->sendSuccess([
                'prediction' => $prediction,
                'message' => 'Predição gerada com sucesso'
            ]);
        } else {
            $this->sendError("Não foi possível gerar predição", 400);
        }
    }
    
    /**
     * Obtém histórico de conversas
     */
    private function getConversationHistory() {
        $session_id = $_GET['session_id'] ?? null;
        $limit = (int)($_GET['limit'] ?? 50);
        $user_id = $_SESSION['user_id'] ?? 1;
        
        try {
            $sql = "SELECT * FROM ai_conversations WHERE user_id = ?";
            $params = [$user_id];
            
            if ($session_id) {
                $sql .= " AND session_id = ?";
                $params[] = $session_id;
            }
            
            $sql .= " ORDER BY created_at DESC LIMIT ?";
            $params[] = $limit;
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $conversations = $stmt->fetchAll();
            
            // Formata dados
            foreach ($conversations as &$conversation) {
                $conversation['created_at_formatted'] = date('d/m/Y H:i:s', strtotime($conversation['created_at']));
                $conversation['context_data'] = json_decode($conversation['context_data'] ?? '{}', true);
            }
            
            $this->sendSuccess([
                'conversations' => array_reverse($conversations), // Mais antigos primeiro
                'total' => count($conversations)
            ]);
            
        } catch (Exception $e) {
            error_log("Erro ao buscar histórico: " . $e->getMessage());
            $this->sendError("Erro ao buscar histórico", 500);
        }
    }
    
    /**
     * Gera insights automáticos
     */
    private function generateAutoInsights() {
        $insights = $this->ai->generateAutomaticInsights();
        
        $this->sendSuccess([
            'insights' => $insights,
            'message' => 'Insights automáticos gerados',
            'count' => count($insights)
        ]);
    }
    
    /**
     * Obtém preferências do usuário
     */
    private function getUserPreferences() {
        $user_id = $_SESSION['user_id'] ?? 1;
        
        try {
            $stmt = $this->db->prepare("SELECT * FROM ai_user_preferences WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $preferences = $stmt->fetch();
            
            if (!$preferences) {
                // Retorna preferências padrão
                $preferences = [
                    'language' => 'pt-BR',
                    'ai_personality' => 'professional',
                    'interaction_level' => 'intermediate',
                    'notification_preferences' => '{}',
                    'preferred_insights' => '{}',
                    'automation_settings' => '{}'
                ];
            } else {
                // Decodifica campos JSON
                $preferences['notification_preferences'] = json_decode($preferences['notification_preferences'] ?? '{}', true);
                $preferences['preferred_insights'] = json_decode($preferences['preferred_insights'] ?? '{}', true);
                $preferences['automation_settings'] = json_decode($preferences['automation_settings'] ?? '{}', true);
            }
            
            $this->sendSuccess(['preferences' => $preferences]);
            
        } catch (Exception $e) {
            error_log("Erro ao buscar preferências: " . $e->getMessage());
            $this->sendError("Erro ao buscar preferências", 500);
        }
    }
    
    /**
     * Atualiza preferências do usuário
     */
    private function updateUserPreferences() {
        $input = json_decode(file_get_contents('php://input'), true);
        $user_id = $_SESSION['user_id'] ?? 1;
        
        try {
            $stmt = $this->db->prepare("
                INSERT INTO ai_user_preferences 
                (user_id, language, ai_personality, interaction_level, 
                 notification_preferences, preferred_insights, automation_settings)
                VALUES (?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                language = VALUES(language),
                ai_personality = VALUES(ai_personality),
                interaction_level = VALUES(interaction_level),
                notification_preferences = VALUES(notification_preferences),
                preferred_insights = VALUES(preferred_insights),
                automation_settings = VALUES(automation_settings)
            ");
            
            $stmt->execute([
                $user_id,
                $input['language'] ?? 'pt-BR',
                $input['ai_personality'] ?? 'professional',
                $input['interaction_level'] ?? 'intermediate',
                json_encode($input['notification_preferences'] ?? []),
                json_encode($input['preferred_insights'] ?? []),
                json_encode($input['automation_settings'] ?? [])
            ]);
            
            $this->sendSuccess(['message' => 'Preferências atualizadas com sucesso']);
            
        } catch (Exception $e) {
            error_log("Erro ao atualizar preferências: " . $e->getMessage());
            $this->sendError("Erro ao atualizar preferências", 500);
        }
    }
    
    /**
     * Obtém sugestões inteligentes
     */
    private function getSmartSuggestions() {
        $context = $_GET['context'] ?? 'general';
        
        try {
            $suggestions = [];
            
            switch ($context) {
                case 'dashboard':
                    $suggestions = [
                        "Ver resumo de leads hoje",
                        "Analisar performance de vendas",
                        "Projetos com prazo próximo",
                        "Gerar relatório executivo"
                    ];
                    break;
                    
                case 'leads':
                    $suggestions = [
                        "Leads não contactados",
                        "Leads com maior score",
                        "Taxa de conversão atual",
                        "Origem dos melhores leads"
                    ];
                    break;
                    
                case 'projects':
                    $suggestions = [
                        "Projetos atrasados",
                        "Projetos mais rentáveis",
                        "Timeline de entregas",
                        "Produtividade da equipe"
                    ];
                    break;
                    
                default:
                    $suggestions = [
                        "Como posso ajudá-lo?",
                        "Resumo do dia",
                        "Próximas tarefas",
                        "Insights importantes"
                    ];
            }
            
            $this->sendSuccess([
                'suggestions' => $suggestions,
                'context' => $context
            ]);
            
        } catch (Exception $e) {
            error_log("Erro ao gerar sugestões: " . $e->getMessage());
            $this->sendError("Erro ao gerar sugestões", 500);
        }
    }
    
    /**
     * Obtém status do sistema de IA
     */
    private function getAIStatus() {
        try {
            // Estatísticas do sistema
            $stats = [
                'conversations_today' => $this->getConversationsToday(),
                'insights_pending' => $this->getPendenteInsights(),
                'predictions_accuracy' => $this->getPredictionsAccuracy(),
                'system_health' => 'operational'
            ];
            
            // Modelos disponíveis
            $models = [
                ['name' => 'Lead Scoring', 'status' => 'active', 'accuracy' => 85],
                ['name' => 'Churn Prediction', 'status' => 'active', 'accuracy' => 78],
                ['name' => 'Receita Forecast', 'status' => 'active', 'accuracy' => 82],
                ['name' => 'Sentiment Analysis', 'status' => 'active', 'accuracy' => 91]
            ];
            
            $this->sendSuccess([
                'status' => 'operational',
                'statistics' => $stats,
                'models' => $models,
                'version' => '8.0',
                'last_update' => date('c')
            ]);
            
        } catch (Exception $e) {
            error_log("Erro ao obter status: " . $e->getMessage());
            $this->sendError("Erro ao obter status", 500);
        }
    }
    
    // Métodos auxiliares
    private function getConversationsToday() {
        try {
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM ai_conversations WHERE DATE(created_at) = CURDATE()");
            $stmt->execute();
            return (int)$stmt->fetchColumn();
        } catch (Exception $e) {
            return 0;
        }
    }
    
    private function getPendenteInsights() {
        try {
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM ai_insights WHERE status = 'new'");
            $stmt->execute();
            return (int)$stmt->fetchColumn();
        } catch (Exception $e) {
            return 0;
        }
    }
    
    private function getPredictionsAccuracy() {
        try {
            $stmt = $this->db->prepare("
                SELECT AVG(accuracy) FROM ai_predictions 
                WHERE accuracy IS NOT NULL 
                AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            ");
            $stmt->execute();
            $accuracy = $stmt->fetchColumn();
            return $accuracy ? round($accuracy * 100, 1) : 0;
        } catch (Exception $e) {
            return 0;
        }
    }
    
    private function sendSuccess($data) {
        echo json_encode([
            'success' => true,
            'data' => $data,
            'timestamp' => time()
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }
    
    private function sendError($message, $code = 400) {
        http_response_code($code);
        echo json_encode([
            'success' => false,
            'error' => $message,
            'code' => $code,
            'timestamp' => time()
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }
}

// Inicializa e processa requisição
session_start();
$api = new AIAssistantAPI();
$api->handleRequest();
?>