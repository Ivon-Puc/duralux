<?php
/**
 * DURALUX CRM - API de Teste para Analytics
 * Versão simplificada para demonstração
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../config/db_config.php';

class SimpleAnalyticsAPI {
    private $db;
    
    public function __construct() {
        try {
            $this->db = getDatabaseConnection();
        } catch (Exception $e) {
            $this->sendError("Erro de conexão: " . $e->getMessage(), 500);
        }
    }
    
    public function handleRequest() {
        $path = $_GET['endpoint'] ?? '';
        
        try {
            switch ($path) {
                case 'dashboard-metrics':
                    $this->getPainel de ControleMetrics();
                    break;
                    
                case 'test-connection':
                    $this->testConnection();
                    break;
                    
                case 'leads-stats':
                    $this->getLeadsStats();
                    break;
                    
                case 'sales-stats':
                    $this->getVendasStats();
                    break;
                    
                case 'projects-stats':
                    $this->getProjectsStats();
                    break;
                    
                default:
                    $this->sendError("Endpoint não encontrado: $path", 404);
            }
        } catch (Exception $e) {
            error_log("Erro na API: " . $e->getMessage());
            $this->sendError("Erro interno do servidor", 500);
        }
    }
    
    private function getPainel de ControleMetrics() {
        $metrics = [
            'leads' => $this->getLeadsData(),
            'sales' => $this->getVendasData(),
            'projects' => $this->getProjectsData(),
            'customers' => $this->getCustomersData(),
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        $this->sendSuccess($metrics);
    }
    
    private function testConnection() {
        try {
            $result = testDatabaseConnection();
            $this->sendSuccess($result);
        } catch (Exception $e) {
            $this->sendError("Erro no teste: " . $e->getMessage(), 500);
        }
    }
    
    private function getLeadsData() {
        try {
            // Total de leads
            $stmt = $this->db->query("SELECT COUNT(*) as total FROM leads");
            $total_leads = $stmt->fetch()['total'] ?? 0;
            
            // Leads por status
            $stmt = $this->db->query("
                SELECT status, COUNT(*) as count 
                FROM leads 
                GROUP BY status
            ");
            $leads_by_status = $stmt->fetchAll();
            
            // Leads hoje
            $stmt = $this->db->query("
                SELECT COUNT(*) as count 
                FROM leads 
                WHERE DATE(created_at) = DATE('now')
            ");
            $leads_today = $stmt->fetch()['count'] ?? 0;
            
            // Conversão aproximada (leads convertidos / total)
            $converted_leads = 0;
            foreach ($leads_by_status as $status) {
                if ($status['status'] === 'convertido') {
                    $converted_leads = $status['count'];
                    break;
                }
            }
            
            $conversion_rate = $total_leads > 0 ? ($converted_leads / $total_leads) * 100 : 0;
            
            // Dados diários fictícios para gráfico
            $daily_leads = [];
            for ($i = 6; $i >= 0; $i--) {
                $date = date('Y-m-d', strtotime("-$i days"));
                $count = rand(2, 15); // Dados fictícios
                $daily_leads[] = [
                    'date' => $date,
                    'count' => $count
                ];
            }
            
            return [
                'total_leads' => (int)$total_leads,
                'qualified_leads' => (int)($total_leads * 0.6), // Estimativa
                'converted_leads' => (int)$converted_leads,
                'conversion_rate' => round($conversion_rate, 2),
                'leads_today' => (int)$leads_today,
                'daily_leads' => $daily_leads,
                'leads_by_status' => $leads_by_status
            ];
            
        } catch (Exception $e) {
            error_log("Erro ao buscar dados de leads: " . $e->getMessage());
            return [
                'total_leads' => 0,
                'qualified_leads' => 0,
                'converted_leads' => 0,
                'conversion_rate' => 0,
                'leads_today' => 0,
                'daily_leads' => [],
                'leads_by_status' => []
            ];
        }
    }
    
    private function getVendasData() {
        try {
            // Receita total
            $stmt = $this->db->query("SELECT SUM(valor) as total FROM vendas WHERE status = 'fechada'");
            $total_revenue = $stmt->fetch()['total'] ?? 0;
            
            // Número de vendas
            $stmt = $this->db->query("SELECT COUNT(*) as count FROM vendas WHERE status = 'fechada'");
            $total_sales = $stmt->fetch()['count'] ?? 0;
            
            // Ticket médio
            $avg_deal_size = $total_sales > 0 ? $total_revenue / $total_sales : 0;
            
            // Receita por mês (dados fictícios)
            $revenue_by_month = [
                ['month' => '2024-08', 'revenue' => 45000],
                ['month' => '2024-09', 'revenue' => 52000],
                ['month' => '2024-10', 'revenue' => 48000],
                ['month' => '2024-11', 'revenue' => 55000]
            ];
            
            // Previsão (fictícia)
            $revenue_forecast = [
                'forecast' => [
                    ['month' => '2024-12', 'forecasted_revenue' => 58000],
                    ['month' => '2025-01', 'forecasted_revenue' => 61000]
                ]
            ];
            
            return [
                'total_revenue' => (float)$total_revenue,
                'total_sales' => (int)$total_sales,
                'avg_deal_size' => round($avg_deal_size, 2),
                'revenue_by_month' => $revenue_by_month,
                'revenue_forecast' => $revenue_forecast
            ];
            
        } catch (Exception $e) {
            error_log("Erro ao buscar dados de vendas: " . $e->getMessage());
            return [
                'total_revenue' => 0,
                'total_sales' => 0,
                'avg_deal_size' => 0,
                'revenue_by_month' => [],
                'revenue_forecast' => ['forecast' => []]
            ];
        }
    }
    
    private function getProjectsData() {
        try {
            $stmt = $this->db->query("
                SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'em_andamento' THEN 1 ELSE 0 END) as active,
                    SUM(CASE WHEN status = 'concluido' THEN 1 ELSE 0 END) as completed,
                    SUM(CASE WHEN status = 'pausado' THEN 1 ELSE 0 END) as on_hold,
                    SUM(CASE WHEN prazo_entrega < DATE('now') AND status != 'concluido' THEN 1 ELSE 0 END) as overdue
                FROM projects
            ");
            
            $stats = $stmt->fetch();
            
            return [
                'total_projects' => (int)($stats['total'] ?? 0),
                'active_projects' => (int)($stats['active'] ?? 0),
                'completed_projects' => (int)($stats['completed'] ?? 0),
                'on_hold_projects' => (int)($stats['on_hold'] ?? 0),
                'overdue_projects' => (int)($stats['overdue'] ?? 0)
            ];
            
        } catch (Exception $e) {
            error_log("Erro ao buscar dados de projetos: " . $e->getMessage());
            return [
                'total_projects' => 0,
                'active_projects' => 0,
                'completed_projects' => 0,
                'on_hold_projects' => 0,
                'overdue_projects' => 0
            ];
        }
    }
    
    private function getCustomersData() {
        try {
            $stmt = $this->db->query("SELECT COUNT(*) as count FROM customers WHERE active = 1");
            $active_customers = $stmt->fetch()['count'] ?? 0;
            
            return [
                'active_customers' => (int)$active_customers,
                'total_customers' => (int)$active_customers // Simplificado
            ];
            
        } catch (Exception $e) {
            error_log("Erro ao buscar dados de clientes: " . $e->getMessage());
            return [
                'active_customers' => 0,
                'total_customers' => 0
            ];
        }
    }
    
    private function getLeadsStats() {
        $data = $this->getLeadsData();
        $this->sendSuccess(['leads' => $data]);
    }
    
    private function getVendasStats() {
        $data = $this->getVendasData();
        $this->sendSuccess(['sales' => $data]);
    }
    
    private function getProjectsStats() {
        $data = $this->getProjectsData();
        $this->sendSuccess(['projects' => $data]);
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

// Processa requisição
$api = new SimpleAnalyticsAPI();
$api->handleRequest();
?>