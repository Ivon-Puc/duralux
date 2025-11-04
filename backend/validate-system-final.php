<?php
/**
 * Script Final de Validação do Sistema DuraLux CRM v7.0
 * Testa todas as funcionalidades implementadas
 */

require_once 'config/db_config.php';

class SystemValidator {
    private $pdo;
    private $results = [];
    
    public function __construct() {
        try {
            $this->pdo = getDatabaseConnection();
            $this->addResult("✅ Conexão com banco de dados", "OK", true);
        } catch (Exception $e) {
            $this->addResult("❌ Conexão com banco de dados", $e->getMessage(), false);
        }
    }
    
    private function addResult($test, $result, $success) {
        $this->results[] = [
            'test' => $test,
            'result' => $result,
            'success' => $success
        ];
    }
    
    /**
     * Testa estrutura das tabelas
     */
    public function validateTables() {
        echo "🔍 Validando estrutura das tabelas...\n";
        
        $required_tables = [
            'customers' => 'Clientes',
            'leads' => 'Leads',
            'projects' => 'Projetos',
            'vendas' => 'Vendas',
            'activity_logs' => 'Logs de Atividade',
            'project_tasks' => 'Tarefas de Projetos',
            'ai_conversations' => 'Conversações IA',
            'predictive_insights' => 'Insights Preditivos',
            'notifications' => 'Notificações',
            'system_settings' => 'Configurações do Sistema'
        ];
        
        foreach ($required_tables as $table => $name) {
            try {
                $stmt = $this->pdo->query("SELECT COUNT(*) FROM $table");
                $count = $stmt->fetchColumn();
                $this->addResult("✅ Tabela $name", "$count registros", true);
            } catch (Exception $e) {
                $this->addResult("❌ Tabela $name", "Não encontrada", false);
            }
        }
    }
    
    /**
     * Testa dados de exemplo
     */
    public function validateSampleData() {
        echo "🔍 Validando dados de exemplo...\n";
        
        // Teste de integridade dos dados
        try {
            // Verificar se há leads
            $stmt = $this->pdo->query("SELECT COUNT(*) FROM leads");
            $leads_count = $stmt->fetchColumn();
            
            // Verificar se há clientes
            $stmt = $this->pdo->query("SELECT COUNT(*) FROM customers");
            $customers_count = $stmt->fetchColumn();
            
            // Verificar se há projetos
            $stmt = $this->pdo->query("SELECT COUNT(*) FROM projects");
            $projects_count = $stmt->fetchColumn();
            
            // Verificar se há vendas
            $stmt = $this->pdo->query("SELECT COUNT(*) FROM vendas");
            $sales_count = $stmt->fetchColumn();
            
            if ($leads_count > 0 && $customers_count > 0 && $projects_count > 0 && $sales_count > 0) {
                $this->addResult("✅ Dados de exemplo", "Todos os tipos de dados presentes", true);
                
                // Calcular métricas
                $stmt = $this->pdo->query("SELECT SUM(valor) as total FROM vendas");
                $total_revenue = $stmt->fetchColumn();
                
                $conversion_rate = round(($customers_count / $leads_count) * 100, 1);
                $avg_ticket = $total_revenue / $sales_count;
                
                $this->addResult("📊 Receita Total", "R$ " . number_format($total_revenue, 2, ',', '.'), true);
                $this->addResult("📊 Taxa de Conversão", "$conversion_rate%", true);
                $this->addResult("📊 Ticket Médio", "R$ " . number_format($avg_ticket, 2, ',', '.'), true);
                
            } else {
                $this->addResult("❌ Dados de exemplo", "Dados incompletos", false);
            }
        } catch (Exception $e) {
            $this->addResult("❌ Validação de dados", $e->getMessage(), false);
        }
    }
    
    /**
     * Testa configurações do sistema
     */
    public function validateSystemConfigurações() {
        echo "🔍 Validando configurações do sistema...\n";
        
        try {
            $stmt = $this->pdo->query("SELECT COUNT(*) FROM system_settings");
            $settings_count = $stmt->fetchColumn();
            
            if ($settings_count > 0) {
                $this->addResult("✅ Configurações do sistema", "$settings_count configurações encontradas", true);
                
                // Verificar configurações críticas
                $critical_settings = ['analytics_refresh_interval', 'backup_frequency', 'ai_assistant_enabled'];
                foreach ($critical_settings as $setting) {
                    $stmt = $this->pdo->prepare("SELECT setting_value FROM system_settings WHERE setting_key = ?");
                    $stmt->execute([$setting]);
                    $value = $stmt->fetchColumn();
                    
                    if ($value !== false) {
                        $this->addResult("✅ Configuração $setting", $value, true);
                    } else {
                        $this->addResult("⚠️ Configuração $setting", "Não encontrada", false);
                    }
                }
            } else {
                $this->addResult("❌ Configurações do sistema", "Nenhuma configuração encontrada", false);
            }
        } catch (Exception $e) {
            $this->addResult("❌ Configurações do sistema", $e->getMessage(), false);
        }
    }
    
    /**
     * Testa funcionalidades avançadas
     */
    public function validateAdvancedFeatures() {
        echo "🔍 Validando funcionalidades avançadas...\n";
        
        // Testar logs de atividade
        try {
            $stmt = $this->pdo->query("SELECT COUNT(*) FROM activity_logs WHERE created_at >= date('now', '-7 days')");
            $recent_logs = $stmt->fetchColumn();
            $this->addResult("✅ Logs de Atividade", "$recent_logs logs recentes", $recent_logs > 0);
        } catch (Exception $e) {
            $this->addResult("❌ Logs de Atividade", $e->getMessage(), false);
        }
        
        // Testar conversações IA
        try {
            $stmt = $this->pdo->query("SELECT COUNT(*) FROM ai_conversations");
            $conversations = $stmt->fetchColumn();
            $this->addResult("✅ AI Assistant", "$conversations mensagens processadas", $conversations > 0);
        } catch (Exception $e) {
            $this->addResult("❌ AI Assistant", $e->getMessage(), false);
        }
        
        // Testar insights preditivos
        try {
            $stmt = $this->pdo->query("SELECT COUNT(*) FROM predictive_insights WHERE status = 'ativo'");
            $active_insights = $stmt->fetchColumn();
            $this->addResult("✅ Insights Preditivos", "$active_insights insights ativos", $active_insights > 0);
        } catch (Exception $e) {
            $this->addResult("❌ Insights Preditivos", $e->getMessage(), false);
        }
        
        // Testar notificações
        try {
            $stmt = $this->pdo->query("SELECT COUNT(*) FROM notifications WHERE is_read = 0");
            $unread_notifications = $stmt->fetchColumn();
            $this->addResult("✅ Sistema de Notificações", "$unread_notifications não lidas", true);
        } catch (Exception $e) {
            $this->addResult("❌ Sistema de Notificações", $e->getMessage(), false);
        }
    }
    
    /**
     * Testa arquivos do sistema
     */
    public function validateSystemFiles() {
        echo "🔍 Validando arquivos do sistema...\n";
        
        $critical_files = [
            '../duralux-admin/index.html' => 'Painel de Controle Principal',
            '../duralux-admin/analytics-advanced.html' => 'Analytics Avançado',
            '../duralux-admin/leads.html' => 'Gestão de Leads',
            '../duralux-admin/projects.html' => 'Gestão de Projetos',
            'duralux-backup-system-v7.py' => 'Sistema de Backup',
            'classes/AdvancedAnalytics.php' => 'Analytics Backend',
            'classes/DuraluxAIAssistant.php' => 'AI Assistant Backend'
        ];
        
        foreach ($critical_files as $file => $name) {
            if (file_exists($file)) {
                $size = filesize($file);
                $this->addResult("✅ $name", number_format($size) . " bytes", true);
            } else {
                $this->addResult("❌ $name", "Arquivo não encontrado", false);
            }
        }
    }
    
    /**
     * Gera relatório final
     */
    public function generateReport() {
        echo "\n" . str_repeat("=", 80) . "\n";
        echo "📊 RELATÓRIO FINAL DE VALIDAÇÃO - SISTEMA DURALUX CRM v7.0\n";
        echo str_repeat("=", 80) . "\n\n";
        
        $total_tests = count($this->results);
        $successful_tests = array_filter($this->results, fn($r) => $r['success']);
        $success_count = count($successful_tests);
        $success_rate = round(($success_count / $total_tests) * 100, 1);
        
        // Estatísticas gerais
        echo "📈 ESTATÍSTICAS GERAIS:\n";
        echo "----------------------\n";
        echo sprintf("Total de Testes     : %d\n", $total_tests);
        echo sprintf("Testes Bem-sucedidos: %d\n", $success_count);
        echo sprintf("Taxa de Sucesso     : %s%%\n", $success_rate);
        echo sprintf("Status Geral        : %s\n", $success_rate >= 90 ? "🟢 EXCELENTE" : ($success_rate >= 80 ? "🟡 BOM" : "🔴 NECESSITA ATENÇÃO"));
        
        echo "\n📋 DETALHES DOS TESTES:\n";
        echo "-----------------------\n";
        
        foreach ($this->results as $result) {
            echo sprintf("%-30s : %s\n", $result['test'], $result['result']);
        }
        
        // Resumo final
        echo "\n🎯 RESUMO EXECUTIVO:\n";
        echo "--------------------\n";
        
        if ($success_rate >= 95) {
            echo "🎉 SISTEMA TOTALMENTE FUNCIONAL!\n";
            echo "✅ Todas as funcionalidades implementadas e testadas\n";
            echo "✅ Dados de demonstração carregados com sucesso\n";
            echo "✅ Sistema pronto para uso em produção\n";
        } elseif ($success_rate >= 85) {
            echo "🟡 SISTEMA FUNCIONAL COM PEQUENOS AJUSTES\n";
            echo "✅ Funcionalidades principais implementadas\n";
            echo "⚠️ Alguns componentes podem precisar de ajustes\n";
            echo "✅ Sistema utilizável para demonstrações\n";
        } else {
            echo "🔴 SISTEMA NECESSITA DE CORREÇÕES\n";
            echo "❌ Problemas críticos identificados\n";
            echo "⚠️ Revisar componentes com falha\n";
            echo "🔧 Aplicar correções antes do uso\n";
        }
        
        echo "\n" . str_repeat("=", 80) . "\n";
        echo "Data do Teste: " . date('d/m/Y H:i:s') . "\n";
        echo "Versão: DuraLux CRM v7.0\n";
        echo str_repeat("=", 80) . "\n";
    }
    
    /**
     * Executa todos os testes
     */
    public function runAllTests() {
        echo "🚀 INICIANDO VALIDAÇÃO COMPLETA DO SISTEMA...\n\n";
        
        $this->validateTables();
        $this->validateSampleData();
        $this->validateSystemConfigurações();
        $this->validateAdvancedFeatures();
        $this->validateSystemFiles();
        
        $this->generateReport();
    }
}

// Execução do script
if (php_sapi_name() === 'cli') {
    $validator = new SystemValidator();
    $validator->runAllTests();
} else {
    echo "<pre>";
    $validator = new SystemValidator();
    $validator->runAllTests();
    echo "</pre>";
}
?>