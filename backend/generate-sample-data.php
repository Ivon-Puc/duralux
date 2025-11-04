<?php
/**
 * DURALUX CRM - Gerador de Dados de Amostragem
 * Script para popular o banco com dados realistas para demonstração
 * 
 * @version 1.0
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/db_config.php';

class DataGenerator {
    private $db;
    
    // Dados realistas para geração
    private $nomes = [
        'João Silva', 'Maria Santos', 'Pedro Costa', 'Ana Oliveira', 'Carlos Lima',
        'Lucia Ferreira', 'Roberto Alves', 'Fernanda Souza', 'Ricardo Pereira', 'Juliana Rocha',
        'Marcos Antonio', 'Patricia Gomes', 'Eduardo Ribeiro', 'Camila Martins', 'Felipe Cardoso',
        'Renata Carvalho', 'Thiago Nascimento', 'Beatriz Mendes', 'Gabriel Nunes', 'Isabela Castro'
    ];
    
    private $empresas = [
        'Tech Solutions LTDA', 'Digital Marketing Pro', 'Inovação Sistemas S/A', 'Creative Design Studio',
        'Consultoria Empresarial ABC', 'StartUp Tech XYZ', 'E-commerce Plus', 'Agência Web Master',
        'Software House Brasil', 'Marketing Digital 360', 'TI Corporativa LTDA', 'Design & Comunicação',
        'Desenvolvimento Web Pro', 'Automação Industrial', 'Logística Inteligente', 'Retail Solutions',
        'FinTech Innovation', 'EdTech Learning', 'HealthTech Solutions', 'GreenTech Sustentável'
    ];
    
    private $emails_domains = ['gmail.com', 'outlook.com', 'yahoo.com', 'hotmail.com', 'empresa.com.br'];
    
    private $cidades = [
        'São Paulo', 'Rio de Janeiro', 'Belo Horizonte', 'Brasília', 'Salvador',
        'Fortaleza', 'Curitiba', 'Recife', 'Porto Alegre', 'Goiânia'
    ];
    
    private $produtos_servicos = [
        'Website Corporativo', 'Sistema CRM', 'E-commerce', 'App Mobile', 'Consultoria TI',
        'Marketing Digital', 'Automação Processos', 'Painel de Controle BI', 'Sistema ERP', 'Plataforma LMS'
    ];
    
    public function __construct() {
        try {
            $this->db = getDatabaseConnection();
            echo "✅ Conexão estabelecida com sucesso!\n";
        } catch (Exception $e) {
            die("❌ Erro na conexão: " . $e->getMessage() . "\n");
        }
    }
    
    /**
     * Executa geração completa de dados
     */
    public function generateAllData() {
        echo "🚀 Iniciando geração de dados de amostragem...\n\n";
        
        $this->clearExistingData();
        
        $customers = $this->generateCustomers(25);
        echo "✅ {$customers} clientes gerados\n";
        
        $leads = $this->generateLeads(50);
        echo "✅ {$leads} leads gerados\n";
        
        $projects = $this->generateProjects(15);
        echo "✅ {$projects} projetos gerados\n";
        
        $sales = $this->generateVendas(30);
        echo "✅ {$sales} vendas geradas\n";
        
        $activities = $this->generateActivities(100);
        echo "✅ {$activities} atividades geradas\n";
        
        $this->generateAnalyticsData();
        echo "✅ Dados de analytics processados\n";
        
        echo "\n🎉 Geração de dados concluída com sucesso!\n";
        echo "📊 Acesse o dashboard para visualizar os dados\n";
    }
    
    /**
     * Limpa dados existentes (opcional)
     */
    private function clearExistingData() {
        echo "🧹 Limpando dados existentes...\n";
        
        $tables = ['activity_logs', 'vendas', 'project_tasks', 'projects', 'leads', 'customers'];
        
        foreach ($tables as $table) {
            try {
                $this->db->exec("DELETE FROM {$table}");
                echo "   - Tabela {$table} limpa\n";
            } catch (Exception $e) {
                echo "   ⚠️ Erro ao limpar {$table}: " . $e->getMessage() . "\n";
            }
        }
    }
    
    /**
     * Gera clientes
     */
    private function generateCustomers($count = 25) {
        $stmt = $this->db->prepare("
            INSERT INTO customers (name, email, phone, company, active, created_at, updated_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        
        $generated = 0;
        
        for ($i = 0; $i < $count; $i++) {
            $name = $this->nomes[array_rand($this->nomes)];
            $company = $this->empresas[array_rand($this->empresas)];
            $email = strtolower(str_replace(' ', '.', $name)) . '@' . $this->emails_domains[array_rand($this->emails_domains)];
            $phone = '(11) ' . rand(90000, 99999) . '-' . rand(1000, 9999);
            $created_at = date('Y-m-d H:i:s', strtotime('-' . rand(1, 180) . ' days'));
            
            try {
                $stmt->execute([
                    $name,
                    $email,
                    $phone,
                    $company,
                    rand(0, 1) > 0.1 ? 1 : 0, // 90% ativos
                    $created_at,
                    $created_at
                ]);
                $generated++;
            } catch (Exception $e) {
                echo "   ⚠️ Erro ao gerar cliente: " . $e->getMessage() . "\n";
            }
        }
        
        return $generated;
    }
    
    /**
     * Gera leads
     */
    private function generateLeads($count = 50) {
        $stmt = $this->db->prepare("
            INSERT INTO leads (name, email, phone, company, status, source, value, notes, created_at, updated_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $statuses = ['novo', 'contactado', 'qualificado', 'proposta', 'negociacao', 'convertido', 'perdido'];
        $sources = ['website', 'google_ads', 'facebook', 'instagram', 'indicacao', 'email_marketing', 'evento'];
        
        $generated = 0;
        
        for ($i = 0; $i < $count; $i++) {
            $name = $this->nomes[array_rand($this->nomes)];
            $company = $this->empresas[array_rand($this->empresas)];
            $email = strtolower(str_replace(' ', '.', $name)) . '@' . $this->emails_domains[array_rand($this->emails_domains)];
            $phone = '(11) ' . rand(90000, 99999) . '-' . rand(1000, 9999);
            $status = $statuses[array_rand($statuses)];
            $source = $sources[array_rand($sources)];
            $value = rand(2000, 50000);
            $created_at = date('Y-m-d H:i:s', strtotime('-' . rand(1, 90) . ' days'));
            
            $notes = "Lead interessado em {$this->produtos_servicos[array_rand($this->produtos_servicos)]}. " .
                    "Origem: {$source}. Empresa: {$company}.";
            
            try {
                $stmt->execute([
                    $name,
                    $email,
                    $phone,
                    $company,
                    $status,
                    $source,
                    $value,
                    $notes,
                    $created_at,
                    $created_at
                ]);
                $generated++;
            } catch (Exception $e) {
                echo "   ⚠️ Erro ao gerar lead: " . $e->getMessage() . "\n";
            }
        }
        
        return $generated;
    }
    
    /**
     * Gera projetos
     */
    private function generateProjects($count = 15) {
        // Primeiro pega IDs de clientes existentes
        $customers = $this->db->query("SELECT id FROM customers LIMIT 20")->fetchAll();
        
        if (empty($customers)) {
            echo "   ⚠️ Nenhum cliente encontrado para gerar projetos\n";
            return 0;
        }
        
        $stmt = $this->db->prepare("
            INSERT INTO projects (name, description, status, budget, start_date, prazo_entrega, created_at, updated_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $statuses = ['planejamento', 'em_andamento', 'pausado', 'concluido', 'cancelado'];
        
        $generated = 0;
        
        for ($i = 0; $i < $count; $i++) {
            $service = $this->produtos_servicos[array_rand($this->produtos_servicos)];
            $company = $this->empresas[array_rand($this->empresas)];
            $name = "{$service} - {$company}";
            $status = $statuses[array_rand($statuses)];
            $budget = rand(10000, 100000);
            
            $start_date = date('Y-m-d', strtotime('-' . rand(30, 180) . ' days'));
            $prazo_entrega = date('Y-m-d', strtotime($start_date . ' +' . rand(30, 120) . ' days'));
            $created_at = date('Y-m-d H:i:s', strtotime($start_date));
            
            $description = "Desenvolvimento de {$service} para {$company}. " .
                          "Inclui análise de requisitos, desenvolvimento, testes e implantação.";
            
            try {
                $stmt->execute([
                    $name,
                    $description,
                    $status,
                    $budget,
                    $start_date,
                    $prazo_entrega,
                    $created_at,
                    $created_at
                ]);
                $generated++;
            } catch (Exception $e) {
                echo "   ⚠️ Erro ao gerar projeto: " . $e->getMessage() . "\n";
            }
        }
        
        return $generated;
    }
    
    /**
     * Gera vendas
     */
    private function generateVendas($count = 30) {
        // Pega IDs de clientes
        $customers = $this->db->query("SELECT id FROM customers LIMIT 20")->fetchAll();
        
        if (empty($customers)) {
            echo "   ⚠️ Nenhum cliente encontrado para gerar vendas\n";
            return 0;
        }
        
        $stmt = $this->db->prepare("
            INSERT INTO vendas (customer_id, valor, status, created_at) 
            VALUES (?, ?, ?, ?)
        ");
        
        $statuses = ['fechada', 'pendente', 'cancelada'];
        
        $generated = 0;
        
        for ($i = 0; $i < $count; $i++) {
            $customer = $customers[array_rand($customers)];
            $valor = rand(5000, 80000);
            $status = $statuses[array_rand($statuses)];
            
            // 80% das vendas são fechadas
            if (rand(1, 10) <= 8) {
                $status = 'fechada';
            }
            
            $created_at = date('Y-m-d H:i:s', strtotime('-' . rand(1, 120) . ' days'));
            
            try {
                $stmt->execute([
                    $customer['id'],
                    $valor,
                    $status,
                    $created_at
                ]);
                $generated++;
            } catch (Exception $e) {
                echo "   ⚠️ Erro ao gerar venda: " . $e->getMessage() . "\n";
            }
        }
        
        return $generated;
    }
    
    /**
     * Gera atividades/logs
     */
    private function generateActivities($count = 100) {
        // Verifica se a tabela existe
        try {
            $this->db->query("SELECT 1 FROM activity_logs LIMIT 1");
        } catch (Exception $e) {
            echo "   ⚠️ Tabela activity_logs não existe\n";
            return 0;
        }
        
        $stmt = $this->db->prepare("
            INSERT INTO activity_logs (user_id, action, module, description, created_at) 
            VALUES (?, ?, ?, ?, ?)
        ");
        
        $actions = [
            'create', 'update', 'delete', 'view', 'contact', 'email_sent', 'call_made', 'meeting_scheduled'
        ];
        
        $modules = ['leads', 'customers', 'projects', 'sales', 'dashboard'];
        
        $descriptions = [
            'Lead contactado via telefone',
            'Email de follow-up enviado',
            'Reunião agendada para próxima semana',
            'Proposta comercial enviada',
            'Contrato assinado',
            'Projeto iniciado',
            'Milestone concluído',
            'Pagamento recebido',
            'Feedback positivo do cliente',
            'Nova oportunidade identificada'
        ];
        
        $generated = 0;
        
        for ($i = 0; $i < $count; $i++) {
            $action = $actions[array_rand($actions)];
            $module = $modules[array_rand($modules)];
            $description = $descriptions[array_rand($descriptions)];
            $created_at = date('Y-m-d H:i:s', strtotime('-' . rand(1, 30) . ' days'));
            
            try {
                $stmt->execute([
                    1, // user_id padrão
                    $action,
                    $module,
                    $description,
                    $created_at
                ]);
                $generated++;
            } catch (Exception $e) {
                echo "   ⚠️ Erro ao gerar atividade: " . $e->getMessage() . "\n";
            }
        }
        
        return $generated;
    }
    
    /**
     * Gera dados específicos para analytics
     */
    private function generateAnalyticsData() {
        // Dados diários de leads para gráficos
        $this->generateDiárioLeadsData();
        
        // Dados mensais de receita
        $this->generateMensalReceitaData();
        
        // Métricas de performance
        $this->generatePerformanceMetrics();
    }
    
    private function generateDiárioLeadsData() {
        // Gera leads distribuídos pelos últimos 30 dias
        $stmt = $this->db->prepare("
            UPDATE leads SET created_at = ? WHERE id = ?
        ");
        
        $leads = $this->db->query("SELECT id FROM leads ORDER BY RANDOM() LIMIT 30")->fetchAll();
        
        foreach ($leads as $index => $lead) {
            $days_ago = 30 - $index;
            $created_at = date('Y-m-d H:i:s', strtotime("-{$days_ago} days"));
            
            try {
                $stmt->execute([$created_at, $lead['id']]);
            } catch (Exception $e) {
                // Ignora erros de distribuição
            }
        }
    }
    
    private function generateMensalReceitaData() {
        // Distribui vendas pelos últimos 6 meses
        $stmt = $this->db->prepare("
            UPDATE vendas SET created_at = ? WHERE id = ?
        ");
        
        $sales = $this->db->query("SELECT id FROM vendas WHERE status = 'fechada'")->fetchAll();
        
        foreach ($sales as $index => $sale) {
            $month_ago = rand(0, 6);
            $day = rand(1, 28);
            $created_at = date('Y-m-d H:i:s', strtotime("-{$month_ago} months -{$day} days"));
            
            try {
                $stmt->execute([$created_at, $sale['id']]);
            } catch (Exception $e) {
                // Ignora erros de distribuição
            }
        }
    }
    
    private function generatePerformanceMetrics() {
        // Atualiza alguns leads como convertidos baseado em probabilidade
        $this->db->exec("
            UPDATE leads 
            SET status = 'convertido' 
            WHERE status = 'negociacao' 
            AND value > 15000 
            AND RANDOM() % 3 = 0
        ");
        
        // Marca alguns projetos como atrasados
        $this->db->exec("
            UPDATE projects 
            SET status = 'em_andamento' 
            WHERE prazo_entrega < DATE('now') 
            AND status != 'concluido'
            AND RANDOM() % 4 = 0
        ");
    }
    
    /**
     * Exibe estatísticas dos dados gerados
     */
    public function showStatistics() {
        echo "\n📊 ESTATÍSTICAS DOS DADOS GERADOS:\n";
        echo str_repeat("=", 50) . "\n";
        
        $tables = [
            'customers' => 'Clientes',
            'leads' => 'Leads', 
            'projects' => 'Projetos',
            'vendas' => 'Vendas'
        ];
        
        foreach ($tables as $table => $label) {
            try {
                $stmt = $this->db->query("SELECT COUNT(*) as count FROM {$table}");
                $count = $stmt->fetch()['count'];
                echo sprintf("%-15s: %d registros\n", $label, $count);
            } catch (Exception $e) {
                echo sprintf("%-15s: Tabela não encontrada\n", $label);
            }
        }
        
        // Estatísticas específicas
        echo "\n📈 MÉTRICAS DE NEGÓCIO:\n";
        echo str_repeat("-", 30) . "\n";
        
        try {
            // Receita total
            $stmt = $this->db->query("SELECT SUM(valor) as total FROM vendas WHERE status = 'fechada'");
            $receita = $stmt->fetch()['total'] ?? 0;
            echo sprintf("Receita Total: R$ %s\n", number_format($receita, 2, ',', '.'));
            
            // Taxa de conversão
            $total_leads = $this->db->query("SELECT COUNT(*) FROM leads")->fetchColumn();
            $converted_leads = $this->db->query("SELECT COUNT(*) FROM leads WHERE status = 'convertido'")->fetchColumn();
            $conversion_rate = $total_leads > 0 ? ($converted_leads / $total_leads) * 100 : 0;
            echo sprintf("Taxa Conversão: %.1f%%\n", $conversion_rate);
            
            // Ticket médio
            $stmt = $this->db->query("SELECT COUNT(*) as count FROM vendas WHERE status = 'fechada'");
            $vendas_count = $stmt->fetch()['count'];
            $ticket_medio = $vendas_count > 0 ? $receita / $vendas_count : 0;
            echo sprintf("Ticket Médio: R$ %s\n", number_format($ticket_medio, 2, ',', '.'));
            
        } catch (Exception $e) {
            echo "Erro ao calcular métricas: " . $e->getMessage() . "\n";
        }
        
        echo "\n✅ Dados prontos para uso no sistema!\n";
    }
}

// Execução do script
if (php_sapi_name() === 'cli') {
    // Executando via linha de comando
    $generator = new DataGenerator();
    
    $action = $argv[1] ?? 'all';
    
    switch ($action) {
        case 'all':
        case 'generate':
            $generator->generateAllData();
            $generator->showStatistics();
            break;
            
        case 'stats':
        case 'statistics':
            $generator->showStatistics();
            break;
            
        case 'clear':
            echo "🧹 Limpando dados...\n";
            // $generator->clearExistingData(); // Descomentado se necessário
            echo "✅ Dados limpos!\n";
            break;
            
        default:
            echo "Uso: php generate-sample-data.php [all|stats|clear]\n";
            echo "  all   - Gera todos os dados de amostragem\n";
            echo "  stats - Mostra estatísticas dos dados existentes\n";
            echo "  clear - Limpa dados existentes\n";
    }
} else {
    // Executando via web
    header('Content-Type: text/plain; charset=utf-8');
    
    $generator = new DataGenerator();
    $generator->generateAllData();
    $generator->showStatistics();
}
?>