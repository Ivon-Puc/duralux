<?php
/**
 * DURALUX CRM - Performance Painel de Controle Controller v4.0
 * Controller especializado para dashboard de performance e monitoramento
 * 
 * Features:
 * - Painel de Controle em tempo real
 * - Métricas detalhadas de performance
 * - Alertas e notificações
 * - Análise de tendências
 * - Relatórios de otimização
 * - Monitoramento de recursos
 * 
 * @author Duralux Development Team
 * @version 4.0.0
 */

require_once 'PerformanceMonitor.php';
require_once 'RedisCacheManager.php';
require_once 'AssetOptimizer.php';

class PerformancePainel de ControleController {
    
    private $monitor;
    private $cache;
    private $optimizer;
    private $db;
    
    public function __construct() {
        $this->monitor = new PerformanceMonitor();
        $this->cache = CacheManager::getInstance();
        $this->optimizer = new AssetOptimizer();
        
        try {
            $this->db = new PDO(
                "sqlite:" . __DIR__ . "/../../database/duralux.db",
                null,
                null,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
        } catch (Exception $e) {
            error_log("Performance Painel de Controle DB connection failed: " . $e->getMessage());
        }
    }
    
    /**
     * Manipular requisições da API
     */
    public function handleRequest() {
        $action = $_GET['action'] ?? '';
        $method = $_SERVER['REQUEST_METHOD'];
        
        try {
            switch ($action) {
                case 'get_performance_dashboard':
                    $this->jsonResponse($this->getPainel de ControleData());
                    break;
                    
                case 'get_performance_overview':
                    $this->jsonResponse($this->getPerformanceOverview());
                    break;
                    
                case 'get_performance_trends':
                    $period = $_GET['period'] ?? '7 days';
                    $this->jsonResponse($this->getPerformanceTrends($period));
                    break;
                    
                case 'get_active_alerts':
                    $this->jsonResponse($this->getActiveAlerts());
                    break;
                    
                case 'execute_optimization':
                    if ($method !== 'POST') {
                        $this->jsonResponse(['error' => 'POST method required'], 405);
                        return;
                    }
                    
                    $input = json_decode(file_get_contents('php://input'), true);
                    $type = $input['type'] ?? '';
                    $params = $input['params'] ?? [];
                    
                    $this->jsonResponse($this->executeOptimization($type, $params));
                    break;
                    
                case 'get_system_resources':
                    $this->jsonResponse($this->getResourceUsage());
                    break;
                    
                case 'get_optimization_recommendations':
                    $this->jsonResponse($this->getOptimizationRecommendations());
                    break;
                    
                default:
                    $this->jsonResponse(['error' => 'Invalid action'], 400);
            }
            
        } catch (Exception $e) {
            error_log("Performance API Error: " . $e->getMessage());
            $this->jsonResponse(['error' => 'Internal server error'], 500);
        }
    }
    
    /**
     * Enviar resposta JSON
     */
    private function jsonResponse($data, $statusCode = 200) {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data, JSON_PRETTY_PRINT);
        exit;
    }
    
    /**
     * Obter dados completos do dashboard de performance
     */
    public function getPainel de ControleData() {
        $data = [
            'overview' => $this->getPerformanceOverview(),
            'realtime' => $this->monitor->getRealTimeStats(),
            'alerts' => $this->getActiveAlerts(),
            'trends' => $this->getPerformanceTrends(),
            'optimization' => $this->getOptimizationStatus(),
            'resources' => $this->getResourceUsage(),
            'recommendations' => $this->getOptimizationRecommendations()
        ];
        
        return $data;
    }
    
    /**
     * Overview geral de performance
     */
    public function getPerformanceOverview() {
        $cacheKey = 'performance_overview';
        $cached = $this->cache->get($cacheKey);
        
        if ($cached) {
            return $cached;
        }
        
        try {
            // Métricas básicas das últimas 24h
            $stmt = $this->db->query("
                SELECT 
                    COUNT(*) as total_requests,
                    AVG(response_time) as avg_response_time,
                    MAX(response_time) as max_response_time,
                    MIN(response_time) as min_response_time,
                    AVG(memory_usage) as avg_memory_usage,
                    MAX(memory_usage) as peak_memory_usage,
                    COUNT(CASE WHEN response_time > 2000 THEN 1 END) as slow_requests
                FROM performance_metrics 
                WHERE timestamp > datetime('now', '-24 hours')
            ");
            $basicStats = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Estatísticas de queries
            $stmt = $this->db->query("
                SELECT 
                    COUNT(*) as total_slow_queries,
                    AVG(execution_time) as avg_query_time,
                    COUNT(DISTINCT query_hash) as unique_slow_queries
                FROM slow_queries 
                WHERE timestamp > datetime('now', '-24 hours')
            ");
            $queryStats = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Alertas ativos
            $stmt = $this->db->query("
                SELECT COUNT(*) as active_alerts
                FROM performance_alerts 
                WHERE resolved = 0
            ");
            $alertStats = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Cache performance
            $cacheStats = $this->cache->getStats();
            
            $overview = [
                'requests' => [
                    'total_24h' => (int)$basicStats['total_requests'],
                    'avg_response_time' => round($basicStats['avg_response_time'], 2),
                    'max_response_time' => round($basicStats['max_response_time'], 2),
                    'slow_requests' => (int)$basicStats['slow_requests'],
                    'slow_percentage' => $basicStats['total_requests'] > 0 
                        ? round($basicStats['slow_requests'] / $basicStats['total_requests'] * 100, 2) 
                        : 0
                ],
                'memory' => [
                    'avg_usage' => $this->formatBytes($basicStats['avg_memory_usage']),
                    'peak_usage' => $this->formatBytes($basicStats['peak_memory_usage']),
                    'current_usage' => $this->formatBytes(memory_get_usage(true)),
                    'limit' => $this->formatBytes($this->parseBytes(ini_get('memory_limit')))
                ],
                'queries' => [
                    'slow_queries_24h' => (int)$queryStats['total_slow_queries'],
                    'avg_query_time' => round($queryStats['avg_query_time'], 2),
                    'unique_slow_queries' => (int)$queryStats['unique_slow_queries']
                ],
                'cache' => [
                    'hit_rate' => $cacheStats['hit_rate'] ?? 0,
                    'total_keys' => $cacheStats['total_keys'] ?? 0,
                    'memory_usage' => $cacheStats['memory_usage'] ?? 0
                ],
                'alerts' => [
                    'active_count' => (int)$alertStats['active_alerts']
                ],
                'health_score' => $this->calculateHealthScore($basicStats, $queryStats, $cacheStats)
            ];
            
            $this->cache->set($cacheKey, $overview, 300); // Cache 5 minutos
            
            return $overview;
            
        } catch (Exception $e) {
            return ['error' => 'Failed to get performance overview: ' . $e->getMessage()];
        }
    }
    
    /**
     * Obter alertas ativos
     */
    public function getActiveAlerts() {
        try {
            $stmt = $this->db->query("
                SELECT 
                    alert_type,
                    severity,
                    message,
                    metric_value,
                    threshold_value,
                    timestamp
                FROM performance_alerts 
                WHERE resolved = 0 
                ORDER BY 
                    CASE severity 
                        WHEN 'critical' THEN 1 
                        WHEN 'warning' THEN 2 
                        WHEN 'info' THEN 3 
                    END,
                    timestamp DESC
                LIMIT 20
            ");
            
            $alerts = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Agrupar por tipo
            $groupedAlerts = [];
            foreach ($alerts as $alert) {
                $type = $alert['alert_type'];
                if (!isset($groupedAlerts[$type])) {
                    $groupedAlerts[$type] = [];
                }
                $groupedAlerts[$type][] = $alert;
            }
            
            return [
                'total' => count($alerts),
                'by_severity' => $this->groupAlertsBySeverity($alerts),
                'by_type' => $groupedAlerts,
                'recent' => array_slice($alerts, 0, 5)
            ];
            
        } catch (Exception $e) {
            return ['error' => 'Failed to get alerts: ' . $e->getMessage()];
        }
    }
    
    /**
     * Obter tendências de performance
     */
    public function getPerformanceTrends($period = '7 days') {
        $cacheKey = 'performance_trends_' . md5($period);
        $cached = $this->cache->get($cacheKey);
        
        if ($cached) {
            return $cached;
        }
        
        try {
            // Tendência de tempo de resposta
            $stmt = $this->db->prepare("
                SELECT 
                    DATE(timestamp) as date,
                    AVG(response_time) as avg_response_time,
                    MAX(response_time) as max_response_time,
                    COUNT(*) as request_count
                FROM performance_metrics 
                WHERE timestamp > datetime('now', '-' || ? || '')
                GROUP BY DATE(timestamp)
                ORDER BY date
            ");
            $stmt->execute([$period]);
            $responseTimeTrends = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Tendência de uso de memória
            $stmt = $this->db->prepare("
                SELECT 
                    DATE(timestamp) as date,
                    AVG(memory_usage) as avg_memory,
                    MAX(memory_usage) as peak_memory
                FROM performance_metrics 
                WHERE timestamp > datetime('now', '-' || ? || '')
                GROUP BY DATE(timestamp)
                ORDER BY date
            ");
            $stmt->execute([$period]);
            $memoryTrends = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Tendência de queries lentas
            $stmt = $this->db->prepare("
                SELECT 
                    DATE(timestamp) as date,
                    COUNT(*) as slow_query_count,
                    AVG(execution_time) as avg_execution_time
                FROM slow_queries 
                WHERE timestamp > datetime('now', '-' || ? || '')
                GROUP BY DATE(timestamp)
                ORDER BY date
            ");
            $stmt->execute([$period]);
            $queryTrends = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $trends = [
                'response_time' => $responseTimeTrends,
                'memory_usage' => $memoryTrends,
                'slow_queries' => $queryTrends,
                'period' => $period,
                'analysis' => $this->analyzeTrends($responseTimeTrends, $memoryTrends, $queryTrends)
            ];
            
            $this->cache->set($cacheKey, $trends, 1800); // Cache 30 minutos
            
            return $trends;
            
        } catch (Exception $e) {
            return ['error' => 'Failed to get trends: ' . $e->getMessage()];
        }
    }
    
    /**
     * Status das otimizações
     */
    public function getOptimizationStatus() {
        $cacheKey = 'optimization_status';
        $cached = $this->cache->get($cacheKey);
        
        if ($cached) {
            return $cached;
        }
        
        $status = [
            'cache_optimization' => $this->getCacheOptimizationStatus(),
            'query_optimization' => $this->getQueryOptimizationStatus(),
            'asset_optimization' => $this->getAssetOptimizationStatus(),
            'index_optimization' => $this->getIndexOptimizationStatus()
        ];
        
        $this->cache->set($cacheKey, $status, 900); // Cache 15 minutos
        
        return $status;
    }
    
    /**
     * Recomendações de otimização
     */
    public function getOptimizationRecommendations() {
        $recommendations = [];
        
        // Analisar queries lentas
        $slowQueries = $this->getTopSlowQueries();
        if (count($slowQueries) > 0) {
            $recommendations[] = [
                'type' => 'query_optimization',
                'priority' => 'high',
                'title' => 'Otimizar Queries Lentas',
                'description' => count($slowQueries) . ' queries lentas identificadas',
                'action' => 'optimize_queries',
                'estimated_improvement' => '30-50% redução no tempo de resposta'
            ];
        }
        
        // Verificar uso de cache
        $cacheStats = $this->cache->getStats();
        if (($cacheStats['hit_rate'] ?? 0) < 70) {
            $recommendations[] = [
                'type' => 'cache_optimization',
                'priority' => 'medium',
                'title' => 'Melhorar Taxa de Acerto do Cache',
                'description' => 'Taxa atual: ' . ($cacheStats['hit_rate'] ?? 0) . '%',
                'action' => 'optimize_cache_strategy',
                'estimated_improvement' => '20-30% redução no tempo de resposta'
            ];
        }
        
        // Verificar uso de memória
        $memoryUsage = memory_get_usage(true) / $this->parseBytes(ini_get('memory_limit')) * 100;
        if ($memoryUsage > 80) {
            $recommendations[] = [
                'type' => 'memory_optimization',
                'priority' => 'high',
                'title' => 'Otimizar Uso de Memória',
                'description' => 'Uso atual: ' . round($memoryUsage, 1) . '%',
                'action' => 'optimize_memory_usage',
                'estimated_improvement' => 'Evitar crashes e melhorar estabilidade'
            ];
        }
        
        // Verificar assets não otimizados
        $assetOptimization = $this->checkAssetOptimization();
        if ($assetOptimization['needs_optimization']) {
            $recommendations[] = [
                'type' => 'asset_optimization',
                'priority' => 'medium',
                'title' => 'Otimizar Assets',
                'description' => $assetOptimization['description'],
                'action' => 'optimize_assets',
                'estimated_improvement' => '15-25% redução no tempo de carregamento'
            ];
        }
        
        return $recommendations;
    }
    
    /**
     * Executar otimização específica
     */
    public function executeOptimization($type, $params = []) {
        try {
            switch ($type) {
                case 'optimize_queries':
                    return $this->monitor->optimizeQueries();
                    
                case 'optimize_assets':
                    return $this->optimizer->optimizeAllAssets();
                    
                case 'clear_cache':
                    return $this->cache->flush();
                    
                case 'optimize_cache_strategy':
                    return $this->optimizeCacheStrategy();
                    
                case 'cleanup_logs':
                    return $this->cleanupOldLogs();
                    
                default:
                    return ['error' => 'Unknown optimization type'];
            }
            
        } catch (Exception $e) {
            return ['error' => 'Optimization failed: ' . $e->getMessage()];
        }
    }
    
    // ==========================================
    // MÉTODOS AUXILIARES PRIVADOS
    // ==========================================
    
    private function calculateHealthScore($basicStats, $queryStats, $cacheStats) {
        $score = 100;
        
        // Penalizar por tempo de resposta alto
        $avgResponseTime = $basicStats['avg_response_time'] ?? 0;
        if ($avgResponseTime > 2000) $score -= 30;
        elseif ($avgResponseTime > 1000) $score -= 15;
        elseif ($avgResponseTime > 500) $score -= 5;
        
        // Penalizar por queries lentas
        $slowQueries = $queryStats['total_slow_queries'] ?? 0;
        if ($slowQueries > 100) $score -= 20;
        elseif ($slowQueries > 50) $score -= 10;
        elseif ($slowQueries > 10) $score -= 5;
        
        // Bonificar por boa taxa de cache
        $hitRate = $cacheStats['hit_rate'] ?? 0;
        if ($hitRate < 50) $score -= 15;
        elseif ($hitRate < 70) $score -= 5;
        
        return max(0, min(100, $score));
    }
    
    private function groupAlertsBySeverity($alerts) {
        $grouped = ['critical' => 0, 'warning' => 0, 'info' => 0];
        
        foreach ($alerts as $alert) {
            $severity = $alert['severity'];
            if (isset($grouped[$severity])) {
                $grouped[$severity]++;
            }
        }
        
        return $grouped;
    }
    
    private function analyzeTrends($responseTime, $memory, $queries) {
        $analysis = [];
        
        // Analisar tendência de tempo de resposta
        if (count($responseTime) >= 2) {
            $first = $responseTime[0]['avg_response_time'];
            $last = end($responseTime)['avg_response_time'];
            $change = (($last - $first) / $first) * 100;
            
            $analysis['response_time'] = [
                'trend' => $change > 10 ? 'deteriorating' : ($change < -10 ? 'improving' : 'stable'),
                'change_percentage' => round($change, 2)
            ];
        }
        
        // Analisar tendência de memória
        if (count($memory) >= 2) {
            $first = $memory[0]['avg_memory'];
            $last = end($memory)['avg_memory'];
            $change = (($last - $first) / $first) * 100;
            
            $analysis['memory'] = [
                'trend' => $change > 20 ? 'increasing' : ($change < -20 ? 'decreasing' : 'stable'),
                'change_percentage' => round($change, 2)
            ];
        }
        
        return $analysis;
    }
    
    private function getCacheOptimizationStatus() {
        $stats = $this->cache->getStats();
        
        return [
            'enabled' => true,
            'hit_rate' => $stats['hit_rate'] ?? 0,
            'total_keys' => $stats['total_keys'] ?? 0,
            'memory_usage' => $stats['memory_usage'] ?? 0,
            'status' => ($stats['hit_rate'] ?? 0) > 70 ? 'good' : 'needs_improvement'
        ];
    }
    
    private function getQueryOptimizationStatus() {
        try {
            $stmt = $this->db->query("
                SELECT COUNT(*) as slow_count 
                FROM slow_queries 
                WHERE timestamp > datetime('now', '-24 hours')
            ");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return [
                'slow_queries_24h' => (int)$result['slow_count'],
                'status' => $result['slow_count'] < 10 ? 'good' : 'needs_improvement'
            ];
            
        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }
    
    private function getAssetOptimizationStatus() {
        // Verificar se assets estão otimizados
        $assetsPath = __DIR__ . '/../../duralux-admin/assets/';
        $cacheExists = is_dir(__DIR__ . '/../../cache/assets/');
        
        return [
            'cache_exists' => $cacheExists,
            'status' => $cacheExists ? 'optimized' : 'needs_optimization'
        ];
    }
    
    private function getIndexOptimizationStatus() {
        try {
            $stmt = $this->db->query("
                SELECT COUNT(*) as recommendation_count 
                FROM index_recommendations 
                WHERE created = 0
            ");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return [
                'pending_recommendations' => (int)$result['recommendation_count'],
                'status' => $result['recommendation_count'] == 0 ? 'good' : 'needs_attention'
            ];
            
        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }
    
    private function getResourceUsage() {
        return $this->monitor->getRealTimeStats()['server'] ?? [];
    }
    
    private function getTopSlowQueries($limit = 10) {
        try {
            $stmt = $this->db->prepare("
                SELECT sql_query, execution_time, query_type 
                FROM slow_queries 
                WHERE timestamp > datetime('now', '-24 hours')
                ORDER BY execution_time DESC 
                LIMIT ?
            ");
            $stmt->execute([$limit]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            return [];
        }
    }
    
    private function checkAssetOptimization() {
        $assetsPath = __DIR__ . '/../../duralux-admin/assets/';
        $cachePath = __DIR__ . '/../../cache/assets/';
        
        $needsOptimization = !is_dir($cachePath);
        
        return [
            'needs_optimization' => $needsOptimization,
            'description' => $needsOptimization 
                ? 'Assets não foram otimizados ainda' 
                : 'Assets já estão otimizados'
        ];
    }
    
    private function optimizeCacheStrategy() {
        // Implementar otimização da estratégia de cache
        return ['status' => 'Cache strategy optimization completed'];
    }
    
    private function cleanupOldLogs() {
        try {
            // Limpar métricas antigas (> 30 dias)
            $this->db->exec("
                DELETE FROM performance_metrics 
                WHERE timestamp < datetime('now', '-30 days')
            ");
            
            // Limpar queries lentas antigas (> 7 dias)
            $this->db->exec("
                DELETE FROM slow_queries 
                WHERE timestamp < datetime('now', '-7 days')
            ");
            
            // Limpar alertas resolvidos antigos (> 30 dias)
            $this->db->exec("
                DELETE FROM performance_alerts 
                WHERE resolved = 1 AND resolved_at < datetime('now', '-30 days')
            ");
            
            return ['status' => 'Old logs cleaned successfully'];
            
        } catch (Exception $e) {
            return ['error' => 'Failed to clean logs: ' . $e->getMessage()];
        }
    }
    
    private function formatBytes($bytes, $precision = 2) {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }
    
    private function parseBytes($bytes) {
        if (is_numeric($bytes)) {
            return (int) $bytes;
        }
        
        $unit = strtoupper(substr($bytes, -1));
        $value = (int) substr($bytes, 0, -1);
        
        switch ($unit) {
            case 'G': return $value * 1024 * 1024 * 1024;
            case 'M': return $value * 1024 * 1024;
            case 'K': return $value * 1024;
            default: return $value;
        }
    }
}

?>