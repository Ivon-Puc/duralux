<?php
/**
 * DURALUX CRM - Advanced Analytics Engine v7.0
 * Sistema avançado de análises e relatórios
 * 
 * @author Duralux Development Team
 * @version 7.0
 * @since 2025-11-04
 */

class AdvancedAnalytics {
    private $db;
    private $cache;
    private $config;
    
    public function __construct($database = null, $cache = null) {
        $this->db = $database ?? $this->initializeDatabase();
        $this->cache = $cache ?? $this->initializeCache();
        $this->config = $this->loadConfig();
        $this->initializeTables();
    }
    
    /**
     * Inicializa cache Redis para performance
     */
    private function initializeCache() {
        try {
            $redis = new Redis();
            $redis->connect('127.0.0.1', 6379);
            return $redis;
        } catch (Exception $e) {
            error_log("Analytics Cache Error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Inicializa conexão com banco de dados
     */
    private function initializeDatabase() {
        $config = [
            'host' => 'localhost',
            'dbname' => 'duralux_crm',
            'username' => 'root',
            'password' => ''
        ];
        
        try {
            $pdo = new PDO(
                "mysql:host={$config['host']};dbname={$config['dbname']};charset=utf8mb4",
                $config['username'],
                $config['password'],
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
            return $pdo;
        } catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            throw new Exception("Erro na conexão com banco de dados");
        }
    }
    
    /**
     * Carrega configurações do sistema
     */
    private function loadConfig() {
        return [
            'cache_ttl' => 300, // 5 minutos
            'date_format' => 'Y-m-d H:i:s',
            'timezone' => 'America/Sao_Paulo',
            'currency' => 'BRL',
            'decimal_places' => 2
        ];
    }
    
    /**
     * Inicializa tabelas necessárias
     */
    private function initializeTables() {
        $sql = "
        CREATE TABLE IF NOT EXISTS analytics_metrics (
            id INT AUTO_INCREMENT PRIMARY KEY,
            metric_name VARCHAR(100) NOT NULL,
            metric_value DECIMAL(15,2) NOT NULL,
            metric_date DATE NOT NULL,
            metric_hour INT DEFAULT 0,
            category VARCHAR(50) NOT NULL,
            subcategory VARCHAR(50) DEFAULT NULL,
            metadata JSON DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_metric_date (metric_date),
            INDEX idx_category (category),
            INDEX idx_metric_name (metric_name)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        
        CREATE TABLE IF NOT EXISTS analytics_reports (
            id INT AUTO_INCREMENT PRIMARY KEY,
            report_name VARCHAR(150) NOT NULL,
            report_type VARCHAR(50) NOT NULL,
            report_config JSON NOT NULL,
            report_data JSON DEFAULT NULL,
            generated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            expires_at TIMESTAMP NULL,
            user_id INT DEFAULT NULL,
            is_scheduled BOOLEAN DEFAULT FALSE,
            schedule_config JSON DEFAULT NULL,
            INDEX idx_report_type (report_type),
            INDEX idx_generated_at (generated_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        
        CREATE TABLE IF NOT EXISTS analytics_kpi_targets (
            id INT AUTO_INCREMENT PRIMARY KEY,
            kpi_name VARCHAR(100) NOT NULL UNIQUE,
            target_value DECIMAL(15,2) NOT NULL,
            period_type ENUM('daily', 'weekly', 'monthly', 'quarterly', 'yearly') NOT NULL,
            is_active BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ";
        
        try {
            $this->db->exec($sql);
            $this->insertDefaultKPITargets();
        } catch (PDOException $e) {
            error_log("Error creating analytics tables: " . $e->getMessage());
        }
    }
    
    /**
     * Insere KPIs padrão do sistema
     */
    private function insertDefaultKPITargets() {
        $defaultKPIs = [
            ['kpi_name' => 'leads_conversion_rate', 'target_value' => 15.00, 'period_type' => 'monthly'],
            ['kpi_name' => 'monthly_revenue', 'target_value' => 50000.00, 'period_type' => 'monthly'],
            ['kpi_name' => 'customer_acquisition_cost', 'target_value' => 200.00, 'period_type' => 'monthly'],
            ['kpi_name' => 'customer_lifetime_value', 'target_value' => 3000.00, 'period_type' => 'yearly'],
            ['kpi_name' => 'active_deals_count', 'target_value' => 25.00, 'period_type' => 'monthly'],
            ['kpi_name' => 'average_deal_size', 'target_value' => 2500.00, 'period_type' => 'monthly']
        ];
        
        $sql = "INSERT IGNORE INTO analytics_kpi_targets (kpi_name, target_value, period_type) VALUES (?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        
        foreach ($defaultKPIs as $kpi) {
            $stmt->execute([$kpi['kpi_name'], $kpi['target_value'], $kpi['period_type']]);
        }
    }
    
    /**
     * Coleta métricas em tempo real do dashboard
     */
    public function getPainel de ControleMetrics($dateRange = null) {
        $cacheKey = "dashboard_metrics_" . md5(serialize($dateRange));
        
        // Tenta buscar do cache primeiro
        if ($this->cache && $cached = $this->cache->get($cacheKey)) {
            return json_decode($cached, true);
        }
        
        $dateFiltrar = $this->buildDateFiltrar($dateRange);
        
        $metrics = [
            'leads' => $this->getLeadsMetrics($dateFiltrar),
            'customers' => $this->getCustomersMetrics($dateFiltrar),
            'projects' => $this->getProjectsMetrics($dateFiltrar),
            'revenue' => $this->getReceitaMetrics($dateFiltrar),
            'performance' => $this->getPerformanceMetrics($dateFiltrar),
            'trends' => $this->getTrendAnalysis($dateFiltrar)
        ];
        
        // Salva no cache
        if ($this->cache) {
            $this->cache->setex($cacheKey, $this->config['cache_ttl'], json_encode($metrics));
        }
        
        return $metrics;
    }
    
    /**
     * Métricas de leads
     */
    private function getLeadsMetrics($dateFiltrar) {
        $sql = "
            SELECT 
                COUNT(*) as total_leads,
                COUNT(CASE WHEN status = 'new' THEN 1 END) as new_leads,
                COUNT(CASE WHEN status = 'qualified' THEN 1 END) as qualified_leads,
                COUNT(CASE WHEN status = 'converted' THEN 1 END) as converted_leads,
                COUNT(CASE WHEN status = 'lost' THEN 1 END) as lost_leads,
                ROUND(
                    (COUNT(CASE WHEN status = 'converted' THEN 1 END) * 100.0 / 
                     NULLIF(COUNT(*), 0)), 2
                ) as conversion_rate,
                AVG(CASE WHEN score IS NOT NULL THEN score END) as avg_score
            FROM leads 
            WHERE 1=1 {$dateFiltrar}
        ";
        
        $stmt = $this->db->query($sql);
        $result = $stmt->fetch();
        
        // Adiciona métricas calculadas
        $result['hot_leads'] = $this->getHotLeads($dateFiltrar);
        $result['lead_sources'] = $this->getLeadSources($dateFiltrar);
        $result['daily_leads'] = $this->getDiárioLeadsChart($dateFiltrar);
        
        return $result;
    }
    
    /**
     * Métricas de clientes
     */
    private function getCustomersMetrics($dateFiltrar) {
        $sql = "
            SELECT 
                COUNT(*) as total_customers,
                COUNT(CASE WHEN status = 'active' THEN 1 END) as active_customers,
                COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as new_customers_30d,
                AVG(
                    CASE WHEN annual_revenue IS NOT NULL AND annual_revenue > 0 
                    THEN annual_revenue END
                ) as avg_customer_value
            FROM customers 
            WHERE 1=1 {$dateFiltrar}
        ";
        
        $stmt = $this->db->query($sql);
        $result = $stmt->fetch();
        
        $result['customer_segments'] = $this->getCustomerSegments($dateFiltrar);
        $result['retention_rate'] = $this->calculateRetentionRate($dateFiltrar);
        
        return $result;
    }
    
    /**
     * Métricas de projetos
     */
    private function getProjectsMetrics($dateFiltrar) {
        $sql = "
            SELECT 
                COUNT(*) as total_projects,
                COUNT(CASE WHEN status = 'in_progress' THEN 1 END) as active_projects,
                COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_projects,
                COUNT(CASE WHEN status = 'on_hold' THEN 1 END) as on_hold_projects,
                COUNT(CASE WHEN due_date < NOW() AND status != 'completed' THEN 1 END) as overdue_projects,
                AVG(CASE WHEN budget IS NOT NULL AND budget > 0 THEN budget END) as avg_project_budget,
                SUM(CASE WHEN status = 'completed' AND budget IS NOT NULL THEN budget END) as completed_revenue
            FROM projects 
            WHERE 1=1 {$dateFiltrar}
        ";
        
        $stmt = $this->db->query($sql);
        $result = $stmt->fetch();
        
        $result['project_timeline'] = $this->getProjectTimeline($dateFiltrar);
        $result['priority_distribution'] = $this->getProjectPriorityDistribution($dateFiltrar);
        
        return $result;
    }
    
    /**
     * Métricas de receita
     */
    private function getReceitaMetrics($dateFiltrar) {
        $baseReceita = [
            'total_revenue' => 0,
            'monthly_recurring_revenue' => 0,
            'average_deal_size' => 0,
            'revenue_growth' => 0
        ];
        
        // Simula dados de receita (integrar com sistema real)
        $sql = "
            SELECT 
                SUM(CASE WHEN status = 'completed' AND budget IS NOT NULL THEN budget END) as total_revenue,
                COUNT(CASE WHEN status = 'completed' THEN 1 END) as closed_deals,
                AVG(CASE WHEN status = 'completed' AND budget IS NOT NULL THEN budget END) as avg_deal_size
            FROM projects 
            WHERE 1=1 {$dateFiltrar}
        ";
        
        $stmt = $this->db->query($sql);
        $result = $stmt->fetch();
        
        $result['revenue_by_month'] = $this->getReceitaByMonth($dateFiltrar);
        $result['revenue_forecast'] = $this->generateReceitaForecast();
        
        return array_merge($baseReceita, $result ?: []);
    }
    
    /**
     * Métricas de performance
     */
    private function getPerformanceMetrics($dateFiltrar) {
        return [
            'response_time' => $this->getAverageResponseTime(),
            'system_uptime' => $this->getSystemUptime(),
            'active_users' => $this->getActiveUsers($dateFiltrar),
            'page_views' => $this->getPageViews($dateFiltrar),
            'cache_hit_rate' => $this->getCacheHitRate()
        ];
    }
    
    /**
     * Análise de tendências
     */
    private function getTrendAnalysis($dateFiltrar) {
        return [
            'leads_trend' => $this->calculateTrend('leads', $dateFiltrar),
            'revenue_trend' => $this->calculateTrend('revenue', $dateFiltrar),
            'conversion_trend' => $this->calculateTrend('conversion', $dateFiltrar),
            'customer_satisfaction' => $this->getCustomerSatisfactionTrend($dateFiltrar)
        ];
    }
    
    /**
     * Gera relatório customizável
     */
    public function generateCustomReport($config) {
        $reportId = $this->saveReportConfig($config);
        
        $data = [
            'report_id' => $reportId,
            'generated_at' => date($this->config['date_format']),
            'config' => $config,
            'data' => []
        ];
        
        // Processa cada seção do relatório
        foreach ($config['sections'] as $section) {
            switch ($section['type']) {
                case 'metrics':
                    $data['data'][$section['name']] = $this->getPainel de ControleMetrics($section['date_range'] ?? null);
                    break;
                case 'chart':
                    $data['data'][$section['name']] = $this->generateChartData($section);
                    break;
                case 'table':
                    $data['data'][$section['name']] = $this->generateTableData($section);
                    break;
            }
        }
        
        // Salva dados do relatório
        $this->saveReportData($reportId, $data);
        
        return $data;
    }
    
    /**
     * Gera forecasting usando análise de tendência
     */
    public function generateReceitaForecast($months = 6) {
        // Busca dados históricos dos últimos 12 meses
        $sql = "
            SELECT 
                DATE_FORMAT(created_at, '%Y-%m') as month,
                SUM(CASE WHEN status = 'completed' AND budget IS NOT NULL THEN budget END) as revenue
            FROM projects 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
            GROUP BY DATE_FORMAT(created_at, '%Y-%m')
            ORDER BY month
        ";
        
        $stmt = $this->db->query($sql);
        $historical = $stmt->fetchAll();
        
        if (count($historical) < 3) {
            return ['error' => 'Dados insuficientes para previsão'];
        }
        
        // Calcula tendência linear simples
        $forecast = [];
        $revenues = array_column($historical, 'revenue');
        $trend = $this->calculateLinearTrend($revenues);
        
        $lastReceita = end($revenues);
        
        for ($i = 1; $i <= $months; $i++) {
            $forecastValue = $lastReceita + ($trend * $i);
            $forecastValue = max(0, $forecastValue); // Não permite valores negativos
            
            $forecast[] = [
                'month' => date('Y-m', strtotime("+{$i} months")),
                'forecasted_revenue' => round($forecastValue, 2),
                'confidence' => max(50, 90 - ($i * 5)) // Diminui confiança com distância
            ];
        }
        
        return [
            'historical' => $historical,
            'forecast' => $forecast,
            'trend' => $trend,
            'generated_at' => date($this->config['date_format'])
        ];
    }
    
    /**
     * Exportarara relatório para PDF
     */
    public function exportReportToPDF($reportId, $template = 'default') {
        // Implementação básica - expandir conforme necessário
        $report = $this->getReportData($reportId);
        
        if (!$report) {
            throw new Exception('Relatório não encontrado');
        }
        
        $html = $this->generateReportHTML($report, $template);
        
        // Usaria biblioteca como TCPDF ou mPDF em produção
        $filename = "duralux_report_{$reportId}_" . date('Y-m-d_H-i-s') . ".pdf";
        
        return [
            'filename' => $filename,
            'html' => $html,
            'success' => true
        ];
    }
    
    /**
     * Métodos auxiliares
     */
    private function buildDateFiltrar($dateRange) {
        if (!$dateRange) return '';
        
        $filter = '';
        if (isset($dateRange['start'])) {
            $filter .= " AND created_at >= '" . $this->db->quote($dateRange['start']) . "'";
        }
        if (isset($dateRange['end'])) {
            $filter .= " AND created_at <= '" . $this->db->quote($dateRange['end']) . "'";
        }
        
        return $filter;
    }
    
    private function calculateLinearTrend($values) {
        $n = count($values);
        if ($n < 2) return 0;
        
        $x = range(1, $n);
        $xy = array_sum(array_map(function($i) use ($values, $x) {
            return $x[$i] * $values[$i];
        }, range(0, $n-1)));
        
        $x_sum = array_sum($x);
        $y_sum = array_sum($values);
        $x_sq_sum = array_sum(array_map(function($v) { return $v * $v; }, $x));
        
        return ($n * $xy - $x_sum * $y_sum) / ($n * $x_sq_sum - $x_sum * $x_sum);
    }
    
    private function getHotLeads($dateFiltrar) {
        $sql = "SELECT COUNT(*) as count FROM leads WHERE score >= 80 {$dateFiltrar}";
        $stmt = $this->db->query($sql);
        return $stmt->fetchColumn();
    }
    
    private function getLeadSources($dateFiltrar) {
        $sql = "
            SELECT 
                COALESCE(source, 'Não informado') as source, 
                COUNT(*) as count 
            FROM leads 
            WHERE 1=1 {$dateFiltrar} 
            GROUP BY source 
            ORDER BY count DESC 
            LIMIT 10
        ";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }
    
    private function getDiárioLeadsChart($dateFiltrar) {
        $sql = "
            SELECT 
                DATE(created_at) as date, 
                COUNT(*) as count 
            FROM leads 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) {$dateFiltrar}
            GROUP BY DATE(created_at) 
            ORDER BY date
        ";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }
    
    private function saveReportConfig($config) {
        $sql = "INSERT INTO analytics_reports (report_name, report_type, report_config) VALUES (?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $config['name'] ?? 'Relatório Customizado',
            $config['type'] ?? 'custom',
            json_encode($config)
        ]);
        return $this->db->lastInsertId();
    }
    
    private function saveReportData($reportId, $data) {
        $sql = "UPDATE analytics_reports SET report_data = ?, expires_at = DATE_ADD(NOW(), INTERVAL 7 DAY) WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([json_encode($data), $reportId]);
    }
    
    /**
     * API endpoints para frontend
     */
    public function handleAPIRequest($endpoint, $params = []) {
        switch ($endpoint) {
            case 'dashboard':
                return $this->getPainel de ControleMetrics($params['date_range'] ?? null);
            
            case 'forecast':
                return $this->generateReceitaForecast($params['months'] ?? 6);
            
            case 'custom_report':
                return $this->generateCustomReport($params['config']);
            
            case 'export_pdf':
                return $this->exportReportToPDF($params['report_id'], $params['template'] ?? 'default');
            
            default:
                throw new Exception('Endpoint não encontrado');
        }
    }
}

// Instanciação para uso global
$duraluxAnalytics = new AdvancedAnalytics();
?>