<?php
/**
 * DURALUX CRM - Advanced Analytics API v7.0
 * Endpoints REST para sistema de análises
 */

require_once '../classes/AdvancedAnalytics.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

try {
    $analytics = new AdvancedAnalytics();
    
    $method = $_SERVER['REQUEST_METHOD'];
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $pathParts = explode('/', trim($path, '/'));
    
    // Remove path base se necessário
    $endpoint = end($pathParts);
    
    // Parse request body para POST/PUT
    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    $params = array_merge($_GET, $_POST, $input);
    
    switch ($endpoint) {
        case 'dashboard-metrics':
            if ($method !== 'GET') {
                throw new Exception('Método não permitido');
            }
            
            $dateRange = null;
            if (isset($params['start_date']) && isset($params['end_date'])) {
                $dateRange = [
                    'start' => $params['start_date'],
                    'end' => $params['end_date']
                ];
            }
            
            $result = $analytics->getDashboardMetrics($dateRange);
            
            echo json_encode([
                'success' => true,
                'data' => $result,
                'timestamp' => date('Y-m-d H:i:s')
            ]);
            break;
            
        case 'revenue-forecast':
            if ($method !== 'GET') {
                throw new Exception('Método não permitido');
            }
            
            $months = isset($params['months']) ? (int)$params['months'] : 6;
            $months = max(1, min(24, $months)); // Entre 1 e 24 meses
            
            $result = $analytics->generateRevenueForecast($months);
            
            echo json_encode([
                'success' => true,
                'data' => $result,
                'parameters' => ['months' => $months],
                'timestamp' => date('Y-m-d H:i:s')
            ]);
            break;
            
        case 'custom-report':
            if ($method !== 'POST') {
                throw new Exception('Método não permitido');
            }
            
            if (!isset($params['config'])) {
                throw new Exception('Configuração do relatório é obrigatória');
            }
            
            $result = $analytics->generateCustomReport($params['config']);
            
            echo json_encode([
                'success' => true,
                'data' => $result,
                'timestamp' => date('Y-m-d H:i:s')
            ]);
            break;
            
        case 'export-pdf':
            if ($method !== 'POST') {
                throw new Exception('Método não permitido');
            }
            
            if (!isset($params['report_id'])) {
                throw new Exception('ID do relatório é obrigatório');
            }
            
            $template = $params['template'] ?? 'default';
            $result = $analytics->exportReportToPDF($params['report_id'], $template);
            
            echo json_encode([
                'success' => true,
                'data' => $result,
                'timestamp' => date('Y-m-d H:i:s')
            ]);
            break;
            
        case 'kpi-targets':
            if ($method === 'GET') {
                // Listar KPI targets
                $result = $analytics->getKPITargets();
            } elseif ($method === 'POST') {
                // Criar novo KPI target
                $result = $analytics->createKPITarget($params);
            } elseif ($method === 'PUT') {
                // Atualizar KPI target
                $result = $analytics->updateKPITarget($params['id'], $params);
            } else {
                throw new Exception('Método não permitido');
            }
            
            echo json_encode([
                'success' => true,
                'data' => $result,
                'timestamp' => date('Y-m-d H:i:s')
            ]);
            break;
            
        case 'real-time-stats':
            if ($method !== 'GET') {
                throw new Exception('Método não permitido');
            }
            
            // Estatísticas em tempo real (últimas 24h)
            $result = [
                'leads_today' => $analytics->getLeadsToday(),
                'revenue_today' => $analytics->getRevenueToday(),
                'active_users' => $analytics->getActiveUsers(),
                'system_health' => $analytics->getSystemHealth(),
                'latest_activities' => $analytics->getLatestActivities(10)
            ];
            
            echo json_encode([
                'success' => true,
                'data' => $result,
                'timestamp' => date('Y-m-d H:i:s'),
                'cache_ttl' => 60 // Cache por 1 minuto
            ]);
            break;
            
        case 'chart-data':
            if ($method !== 'GET') {
                throw new Exception('Método não permitido');
            }
            
            $chartType = $params['type'] ?? 'leads_timeline';
            $dateRange = isset($params['start_date']) ? [
                'start' => $params['start_date'],
                'end' => $params['end_date'] ?? date('Y-m-d')
            ] : null;
            
            $result = $analytics->getChartData($chartType, $dateRange);
            
            echo json_encode([
                'success' => true,
                'data' => $result,
                'chart_type' => $chartType,
                'timestamp' => date('Y-m-d H:i:s')
            ]);
            break;
            
        case 'performance-metrics':
            if ($method !== 'GET') {
                throw new Exception('Método não permitido');
            }
            
            $result = [
                'database_performance' => $analytics->getDatabasePerformance(),
                'cache_statistics' => $analytics->getCacheStatistics(),
                'api_response_times' => $analytics->getAPIResponseTimes(),
                'error_rates' => $analytics->getErrorRates(),
                'uptime_status' => $analytics->getUptimeStatus()
            ];
            
            echo json_encode([
                'success' => true,
                'data' => $result,
                'timestamp' => date('Y-m-d H:i:s')
            ]);
            break;
            
        case 'data-export':
            if ($method !== 'POST') {
                throw new Exception('Método não permitido');
            }
            
            $exportType = $params['type'] ?? 'csv';
            $dataSource = $params['source'] ?? '';
            $filters = $params['filters'] ?? [];
            
            if (!$dataSource) {
                throw new Exception('Fonte de dados é obrigatória');
            }
            
            $result = $analytics->exportData($dataSource, $exportType, $filters);
            
            // Para exports de arquivo, retorna informações do arquivo
            if (isset($result['file_path'])) {
                header('Content-Type: application/octet-stream');
                header('Content-Disposition: attachment; filename="' . basename($result['filename']) . '"');
                readfile($result['file_path']);
                exit();
            }
            
            echo json_encode([
                'success' => true,
                'data' => $result,
                'timestamp' => date('Y-m-d H:i:s')
            ]);
            break;
            
        case 'health-check':
            // Endpoint de health check para monitoramento
            $health = [
                'status' => 'healthy',
                'version' => '7.0',
                'timestamp' => date('Y-m-d H:i:s'),
                'services' => [
                    'database' => $analytics->checkDatabaseConnection(),
                    'cache' => $analytics->checkCacheConnection(),
                    'analytics_engine' => 'operational'
                ]
            ];
            
            http_response_code(200);
            echo json_encode($health);
            break;
            
        default:
            throw new Exception('Endpoint não encontrado: ' . $endpoint);
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
} catch (Error $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Erro interno do servidor',
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}
?>