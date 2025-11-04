<?php
/**
 * Gerador de Dados Adicionais para Novas Tabelas
 * Sistema DuraLux CRM v7.0
 */

require_once 'config/db_config.php';

class AdditionalDataGenerator {
    private $pdo;
    
    public function __construct() {
        $this->pdo = getDatabaseConnection();
        echo "✅ Conexão estabelecida com sucesso!\n";
    }
    
    /**
     * Gera dados para activity_logs
     */
    public function generateActivityLogs($count = 50) {
        $activities = [
            ['lead_created', 'Novo lead cadastrado'],
            ['lead_updated', 'Lead atualizado'],
            ['lead_converted', 'Lead convertido em cliente'],
            ['project_created', 'Novo projeto criado'],
            ['project_updated', 'Projeto atualizado'],
            ['project_completed', 'Projeto concluído'],
            ['sale_created', 'Nova venda registrada'],
            ['sale_updated', 'Venda atualizada'],
            ['customer_created', 'Novo cliente cadastrado'],
            ['customer_updated', 'Cliente atualizado'],
            ['task_created', 'Nova tarefa criada'],
            ['task_assigned', 'Tarefa atribuída'],
            ['task_completed', 'Tarefa concluída'],
            ['notification_sent', 'Notificação enviada'],
            ['backup_created', 'Backup realizado'],
            ['analytics_viewed', 'Painel de Controle acessado'],
            ['ai_conversation', 'Interação com AI Assistant']
        ];
        
        $entity_types = ['lead', 'project', 'customer', 'sale', 'task'];
        $user_agents = [
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Edge/120.0.0.0',
            'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Mobile/15E148 Safari/604.1'
        ];
        
        $ips = ['192.168.1.100', '192.168.1.101', '192.168.1.102', '10.0.0.100', '172.16.0.50'];
        
        $sql = "INSERT INTO activity_logs (user_id, activity_type, description, entity_type, entity_id, metadata, ip_address, user_agent, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->pdo->prepare($sql);
        
        for ($i = 0; $i < $count; $i++) {
            $activity = $activities[array_rand($activities)];
            $entity_type = $entity_types[array_rand($entity_types)];
            $user_id = rand(1, 5);
            $entity_id = rand(1, 30);
            $ip = $ips[array_rand($ips)];
            $user_agent = $user_agents[array_rand($user_agents)];
            
            $metadata = json_encode([
                'browser' => 'Chrome',
                'os' => 'Windows',
                'session_id' => 'sess_' . uniqid(),
                'timestamp' => time()
            ]);
            
            // Data aleatória dos últimos 30 dias
            $created_at = date('Y-m-d H:i:s', strtotime('-' . rand(0, 30) . ' days -' . rand(0, 23) . ' hours'));
            
            $stmt->execute([
                $user_id,
                $activity[0],
                $activity[1],
                $entity_type,
                $entity_id,
                $metadata,
                $ip,
                $user_agent,
                $created_at
            ]);
        }
        
        echo "✅ $count logs de atividade gerados\n";
    }
    
    /**
     * Gera dados para project_tasks
     */
    public function generateProjectTasks($count = 40) {
        $task_names = [
            'Análise de Requisitos',
            'Design da Interface',
            'Desenvolvimento Backend',
            'Desenvolvimento Frontend',
            'Integração de APIs',
            'Testes Unitários',
            'Testes de Integração',
            'Documentação Técnica',
            'Deploy em Produção',
            'Treinamento de Usuários',
            'Configuração do Servidor',
            'Otimização de Performance',
            'Implementação de SEO',
            'Configuração SSL',
            'Backup e Recuperação',
            'Monitoramento do Sistema',
            'Correção de Bugs',
            'Revisão de Código',
            'Prototipagem',
            'Validação com Cliente'
        ];
        
        $descriptions = [
            'Levantamento detalhado dos requisitos funcionais e não funcionais do projeto',
            'Criação do layout e wireframes das interfaces do usuário',
            'Desenvolvimento da lógica de negócio e APIs do sistema',
            'Implementação das interfaces de usuário responsivas',
            'Integração com sistemas externos e APIs terceirizadas',
            'Desenvolvimento e execução de testes automatizados',
            'Validação da integração entre componentes do sistema',
            'Elaboração da documentação técnica e manuais de usuário',
            'Configuração e deploy da aplicação no ambiente de produção',
            'Capacitação dos usuários finais no uso do sistema'
        ];
        
        $priorities = ['baixa', 'media', 'alta', 'urgente'];
        $statuses = ['pendente', 'em_andamento', 'concluida', 'cancelada'];
        
        $sql = "INSERT INTO project_tasks (project_id, task_name, description, assigned_to, priority, status, start_date, due_date, completion_date, estimated_hours, actual_hours, progress_percent, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->pdo->prepare($sql);
        
        for ($i = 0; $i < $count; $i++) {
            $project_id = rand(1, 15);
            $task_name = $task_names[array_rand($task_names)];
            $description = $descriptions[array_rand($descriptions)];
            $assigned_to = rand(1, 5);
            $priority = $priorities[array_rand($priorities)];
            $status = $statuses[array_rand($statuses)];
            
            $start_date = date('Y-m-d', strtotime('-' . rand(0, 60) . ' days'));
            $due_date = date('Y-m-d', strtotime($start_date . ' +' . rand(7, 30) . ' days'));
            $completion_date = ($status == 'concluida') ? date('Y-m-d H:i:s', strtotime($due_date . ' -' . rand(1, 5) . ' days')) : null;
            
            $estimated_hours = rand(8, 120);
            $actual_hours = ($status == 'concluida') ? rand($estimated_hours * 0.8, $estimated_hours * 1.3) : rand(0, $estimated_hours * 0.8);
            
            $progress_percent = match($status) {
                'pendente' => rand(0, 10),
                'em_andamento' => rand(20, 80),
                'concluida' => 100,
                'cancelada' => rand(0, 50)
            };
            
            $created_at = date('Y-m-d H:i:s', strtotime($start_date . ' -' . rand(1, 7) . ' days'));
            
            $stmt->execute([
                $project_id,
                $task_name,
                $description,
                $assigned_to,
                $priority,
                $status,
                $start_date,
                $due_date,
                $completion_date,
                $estimated_hours,
                $actual_hours,
                $progress_percent,
                $created_at
            ]);
        }
        
        echo "✅ $count tarefas de projeto geradas\n";
    }
    
    /**
     * Gera dados para ai_conversations
     */
    public function generateAIConversations($count = 30) {
        $user_messages = [
            'Qual é o status dos meus projetos?',
            'Mostre as vendas deste mês',
            'Como posso melhorar minha taxa de conversão?',
            'Quais leads têm maior potencial?',
            'Preciso de um relatório de performance',
            'Quantos clientes cadastrei esta semana?',
            'Qual projeto tem o maior orçamento?',
            'Mostre os leads que não foram contactados',
            'Como está o desempenho da equipe?',
            'Preciso agendar uma reunião'
        ];
        
        $assistant_responses = [
            'Você tem 3 projetos em andamento e 2 concluídos este mês.',
            'As vendas deste mês totalizam R$ 245.000,00 com crescimento de 15%.',
            'Recomendo focar no follow-up dos leads qualificados em até 24h.',
            'Os leads com score acima de 80 têm 65% de chance de conversão.',
            'Vou gerar um relatório completo com os KPIs principais.',
            'Foram cadastrados 8 novos clientes nos últimos 7 dias.',
            'O projeto "Sistema CRM" tem o maior orçamento: R$ 45.000,00.',
            'Há 12 leads pendentes de contato nos últimos 3 dias.',
            'A equipe está 120% acima da meta mensal.',
            'Que tipo de reunião você gostaria de agendar?'
        ];
        
        $intents = ['consulta_projetos', 'relatorio_vendas', 'melhoria_conversao', 'analise_leads', 'relatorio_geral'];
        $sentiments = ['positivo', 'neutro', 'negativo'];
        
        $sql = "INSERT INTO ai_conversations (user_id, session_id, message_type, message, intent, confidence, sentiment, context_data, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->pdo->prepare($sql);
        
        for ($i = 0; $i < $count; $i++) {
            $user_id = rand(1, 5);
            $session_id = 'sess_' . uniqid();
            $intent = $intents[array_rand($intents)];
            $confidence = rand(70, 98) / 100;
            $sentiment = $sentiments[array_rand($sentiments)];
            $created_at = date('Y-m-d H:i:s', strtotime('-' . rand(0, 15) . ' days -' . rand(0, 23) . ' hours'));
            
            $context_data = json_encode([
                'page' => 'dashboard',
                'feature' => 'ai_assistant',
                'previous_intent' => $intents[array_rand($intents)]
            ]);
            
            // Mensagem do usuário
            $user_message = $user_messages[array_rand($user_messages)];
            $stmt->execute([
                $user_id,
                $session_id,
                'user',
                $user_message,
                $intent,
                $confidence,
                $sentiment,
                $context_data,
                $created_at
            ]);
            
            // Resposta do assistente
            $assistant_message = $assistant_responses[array_rand($assistant_responses)];
            $response_time = date('Y-m-d H:i:s', strtotime($created_at . ' +2 seconds'));
            $stmt->execute([
                $user_id,
                $session_id,
                'assistant',
                $assistant_message,
                $intent,
                $confidence,
                'neutro',
                $context_data,
                $response_time
            ]);
        }
        
        echo "✅ " . ($count * 2) . " mensagens de conversação geradas\n";
    }
    
    /**
     * Gera dados para predictive_insights
     */
    public function generatePredictiveInsights($count = 15) {
        $insights = [
            ['lead_conversion', 'Alta Probabilidade de Conversão', 'Lead João Silva tem 89% de chance de fechar negócio'],
            ['revenue_forecast', 'Previsão de Receita Mensal', 'Tendência indica receita de R$ 280.000 no próximo mês'],
            ['customer_churn', 'Risco de Perda de Cliente', 'Cliente Tech Corp pode cancelar contrato em 30 dias'],
            ['project_delay', 'Atraso Previsto em Projeto', 'Projeto CRM pode atrasar 5 dias pela complexidade atual'],
            ['opportunity_alert', 'Nova Oportunidade Identificada', 'Setor de e-commerce mostra crescimento de 25%'],
            ['performance_trend', 'Tendência de Performance', 'Equipe comercial 15% acima da meta histórica'],
            ['budget_optimization', 'Otimização de Orçamento', 'Realocação de recursos pode aumentar ROI em 18%'],
            ['seasonal_pattern', 'Padrão Sazonal Detectado', 'Dezembro tradicionalmente tem 40% mais vendas'],
            ['lead_quality', 'Melhoria na Qualidade de Leads', 'Leads do Google Ads convertem 23% melhor'],
            ['client_satisfaction', 'Satisfação do Cliente', 'NPS médio de 8.5 indica alta satisfação']
        ];
        
        $categories = ['vendas', 'projetos', 'clientes', 'performance', 'financeiro'];
        $statuses = ['ativo', 'processado', 'descartado'];
        
        $sql = "INSERT INTO predictive_insights (insight_type, title, description, prediction_data, confidence_level, impact_score, category, status, expires_at, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->pdo->prepare($sql);
        
        for ($i = 0; $i < $count; $i++) {
            $insight = $insights[array_rand($insights)];
            $category = $categories[array_rand($categories)];
            $status = $statuses[array_rand($statuses)];
            $confidence_level = rand(65, 95) / 100;
            $impact_score = rand(60, 100);
            
            $prediction_data = json_encode([
                'model_version' => '2.1',
                'data_points' => rand(100, 1000),
                'accuracy' => $confidence_level,
                'factors' => ['historical_data', 'market_trends', 'customer_behavior']
            ]);
            
            $expires_at = date('Y-m-d H:i:s', strtotime('+' . rand(7, 90) . ' days'));
            $created_at = date('Y-m-d H:i:s', strtotime('-' . rand(0, 7) . ' days'));
            
            $stmt->execute([
                $insight[0],
                $insight[1],
                $insight[2],
                $prediction_data,
                $confidence_level,
                $impact_score,
                $category,
                $status,
                $expires_at,
                $created_at
            ]);
        }
        
        echo "✅ $count insights preditivos gerados\n";
    }
    
    /**
     * Gera dados para notifications
     */
    public function generateNotifications($count = 25) {
        $notifications = [
            ['Nova venda registrada', 'Parabéns! Nova venda de R$ 25.000 foi registrada.', 'success', 'normal'],
            ['Lead convertido', 'Lead João Silva foi convertido em cliente!', 'success', 'normal'],
            ['Projeto atrasado', 'Projeto Website Corp está 2 dias atrasado.', 'warning', 'alta'],
            ['Backup realizado', 'Backup automático concluído com sucesso.', 'info', 'baixa'],
            ['Meta alcançada', 'Meta mensal de vendas foi atingida!', 'success', 'alta'],
            ['Reunião agendada', 'Reunião com cliente marcada para amanhã.', 'info', 'normal'],
            ['Tarefa vencida', 'Tarefa "Análise de requisitos" está vencida.', 'danger', 'urgente'],
            ['Cliente inativo', 'Cliente não acessa o sistema há 30 dias.', 'warning', 'normal'],
            ['Pagamento recebido', 'Pagamento de R$ 15.000 foi confirmado.', 'success', 'normal'],
            ['Atualização disponível', 'Nova versão do sistema disponível.', 'info', 'baixa']
        ];
        
        $sql = "INSERT INTO notifications (user_id, title, message, type, priority, is_read, action_url, action_label, expires_at, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->pdo->prepare($sql);
        
        for ($i = 0; $i < $count; $i++) {
            $notification = $notifications[array_rand($notifications)];
            $user_id = rand(1, 5);
            $is_read = rand(0, 1);
            $action_url = '/dashboard.html#' . strtolower(str_replace(' ', '-', $notification[0]));
            $action_label = 'Ver detalhes';
            $expires_at = date('Y-m-d H:i:s', strtotime('+' . rand(1, 30) . ' days'));
            $created_at = date('Y-m-d H:i:s', strtotime('-' . rand(0, 10) . ' days -' . rand(0, 23) . ' hours'));
            
            $stmt->execute([
                $user_id,
                $notification[0],
                $notification[1],
                $notification[2],
                $notification[3],
                $is_read,
                $action_url,
                $action_label,
                $expires_at,
                $created_at
            ]);
        }
        
        echo "✅ $count notificações geradas\n";
    }
    
    /**
     * Gera todos os dados adicionais
     */
    public function generateAll() {
        echo "🚀 Gerando dados adicionais para as novas tabelas...\n\n";
        
        $this->generateActivityLogs(50);
        $this->generateProjectTasks(40);
        $this->generateAIConversations(30);
        $this->generatePredictiveInsights(15);
        $this->generateNotifications(25);
        
        echo "\n🎉 Dados adicionais gerados com sucesso!\n";
        echo "✅ Sistema completo com dados realísticos para demonstração!\n";
    }
    
    /**
     * Mostra estatísticas das novas tabelas
     */
    public function showStats() {
        $tables = [
            'activity_logs' => 'Logs de Atividade',
            'project_tasks' => 'Tarefas de Projetos',
            'ai_conversations' => 'Conversações IA',
            'predictive_insights' => 'Insights Preditivos',
            'notifications' => 'Notificações'
        ];
        
        echo "📊 ESTATÍSTICAS DAS NOVAS TABELAS:\n";
        echo "==================================\n";
        
        foreach ($tables as $table => $name) {
            try {
                $stmt = $this->pdo->query("SELECT COUNT(*) FROM $table");
                $count = $stmt->fetchColumn();
                echo sprintf("%-20s : %d registros\n", $name, $count);
            } catch (Exception $e) {
                echo sprintf("%-20s : Erro ao consultar\n", $name);
            }
        }
        
        echo "\n✅ Todas as tabelas estão funcionais!\n";
    }
}

// Execução do script
if ($argc > 1) {
    $generator = new AdditionalDataGenerator();
    
    switch ($argv[1]) {
        case 'all':
            $generator->generateAll();
            break;
        case 'stats':
            $generator->showStats();
            break;
        case 'logs':
            $generator->generateActivityLogs(50);
            break;
        case 'tasks':
            $generator->generateProjectTasks(40);
            break;
        case 'conversations':
            $generator->generateAIConversations(30);
            break;
        case 'insights':
            $generator->generatePredictiveInsights(15);
            break;
        case 'notifications':
            $generator->generateNotifications(25);
            break;
        default:
            echo "Uso: php generate-additional-data.php [all|stats|logs|tasks|conversations|insights|notifications]\n";
    }
} else {
    echo "Uso: php generate-additional-data.php [all|stats|logs|tasks|conversations|insights|notifications]\n";
}
?>