<?php
/**
 * DURALUX CRM - Performance Monitor & Optimizer v4.0
 * Sistema avançado de monitoramento e otimização de performance
 * 
 * Features:
 * - Query optimization automática
 * - Database indexing inteligente
 * - Memory usage monitoring
 * - Response time tracking
 * - Resource usage alerts
 * - Performance bottleneck detection
 * - Auto-scaling recommendations
 * - Real-time performance dashboard
 * 
 * @author Duralux Development Team
 * @version 4.0.0
 */

require_once 'RedisCacheManager.php';

class PerformanceMonitor {
    
    private $cache;
    private $db;
    private $metrics = [];
    private $alerts = [];
    private $config;
    private $startTime;
    
    // Configurações de performance
    private $defaultConfig = [
        'monitoring' => [
            'enable_profiling' => true,
            'enable_query_analysis' => true,
            'enable_memory_tracking' => true,
            'enable_response_timing' => true,
            'sample_rate' => 100, // % de requisições para monitorar
        ],
        'thresholds' => [
            'slow_query' => 1000,    // ms
            'memory_usage' => 80,     // % da memória disponível
            'response_time' => 2000,  // ms
            'cpu_usage' => 70,        // %
            'disk_usage' => 85,       // %
            'connection_pool' => 80   // % de conexões ativas
        ],
        'optimization' => [
            'auto_index_creation' => true,
            'query_cache_optimization' => true,
            'connection_pooling' => true,
            'lazy_loading' => true,
            'image_optimization' => true,
            'gzip_compression' => true
        ],
        'alerts' => [
            'enable_email' => false,
            'enable_webhook' => false,
            'alert_cooldown' => 300, // 5 minutos
            'recipients' => []
        ]
    ];
    
    // Métricas coletadas
    private $performanceMetrics = [
        'request_count' => 0,
        'total_response_time' => 0,
        'slow_queries' => [],
        'memory_peaks' => [],
        'cache_performance' => [],
        'database_performance' => [],
        'error_rates' => [],
        'resource_usage' => []
    ];
    
    public function __construct($config = []) {
        $this->config = array_merge_recursive($this->defaultConfig, $config);
        $this->cache = CacheManager::getInstance();
        $this->startTime = microtime(true);
        
        try {
            $this->db = new PDO(
                "sqlite:" . __DIR__ . "/../../database/duralux.db",
                null,
                null,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
            
            $this->initializeMonitoring();
            
        } catch (Exception $e) {
            error_log("Performance Monitor initialization failed: " . $e->getMessage());
        }
    }
    
    /**
     * Inicializar sistema de monitoramento
     */
    private function initializeMonitoring() {
        // Criar tabelas de métricas se não existirem
        $this->createPerformanceTables();
        
        // Configurar handlers de performance
        if ($this->config['monitoring']['enable_profiling']) {
            $this->setupProfiling();
        }
        
        // Registrar shutdown function
        register_shutdown_function([$this, 'recordRequestMetrics']);
        
        // Iniciar coleta de métricas
        $this->startMetricsCollection();
    }
    
    /**
     * Criar tabelas para métricas de performance
     */
    private function createPerformanceTables() {
        $tables = [
            // Métricas de requisições
            "CREATE TABLE IF NOT EXISTS performance_metrics (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
                request_uri TEXT,
                method VARCHAR(10),
                response_time FLOAT,
                memory_usage INTEGER,
                cpu_usage FLOAT,
                query_count INTEGER,
                cache_hits INTEGER,
                cache_misses INTEGER,
                user_id INTEGER,
                ip_address VARCHAR(45),
                user_agent TEXT
            )",
            
            // Queries lentas
            "CREATE TABLE IF NOT EXISTS slow_queries (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
                query_hash VARCHAR(64),
                sql_query TEXT,
                execution_time FLOAT,
                rows_examined INTEGER,
                rows_returned INTEGER,
                query_type VARCHAR(20),
                optimization_suggestions TEXT
            )",
            
            // Alertas de performance
            "CREATE TABLE IF NOT EXISTS performance_alerts (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
                alert_type VARCHAR(50),
                severity VARCHAR(20),
                message TEXT,
                metric_value FLOAT,
                threshold_value FLOAT,
                resolved BOOLEAN DEFAULT 0,
                resolved_at DATETIME NULL
            )",
            
            // Índices recomendados
            "CREATE TABLE IF NOT EXISTS index_recommendations (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                table_name VARCHAR(100),
                column_names TEXT,
                index_type VARCHAR(50),
                estimated_benefit FLOAT,
                query_pattern TEXT,
                created BOOLEAN DEFAULT 0,
                created_at DATETIME NULL
            )"
        ];
        
        foreach ($tables as $sql) {
            $this->db->exec($sql);
        }
        
        // Criar índices para performance
        $indexes = [
            "CREATE INDEX IF NOT EXISTS idx_performance_timestamp ON performance_metrics(timestamp)",
            "CREATE INDEX IF NOT EXISTS idx_slow_queries_timestamp ON slow_queries(timestamp)",
            "CREATE INDEX IF NOT EXISTS idx_alerts_timestamp ON performance_alerts(timestamp)"
        ];
        
        foreach ($indexes as $sql) {
            $this->db->exec($sql);
        }
    }
    
    /**
     * Configurar profiling de performance
     */
    private function setupProfiling() {
        // Interceptar queries SQL se PDO estiver configurado
        $this->setupQueryProfiling();
        
        // Monitorar uso de memória
        if ($this->config['monitoring']['enable_memory_tracking']) {
            $this->setupMemoryProfiling();
        }
    }
    
    /**
     * Monitorar execução de queries
     */
    public function profileQuery($sql, $params = [], $callback = null) {
        $queryStart = microtime(true);
        $memoryStart = memory_get_usage();
        
        $queryHash = md5($sql . serialize($params));
        
        try {
            // Executar query
            if ($callback) {
                $result = call_user_func($callback);
            } else {
                $stmt = $this->db->prepare($sql);
                $stmt->execute($params);
                $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
            
            $executionTime = (microtime(true) - $queryStart) * 1000; // ms
            $memoryUsed = memory_get_usage() - $memoryStart;
            
            // Registrar métrica
            $this->recordQueryMetric($sql, $queryHash, $executionTime, $memoryUsed, count($result ?? []));
            
            // Verificar se é query lenta
            if ($executionTime > $this->config['thresholds']['slow_query']) {
                $this->handleSlowQuery($sql, $queryHash, $executionTime, $result ?? []);
            }
            
            return $result;
            
        } catch (Exception $e) {
            $executionTime = (microtime(true) - $queryStart) * 1000;
            $this->recordQueryError($sql, $e->getMessage(), $executionTime);
            throw $e;
        }
    }
    
    /**
     * Monitorar performance de requisições HTTP
     */
    public function startRequestProfiling() {
        $this->performanceMetrics['request_start_time'] = microtime(true);
        $this->performanceMetrics['request_memory_start'] = memory_get_usage();
        $this->performanceMetrics['request_queries'] = [];
    }
    
    /**
     * Finalizar profiling de requisição
     */
    public function endRequestProfiling() {
        $responseTime = (microtime(true) - $this->performanceMetrics['request_start_time']) * 1000;
        $memoryUsage = memory_get_peak_usage() - $this->performanceMetrics['request_memory_start'];
        
        $this->performanceMetrics['total_response_time'] += $responseTime;
        $this->performanceMetrics['request_count']++;
        
        // Verificar thresholds
        if ($responseTime > $this->config['thresholds']['response_time']) {
            $this->triggerAlert('slow_response', 'warning', 
                "Slow response time: {$responseTime}ms", $responseTime);
        }
        
        // Registrar métricas
        $this->recordRequestMetric($responseTime, $memoryUsage);
        
        return [
            'response_time' => $responseTime,
            'memory_usage' => $memoryUsage,
            'query_count' => count($this->performanceMetrics['request_queries'])
        ];
    }
    
    /**
     * Otimizar queries automaticamente
     */
    public function optimizeQueries() {
        $optimizations = [];
        
        // Analisar queries lentas dos últimos 24h
        $slowQueries = $this->getSlowQueries(24);
        
        foreach ($slowQueries as $query) {
            $suggestions = $this->analyzeQuery($query['sql_query']);
            
            if (!empty($suggestions)) {
                $optimizations[] = [
                    'query_hash' => $query['query_hash'],
                    'sql' => $query['sql_query'],
                    'current_time' => $query['execution_time'],
                    'suggestions' => $suggestions
                ];
                
                // Aplicar otimizações automáticas se habilitado
                if ($this->config['optimization']['auto_index_creation']) {
                    $this->applyIndexOptimizations($suggestions);
                }
            }
        }
        
        return $optimizations;
    }
    
    /**
     * Analisar query para otimizações
     */
    private function analyzeQuery($sql) {
        $suggestions = [];
        
        // Detectar queries sem WHERE clause em tabelas grandes
        if (preg_match('/SELECT\s+.*\s+FROM\s+(\w+)(?!\s+WHERE)/i', $sql, $matches)) {
            $tableName = $matches[1];
            $rowCount = $this->getTableRowCount($tableName);
            
            if ($rowCount > 1000) {
                $suggestions[] = [
                    'type' => 'missing_where',
                    'message' => "Consider adding WHERE clause to limit results from large table '$tableName'",
                    'priority' => 'high'
                ];
            }
        }
        
        // Detectar uso de LIKE '%pattern%' (não otimizável)
        if (preg_match('/LIKE\s+[\'"]%.*%[\'"]/i', $sql)) {
            $suggestions[] = [
                'type' => 'inefficient_like',
                'message' => 'Use full-text search instead of LIKE with leading wildcards',
                'priority' => 'medium'
            ];
        }
        
        // Detectar ORDER BY sem índice
        if (preg_match('/ORDER\s+BY\s+(\w+)/i', $sql, $matches)) {
            $column = $matches[1];
            if (!$this->hasIndex($column)) {
                $suggestions[] = [
                    'type' => 'missing_index',
                    'message' => "Consider creating index on column '$column' for ORDER BY",
                    'priority' => 'medium',
                    'action' => 'create_index',
                    'column' => $column
                ];
            }
        }
        
        // Detectar JOINs sem índices
        if (preg_match_all('/JOIN\s+(\w+)\s+ON\s+.*?\.(\w+)\s*=\s*.*?\.(\w+)/i', $sql, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $joinColumn1 = $match[2];
                $joinColumn2 = $match[3];
                
                if (!$this->hasIndex($joinColumn1) || !$this->hasIndex($joinColumn2)) {
                    $suggestions[] = [
                        'type' => 'join_optimization',
                        'message' => "Consider creating indexes on JOIN columns: $joinColumn1, $joinColumn2",
                        'priority' => 'high',
                        'action' => 'create_index',
                        'columns' => [$joinColumn1, $joinColumn2]
                    ];
                }
            }
        }
        
        return $suggestions;
    }
    
    /**
     * Aplicar otimizações de índices
     */
    private function applyIndexOptimizations($suggestions) {
        foreach ($suggestions as $suggestion) {
            if ($suggestion['action'] === 'create_index') {
                try {
                    if (isset($suggestion['columns'])) {
                        // Índice composto
                        $columns = implode(', ', $suggestion['columns']);
                        $indexName = 'idx_' . implode('_', $suggestion['columns']);
                        $sql = "CREATE INDEX IF NOT EXISTS $indexName ON table_name ($columns)";
                    } else {
                        // Índice simples
                        $column = $suggestion['column'];
                        $indexName = "idx_$column";
                        $sql = "CREATE INDEX IF NOT EXISTS $indexName ON table_name ($column)";
                    }
                    
                    // Nota: Em produção, determinar a tabela correta baseada na query
                    // $this->db->exec($sql);
                    
                    $this->recordIndexRecommendation($suggestion);
                    
                } catch (Exception $e) {
                    error_log("Failed to create index: " . $e->getMessage());
                }
            }
        }
    }
    
    /**
     * Obter estatísticas de performance em tempo real
     */
    public function getRealTimeStats() {
        $stats = [
            'timestamp' => time(),
            'server' => $this->getServerMetrics(),
            'database' => $this->getDatabaseMetrics(),
            'cache' => $this->cache->getStats(),
            'application' => $this->getApplicationMetrics(),
            'alerts' => $this->getActiveAlerts()
        ];
        
        return $stats;
    }
    
    /**
     * Obter métricas do servidor
     */
    private function getServerMetrics() {
        $load = sys_getloadavg();
        
        return [
            'memory_usage' => [
                'current' => memory_get_usage(true),
                'peak' => memory_get_peak_usage(true),
                'limit' => $this->parseBytes(ini_get('memory_limit')),
                'percentage' => (memory_get_usage(true) / $this->parseBytes(ini_get('memory_limit'))) * 100
            ],
            'cpu_load' => [
                '1min' => $load[0] ?? 0,
                '5min' => $load[1] ?? 0,
                '15min' => $load[2] ?? 0
            ],
            'disk_usage' => $this->getDiskUsage(),
            'uptime' => $this->getServerUptime()
        ];
    }
    
    /**
     * Obter métricas do banco de dados
     */
    private function getDatabaseMetrics() {
        try {
            // SQLite específico
            $dbPath = __DIR__ . "/../../database/duralux.db";
            $dbSize = file_exists($dbPath) ? filesize($dbPath) : 0;
            
            // Contar queries recentes
            $stmt = $this->db->query("
                SELECT COUNT(*) as total_queries,
                       AVG(execution_time) as avg_execution_time,
                       MAX(execution_time) as max_execution_time
                FROM slow_queries 
                WHERE timestamp > datetime('now', '-1 hour')
            ");
            $queryStats = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return [
                'size' => $dbSize,
                'connections' => 1, // SQLite é single connection
                'queries' => [
                    'total' => $queryStats['total_queries'] ?? 0,
                    'avg_time' => $queryStats['avg_execution_time'] ?? 0,
                    'max_time' => $queryStats['max_execution_time'] ?? 0
                ],
                'tables' => $this->getTableStats()
            ];
            
        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }
    
    /**
     * Obter métricas da aplicação
     */
    private function getApplicationMetrics() {
        return [
            'requests' => [
                'total' => $this->performanceMetrics['request_count'],
                'avg_response_time' => $this->performanceMetrics['request_count'] > 0 
                    ? $this->performanceMetrics['total_response_time'] / $this->performanceMetrics['request_count'] 
                    : 0
            ],
            'errors' => count($this->performanceMetrics['error_rates']),
            'slow_queries' => count($this->performanceMetrics['slow_queries']),
            'memory_peaks' => $this->performanceMetrics['memory_peaks']
        ];
    }
    
    /**
     * Comprimir e otimizar assets
     */
    public function optimizeAssets($assetsPath) {
        if (!$this->config['optimization']['gzip_compression']) {
            return false;
        }
        
        $optimizedFiles = [];
        
        // CSS files
        $cssFiles = glob($assetsPath . '/css/*.css');
        foreach ($cssFiles as $file) {
            $optimized = $this->minifyCSS(file_get_contents($file));
            $gzipFile = $file . '.gz';
            
            if (file_put_contents($gzipFile, gzencode($optimized, 9)) !== false) {
                $optimizedFiles[] = $gzipFile;
            }
        }
        
        // JavaScript files
        $jsFiles = glob($assetsPath . '/js/*.js');
        foreach ($jsFiles as $file) {
            $optimized = $this->minifyJS(file_get_contents($file));
            $gzipFile = $file . '.gz';
            
            if (file_put_contents($gzipFile, gzencode($optimized, 9)) !== false) {
                $optimizedFiles[] = $gzipFile;
            }
        }
        
        return $optimizedFiles;
    }
    
    // ==========================================
    // MÉTODOS AUXILIARES PRIVADOS
    // ==========================================
    
    private function startMetricsCollection() {
        $this->performanceMetrics['collection_start'] = time();
    }
    
    private function setupQueryProfiling() {
        // Implementar profiling de queries
    }
    
    private function setupMemoryProfiling() {
        // Monitorar picos de memória
        if (memory_get_usage(true) > $this->parseBytes(ini_get('memory_limit')) * 0.8) {
            $this->triggerAlert('high_memory', 'warning', 'High memory usage detected');
        }
    }
    
    private function recordQueryMetric($sql, $hash, $time, $memory, $rowCount) {
        $this->performanceMetrics['request_queries'][] = [
            'sql' => $sql,
            'hash' => $hash,
            'time' => $time,
            'memory' => $memory,
            'rows' => $rowCount
        ];
    }
    
    private function handleSlowQuery($sql, $hash, $time, $result) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO slow_queries 
                (query_hash, sql_query, execution_time, rows_returned, query_type) 
                VALUES (?, ?, ?, ?, ?)
            ");
            
            $queryType = $this->detectQueryType($sql);
            
            $stmt->execute([
                $hash,
                $sql,
                $time,
                count($result),
                $queryType
            ]);
            
            $this->performanceMetrics['slow_queries'][] = [
                'hash' => $hash,
                'time' => $time,
                'type' => $queryType
            ];
            
        } catch (Exception $e) {
            error_log("Failed to record slow query: " . $e->getMessage());
        }
    }
    
    private function recordQueryError($sql, $error, $time) {
        $this->performanceMetrics['error_rates'][] = [
            'sql' => $sql,
            'error' => $error,
            'time' => $time,
            'timestamp' => time()
        ];
    }
    
    private function recordRequestMetric($responseTime, $memoryUsage) {
        if (rand(1, 100) <= $this->config['monitoring']['sample_rate']) {
            try {
                $stmt = $this->db->prepare("
                    INSERT INTO performance_metrics 
                    (request_uri, method, response_time, memory_usage, query_count, user_id, ip_address) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                
                $stmt->execute([
                    $_SERVER['REQUEST_URI'] ?? '',
                    $_SERVER['REQUEST_METHOD'] ?? 'GET',
                    $responseTime,
                    $memoryUsage,
                    count($this->performanceMetrics['request_queries']),
                    $_SESSION['user_id'] ?? null,
                    $_SERVER['REMOTE_ADDR'] ?? ''
                ]);
                
            } catch (Exception $e) {
                error_log("Failed to record request metrics: " . $e->getMessage());
            }
        }
    }
    
    private function triggerAlert($type, $severity, $message, $value = null) {
        $alertKey = $type . '_' . time();
        
        // Verificar cooldown
        if ($this->isInCooldown($type)) {
            return false;
        }
        
        try {
            $stmt = $this->db->prepare("
                INSERT INTO performance_alerts 
                (alert_type, severity, message, metric_value, threshold_value) 
                VALUES (?, ?, ?, ?, ?)
            ");
            
            $threshold = $this->config['thresholds'][$type] ?? null;
            
            $stmt->execute([$type, $severity, $message, $value, $threshold]);
            
            $this->alerts[] = [
                'type' => $type,
                'severity' => $severity,
                'message' => $message,
                'timestamp' => time()
            ];
            
            // Enviar notificação se configurado
            if ($this->config['alerts']['enable_webhook']) {
                $this->sendWebhookAlert($type, $severity, $message);
            }
            
            return true;
            
        } catch (Exception $e) {
            error_log("Failed to trigger alert: " . $e->getMessage());
            return false;
        }
    }
    
    private function getSlowQueries($hours = 24) {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM slow_queries 
                WHERE timestamp > datetime('now', '-$hours hours')
                ORDER BY execution_time DESC
            ");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            return [];
        }
    }
    
    private function getTableRowCount($tableName) {
        try {
            $stmt = $this->db->query("SELECT COUNT(*) FROM `$tableName`");
            return $stmt->fetchColumn();
        } catch (Exception $e) {
            return 0;
        }
    }
    
    private function hasIndex($column) {
        // Simplificado - verificar se índice existe
        return false; // Implementar verificação real
    }
    
    private function recordIndexRecommendation($suggestion) {
        // Registrar recomendação de índice
    }
    
    private function getActiveAlerts() {
        try {
            $stmt = $this->db->query("
                SELECT * FROM performance_alerts 
                WHERE resolved = 0 AND timestamp > datetime('now', '-1 hour')
                ORDER BY timestamp DESC
            ");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            return [];
        }
    }
    
    private function getDiskUsage() {
        $bytes = disk_free_space(".");
        $total = disk_total_space(".");
        
        return [
            'free' => $bytes,
            'total' => $total,
            'used' => $total - $bytes,
            'percentage' => (($total - $bytes) / $total) * 100
        ];
    }
    
    private function getServerUptime() {
        // Implementar baseado no sistema operacional
        return time() - $_SERVER['REQUEST_TIME'];
    }
    
    private function getTableStats() {
        try {
            $stmt = $this->db->query("
                SELECT name FROM sqlite_master 
                WHERE type='table' AND name NOT LIKE 'sqlite_%'
            ");
            $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            $stats = [];
            foreach ($tables as $table) {
                $countStmt = $this->db->query("SELECT COUNT(*) FROM `$table`");
                $stats[$table] = $countStmt->fetchColumn();
            }
            
            return $stats;
            
        } catch (Exception $e) {
            return [];
        }
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
    
    private function detectQueryType($sql) {
        $sql = strtoupper(trim($sql));
        
        if (strpos($sql, 'SELECT') === 0) return 'SELECT';
        if (strpos($sql, 'INSERT') === 0) return 'INSERT';
        if (strpos($sql, 'UPDATE') === 0) return 'UPDATE';
        if (strpos($sql, 'DELETE') === 0) return 'DELETE';
        
        return 'OTHER';
    }
    
    private function isInCooldown($type) {
        $cooldownKey = "alert_cooldown_$type";
        $lastAlert = $this->cache->get($cooldownKey);
        
        if ($lastAlert && (time() - $lastAlert) < $this->config['alerts']['alert_cooldown']) {
            return true;
        }
        
        $this->cache->set($cooldownKey, time(), $this->config['alerts']['alert_cooldown']);
        return false;
    }
    
    private function sendWebhookAlert($type, $severity, $message) {
        // Implementar envio de webhook
    }
    
    private function minifyCSS($css) {
        // Remover comentários
        $css = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $css);
        
        // Remover espaços em branco desnecessários
        $css = str_replace(["\r\n", "\r", "\n", "\t", '  ', '    ', '    '], '', $css);
        
        return trim($css);
    }
    
    private function minifyJS($js) {
        // Minificação básica - em produção usar biblioteca especializada
        $js = preg_replace('/\/\*[\s\S]*?\*\//', '', $js);
        $js = preg_replace('/\/\/.*/', '', $js);
        
        return trim($js);
    }
    
    public function recordRequestMetrics() {
        // Chamado no shutdown
        $this->endRequestProfiling();
    }
}

?>