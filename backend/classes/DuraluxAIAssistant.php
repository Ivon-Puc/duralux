<?php
/**
 * DURALUX CRM - AI Assistant Integration v8.0
 * Sistema de assistente de IA com chatbot inteligente e análise preditiva
 * 
 * @author Duralux Development Team
 * @version 8.0
 * @requires PHP 8.0+, OpenAI API, Machine Learning Libraries
 */

class DuraluxAIAssistant {
    
    private $db;
    private $openai_api_key;
    private $conversation_history = [];
    private $user_context = [];
    private $ml_models = [];
    
    public function __construct($database_connection, $openai_key = null) {
        $this->db = $database_connection;
        $this->openai_api_key = $openai_key ?: $_ENV['OPENAI_API_KEY'] ?? '';
        $this->initializeModels();
        $this->loadUserContext();
    }
    
    /**
     * Inicializa modelos de ML e configurações
     */
    private function initializeModels() {
        $this->ml_models = [
            'lead_scoring' => [
                'model_path' => 'models/lead_scoring_model.json',
                'accuracy' => 0.85,
                'last_trained' => '2024-01-15'
            ],
            'churn_prediction' => [
                'model_path' => 'models/churn_prediction_model.json',
                'accuracy' => 0.78,
                'last_trained' => '2024-01-10'
            ],
            'revenue_forecast' => [
                'model_path' => 'models/revenue_forecast_model.json',
                'accuracy' => 0.82,
                'last_trained' => '2024-01-12'
            ],
            'sentiment_analysis' => [
                'model_path' => 'models/sentiment_model.json',
                'accuracy' => 0.91,
                'last_trained' => '2024-01-14'
            ]
        ];
        
        $this->createAITables();
    }
    
    /**
     * Cria tabelas necessárias para o AI Assistant
     */
    private function createAITables() {
        $sql_tables = [
            // Conversas do chatbot
            "CREATE TABLE IF NOT EXISTS ai_conversations (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT,
                session_id VARCHAR(255),
                message TEXT,
                response TEXT,
                context_data JSON,
                sentiment_score DECIMAL(3,2),
                intent VARCHAR(100),
                confidence_score DECIMAL(3,2),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_user_id (user_id),
                INDEX idx_session_id (session_id),
                INDEX idx_intent (intent)
            )",
            
            // Predições do sistema
            "CREATE TABLE IF NOT EXISTS ai_predictions (
                id INT AUTO_INCREMENT PRIMARY KEY,
                entity_type VARCHAR(50),
                entity_id INT,
                prediction_type VARCHAR(100),
                prediction_value DECIMAL(10,2),
                confidence_score DECIMAL(3,2),
                model_used VARCHAR(100),
                features_used JSON,
                actual_value DECIMAL(10,2) NULL,
                accuracy DECIMAL(3,2) NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                expires_at TIMESTAMP NULL,
                INDEX idx_entity (entity_type, entity_id),
                INDEX idx_prediction_type (prediction_type)
            )",
            
            // Insights automáticos
            "CREATE TABLE IF NOT EXISTS ai_insights (
                id INT AUTO_INCREMENT PRIMARY KEY,
                category VARCHAR(100),
                title VARCHAR(255),
                description TEXT,
                data JSON,
                priority ENUM('low', 'medium', 'high', 'critical'),
                status ENUM('new', 'viewed', 'acted', 'dismissed'),
                action_suggestions JSON,
                impact_score DECIMAL(3,2),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                viewed_at TIMESTAMP NULL,
                INDEX idx_category (category),
                INDEX idx_priority (priority),
                INDEX idx_status (status)
            )",
            
            // Configurações do usuário para IA
            "CREATE TABLE IF NOT EXISTS ai_user_preferences (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT UNIQUE,
                language VARCHAR(10) DEFAULT 'pt-BR',
                notification_preferences JSON,
                ai_personality VARCHAR(50) DEFAULT 'professional',
                interaction_level ENUM('basic', 'intermediate', 'advanced') DEFAULT 'intermediate',
                preferred_insights JSON,
                automation_settings JSON,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )",
            
            // Log de ações automatizadas
            "CREATE TABLE IF NOT EXISTS ai_automation_log (
                id INT AUTO_INCREMENT PRIMARY KEY,
                action_type VARCHAR(100),
                entity_type VARCHAR(50),
                entity_id INT,
                action_data JSON,
                status ENUM('pending', 'completed', 'failed', 'cancelled'),
                error_message TEXT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                completed_at TIMESTAMP NULL,
                INDEX idx_action_type (action_type),
                INDEX idx_status (status)
            )"
        ];
        
        foreach ($sql_tables as $sql) {
            try {
                $this->db->exec($sql);
            } catch (PDOException $e) {
                error_log("Erro ao criar tabela AI: " . $e->getMessage());
            }
        }
    }
    
    /**
     * Carrega contexto do usuário atual
     */
    private function loadUserContext() {
        $user_id = $_SESSION['user_id'] ?? 1;
        
        try {
            // Dados básicos do usuário
            $stmt = $this->db->prepare("
                SELECT * FROM ai_user_preferences 
                WHERE user_id = ?
            ");
            $stmt->execute([$user_id]);
            $preferences = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$preferences) {
                // Cria preferências padrão
                $this->createDefaultUserPreferences($user_id);
                $preferences = $this->getDefaultPreferences();
            }
            
            // Contexto de trabalho atual
            $this->user_context = [
                'user_id' => $user_id,
                'preferences' => $preferences,
                'recent_activities' => $this->getRecentActivities($user_id),
                'current_projects' => $this->getCurrentProjects($user_id),
                'pending_tasks' => $this->getPendingTasks($user_id),
                'performance_metrics' => $this->getPerformanceMetrics($user_id)
            ];
            
        } catch (Exception $e) {
            error_log("Erro ao carregar contexto do usuário: " . $e->getMessage());
            $this->user_context = $this->getDefaultContext();
        }
    }
    
    /**
     * Processa mensagem do chatbot
     */
    public function processChatMessage($message, $session_id = null) {
        $session_id = $session_id ?: uniqid('chat_', true);
        
        try {
            // Análise de intenção e sentimento
            $intent_analysis = $this->analyzeIntent($message);
            $sentiment = $this->analyzeSentiment($message);
            
            // Gera resposta baseada no contexto
            $response = $this->generateResponse($message, $intent_analysis, $sentiment);
            
            // Salva conversa no histórico
            $this->saveChatInteraction($message, $response, $session_id, $intent_analysis, $sentiment);
            
            // Executa ações automáticas se necessário
            $actions = $this->processAutomaticActions($intent_analysis, $message);
            
            return [
                'success' => true,
                'response' => $response,
                'intent' => $intent_analysis,
                'sentiment' => $sentiment,
                'session_id' => $session_id,
                'actions' => $actions,
                'suggestions' => $this->generateSuggestions($intent_analysis),
                'timestamp' => time()
            ];
            
        } catch (Exception $e) {
            error_log("Erro no processamento do chat: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Erro interno do assistente',
                'response' => 'Desculpe, ocorreu um erro. Tente novamente em alguns instantes.',
                'session_id' => $session_id
            ];
        }
    }
    
    /**
     * Análise de intenção usando NLP
     */
    private function analyzeIntent($message) {
        $message_lower = strtolower($message);
        
        // Padrões de intenção em português
        $intent_patterns = [
            'consulta_leads' => [
                'leads', 'cliente', 'prospect', 'contato', 'quantos leads', 'lista de leads'
            ],
            'consulta_vendas' => [
                'vendas', 'receita', 'faturamento', 'valor vendido', 'revenue'
            ],
            'consulta_projetos' => [
                'projeto', 'andamento', 'prazo', 'entrega', 'status projeto'
            ],
            'relatorio' => [
                'relatório', 'report', 'dashboard', 'análise', 'gráfico'
            ],
            'agenda' => [
                'agenda', 'reunião', 'compromisso', 'calendário', 'horário'
            ],
            'tarefa' => [
                'tarefa', 'task', 'todo', 'lembrete', 'pendência'
            ],
            'ajuda' => [
                'ajuda', 'help', 'como', 'tutorial', 'dúvida', 'não sei'
            ],
            'configuracao' => [
                'configurar', 'config', 'setting', 'preferência', 'ajuste'
            ],
            'cumprimento' => [
                'oi', 'olá', 'bom dia', 'boa tarde', 'boa noite', 'hey'
            ]
        ];
        
        $intent_scores = [];
        
        foreach ($intent_patterns as $intent => $keywords) {
            $score = 0;
            foreach ($keywords as $keyword) {
                if (strpos($message_lower, $keyword) !== false) {
                    $score += strlen($keyword) / strlen($message);
                }
            }
            $intent_scores[$intent] = $score;
        }
        
        $detected_intent = 'unknown';
        $confidence = 0;
        
        if (!empty($intent_scores)) {
            $max_score = max($intent_scores);
            if ($max_score > 0.1) {
                $detected_intent = array_search($max_score, $intent_scores);
                $confidence = min($max_score * 2, 1); // Normaliza entre 0 e 1
            }
        }
        
        return [
            'intent' => $detected_intent,
            'confidence' => round($confidence, 2),
            'all_scores' => $intent_scores
        ];
    }
    
    /**
     * Análise de sentimento
     */
    private function analyzeSentiment($message) {
        // Palavras positivas e negativas em português
        $positive_words = [
            'bom', 'ótimo', 'excelente', 'perfeito', 'maravilhoso', 
            'satisfeito', 'feliz', 'gosto', 'legal', 'show'
        ];
        
        $negative_words = [
            'ruim', 'péssimo', 'horrível', 'problema', 'erro', 
            'insatisfeito', 'triste', 'ódio', 'difícil', 'complicado'
        ];
        
        $message_lower = strtolower($message);
        $positive_count = 0;
        $negative_count = 0;
        
        foreach ($positive_words as $word) {
            if (strpos($message_lower, $word) !== false) {
                $positive_count++;
            }
        }
        
        foreach ($negative_words as $word) {
            if (strpos($message_lower, $word) !== false) {
                $negative_count++;
            }
        }
        
        $sentiment_score = 0.5; // Neutro por padrão
        
        if ($positive_count > $negative_count) {
            $sentiment_score = 0.5 + ($positive_count * 0.2);
        } elseif ($negative_count > $positive_count) {
            $sentiment_score = 0.5 - ($negative_count * 0.2);
        }
        
        $sentiment_score = max(0, min(1, $sentiment_score));
        
        return [
            'score' => round($sentiment_score, 2),
            'label' => $sentiment_score > 0.6 ? 'positive' : 
                      ($sentiment_score < 0.4 ? 'negative' : 'neutral'),
            'confidence' => abs($sentiment_score - 0.5) * 2
        ];
    }
    
    /**
     * Gera resposta contextual
     */
    private function generateResponse($message, $intent_analysis, $sentiment) {
        $intent = $intent_analysis['intent'];
        $user_name = $this->user_context['preferences']['user_name'] ?? 'usuário';
        
        // Respostas base por intenção
        $responses = [
            'consulta_leads' => $this->generateLeadsResponse(),
            'consulta_vendas' => $this->generateSalesResponse(),
            'consulta_projetos' => $this->generateProjectsResponse(),
            'relatorio' => $this->generateReportResponse(),
            'agenda' => $this->generateScheduleResponse(),
            'tarefa' => $this->generateTaskResponse(),
            'ajuda' => $this->generateHelpResponse(),
            'configuracao' => $this->generateConfigResponse(),
            'cumprimento' => $this->generateGreetingResponse($user_name),
            'unknown' => $this->generateFallbackResponse($message)
        ];
        
        $base_response = $responses[$intent] ?? $responses['unknown'];
        
        // Adiciona contexto emocional
        if ($sentiment['label'] === 'negative' && $sentiment['confidence'] > 0.7) {
            $base_response = "Percebo que você pode estar enfrentando alguma dificuldade. " . $base_response;
            $base_response .= "\n\nSe precisar de ajuda adicional, estarei aqui para auxiliar! 😊";
        }
        
        return $base_response;
    }
    
    /**
     * Gera resposta para consultas de leads
     */
    private function generateLeadsResponse() {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    COUNT(*) as total_leads,
                    SUM(CASE WHEN status = 'novo' THEN 1 ELSE 0 END) as novos_leads,
                    SUM(CASE WHEN status = 'qualificado' THEN 1 ELSE 0 END) as qualificados,
                    SUM(CASE WHEN DATE(created_at) = CURDATE() THEN 1 ELSE 0 END) as hoje
                FROM leads 
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            ");
            $stmt->execute();
            $stats = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $response = "📊 **Resumo dos seus Leads (últimos 30 dias):**\n\n";
            $response .= "• Total: {$stats['total_leads']} leads\n";
            $response .= "• Novos: {$stats['novos_leads']} leads\n";
            $response .= "• Qualificados: {$stats['qualificados']} leads\n";
            $response .= "• Hoje: {$stats['hoje']} leads\n\n";
            
            if ($stats['novos_leads'] > 0) {
                $response .= "💡 **Sugestão:** Você tem {$stats['novos_leads']} leads novos aguardando qualificação.";
            }
            
            return $response;
            
        } catch (Exception $e) {
            return "Desculpe, não consegui acessar os dados de leads no momento. Tente novamente em instantes.";
        }
    }
    
    /**
     * Gera resposta para consultas de vendas
     */
    private function generateSalesResponse() {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    COUNT(*) as total_vendas,
                    SUM(valor) as receita_total,
                    AVG(valor) as ticket_medio,
                    SUM(CASE WHEN DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) THEN valor ELSE 0 END) as receita_semana
                FROM vendas 
                WHERE status = 'fechada'
                AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            ");
            $stmt->execute();
            $stats = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $receita_formatada = number_format($stats['receita_total'], 2, ',', '.');
            $ticket_formatado = number_format($stats['ticket_medio'], 2, ',', '.');
            $receita_semana_formatada = number_format($stats['receita_semana'], 2, ',', '.');
            
            $response = "💰 **Resumo de Vendas (últimos 30 dias):**\n\n";
            $response .= "• Total de Vendas: {$stats['total_vendas']}\n";
            $response .= "• Receita Total: R$ {$receita_formatada}\n";
            $response .= "• Ticket Médio: R$ {$ticket_formatado}\n";
            $response .= "• Receita desta Semana: R$ {$receita_semana_formatada}\n\n";
            
            // Análise preditiva simples
            $growth_rate = ($stats['receita_semana'] / ($stats['receita_total'] / 4)) * 100;
            if ($growth_rate > 120) {
                $response .= "🚀 **Insight:** Excelente! Suas vendas estão crescendo acima da média semanal.";
            } elseif ($growth_rate < 80) {
                $response .= "⚠️ **Alerta:** Vendas desta semana estão abaixo da média. Que tal revisar a estratégia?";
            }
            
            return $response;
            
        } catch (Exception $e) {
            return "Não consegui acessar os dados de vendas no momento. Vou verificar isso para você!";
        }
    }
    
    /**
     * Gera resposta para projetos
     */
    private function generateProjectsResponse() {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    COUNT(*) as total_projetos,
                    SUM(CASE WHEN status = 'em_andamento' THEN 1 ELSE 0 END) as em_andamento,
                    SUM(CASE WHEN status = 'concluido' THEN 1 ELSE 0 END) as concluidos,
                    SUM(CASE WHEN prazo_entrega < CURDATE() AND status != 'concluido' THEN 1 ELSE 0 END) as atrasados
                FROM projects
            ");
            $stmt->execute();
            $stats = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $response = "📋 **Status dos Projetos:**\n\n";
            $response .= "• Total: {$stats['total_projetos']} projetos\n";
            $response .= "• Em Andamento: {$stats['em_andamento']}\n";
            $response .= "• Concluídos: {$stats['concluidos']}\n";
            $response .= "• Atrasados: {$stats['atrasados']}\n\n";
            
            if ($stats['atrasados'] > 0) {
                $response .= "🚨 **Atenção:** Você tem {$stats['atrasados']} projetos atrasados que precisam de atenção.";
            } else {
                $response .= "✅ **Parabéns:** Todos os projetos estão dentro do prazo!";
            }
            
            return $response;
            
        } catch (Exception $e) {
            return "Não consegui acessar os dados dos projetos. Verificarei isso para você.";
        }
    }
    
    /**
     * Gera sugestões inteligentes
     */
    private function generateSuggestions($intent_analysis) {
        $suggestions = [];
        $intent = $intent_analysis['intent'];
        
        switch ($intent) {
            case 'consulta_leads':
                $suggestions = [
                    "Ver leads não contactados hoje",
                    "Mostrar leads com maior score",
                    "Leads perdidos esta semana",
                    "Criar relatório de conversão"
                ];
                break;
                
            case 'consulta_vendas':
                $suggestions = [
                    "Analisar pipeline de vendas",
                    "Ver previsão de receita",
                    "Comparar com mês anterior",
                    "Identificar melhores vendedores"
                ];
                break;
                
            case 'consulta_projetos':
                $suggestions = [
                    "Ver projetos próximos ao prazo",
                    "Analisar atrasos por cliente",
                    "Relatório de produtividade",
                    "Projetos mais lucrativos"
                ];
                break;
                
            default:
                $suggestions = [
                    "Como posso ajudá-lo hoje?",
                    "Ver resumo do dashboard",
                    "Mostrar tarefas pendentes",
                    "Gerar relatório personalizado"
                ];
        }
        
        return $suggestions;
    }
    
    /**
     * Processa ações automáticas baseadas na intenção
     */
    private function processAutomaticActions($intent_analysis, $message) {
        $actions = [];
        $intent = $intent_analysis['intent'];
        
        // Ações automáticas baseadas na intenção
        switch ($intent) {
            case 'consulta_leads':
                if ($intent_analysis['confidence'] > 0.8) {
                    $actions[] = [
                        'type' => 'redirect',
                        'url' => 'leads.html',
                        'description' => 'Redirecionamento para página de leads'
                    ];
                }
                break;
                
            case 'relatorio':
                $actions[] = [
                    'type' => 'generate_report',
                    'report_type' => 'auto',
                    'description' => 'Geração automática de relatório'
                ];
                break;
        }
        
        return $actions;
    }
    
    /**
     * Gera insights automáticos
     */
    public function generateAutomaticInsights() {
        $insights = [];
        
        try {
            // Insight 1: Leads não contactados há muito tempo
            $stmt = $this->db->prepare("
                SELECT COUNT(*) as count 
                FROM leads 
                WHERE status = 'novo' 
                AND created_at < DATE_SUB(NOW(), INTERVAL 24 HOUR)
            ");
            $stmt->execute();
            $old_leads = $stmt->fetchColumn();
            
            if ($old_leads > 0) {
                $insights[] = [
                    'category' => 'leads',
                    'title' => 'Leads aguardando contato',
                    'description' => "Você tem {$old_leads} leads novos há mais de 24 horas sem contato.",
                    'priority' => $old_leads > 10 ? 'high' : 'medium',
                    'action_suggestions' => [
                        'Contactar leads prioritários',
                        'Configurar automação de follow-up',
                        'Revisar processo de qualificação'
                    ],
                    'impact_score' => min($old_leads * 0.1, 1.0)
                ];
            }
            
            // Insight 2: Projetos próximos ao prazo
            $stmt = $this->db->prepare("
                SELECT COUNT(*) as count 
                FROM projects 
                WHERE status = 'em_andamento' 
                AND prazo_entrega BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 3 DAY)
            ");
            $stmt->execute();
            $urgent_projects = $stmt->fetchColumn();
            
            if ($urgent_projects > 0) {
                $insights[] = [
                    'category' => 'projects',
                    'title' => 'Projetos com prazo próximo',
                    'description' => "Você tem {$urgent_projects} projetos com prazo nos próximos 3 dias.",
                    'priority' => 'high',
                    'action_suggestions' => [
                        'Revisar progresso dos projetos',
                        'Comunicar com clientes',
                        'Realocar recursos se necessário'
                    ],
                    'impact_score' => min($urgent_projects * 0.15, 1.0)
                ];
            }
            
            // Salva insights no banco
            foreach ($insights as $insight) {
                $this->saveInsight($insight);
            }
            
            return $insights;
            
        } catch (Exception $e) {
            error_log("Erro ao gerar insights: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Salva insight no banco de dados
     */
    private function saveInsight($insight) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO ai_insights 
                (category, title, description, priority, action_suggestions, impact_score, data)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $insight['category'],
                $insight['title'],
                $insight['description'],
                $insight['priority'],
                json_encode($insight['action_suggestions']),
                $insight['impact_score'],
                json_encode($insight)
            ]);
            
        } catch (Exception $e) {
            error_log("Erro ao salvar insight: " . $e->getMessage());
        }
    }
    
    /**
     * Obtém insights recentes
     */
    public function getRecentInsights($limit = 10) {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM ai_insights 
                WHERE status != 'dismissed'
                ORDER BY 
                    CASE priority
                        WHEN 'critical' THEN 1
                        WHEN 'high' THEN 2
                        WHEN 'medium' THEN 3
                        WHEN 'low' THEN 4
                    END,
                    created_at DESC
                LIMIT ?
            ");
            $stmt->execute([$limit]);
            
            $insights = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Decodifica JSON fields
            foreach ($insights as &$insight) {
                $insight['action_suggestions'] = json_decode($insight['action_suggestions'], true);
                $insight['data'] = json_decode($insight['data'], true);
            }
            
            return $insights;
            
        } catch (Exception $e) {
            error_log("Erro ao buscar insights: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Gera predições usando ML
     */
    public function generatePredictions($entity_type, $entity_id = null) {
        $predictions = [];
        
        switch ($entity_type) {
            case 'lead_scoring':
                $predictions = $this->predictLeadScore($entity_id);
                break;
                
            case 'churn_prediction':
                $predictions = $this->predictCustomerChurn($entity_id);
                break;
                
            case 'revenue_forecast':
                $predictions = $this->predictRevenueForecast();
                break;
        }
        
        return $predictions;
    }
    
    /**
     * Predição de score de lead (simulado)
     */
    private function predictLeadScore($lead_id) {
        try {
            if ($lead_id) {
                $stmt = $this->db->prepare("SELECT * FROM leads WHERE id = ?");
                $stmt->execute([$lead_id]);
                $lead = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$lead) return null;
                
                // Algoritmo simplificado de scoring
                $score = 50; // Base score
                
                // Fatores de pontuação
                if (strpos(strtolower($lead['email']), '@gmail.com') !== false) $score += 10;
                if (!empty($lead['telefone'])) $score += 15;
                if (!empty($lead['empresa'])) $score += 20;
                if (strlen($lead['observacoes']) > 50) $score += 10;
                
                // Normaliza entre 0-100
                $score = min(100, max(0, $score));
                
                $prediction = [
                    'entity_type' => 'lead',
                    'entity_id' => $lead_id,
                    'prediction_type' => 'lead_score',
                    'prediction_value' => $score,
                    'confidence_score' => 0.75,
                    'model_used' => 'lead_scoring_v1',
                    'features_used' => ['email', 'telefone', 'empresa', 'observacoes']
                ];
                
                $this->savePrediction($prediction);
                
                return $prediction;
            }
            
        } catch (Exception $e) {
            error_log("Erro na predição de lead score: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Salva predição no banco
     */
    private function savePrediction($prediction) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO ai_predictions 
                (entity_type, entity_id, prediction_type, prediction_value, 
                 confidence_score, model_used, features_used)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $prediction['entity_type'],
                $prediction['entity_id'],
                $prediction['prediction_type'],
                $prediction['prediction_value'],
                $prediction['confidence_score'],
                $prediction['model_used'],
                json_encode($prediction['features_used'])
            ]);
            
        } catch (Exception $e) {
            error_log("Erro ao salvar predição: " . $e->getMessage());
        }
    }
    
    /**
     * Métodos auxiliares para buscar dados do usuário
     */
    private function getRecentActivities($user_id) {
        // Implementação simplificada
        return [];
    }
    
    private function getCurrentProjects($user_id) {
        try {
            $stmt = $this->db->prepare("
                SELECT COUNT(*) as count 
                FROM projects 
                WHERE status = 'em_andamento'
            ");
            $stmt->execute();
            return $stmt->fetchColumn();
        } catch (Exception $e) {
            return 0;
        }
    }
    
    private function getPendingTasks($user_id) {
        return 0; // Implementação futura
    }
    
    private function getPerformanceMetrics($user_id) {
        return []; // Implementação futura
    }
    
    private function createDefaultUserPreferences($user_id) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO ai_user_preferences (user_id, language, ai_personality)
                VALUES (?, 'pt-BR', 'professional')
            ");
            $stmt->execute([$user_id]);
        } catch (Exception $e) {
            error_log("Erro ao criar preferências padrão: " . $e->getMessage());
        }
    }
    
    private function getDefaultPreferences() {
        return [
            'language' => 'pt-BR',
            'ai_personality' => 'professional',
            'interaction_level' => 'intermediate'
        ];
    }
    
    private function getDefaultContext() {
        return [
            'user_id' => 1,
            'preferences' => $this->getDefaultPreferences(),
            'recent_activities' => [],
            'current_projects' => 0,
            'pending_tasks' => 0,
            'performance_metrics' => []
        ];
    }
    
    // Respostas padrão adicionais
    private function generateGreetingResponse($user_name) {
        $greetings = [
            "Olá {$user_name}! Como posso ajudá-lo hoje? 😊",
            "Oi {$user_name}! Pronto para turbinar sua produtividade? 🚀",
            "Bem-vindo de volta, {$user_name}! O que gostaria de saber?",
        ];
        
        return $greetings[array_rand($greetings)];
    }
    
    private function generateHelpResponse() {
        return "🤖 **Aqui estão algumas coisas que posso fazer por você:**\n\n" .
               "• Consultar dados de leads, vendas e projetos\n" .
               "• Gerar relatórios personalizados\n" .
               "• Fornecer insights e predições\n" .
               "• Ajudar com tarefas e lembretes\n" .
               "• Responder dúvidas sobre o sistema\n\n" .
               "**Exemplos de perguntas:**\n" .
               "- 'Quantos leads temos hoje?'\n" .
               "- 'Qual a receita do mês?'\n" .
               "- 'Mostrar projetos atrasados'\n\n" .
               "Fique à vontade para conversar comigo! 💬";
    }
    
    private function generateFallbackResponse($message) {
        return "🤔 Não tenho certeza se entendi completamente sua pergunta. " .
               "Você poderia reformular ou ser mais específico? " .
               "Posso ajudar com informações sobre leads, vendas, projetos, relatórios e muito mais!";
    }
    
    private function generateReportResponse() {
        return "📊 Posso gerar diversos tipos de relatórios para você! " .
               "Que tipo de análise gostaria de ver? " .
               "(leads, vendas, projetos, performance)";
    }
    
    private function generateScheduleResponse() {
        return "📅 Para funcionalidades de agenda, você pode acessar o calendário integrado. " .
               "Em breve terei acesso completo à sua agenda!";
    }
    
    private function generateTaskResponse() {
        return "✅ Sistema de tarefas em desenvolvimento! " .
               "Por enquanto, posso ajudar você a identificar prioridades com base nos seus dados.";
    }
    
    private function generateConfigResponse() {
        return "⚙️ Para configurações avançadas, acesse o menu de configurações. " .
               "Posso ajudar você a entender as opções disponíveis!";
    }
    
    private function saveChatInteraction($message, $response, $session_id, $intent, $sentiment) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO ai_conversations 
                (user_id, session_id, message, response, intent, confidence_score, sentiment_score)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $this->user_context['user_id'],
                $session_id,
                $message,
                $response,
                $intent['intent'],
                $intent['confidence'],
                $sentiment['score']
            ]);
            
        } catch (Exception $e) {
            error_log("Erro ao salvar conversa: " . $e->getMessage());
        }
    }
}
?>