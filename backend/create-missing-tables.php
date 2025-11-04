<?php
/**
 * Criação de Tabelas Faltantes no Sistema DuraLux CRM
 * Versão: 7.0
 * Data: 2024
 */

require_once 'config/db_config.php';

class TableCreator {
    private $pdo;
    
    public function __construct() {
        $this->pdo = getDatabaseConnection();
    }
    
    /**
     * Cria tabela de logs de atividades
     */
    public function createActivityLogsTable() {
        $sql = "CREATE TABLE IF NOT EXISTS activity_logs (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER,
            activity_type VARCHAR(50) NOT NULL,
            description TEXT NOT NULL,
            entity_type VARCHAR(50),
            entity_id INTEGER,
            metadata TEXT,
            ip_address VARCHAR(45),
            user_agent TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )";
        
        try {
            $this->pdo->exec($sql);
            echo "✅ Tabela activity_logs criada com sucesso!\n";
            return true;
        } catch (Exception $e) {
            echo "❌ Erro ao criar tabela activity_logs: " . $e->getMessage() . "\n";
            return false;
        }
    }
    
    /**
     * Cria tabela de tarefas de projetos
     */
    public function createProjectTasksTable() {
        $sql = "CREATE TABLE IF NOT EXISTS project_tasks (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            project_id INTEGER NOT NULL,
            task_name VARCHAR(200) NOT NULL,
            description TEXT,
            assigned_to INTEGER,
            priority VARCHAR(20) DEFAULT 'media',
            status VARCHAR(20) DEFAULT 'pendente',
            start_date DATE,
            due_date DATE,
            completion_date DATETIME,
            estimated_hours DECIMAL(8,2),
            actual_hours DECIMAL(8,2),
            progress_percent INTEGER DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
        )";
        
        try {
            $this->pdo->exec($sql);
            echo "✅ Tabela project_tasks criada com sucesso!\n";
            return true;
        } catch (Exception $e) {
            echo "❌ Erro ao criar tabela project_tasks: " . $e->getMessage() . "\n";
            return false;
        }
    }
    
    /**
     * Cria tabela de conversações do AI Assistant
     */
    public function createAIConversationsTable() {
        $sql = "CREATE TABLE IF NOT EXISTS ai_conversations (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER,
            session_id VARCHAR(100) NOT NULL,
            message_type VARCHAR(20) NOT NULL,
            message TEXT NOT NULL,
            intent VARCHAR(100),
            confidence DECIMAL(3,2),
            sentiment VARCHAR(20),
            context_data TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )";
        
        try {
            $this->pdo->exec($sql);
            echo "✅ Tabela ai_conversations criada com sucesso!\n";
            return true;
        } catch (Exception $e) {
            echo "❌ Erro ao criar tabela ai_conversations: " . $e->getMessage() . "\n";
            return false;
        }
    }
    
    /**
     * Cria tabela de insights preditivos
     */
    public function createPredictiveInsightsTable() {
        $sql = "CREATE TABLE IF NOT EXISTS predictive_insights (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            insight_type VARCHAR(50) NOT NULL,
            title VARCHAR(200) NOT NULL,
            description TEXT,
            prediction_data TEXT,
            confidence_level DECIMAL(3,2),
            impact_score INTEGER,
            category VARCHAR(50),
            status VARCHAR(20) DEFAULT 'ativo',
            expires_at DATETIME,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )";
        
        try {
            $this->pdo->exec($sql);
            echo "✅ Tabela predictive_insights criada com sucesso!\n";
            return true;
        } catch (Exception $e) {
            echo "❌ Erro ao criar tabela predictive_insights: " . $e->getMessage() . "\n";
            return false;
        }
    }
    
    /**
     * Cria tabela de configurações do sistema
     */
    public function createSystemConfiguraçõesTable() {
        $sql = "CREATE TABLE IF NOT EXISTS system_settings (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            setting_key VARCHAR(100) UNIQUE NOT NULL,
            setting_value TEXT,
            setting_type VARCHAR(20) DEFAULT 'string',
            category VARCHAR(50),
            description TEXT,
            is_public INTEGER DEFAULT 0,
            updated_by INTEGER,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )";
        
        try {
            $this->pdo->exec($sql);
            echo "✅ Tabela system_settings criada com sucesso!\n";
            return true;
        } catch (Exception $e) {
            echo "❌ Erro ao criar tabela system_settings: " . $e->getMessage() . "\n";
            return false;
        }
    }
    
    /**
     * Cria tabela de notificações
     */
    public function createNotificationsTable() {
        $sql = "CREATE TABLE IF NOT EXISTS notifications (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER,
            title VARCHAR(200) NOT NULL,
            message TEXT NOT NULL,
            type VARCHAR(50) DEFAULT 'info',
            priority VARCHAR(20) DEFAULT 'normal',
            is_read INTEGER DEFAULT 0,
            action_url VARCHAR(500),
            action_label VARCHAR(100),
            expires_at DATETIME,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            read_at DATETIME
        )";
        
        try {
            $this->pdo->exec($sql);
            echo "✅ Tabela notifications criada com sucesso!\n";
            return true;
        } catch (Exception $e) {
            echo "❌ Erro ao criar tabela notifications: " . $e->getMessage() . "\n";
            return false;
        }
    }
    
    /**
     * Popula configurações padrão do sistema
     */
    public function insertDefaultConfigurações() {
        $defaultConfigurações = [
            ['analytics_refresh_interval', '30', 'integer', 'analytics', 'Intervalo de atualização do dashboard em segundos'],
            ['backup_frequency', 'daily', 'string', 'backup', 'Frequência dos backups automáticos'],
            ['ai_assistant_enabled', 'true', 'boolean', 'ai', 'Habilitar assistente de IA'],
            ['notification_email_enabled', 'true', 'boolean', 'notifications', 'Enviar notificações por email'],
            ['max_file_upload_size', '10', 'integer', 'files', 'Tamanho máximo de upload em MB'],
            ['company_name', 'DuraLux CRM', 'string', 'company', 'Nome da empresa'],
            ['company_logo', '/assets/images/logo.png', 'string', 'company', 'Logo da empresa'],
            ['timezone', 'America/Sao_Paulo', 'string', 'general', 'Fuso horário do sistema'],
            ['currency_symbol', 'R$', 'string', 'financial', 'Símbolo da moeda'],
            ['language', 'pt-br', 'string', 'general', 'Idioma padrão do sistema']
        ];
        
        $sql = "INSERT OR IGNORE INTO system_settings (setting_key, setting_value, setting_type, category, description) VALUES (?, ?, ?, ?, ?)";
        $stmt = $this->pdo->prepare($sql);
        
        $inserted = 0;
        foreach ($defaultConfigurações as $setting) {
            if ($stmt->execute($setting)) {
                $inserted++;
            }
        }
        
        echo "✅ $inserted configurações padrão inseridas!\n";
    }
    
    /**
     * Executa todas as criações de tabelas
     */
    public function createAllTables() {
        echo "🚀 Criando tabelas do sistema...\n\n";
        
        $this->createActivityLogsTable();
        $this->createProjectTasksTable();
        $this->createAIConversationsTable();
        $this->createPredictiveInsightsTable();
        $this->createSystemConfiguraçõesTable();
        $this->createNotificationsTable();
        
        echo "\n🔧 Inserindo configurações padrão...\n";
        $this->insertDefaultConfigurações();
        
        echo "\n🎉 Todas as tabelas foram criadas com sucesso!\n";
        echo "✅ Sistema DuraLux CRM v7.0 totalmente configurado!\n";
    }
}

// Executar criação das tabelas
if ($argc > 1 && $argv[1] === 'create') {
    $creator = new TableCreator();
    $creator->createAllTables();
} else {
    echo "Uso: php create-missing-tables.php create\n";
}
?>