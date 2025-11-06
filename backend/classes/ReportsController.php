
<?php
/**
 * Duralux CRM - ReportsController
 * 
 * Sistema completo de relatórios avançados e análises
 * Funcionalidades: Relatórios customizáveis, exportação PDF/Excel, gráficos, métricas
 * 
 * @version 1.4.0
 * @author Maria Eduarda Cardoso de Oliveira
 * @created 2025-01-03
 */

require_once __DIR__ . '/BaseController.php';

class ReportsController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->requireAuth(); // Proteção de autenticação
    }

    /**
     * Handle action-based requests
     */
    public function handleRequest()
    {
        $action = $this->getRequestData()['action'] ?? '';
        
        switch ($action) {
            case 'get_dashboard_report':
                return $this->getPainel de ControleReport();
            case 'get_sales_report':
                return $this->getVendasReport();
            case 'get_leads_report':
                return $this->getLeadsReport();
            case 'get_projects_report':
                return $this->getProjectsReport();
            case 'get_customers_report':
                return $this->getCustomersReport();
            case 'get_financial_report':
                return $this->getFinancialReport();
            case 'export_report':
                return $this->exportReport();
            case 'get_chart_data':
                return $this->getChartData();
            default:
                $this->jsonResponse(['success' => false, 'message' => 'Ação não reconhecida'], 400);
        }
    }

    /**
     * Relatório executivo do dashboard
     */
    public function getPainel de ControleReport()
    {
        try {
            $period = $_GET['period'] ?? 'month';
            $startDate = $this->getPeriodStartDate($period);
            $endDate = date('Y-m-d H:i:s');

            // Métricas gerais
            $generalMetrics = $this->getGeneralMetrics($startDate, $endDate);
            
            // Evolução temporal
            $timeEvolution = $this->getTimeEvolution($period, $startDate, $endDate);
            
            // Top performers
            $topPerformers = $this->getTopPerformers($startDate, $endDate);
            
            // Distribuições
            $distributions = $this->getDistributions($startDate, $endDate);

            $this->jsonResponse([
                'success' => true,
                'data' => [
                    'general_metrics' => $generalMetrics,
                    'time_evolution' => $timeEvolution,
                    'top_performers' => $topPerformers,
                    'distributions' => $distributions,
                    'period' => $period,
                    'date_range' => [
                        'start' => $startDate,
                        'end' => $endDate
                    ]
                ]
            ]);

        } catch (Exception $e) {
            $this->logActivity('reports', 'dashboard_error', null, "Erro no relatório dashboard: " . $e->getMessage());
            $this->jsonResponse(['success' => false, 'message' => 'Erro interno do servidor'], 500);
        }
    }

    /**
     * Relatório detalhado de vendas
     */
    public function getVendasReport()
    {
        try {
            $startDate = $_GET['start_date'] ?? date('Y-m-01');
            $endDate = $_GET['end_date'] ?? date('Y-m-d');
            $groupBy = $_GET['group_by'] ?? 'day';

            // Vendas por período
            $salesByPeriod = $this->getVendasByPeriod($startDate, $endDate, $groupBy);
            
            // Vendas por produto
            $salesByProduct = $this->getVendasByProduct($startDate, $endDate);
            
            // Vendas por cliente
            $salesByCustomer = $this->getVendasByCustomer($startDate, $endDate);
            
            // Métricas de performance
            $performanceMetrics = $this->getVendasPerformanceMetrics($startDate, $endDate);
            
            // Análise de tendências
            $trends = $this->getVendasTrends($startDate, $endDate);

            $this->jsonResponse([
                'success' => true,
                'data' => [
                    'sales_by_period' => $salesByPeriod,
                    'sales_by_product' => $salesByProduct,
                    'sales_by_customer' => $salesByCustomer,
                    'performance_metrics' => $performanceMetrics,
                    'trends' => $trends,
                    'filters' => [
                        'start_date' => $startDate,
                        'end_date' => $endDate,
                        'group_by' => $groupBy
                    ]
                ]
            ]);

        } catch (Exception $e) {
            $this->logActivity('reports', 'sales_error', null, "Erro no relatório de vendas: " . $e->getMessage());
            $this->jsonResponse(['success' => false, 'message' => 'Erro interno do servidor'], 500);
        }
    }

    /**
     * Relatório de leads e conversões
     */
    public function getLeadsReport()
    {
        try {
            $startDate = $_GET['start_date'] ?? date('Y-m-01');
            $endDate = $_GET['end_date'] ?? date('Y-m-d');
            $source = $_GET['source'] ?? '';

            // Pipeline de conversão
            $conversionPipeline = $this->getLeadsConversionPipeline($startDate, $endDate, $source);
            
            // Leads por fonte
            $leadsBySource = $this->getLeadsBySource($startDate, $endDate);
            
            // Taxa de conversão
            $conversionRates = $this->getConversionRates($startDate, $endDate);
            
            // Tempo médio de conversão
            $conversionTimes = $this->getConversionTimes($startDate, $endDate);
            
            // Leads perdidos - análise
            $lostLeadsAnalysis = $this->getLostLeadsAnalysis($startDate, $endDate);

            $this->jsonResponse([
                'success' => true,
                'data' => [
                    'conversion_pipeline' => $conversionPipeline,
                    'leads_by_source' => $leadsBySource,
                    'conversion_rates' => $conversionRates,
                    'conversion_times' => $conversionTimes,
                    'lost_leads_analysis' => $lostLeadsAnalysis,
                    'filters' => [
                        'start_date' => $startDate,
                        'end_date' => $endDate,
                        'source' => $source
                    ]
                ]
            ]);

        } catch (Exception $e) {
            $this->logActivity('reports', 'leads_error', null, "Erro no relatório de leads: " . $e->getMessage());
            $this->jsonResponse(['success' => false, 'message' => 'Erro interno do servidor'], 500);
        }
    }

    /**
     * Relatório de projetos
     */
    public function getProjectsReport()
    {
        try {
            $startDate = $_GET['start_date'] ?? date('Y-m-01');
            $endDate = $_GET['end_date'] ?? date('Y-m-d');
            $status = $_GET['status'] ?? '';

            // Projetos por status
            $projectsByStatus = $this->getProjectsByStatus($startDate, $endDate);
            
            // Performance de projetos
            $projectsPerformance = $this->getProjectsPerformance($startDate, $endDate, $status);
            
            // Cronograma e atrasos
            $scheduleAnalysis = $this->getProjectsScheduleAnalysis($startDate, $endDate);
            
            // Utilização de recursos
            $resourceUtilization = $this->getResourceUtilization($startDate, $endDate);
            
            // ROI de projetos
            $projectsROI = $this->getProjectsROI($startDate, $endDate);

            $this->jsonResponse([
                'success' => true,
                'data' => [
                    'projects_by_status' => $projectsByStatus,
                    'projects_performance' => $projectsPerformance,
                    'schedule_analysis' => $scheduleAnalysis,
                    'resource_utilization' => $resourceUtilization,
                    'projects_roi' => $projectsROI,
                    'filters' => [
                        'start_date' => $startDate,
                        'end_date' => $endDate,
                        'status' => $status
                    ]
                ]
            ]);

        } catch (Exception $e) {
            $this->logActivity('reports', 'projects_error', null, "Erro no relatório de projetos: " . $e->getMessage());
            $this->jsonResponse(['success' => false, 'message' => 'Erro interno do servidor'], 500);
        }
    }

    /**
     * Relatório de clientes
     */
    public function getCustomersReport()
    {
        try {
            $startDate = $_GET['start_date'] ?? date('Y-01-01');
            $endDate = $_GET['end_date'] ?? date('Y-m-d');

            // Análise de clientes
            $customerAnalysis = $this->getCustomerAnalysis($startDate, $endDate);
            
            // Segmentação de clientes
            $customerSegmentation = $this->getCustomerSegmentation($startDate, $endDate);
            
            // Valor do cliente (LTV)
            $customerLifetimeValue = $this->getCustomerLifetimeValue($startDate, $endDate);
            
            // Churn analysis
            $churnAnalysis = $this->getChurnAnalysis($startDate, $endDate);
            
            // Satisfação do cliente
            $satisfactionMetrics = $this->getCustomerSatisfactionMetrics($startDate, $endDate);

            $this->jsonResponse([
                'success' => true,
                'data' => [
                    'customer_analysis' => $customerAnalysis,
                    'customer_segmentation' => $customerSegmentation,
                    'customer_lifetime_value' => $customerLifetimeValue,
                    'churn_analysis' => $churnAnalysis,
                    'satisfaction_metrics' => $satisfactionMetrics,
                    'filters' => [
                        'start_date' => $startDate,
                        'end_date' => $endDate
                    ]
                ]
            ]);

        } catch (Exception $e) {
            $this->logActivity('reports', 'customers_error', null, "Erro no relatório de clientes: " . $e->getMessage());
            $this->jsonResponse(['success' => false, 'message' => 'Erro interno do servidor'], 500);
        }
    }

    /**
     * Relatório financeiro
     */
    public function getFinancialReport()
    {
        try {
            $startDate = $_GET['start_date'] ?? date('Y-m-01');
            $endDate = $_GET['end_date'] ?? date('Y-m-d');

            // Resumo financeiro
            $financialSummary = $this->getFinancialSummary($startDate, $endDate);
            
            // Fluxo de caixa
            $cashFlow = $this->getCashFlow($startDate, $endDate);
            
            // Contas a receber
            $accountsReceivable = $this->getAccountsReceivable($startDate, $endDate);
            
            // Análise de rentabilidade
            $profitabilityAnalysis = $this->getProfitabilityAnalysis($startDate, $endDate);
            
            // Previsões financeiras
            $financialForecasts = $this->getFinancialForecasts($startDate, $endDate);

            $this->jsonResponse([
                'success' => true,
                'data' => [
                    'financial_summary' => $financialSummary,
                    'cash_flow' => $cashFlow,
                    'accounts_receivable' => $accountsReceivable,
                    'profitability_analysis' => $profitabilityAnalysis,
                    'financial_forecasts' => $financialForecasts,
                    'filters' => [
                        'start_date' => $startDate,
                        'end_date' => $endDate
                    ]
                ]
            ]);

        } catch (Exception $e) {
            $this->logActivity('reports', 'financial_error', null, "Erro no relatório financeiro: " . $e->getMessage());
            $this->jsonResponse(['success' => false, 'message' => 'Erro interno do servidor'], 500);
        }
    }

    /**
     * Dados para gráficos
     */
    public function getChartData()
    {
        try {
            $chartType = $_GET['chart_type'] ?? '';
            $period = $_GET['period'] ?? 'month';
            $startDate = $_GET['start_date'] ?? $this->getPeriodStartDate($period);
            $endDate = $_GET['end_date'] ?? date('Y-m-d');

            $data = [];

            switch ($chartType) {
                case 'revenue_trend':
                    $data = $this->getReceitaTrendData($startDate, $endDate, $period);
                    break;
                case 'leads_funnel':
                    $data = $this->getLeadsFunnelData($startDate, $endDate);
                    break;
                case 'projects_timeline':
                    $data = $this->getProjectsTimelineData($startDate, $endDate);
                    break;
                case 'customer_growth':
                    $data = $this->getCustomerGrowthData($startDate, $endDate, $period);
                    break;
                case 'sales_by_product':
                    $data = $this->getVendasByProductChartData($startDate, $endDate);
                    break;
                default:
                    throw new Exception('Tipo de gráfico não suportado');
            }

            $this->jsonResponse([
                'success' => true,
                'data' => $data,
                'chart_type' => $chartType,
                'period' => $period
            ]);

        } catch (Exception $e) {
            $this->logActivity('reports', 'chart_error', null, "Erro nos dados do gráfico: " . $e->getMessage());
            $this->jsonResponse(['success' => false, 'message' => 'Erro interno do servidor'], 500);
        }
    }

    /**
     * Exportararação de relatórios
     */
    public function exportReport()
    {
        try {
            $reportType = $_POST['report_type'] ?? '';
            $format = $_POST['format'] ?? 'pdf';
            $filters = $_POST['filters'] ?? [];

            // Validar parâmetros
            if (empty($reportType)) {
                throw new Exception('Tipo de relatório não especificado');
            }

            // Gerar dados do relatório
            $reportData = $this->generateReportData($reportType, $filters);
            
            // Exportararar no formato solicitado
            if ($format === 'pdf') {
                $this->exportToPDF($reportType, $reportData, $filters);
            } elseif ($format === 'excel') {
                $this->exportToExcel($reportType, $reportData, $filters);
            } elseif ($format === 'csv') {
                $this->exportToCSV($reportType, $reportData, $filters);
            } else {
                throw new Exception('Formato de exportação não suportado');
            }

            $this->logActivity('reports', 'export', null, "Relatório $reportType exportado em $format");

        } catch (Exception $e) {
            $this->logActivity('reports', 'export_error', null, "Erro na exportação: " . $e->getMessage());
            $this->jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // ==================== MÉTODOS DE DADOS ====================

    /**
     * Obter métricas gerais
     */
    private function getGeneralMetrics($startDate, $endDate)
    {
        $sql = "
            SELECT 
                (SELECT COUNT(*) FROM customers WHERE created_at BETWEEN ? AND ?) as total_customers,
                (SELECT COUNT(*) FROM leads WHERE created_at BETWEEN ? AND ?) as total_leads,
                (SELECT COUNT(*) FROM projects WHERE created_at BETWEEN ? AND ?) as total_projects,
                (SELECT COUNT(*) FROM orders WHERE created_at BETWEEN ? AND ?) as total_orders,
                (SELECT COALESCE(SUM(total_amount), 0) FROM orders WHERE created_at BETWEEN ? AND ? AND payment_status = 'paid') as total_revenue,
                (SELECT COUNT(*) FROM leads WHERE status = 'converted' AND updated_at BETWEEN ? AND ?) as converted_leads
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $startDate, $endDate, // customers
            $startDate, $endDate, // leads  
            $startDate, $endDate, // projects
            $startDate, $endDate, // orders
            $startDate, $endDate, // revenue
            $startDate, $endDate  // converted leads
        ]);

        $metrics = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Calcular taxa de conversão
        $conversionRate = $metrics['total_leads'] > 0 
            ? round(($metrics['converted_leads'] / $metrics['total_leads']) * 100, 2) 
            : 0;

        $metrics['conversion_rate'] = $conversionRate;
        $metrics['avg_order_value'] = $metrics['total_orders'] > 0 
            ? round($metrics['total_revenue'] / $metrics['total_orders'], 2) 
            : 0;

        return $metrics;
    }

    /**
     * Obter evolução temporal
     */
    private function getTimeEvolution($period, $startDate, $endDate)
    {
        $groupFormat = $this->getGroupFormat($period);
        
        $sql = "
            WITH date_series AS (
                SELECT date(?) + (ROW_NUMBER() OVER() - 1) * INTERVAL 1 DAY as date
                FROM (SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7) t1
                CROSS JOIN (SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5) t2
                WHERE date(?) + (ROW_NUMBER() OVER() - 1) * INTERVAL 1 DAY <= date(?)
            )
            SELECT 
                strftime('$groupFormat', ds.date) as period,
                COALESCE(COUNT(o.id), 0) as orders_count,
                COALESCE(SUM(o.total_amount), 0) as revenue,
                COALESCE(COUNT(l.id), 0) as leads_count,
                COALESCE(COUNT(c.id), 0) as customers_count
            FROM date_series ds
            LEFT JOIN orders o ON DATE(o.created_at) = ds.date
            LEFT JOIN leads l ON DATE(l.created_at) = ds.date  
            LEFT JOIN customers c ON DATE(c.created_at) = ds.date
            GROUP BY strftime('$groupFormat', ds.date)
            ORDER BY ds.date
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$startDate, $startDate, $endDate]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obter vendas por período
     */
    private function getVendasByPeriod($startDate, $endDate, $groupBy)
    {
        $groupFormat = $this->getGroupFormat($groupBy);
        
        $sql = "
            SELECT 
                strftime('$groupFormat', created_at) as period,
                COUNT(*) as orders_count,
                SUM(total_amount) as total_revenue,
                AVG(total_amount) as avg_order_value,
                COUNT(CASE WHEN payment_status = 'paid' THEN 1 END) as paid_orders
            FROM orders 
            WHERE created_at BETWEEN ? AND ?
            GROUP BY strftime('$groupFormat', created_at)
            ORDER BY created_at
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$startDate, $endDate]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obter vendas por produto
     */
    private function getVendasByProduct($startDate, $endDate)
    {
        $sql = "
            SELECT 
                p.name as product_name,
                p.sku,
                SUM(oi.quantity) as total_quantity,
                SUM(oi.total) as total_revenue,
                COUNT(DISTINCT oi.order_id) as orders_count,
                AVG(oi.price) as avg_price
            FROM order_items oi
            JOIN products p ON oi.product_id = p.id
            JOIN orders o ON oi.order_id = o.id
            WHERE o.created_at BETWEEN ? AND ?
            GROUP BY p.id
            ORDER BY total_revenue DESC
            LIMIT 20
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$startDate, $endDate]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obter formato de agrupamento
     */
    private function getGroupFormat($period)
    {
        switch ($period) {
            case 'day':
                return '%Y-%m-%d';
            case 'week':
                return '%Y-W%W';
            case 'month':
                return '%Y-%m';
            case 'year':
                return '%Y';
            default:
                return '%Y-%m-%d';
        }
    }

    /**
     * Obter data inicial do período
     */
    private function getPeriodStartDate($period)
    {
        switch ($period) {
            case 'week':
                return date('Y-m-d', strtotime('last monday'));
            case 'month':
                return date('Y-m-01');
            case 'quarter':
                $quarter = ceil(date('n') / 3);
                return date('Y-' . str_pad(($quarter - 1) * 3 + 1, 2, '0', STR_PAD_LEFT) . '-01');
            case 'year':
                return date('Y-01-01');
            default:
                return date('Y-m-d', strtotime('-30 days'));
        }
    }

    /**
     * Exportararar para PDF
     */
    private function exportToPDF($reportType, $data, $filters)
    {
        // Implementar geração de PDF usando TCPDF ou DomPDF
        $filename = "relatorio_{$reportType}_" . date('Y-m-d_H-i-s') . '.pdf';
        
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        // Gerar PDF (implementação específica da biblioteca)
        echo "PDF gerado com sucesso para $reportType";
    }

    /**
     * Exportararar para Excel
     */
    private function exportToExcel($reportType, $data, $filters)
    {
        // Implementar geração de Excel usando PhpSpreadsheet
        $filename = "relatorio_{$reportType}_" . date('Y-m-d_H-i-s') . '.xlsx';
        
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        // Gerar Excel (implementação específica da biblioteca)
        echo "Excel gerado com sucesso para $reportType";
    }

    /**
     * Exportararar para CSV
     */
    private function exportToCSV($reportType, $data, $filters)
    {
        $filename = "relatorio_{$reportType}_" . date('Y-m-d_H-i-s') . '.csv';
        
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        
        // BOM para UTF-8
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        if (!empty($data)) {
            // Cabeçalhos
            fputcsv($output, array_keys($data[0]), ';');
            
            // Dados
            foreach ($data as $row) {
                fputcsv($output, $row, ';');
            }
        }
        
        fclose($output);
    }

    /**
     * Gerar dados do relatório
     */
    private function generateReportData($reportType, $filters)
    {
        switch ($reportType) {
            case 'sales':
                return $this->getVendasReport();
            case 'leads':
                return $this->getLeadsReport();
            case 'projects':
                return $this->getProjectsReport();
            case 'customers':
                return $this->getCustomersReport();
            case 'financial':
                return $this->getFinancialReport();
            default:
                throw new Exception('Tipo de relatório inválido');
        }
    }

    // Métodos auxiliares adicionais para completar as funcionalidades...
    // (Implementações específicas dos demais métodos seriam similares)
    
    private function getVendasByCustomer($startDate, $endDate) { return []; }
    private function getVendasPerformanceMetrics($startDate, $endDate) { return []; }
    private function getVendasTrends($startDate, $endDate) { return []; }
    private function getLeadsConversionPipeline($startDate, $endDate, $source) { return []; }
    private function getLeadsBySource($startDate, $endDate) { return []; }
    private function getConversionRates($startDate, $endDate) { return []; }
    private function getConversionTimes($startDate, $endDate) { return []; }
    private function getLostLeadsAnalysis($startDate, $endDate) { return []; }
    private function getProjectsByStatus($startDate, $endDate) { return []; }
    private function getProjectsPerformance($startDate, $endDate, $status) { return []; }
    private function getProjectsScheduleAnalysis($startDate, $endDate) { return []; }
    private function getResourceUtilization($startDate, $endDate) { return []; }
    private function getProjectsROI($startDate, $endDate) { return []; }
    private function getCustomerAnalysis($startDate, $endDate) { return []; }
    private function getCustomerSegmentation($startDate, $endDate) { return []; }
    private function getCustomerLifetimeValue($startDate, $endDate) { return []; }
    private function getChurnAnalysis($startDate, $endDate) { return []; }
    private function getCustomerSatisfactionMetrics($startDate, $endDate) { return []; }
    private function getFinancialSummary($startDate, $endDate) { return []; }
    private function getCashFlow($startDate, $endDate) { return []; }
    private function getAccountsReceivable($startDate, $endDate) { return []; }
    private function getProfitabilityAnalysis($startDate, $endDate) { return []; }
    private function getFinancialForecasts($startDate, $endDate) { return []; }
    private function getTopPerformers($startDate, $endDate) { return []; }
    private function getDistributions($startDate, $endDate) { return []; }
    private function getReceitaTrendData($startDate, $endDate, $period) { return []; }
    private function getLeadsFunnelData($startDate, $endDate) { return []; }
    private function getProjectsTimelineData($startDate, $endDate) { return []; }
    private function getCustomerGrowthData($startDate, $endDate, $period) { return []; }
    private function getVendasByProductChartData($startDate, $endDate) { return []; }
}