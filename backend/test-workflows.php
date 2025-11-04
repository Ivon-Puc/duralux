<?php
/**
 * DURALUX CRM - Workflow Automation Test Suite v5.0
 * Sistema de testes para o motor de workflows
 * 
 * @author Duralux Development Team
 * @version 5.0.0
 */

require_once 'classes/WorkflowEngine.php';

class WorkflowEngineTest {
    private $workflowEngine;
    private $mockDb;
    private $testResults = [];
    
    public function __construct() {
        // Criar mock database para testes
        $this->mockDb = $this->createMockDatabase();
        $this->workflowEngine = new WorkflowEngine($this->mockDb);
    }
    
    /**
     * Executar todos os testes
     */
    public function runAllTests() {
        echo "🧪 DURALUX CRM - Workflow Engine Test Suite v5.0\n";
        echo str_repeat("=", 55) . "\n";
        echo "\n";
        
        $tests = [
            'testWorkflowCreation' => 'Criação de Workflow',
            'testWorkflowExecution' => 'Execução de Workflow',
            'testTriggerProcessing' => 'Processamento de Triggers',
            'testActionExecution' => 'Execução de Actions',
            'testConditionEvaluation' => 'Avaliação de Condições',
            'testTemplateSystem' => 'Sistema de Templates',
            'testWorkflowStats' => 'Estatísticas de Workflow',
            'testErrorHandling' => 'Tratamento de Erros',
            'testDatabaseIntegration' => 'Integração com Banco'
        ];
        
        $passed = 0;
        $total = count($tests);
        
        foreach ($tests as $method => $description) {
            echo "🔍 Testando: $description... ";
            
            try {
                $result = $this->$method();
                if ($result['success']) {
                    echo "✅ PASSOU\n";
                    $passed++;
                } else {
                    echo "❌ FALHOU: {$result['error']}\n";
                }
                
                $this->testResults[$method] = $result;
                
            } catch (Exception $e) {
                echo "💥 ERRO: " . $e->getMessage() . "\n";
                $this->testResults[$method] = [
                    'success' => false,
                    'error' => $e->getMessage()
                ];
            }
        }
        
        $this->generateReport($passed, $total);
    }
    
    /**
     * Teste de criação de workflow
     */
    private function testWorkflowCreation() {
        $workflowData = [
            'name' => 'Test Workflow',
            'description' => 'Workflow de teste automatizado',
            'trigger_type' => 'manual',
            'trigger_config' => [
                'enabled' => true
            ],
            'conditions' => [
                'operator' => 'AND',
                'conditions' => [
                    [
                        'field' => 'status',
                        'operator' => '==',
                        'value' => 'active'
                    ]
                ]
            ],
            'actions' => [
                [
                    'type' => 'send_email',
                    'config' => [
                        'to' => 'test@example.com',
                        'subject' => 'Test Email',
                        'body' => 'This is a test email from workflow'
                    ]
                ]
            ],
            'priority' => 5,
            'is_active' => true,
            'created_by' => 1
        ];
        
        $result = $this->workflowEngine->createWorkflow($workflowData);
        
        if (!$result['success']) {
            return ['success' => false, 'error' => $result['error']];
        }
        
        // Verificar se workflow foi criado
        if (!isset($result['workflow_id']) || $result['workflow_id'] <= 0) {
            return ['success' => false, 'error' => 'ID do workflow inválido'];
        }
        
        return ['success' => true, 'workflow_id' => $result['workflow_id']];
    }
    
    /**
     * Teste de execução de workflow
     */
    private function testWorkflowExecution() {
        // Primeiro criar um workflow simples
        $workflowData = [
            'name' => 'Execution Test Workflow',
            'description' => 'Teste de execução',
            'trigger_type' => 'manual',
            'trigger_config' => ['enabled' => true],
            'actions' => [
                [
                    'type' => 'create_task',
                    'config' => [
                        'title' => 'Test Task',
                        'description' => 'Task created by workflow test'
                    ]
                ]
            ],
            'created_by' => 1
        ];
        
        $createResult = $this->workflowEngine->createWorkflow($workflowData);
        
        if (!$createResult['success']) {
            return ['success' => false, 'error' => 'Falha ao criar workflow para teste'];
        }
        
        // Executar o workflow
        $triggerData = ['test_data' => 'execution_test'];
        $context = ['test_mode' => true];
        
        $executeResult = $this->workflowEngine->executeWorkflow(
            $createResult['workflow_id'],
            $triggerData,
            $context
        );
        
        if (!$executeResult['success']) {
            return ['success' => false, 'error' => $executeResult['error']];
        }
        
        // Verificar se execução foi registrada
        if (!isset($executeResult['execution_id'])) {
            return ['success' => false, 'error' => 'ID de execução não retornado'];
        }
        
        return ['success' => true, 'execution_id' => $executeResult['execution_id']];
    }
    
    /**
     * Teste de processamento de triggers
     */
    private function testTriggerProcessing() {
        // Simular processamento de triggers
        $result = $this->workflowEngine->processTriggers('manual');
        
        if (!$result['success']) {
            return ['success' => false, 'error' => $result['error']];
        }
        
        // Verificar estrutura da resposta
        $requiredFields = ['triggers_processed', 'total_triggers', 'results'];
        
        foreach ($requiredFields as $field) {
            if (!isset($result[$field])) {
                return ['success' => false, 'error' => "Campo '$field' não encontrado na resposta"];
            }
        }
        
        return ['success' => true, 'processed' => $result['triggers_processed']];
    }
    
    /**
     * Teste de execução de actions
     */
    private function testActionExecution() {
        $actionExecutor = new ActionExecutor($this->mockDb);
        
        // Testar diferentes tipos de actions
        $actions = [
            [
                'type' => 'send_email',
                'config' => [
                    'to' => 'test@example.com',
                    'subject' => 'Test',
                    'body' => 'Test message'
                ]
            ],
            [
                'type' => 'create_task',
                'config' => [
                    'title' => 'Test Task',
                    'description' => 'Task from test'
                ]
            ]
        ];
        
        foreach ($actions as $action) {
            try {
                $result = $actionExecutor->execute(
                    $action,
                    ['test' => true],
                    ['context' => 'test']
                );
                
                if (!isset($result['status'])) {
                    return ['success' => false, 'error' => "Action {$action['type']} não retornou status"];
                }
                
            } catch (Exception $e) {
                return ['success' => false, 'error' => "Erro na action {$action['type']}: " . $e->getMessage()];
            }
        }
        
        return ['success' => true, 'actions_tested' => count($actions)];
    }
    
    /**
     * Teste de avaliação de condições
     */
    private function testConditionEvaluation() {
        $evaluator = new ConditionEvaluator();
        
        // Testar condições simples
        $simpleCondition = [
            'operator' => 'AND',
            'conditions' => [
                [
                    'field' => 'status',
                    'operator' => '==',
                    'value' => 'active'
                ]
            ]
        ];
        
        $triggerData = ['status' => 'active'];
        $result = $evaluator->evaluate($simpleCondition, $triggerData, []);
        
        if (!$result) {
            return ['success' => false, 'error' => 'Condição simples falhou'];
        }
        
        // Testar condições complexas
        $complexCondition = [
            'operator' => 'OR',
            'conditions' => [
                [
                    'field' => 'priority',
                    'operator' => '>',
                    'value' => 5
                ],
                [
                    'field' => 'urgent',
                    'operator' => '==',
                    'value' => true
                ]
            ]
        ];
        
        $triggerData = ['priority' => 3, 'urgent' => true];
        $result = $evaluator->evaluate($complexCondition, $triggerData, []);
        
        if (!$result) {
            return ['success' => false, 'error' => 'Condição complexa falhou'];
        }
        
        return ['success' => true, 'conditions_tested' => 2];
    }
    
    /**
     * Teste do sistema de templates
     */
    private function testTemplateSystem() {
        $templateData = [
            'name' => 'Test Template',
            'description' => 'Template de teste',
            'category' => 'test',
            'template_data' => [
                'name' => 'Email Notification Template',
                'trigger_type' => 'event',
                'actions' => [
                    [
                        'type' => 'send_email',
                        'config' => [
                            'subject' => 'Template Email',
                            'body' => 'This is a template email'
                        ]
                    ]
                ]
            ],
            'is_public' => true,
            'created_by' => 1
        ];
        
        $result = $this->workflowEngine->createTemplate($templateData);
        
        if (!$result['success']) {
            return ['success' => false, 'error' => $result['error']];
        }
        
        return ['success' => true, 'template_id' => $result['template_id']];
    }
    
    /**
     * Teste de estatísticas
     */
    private function testWorkflowStats() {
        $stats = $this->workflowEngine->getWorkflowStats();
        
        if (!is_array($stats)) {
            return ['success' => false, 'error' => 'Estatísticas não retornaram array'];
        }
        
        // Verificar se há pelo menos algumas colunas esperadas
        if (count($stats) > 0) {
            $requiredColumns = ['id', 'name', 'total_executions', 'success_rate'];
            $firstStat = $stats[0];
            
            foreach ($requiredColumns as $column) {
                if (!array_key_exists($column, $firstStat)) {
                    return ['success' => false, 'error' => "Coluna '$column' não encontrada nas estatísticas"];
                }
            }
        }
        
        return ['success' => true, 'stats_count' => count($stats)];
    }
    
    /**
     * Teste de tratamento de erros
     */
    private function testErrorHandling() {
        // Tentar criar workflow com dados inválidos
        $invalidWorkflow = [
            'name' => '', // Nome vazio deve falhar
            'trigger_type' => 'invalid_type',
            'created_by' => 1
        ];
        
        $result = $this->workflowEngine->createWorkflow($invalidWorkflow);
        
        // Deve falhar graciosamente
        if ($result['success']) {
            return ['success' => false, 'error' => 'Workflow inválido foi aceito'];
        }
        
        // Tentar executar workflow inexistente
        $executeResult = $this->workflowEngine->executeWorkflow(99999);
        
        if ($executeResult['success']) {
            return ['success' => false, 'error' => 'Execução de workflow inexistente foi aceita'];
        }
        
        return ['success' => true, 'errors_handled' => 2];
    }
    
    /**
     * Teste de integração com banco
     */
    private function testDatabaseIntegration() {
        // Verificar se as tabelas foram criadas
        $tables = [
            'workflows',
            'workflow_executions',
            'workflow_triggers',
            'workflow_actions',
            'workflow_templates'
        ];
        
        foreach ($tables as $table) {
            try {
                $stmt = $this->mockDb->query("SELECT 1 FROM $table LIMIT 1");
                if (!$stmt) {
                    return ['success' => false, 'error' => "Tabela '$table' não existe ou inacessível"];
                }
            } catch (Exception $e) {
                return ['success' => false, 'error' => "Erro ao acessar tabela '$table': " . $e->getMessage()];
            }
        }
        
        return ['success' => true, 'tables_verified' => count($tables)];
    }
    
    /**
     * Criar mock database
     */
    private function createMockDatabase() {
        try {
            // Usar SQLite em memória para testes
            $pdo = new PDO('sqlite::memory:');
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            return $pdo;
            
        } catch (Exception $e) {
            throw new Exception("Falha ao criar mock database: " . $e->getMessage());
        }
    }
    
    /**
     * Gerar relatório final
     */
    private function generateReport($passed, $total) {
        echo "\n" . str_repeat("=", 55) . "\n";
        echo "📋 RELATÓRIO DE TESTES - WORKFLOW ENGINE v5.0\n";
        echo str_repeat("=", 55) . "\n";
        
        $successRate = ($passed / $total) * 100;
        
        foreach ($this->testResults as $test => $result) {
            $status = $result['success'] ? '✅' : '❌';
            $testName = str_replace('test', '', $test);
            echo sprintf("   %s %-40s\n", $status, $testName);
            
            if (!$result['success']) {
                echo sprintf("      Erro: %s\n", $result['error']);
            }
        }
        
        echo "\n" . str_repeat("=", 55) . "\n";
        echo "📊 RESULTADO FINAL\n";
        echo str_repeat("=", 55) . "\n";
        
        if ($successRate >= 90) {
            $status = "🎉 EXCELENTE";
        } elseif ($successRate >= 80) {
            $status = "✅ BOM";
        } elseif ($successRate >= 70) {
            $status = "⚠️ ACEITÁVEL";
        } else {
            $status = "❌ PRECISA MELHORIAS";
        }
        
        echo "Status: $status\n";
        echo "Testes executados: $total\n";
        echo "Testes aprovados: $passed\n";
        echo "Taxa de sucesso: " . number_format($successRate, 1) . "%\n";
        
        if ($successRate >= 80) {
            echo "\n🚀 Workflow Engine v5.0 está pronto para produção!\n";
            echo "✅ Sistema de automação validado e funcional\n";
            echo "🔄 Pronto para deploy e uso em produção\n";
        } else {
            echo "\n⚠️ " . ($total - $passed) . " testes falharam\n";
            echo "📝 Revise os erros listados acima\n";
        }
        
        echo "\n📋 PRÓXIMOS PASSOS:\n";
        echo "1. 🔧 Corrigir eventuais problemas encontrados\n";
        echo "2. 🧪 Testar em ambiente com banco real\n";
        echo "3. 📊 Validar interface web e dashboard\n";
        echo "4. 🚀 Deploy em produção\n";
        echo "5. 🔄 Continuar com Notification Center v6.0\n";
    }
}

// Executar testes se chamado diretamente
if (basename(__FILE__) == basename($_SERVER['SCRIPT_NAME'])) {
    try {
        $tester = new WorkflowEngineTest();
        $tester->runAllTests();
    } catch (Exception $e) {
        echo "💥 ERRO CRÍTICO: " . $e->getMessage() . "\n";
        exit(1);
    }
}

?>