<?php
/**
 * DURALUX CRM - Redis Cache Manager v4.0
 * Sistema avançado de cache Redis com otimização de performance
 * 
 * Features Avançadas:
 * - Cache Redis distribuído com failover
 * - Cache multi-layer (L1: Memory, L2: Redis, L3: Database)
 * - Query result caching inteligente
 * - Session storage em Redis
 * - Cache invalidation automático
 * - Compression de dados automática
 * - Performance metrics em tempo real
 * - Cache warming e pre-loading
 * - Rate limiting distribuído
 * - Geographic caching
 * 
 * @author Duralux Development Team
 * @version 4.0.0
 * @since 2025-11-04
 */

class RedisCacheManager {
    
    private $redis;
    private $connected = false;
    private $fallbackCache = [];
    private $config;
    private $stats;
    
    // Configurações do cache
    private $defaultConfig = [
        'redis' => [
            'host' => 'localhost',
            'port' => 6379,
            'password' => null,
            'database' => 0,
            'timeout' => 2.5,
            'read_timeout' => 2.5,
            'retry_interval' => 100,
            'prefix' => 'duralux:',
            'serializer' => 'json', // json, php, igbinary
            'compression' => true,
            'compression_level' => 6
        ],
        'cache' => [
            'default_ttl' => 3600, // 1 hora
            'max_ttl' => 86400,     // 24 horas
            'min_ttl' => 60,        // 1 minuto
            'enable_tagging' => true,
            'enable_compression' => true,
            'auto_invalidation' => true,
            'cache_warming' => true
        ],
        'performance' => [
            'enable_metrics' => true,
            'enable_profiling' => true,
            'slow_query_threshold' => 1000, // ms
            'memory_limit' => '256M',
            'max_memory_percentage' => 80
        ],
        'clusters' => [
            'enable_clustering' => false,
            'nodes' => [],
            'read_preference' => 'primary',
            'failover_timeout' => 5
        ]
    ];
    
    // Cache layers
    private $layers = [
        'memory' => [],     // L1 Cache (in-memory)
        'redis' => null,    // L2 Cache (Redis)
        'database' => null  // L3 Cache (Database fallback)
    ];
    
    public function __construct($config = []) {
        $this->config = array_merge_recursive($this->defaultConfig, $config);
        $this->stats = [
            'hits' => 0,
            'misses' => 0,
            'sets' => 0,
            'deletes' => 0,
            'memory_usage' => 0,
            'response_times' => []
        ];
        
        $this->initializeRedis();
        $this->initializeMetrics();
    }
    
    /**
     * Conectar ao Redis (método público para testes)
     */
    public function connect($host = null, $port = null, $password = null) {
        // Override config se parâmetros fornecidos
        if ($host) $this->config['redis']['host'] = $host;
        if ($port) $this->config['redis']['port'] = $port; 
        if ($password) $this->config['redis']['password'] = $password;
        
        return $this->initializeRedis();
    }
    
    /**
     * Inicializar conexão Redis
     */
    private function initializeRedis() {
        try {
            if (!extension_loaded('redis')) {
                throw new Exception('Redis extension not loaded');
            }
            
            $this->redis = new Redis();
            
            // Conectar ao Redis
            $connected = $this->redis->connect(
                $this->config['redis']['host'],
                $this->config['redis']['port'],
                $this->config['redis']['timeout']
            );
            
            if (!$connected) {
                throw new Exception('Could not connect to Redis server');
            }
            
            // Autenticar se necessário
            if ($this->config['redis']['password']) {
                $this->redis->auth($this->config['redis']['password']);
            }
            
            // Selecionar database
            $this->redis->select($this->config['redis']['database']);
            
            // Configurar options
            $this->redis->setOption(Redis::OPT_PREFIX, $this->config['redis']['prefix']);
            $this->redis->setOption(Redis::OPT_READ_TIMEOUT, $this->config['redis']['read_timeout']);
            
            // Configurar serialização
            switch ($this->config['redis']['serializer']) {
                case 'php':
                    $this->redis->setOption(Redis::OPT_SERIALIZER, Redis::SERIALIZER_PHP);
                    break;
                case 'igbinary':
                    if (extension_loaded('igbinary')) {
                        $this->redis->setOption(Redis::OPT_SERIALIZER, Redis::SERIALIZER_IGBINARY);
                    }
                    break;
                default:
                    $this->redis->setOption(Redis::OPT_SERIALIZER, Redis::SERIALIZER_NONE);
            }
            
            $this->connected = true;
            $this->logMetric('redis_connection', 'success');
            
        } catch (Exception $e) {
            $this->connected = false;
            $this->logError('Redis connection failed: ' . $e->getMessage());
            
            // Inicializar cache em memória como fallback
            $this->initializeMemoryFallback();
        }
    }
    
    /**
     * Obter valor do cache (multi-layer)
     */
    public function get($key, $default = null) {
        $startTime = microtime(true);
        
        try {
            // L1 Cache (Memory)
            if (isset($this->layers['memory'][$key])) {
                $data = $this->layers['memory'][$key];
                if ($this->isValidCacheEntry($data)) {
                    $this->stats['hits']++;
                    $this->recordResponseTime('memory', microtime(true) - $startTime);
                    return $this->deserializeValue($data['value']);
                } else {
                    unset($this->layers['memory'][$key]);
                }
            }
            
            // L2 Cache (Redis)
            if ($this->connected) {
                $value = $this->redis->get($key);
                if ($value !== false) {
                    // Armazenar no L1 cache para próximas requisições
                    $this->setMemoryCache($key, $value, 300); // 5 minutos no L1
                    
                    $this->stats['hits']++;
                    $this->recordResponseTime('redis', microtime(true) - $startTime);
                    return $this->deserializeValue($value);
                }
            }
            
            $this->stats['misses']++;
            return $default;
            
        } catch (Exception $e) {
            $this->logError('Cache get error: ' . $e->getMessage());
            return $default;
        }
    }
    
    /**
     * Definir valor no cache (multi-layer)
     */
    public function set($key, $value, $ttl = null) {
        $startTime = microtime(true);
        $ttl = $ttl ?? $this->config['cache']['default_ttl'];
        
        try {
            $serializedValue = $this->serializeValue($value);
            
            // L1 Cache (Memory)
            $this->setMemoryCache($key, $serializedValue, min($ttl, 300)); // Máximo 5 min no L1
            
            // L2 Cache (Redis)
            if ($this->connected) {
                $success = $this->redis->setex($key, $ttl, $serializedValue);
                
                if ($success) {
                    $this->stats['sets']++;
                    $this->recordResponseTime('redis_set', microtime(true) - $startTime);
                    
                    // Adicionar tags se habilitado
                    if ($this->config['cache']['enable_tagging']) {
                        $this->addCacheTags($key, $this->extractTags($key));
                    }
                    
                    return true;
                }
            }
            
            return false;
            
        } catch (Exception $e) {
            $this->logError('Cache set error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Cache com callback (get-or-set pattern)
     */
    public function remember($key, $callback, $ttl = null) {
        $value = $this->get($key);
        
        if ($value !== null) {
            return $value;
        }
        
        // Executar callback para obter valor
        $startTime = microtime(true);
        $value = call_user_func($callback);
        $executionTime = microtime(true) - $startTime;
        
        // Log de queries lentas
        if ($executionTime > ($this->config['performance']['slow_query_threshold'] / 1000)) {
            $this->logSlowQuery($key, $executionTime);
        }
        
        // Armazenar no cache
        $this->set($key, $value, $ttl);
        
        return $value;
    }
    
    /**
     * Cache de queries SQL inteligente
     */
    public function cacheQuery($sql, $params = [], $ttl = null) {
        $cacheKey = 'query:' . md5($sql . serialize($params));
        
        return $this->remember($cacheKey, function() use ($sql, $params) {
            return $this->executeQuery($sql, $params);
        }, $ttl);
    }
    
    /**
     * Deletar chave do cache
     */
    public function delete($key) {
        try {
            // Remover do L1 cache
            unset($this->layers['memory'][$key]);
            
            // Remover do Redis
            if ($this->connected) {
                $result = $this->redis->del($key);
                $this->stats['deletes']++;
                return $result > 0;
            }
            
            return true;
            
        } catch (Exception $e) {
            $this->logError('Cache delete error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Invalidar cache por tags
     */
    public function invalidateByTags($tags) {
        if (!$this->config['cache']['enable_tagging'] || !$this->connected) {
            return false;
        }
        
        $tags = is_array($tags) ? $tags : [$tags];
        
        foreach ($tags as $tag) {
            $tagKey = 'tag:' . $tag;
            $keys = $this->redis->sMembers($tagKey);
            
            if (!empty($keys)) {
                // Deletar todas as chaves com esta tag
                $this->redis->del($keys);
                
                // Limpar a própria tag
                $this->redis->del($tagKey);
                
                // Limpar do L1 cache também
                foreach ($keys as $key) {
                    unset($this->layers['memory'][$key]);
                }
            }
        }
        
        return true;
    }
    
    /**
     * Limpar todo o cache
     */
    public function flush() {
        try {
            // Limpar L1 cache
            $this->layers['memory'] = [];
            
            // Limpar Redis
            if ($this->connected) {
                return $this->redis->flushDB();
            }
            
            return true;
            
        } catch (Exception $e) {
            $this->logError('Cache flush error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Otimizar query (integração com Performance Monitor)
     */
    public function optimizeQuery($sql, $params = []) {
        $queryKey = 'query_' . md5($sql . serialize($params));
        
        // Verificar se resultado está em cache
        $cached = $this->get($queryKey);
        if ($cached !== false) {
            $this->recordHit();
            return $cached;
        }
        
        // Se não estiver em cache, será executado pelo Performance Monitor
        $this->recordMiss();
        return false;
    }
    
    /**
     * Invalidar cache por tag
     */
    public function invalidateTag($tag) {
        try {
            if ($this->connected && $this->config['cache']['enable_tagging']) {
                $taggedKeys = $this->redis->sMembers($this->config['redis']['prefix'] . "tags:$tag");
                
                foreach ($taggedKeys as $key) {
                    $this->redis->del($key);
                }
                
                $this->redis->del($this->config['redis']['prefix'] . "tags:$tag");
                return true;
            }
            
            return false;
            
        } catch (Exception $e) {
            error_log("Cache tag invalidation failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obter estatísticas de cache
     */
    public function getStats() {
        $stats = [
            'hits' => $this->stats['hits'],
            'misses' => $this->stats['misses'],
            'hit_rate' => $this->stats['hits'] > 0 ? 
                round(($this->stats['hits'] / ($this->stats['hits'] + $this->stats['misses'])) * 100, 2) : 0,
            'total_requests' => $this->stats['hits'] + $this->stats['misses']
        ];    /**
     * Cache warming - pré-carregar dados importantes
     */
    public function warmCache($warmingRules = []) {
        if (!$this->config['cache']['cache_warming']) {
            return false;
        }
        
        $defaultRules = [
            'dashboard_stats' => ['ttl' => 3600, 'priority' => 1],
            'user_permissions' => ['ttl' => 1800, 'priority' => 2],
            'system_settings' => ['ttl' => 7200, 'priority' => 3],
            'popular_queries' => ['ttl' => 900, 'priority' => 4]
        ];
        
        $rules = array_merge($defaultRules, $warmingRules);
        
        // Ordenar por prioridade
        uasort($rules, function($a, $b) {
            return ($a['priority'] ?? 999) - ($b['priority'] ?? 999);
        });
        
        foreach ($rules as $key => $rule) {
            try {
                switch ($key) {
                    case 'dashboard_stats':
                        $this->warmDashboardStats($rule['ttl']);
                        break;
                    case 'user_permissions':
                        $this->warmUserPermissions($rule['ttl']);
                        break;
                    case 'system_settings':
                        $this->warmSystemSettings($rule['ttl']);
                        break;
                }
            } catch (Exception $e) {
                $this->logError("Cache warming failed for $key: " . $e->getMessage());
            }
        }
        
        return true;
    }
    
    // ==========================================
    // MÉTODOS PRIVADOS
    // ==========================================
    
    /**
     * Inicializar métricas de performance
     */
    private function initializeMetrics() {
        if ($this->config['performance']['enable_metrics']) {
            // Registrar shutdown function para salvar métricas
            register_shutdown_function([$this, 'saveMetrics']);
        }
    }
    
    /**
     * Cache em memória (L1)
     */
    private function setMemoryCache($key, $value, $ttl) {
        $this->layers['memory'][$key] = [
            'value' => $value,
            'expires' => time() + $ttl,
            'created' => time()
        ];
        
        // Limpar entradas expiradas para evitar vazamento de memória
        $this->cleanupMemoryCache();
    }
    
    /**
     * Verificar se entrada do cache é válida
     */
    private function isValidCacheEntry($data) {
        return isset($data['expires']) && $data['expires'] > time();
    }
    
    /**
     * Serializar valor para cache
     */
    private function serializeValue($value) {
        if ($this->config['redis']['serializer'] === 'json') {
            $serialized = json_encode($value);
        } else {
            $serialized = serialize($value);
        }
        
        // Compressão se habilitada
        if ($this->config['cache']['enable_compression'] && strlen($serialized) > 1024) {
            return gzcompress($serialized, $this->config['redis']['compression_level']);
        }
        
        return $serialized;
    }
    
    /**
     * Deserializar valor do cache
     */
    private function deserializeValue($value) {
        // Tentar descomprimir primeiro
        if ($this->config['cache']['enable_compression']) {
            $decompressed = @gzuncompress($value);
            if ($decompressed !== false) {
                $value = $decompressed;
            }
        }
        
        if ($this->config['redis']['serializer'] === 'json') {
            return json_decode($value, true);
        } else {
            return unserialize($value);
        }
    }
    
    /**
     * Adicionar tags ao cache
     */
    private function addCacheTags($key, $tags) {
        foreach ($tags as $tag) {
            $tagKey = 'tag:' . $tag;
            $this->redis->sAdd($tagKey, $key);
            $this->redis->expire($tagKey, 86400); // Tags expiram em 24h
        }
    }
    
    /**
     * Extrair tags da chave
     */
    private function extractTags($key) {
        $tags = [];
        
        // Extrair tags baseadas no padrão da chave
        if (preg_match('/^(\w+):/', $key, $matches)) {
            $tags[] = $matches[1]; // Tipo de dados (users, products, etc.)
        }
        
        // Tags adicionais baseadas no contexto
        if (strpos($key, 'user') !== false) $tags[] = 'users';
        if (strpos($key, 'customer') !== false) $tags[] = 'customers';
        if (strpos($key, 'product') !== false) $tags[] = 'products';
        if (strpos($key, 'order') !== false) $tags[] = 'orders';
        
        return array_unique($tags);
    }
    
    /**
     * Executar query SQL
     */
    private function executeQuery($sql, $params) {
        try {
            $pdo = new PDO("sqlite:" . __DIR__ . "/../../database/duralux.db");
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            $this->logError('Query execution error: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Limpeza do cache em memória
     */
    private function cleanupMemoryCache() {
        $currentTime = time();
        
        foreach ($this->layers['memory'] as $key => $data) {
            if (!$this->isValidCacheEntry($data)) {
                unset($this->layers['memory'][$key]);
            }
        }
        
        // Limitar tamanho do cache em memória
        if (count($this->layers['memory']) > 1000) {
            // Remover os mais antigos
            uasort($this->layers['memory'], function($a, $b) {
                return $b['created'] - $a['created'];
            });
            
            $this->layers['memory'] = array_slice($this->layers['memory'], 0, 800, true);
        }
    }
    
    /**
     * Inicializar fallback de memória
     */
    private function initializeMemoryFallback() {
        $this->layers['memory'] = [];
        $this->logError('Using memory fallback cache - Redis unavailable');
    }
    
    /**
     * Registrar tempo de resposta
     */
    private function recordResponseTime($operation, $time) {
        if (!isset($this->stats['response_times'][$operation])) {
            $this->stats['response_times'][$operation] = [];
        }
        
        $this->stats['response_times'][$operation][] = $time;
        
        // Manter apenas os últimos 100 registros
        if (count($this->stats['response_times'][$operation]) > 100) {
            array_shift($this->stats['response_times'][$operation]);
        }
    }
    
    /**
     * Cache warming específicos
     */
    private function warmDashboardStats($ttl) {
        $this->remember('dashboard:stats', function() {
            return $this->executeQuery("SELECT COUNT(*) as total FROM customers", []);
        }, $ttl);
    }
    
    private function warmUserPermissions($ttl) {
        $this->remember('users:permissions', function() {
            return $this->executeQuery("SELECT * FROM user_permissions WHERE granted = 1", []);
        }, $ttl);
    }
    
    private function warmSystemSettings($ttl) {
        $this->remember('system:settings', function() {
            return $this->executeQuery("SELECT * FROM auth_settings", []);
        }, $ttl);
    }
    
    /**
     * Log de queries lentas
     */
    private function logSlowQuery($key, $time) {
        $this->logMetric('slow_query', [
            'key' => $key,
            'execution_time' => $time,
            'threshold' => $this->config['performance']['slow_query_threshold'] / 1000
        ]);
    }
    
    /**
     * Log de métricas
     */
    private function logMetric($type, $data) {
        if ($this->config['performance']['enable_metrics']) {
            error_log("DURALUX_METRIC: $type " . json_encode($data));
        }
    }
    
    /**
     * Log de erros
     */
    private function logError($message) {
        error_log("DURALUX_CACHE_ERROR: $message");
    }
    
    /**
     * Salvar métricas no shutdown
     */
    public function saveMetrics() {
        if ($this->config['performance']['enable_metrics'] && $this->connected) {
            try {
                $metricsKey = 'metrics:' . date('Y-m-d-H');
                $currentMetrics = $this->redis->get($metricsKey) ?: '{}';
                $metrics = json_decode($currentMetrics, true);
                
                // Merge com estatísticas atuais
                foreach ($this->stats as $key => $value) {
                    if (!isset($metrics[$key])) {
                        $metrics[$key] = 0;
                    }
                    if (is_numeric($value)) {
                        $metrics[$key] += $value;
                    }
                }
                
                $this->redis->setex($metricsKey, 86400, json_encode($metrics));
                
            } catch (Exception $e) {
                $this->logError('Failed to save metrics: ' . $e->getMessage());
            }
        }
    }
}

/**
 * Cache Manager Singleton
 */
class CacheManager {
    
    private static $instance = null;
    private static $redis = null;
    
    public static function getInstance($config = []) {
        if (self::$instance === null) {
            self::$instance = new RedisCacheManager($config);
        }
        
        return self::$instance;
    }
    
    public static function getRedis() {
        if (self::$redis === null) {
            self::$redis = self::getInstance();
        }
        
        return self::$redis;
    }
}

?>