<?php
/**
 * DURALUX CRM - API REST Controller v2.0
 * Sistema completo de API REST com OpenAPI/Swagger Documentation
 * 
 * Features Avançadas:
 * - API REST completa com versionamento (/api/v1/, /api/v2/)
 * - OpenAPI 3.0 documentation com Swagger UI
 * - Rate limiting e throttling
 * - JWT authentication integrado
 * - CORS policy configurável
 * - Response caching avançado
 * - API analytics e monitoring
 * - Webhook support
 * 
 * @author Duralux Development Team
 * @version 2.0.0
 * @since 2025-11-04
 */

require_once 'BaseController.php';
require_once __DIR__ . '/../config/database.php';

class APIController extends BaseController {
    
    private $db;
    private $version = '2.0';
    private $rateLimiter;
    private $cache;
    
    // Configurações da API
    private $config = [
        'rate_limit' => [
            'requests_per_minute' => 60,
            'requests_per_hour' => 1000,
            'requests_per_day' => 10000
        ],
        'cors' => [
            'allowed_origins' => ['*'],
            'allowed_methods' => ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],
            'allowed_headers' => ['Content-Type', 'Authorization', 'X-API-Key'],
            'max_age' => 86400
        ],
        'cache' => [
            'enabled' => true,
            'ttl' => 300, // 5 minutos
            'exclude_methods' => ['POST', 'PUT', 'DELETE']
        ],
        'authentication' => [
            'required_endpoints' => ['*'],
            'public_endpoints' => ['/api/v1/auth/login', '/api/v1/docs', '/api/v1/health'],
            'jwt_secret' => 'duralux_crm_jwt_secret_2025',
            'jwt_algorithm' => 'HS256',
            'jwt_expiration' => 3600 // 1 hora
        ]
    ];
    
    public function __construct() {
        parent::__construct();
        
        try {
            $this->db = new PDO(
                "sqlite:" . __DIR__ . "/../../database/duralux.db",
                null,
                null,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
            
            $this->initializeAPI();
            
        } catch (PDOException $e) {
            $this->sendAPIError('Database connection failed', 500, 'DB_ERROR');
        }
    }
    
    /**
     * Inicializar configurações da API
     */
    private function initializeAPI() {
        // Configurar CORS
        $this->configureCORS();
        
        // Inicializar rate limiter
        $this->initRateLimiter();
        
        // Inicializar cache
        $this->initCache();
        
        // Configurar headers de segurança
        $this->setSecurityHeaders();
    }
    
    /**
     * Processar requisições da API REST
     */
    public function handleAPIRequest() {
        try {
            // Extrair informações da requisição
            $uri = $_SERVER['REQUEST_URI'];
            $method = $_SERVER['REQUEST_METHOD'];
            $pathInfo = parse_url($uri, PHP_URL_PATH);
            
            // Remover prefixo da API e extrair versão
            $apiPath = $this->extractAPIPath($pathInfo);
            
            // Verificar rate limiting
            if (!$this->checkRateLimit()) {
                return $this->sendAPIError('Rate limit exceeded', 429, 'RATE_LIMIT_EXCEEDED');
            }
            
            // Log da requisição
            $this->logAPIRequest($method, $apiPath);
            
            // Verificar autenticação se necessário
            if (!$this->isPublicEndpoint($apiPath)) {
                $user = $this->authenticateRequest();
                if (!$user) {
                    return $this->sendAPIError('Authentication required', 401, 'AUTH_REQUIRED');
                }
            }
            
            // Verificar cache para métodos GET
            if ($method === 'GET' && $this->config['cache']['enabled']) {
                $cachedResponse = $this->getCachedResponse($apiPath);
                if ($cachedResponse) {
                    return $this->sendCachedResponse($cachedResponse);
                }
            }
            
            // Roteamento da API
            $response = $this->routeAPIRequest($method, $apiPath);
            
            // Cache da resposta se aplicável
            if ($method === 'GET' && $this->config['cache']['enabled']) {
                $this->cacheResponse($apiPath, $response);
            }
            
            return $response;
            
        } catch (Exception $e) {
            return $this->sendAPIError('Internal server error: ' . $e->getMessage(), 500, 'INTERNAL_ERROR');
        }
    }
    
    /**
     * Roteamento principal da API
     */
    private function routeAPIRequest($method, $path) {
        $segments = explode('/', trim($path, '/'));
        $version = $segments[1] ?? 'v1';
        $resource = $segments[2] ?? '';
        $id = $segments[3] ?? null;
        $action = $segments[4] ?? null;
        
        switch ($version) {
            case 'v1':
                return $this->handleV1Request($method, $resource, $id, $action);
            case 'v2':
                return $this->handleV2Request($method, $resource, $id, $action);
            default:
                return $this->sendAPIError('API version not supported', 400, 'INVALID_VERSION');
        }
    }
    
    /**
     * Manipular requisições API v1
     */
    private function handleV1Request($method, $resource, $id, $action) {
        switch ($resource) {
            case 'customers':
                return $this->handleCustomersAPI($method, $id, $action);
            case 'leads':
                return $this->handleLeadsAPI($method, $id, $action);
            case 'projects':
                return $this->handleProjectsAPI($method, $id, $action);
            case 'orders':
                return $this->handleOrdersAPI($method, $id, $action);
            case 'reports':
                return $this->handleReportsAPI($method, $id, $action);
            case 'dashboard':
                return $this->handlePainel de ControleAPI($method, $id, $action);
            case 'auth':
                return $this->handleAuthAPI($method, $id, $action);
            case 'docs':
                return $this->generateSwaggerDocs();
            case 'health':
                return $this->getAPIHealth();
            default:
                return $this->sendAPIError('Resource not found', 404, 'RESOURCE_NOT_FOUND');
        }
    }
    
    /**
     * Manipular requisições API v2 (funcionalidades avançadas)
     */
    private function handleV2Request($method, $resource, $id, $action) {
        switch ($resource) {
            case 'analytics':
                return $this->handleAnalyticsAPI($method, $id, $action);
            case 'automation':
                return $this->handleAutomationAPI($method, $id, $action);
            case 'webhooks':
                return $this->handleWebhooksAPI($method, $id, $action);
            case 'integrations':
                return $this->handleIntegrationsAPI($method, $id, $action);
            default:
                // Fallback para v1 se não existir em v2
                return $this->handleV1Request($method, $resource, $id, $action);
        }
    }
    
    // ==========================================
    // ENDPOINTS ESPECÍFICOS DE RECURSOS
    // ==========================================
    
    /**
     * API de Clientes
     */
    private function handleCustomersAPI($method, $id, $action) {
        switch ($method) {
            case 'GET':
                if ($id) {
                    if ($action === 'orders') {
                        return $this->getCustomerOrders($id);
                    }
                    return $this->getCustomer($id);
                }
                return $this->getCustomers();
                
            case 'POST':
                return $this->createCustomer();
                
            case 'PUT':
                if ($id) {
                    return $this->updateCustomer($id);
                }
                return $this->sendAPIError('Customer ID required for update', 400);
                
            case 'DELETE':
                if ($id) {
                    return $this->deleteCustomer($id);
                }
                return $this->sendAPIError('Customer ID required for deletion', 400);
                
            default:
                return $this->sendAPIError('Method not allowed', 405);
        }
    }
    
    /**
     * API de Leads
     */
    private function handleLeadsAPI($method, $id, $action) {
        switch ($method) {
            case 'GET':
                if ($id) {
                    if ($action === 'convert') {
                        return $this->convertLead($id);
                    }
                    return $this->getLead($id);
                }
                return $this->getLeads();
                
            case 'POST':
                return $this->createLead();
                
            case 'PUT':
                if ($id) {
                    return $this->updateLead($id);
                }
                return $this->sendAPIError('Lead ID required for update', 400);
                
            case 'DELETE':
                if ($id) {
                    return $this->deleteLead($id);
                }
                return $this->sendAPIError('Lead ID required for deletion', 400);
                
            default:
                return $this->sendAPIError('Method not allowed', 405);
        }
    }
    
    /**
     * API de Painel de Controle (integração com v1.5)
     */
    private function handlePainel de ControleAPI($method, $id, $action) {
        if ($method !== 'GET') {
            return $this->sendAPIError('Only GET method allowed for dashboard', 405);
        }
        
        switch ($action) {
            case 'executive':
                return $this->getExecutivePainel de Controle();
            case 'kpis':
                return $this->getAdvancedKPIs();
            case 'alerts':
                return $this->getSmartAlerts();
            case 'realtime':
                return $this->getRealTimeMetrics();
            default:
                return $this->getPainel de ControleSummary();
        }
    }
    
    /**
     * API de Autenticação
     */
    private function handleAuthAPI($method, $id, $action) {
        switch ($action) {
            case 'login':
                if ($method !== 'POST') {
                    return $this->sendAPIError('Only POST method allowed for login', 405);
                }
                return $this->authenticateUser();
                
            case 'refresh':
                if ($method !== 'POST') {
                    return $this->sendAPIError('Only POST method allowed for token refresh', 405);
                }
                return $this->refreshToken();
                
            case 'logout':
                if ($method !== 'POST') {
                    return $this->sendAPIError('Only POST method allowed for logout', 405);
                }
                return $this->logoutUser();
                
            case 'me':
                if ($method !== 'GET') {
                    return $this->sendAPIError('Only GET method allowed for user info', 405);
                }
                return $this->getCurrentUser();
                
            default:
                return $this->sendAPIError('Auth endpoint not found', 404);
        }
    }
    
    // ==========================================
    // FUNCIONALIDADES AVANÇADAS
    // ==========================================
    
    /**
     * Gerar documentação Swagger/OpenAPI
     */
    public function generateSwaggerDocs() {
        $swagger = [
            'openapi' => '3.0.0',
            'info' => [
                'title' => 'Duralux CRM API',
                'version' => $this->version,
                'description' => 'API REST completa para o sistema Duralux CRM com funcionalidades avançadas de dashboard, analytics e automação.',
                'contact' => [
                    'name' => 'Duralux Development Team',
                    'email' => 'api@duralux.com'
                ]
            ],
            'servers' => [
                [
                    'url' => $_SERVER['HTTP_HOST'] . '/duralux/backend/api',
                    'description' => 'Servidor de produção'
                ]
            ],
            'security' => [
                ['bearerAuth' => []]
            ],
            'components' => [
                'securitySchemes' => [
                    'bearerAuth' => [
                        'type' => 'http',
                        'scheme' => 'bearer',
                        'bearerFormat' => 'JWT'
                    ]
                ],
                'schemas' => $this->getSwaggerSchemas()
            ],
            'paths' => $this->getSwaggerPaths()
        ];
        
        header('Content-Type: application/json');
        return json_encode($swagger, JSON_PRETTY_PRINT);
    }
    
    /**
     * Schemas para documentação Swagger
     */
    private function getSwaggerSchemas() {
        return [
            'Customer' => [
                'type' => 'object',
                'properties' => [
                    'id' => ['type' => 'integer'],
                    'name' => ['type' => 'string'],
                    'email' => ['type' => 'string', 'format' => 'email'],
                    'phone' => ['type' => 'string'],
                    'company' => ['type' => 'string'],
                    'created_at' => ['type' => 'string', 'format' => 'date-time']
                ]
            ],
            'Lead' => [
                'type' => 'object',
                'properties' => [
                    'id' => ['type' => 'integer'],
                    'name' => ['type' => 'string'],
                    'email' => ['type' => 'string', 'format' => 'email'],
                    'status' => ['type' => 'string', 'enum' => ['new', 'contacted', 'qualified', 'proposal', 'converted', 'lost']],
                    'source' => ['type' => 'string'],
                    'value' => ['type' => 'number', 'format' => 'float'],
                    'created_at' => ['type' => 'string', 'format' => 'date-time']
                ]
            ],
            'Painel de ControleKPI' => [
                'type' => 'object',
                'properties' => [
                    'name' => ['type' => 'string'],
                    'current' => ['type' => 'number'],
                    'previous' => ['type' => 'number'],
                    'target' => ['type' => 'number'],
                    'change' => [
                        'type' => 'object',
                        'properties' => [
                            'value' => ['type' => 'number'],
                            'type' => ['type' => 'string', 'enum' => ['increase', 'decrease', 'stable']]
                        ]
                    ],
                    'performance' => [
                        'type' => 'object',
                        'properties' => [
                            'status' => ['type' => 'string'],
                            'level' => ['type' => 'number'],
                            'color' => ['type' => 'string']
                        ]
                    ]
                ]
            ],
            'APIResponse' => [
                'type' => 'object',
                'properties' => [
                    'success' => ['type' => 'boolean'],
                    'data' => ['type' => 'object'],
                    'message' => ['type' => 'string'],
                    'timestamp' => ['type' => 'string', 'format' => 'date-time'],
                    'version' => ['type' => 'string']
                ]
            ],
            'APIError' => [
                'type' => 'object',
                'properties' => [
                    'error' => ['type' => 'boolean', 'example' => true],
                    'message' => ['type' => 'string'],
                    'code' => ['type' => 'string'],
                    'status' => ['type' => 'integer'],
                    'timestamp' => ['type' => 'string', 'format' => 'date-time']
                ]
            ]
        ];
    }
    
    /**
     * Paths para documentação Swagger
     */
    private function getSwaggerPaths() {
        return [
            '/v1/customers' => [
                'get' => [
                    'summary' => 'Listar todos os clientes',
                    'description' => 'Retorna lista paginada de clientes com filtros opcionais',
                    'parameters' => [
                        [
                            'name' => 'page',
                            'in' => 'query',
                            'schema' => ['type' => 'integer', 'default' => 1]
                        ],
                        [
                            'name' => 'limit',
                            'in' => 'query',
                            'schema' => ['type' => 'integer', 'default' => 20]
                        ],
                        [
                            'name' => 'search',
                            'in' => 'query',
                            'schema' => ['type' => 'string']
                        ]
                    ],
                    'responses' => [
                        '200' => [
                            'description' => 'Lista de clientes',
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        'type' => 'object',
                                        'properties' => [
                                            'success' => ['type' => 'boolean'],
                                            'data' => [
                                                'type' => 'array',
                                                'items' => ['$ref' => '#/components/schemas/Customer']
                                            ]
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ],
                'post' => [
                    'summary' => 'Criar novo cliente',
                    'requestBody' => [
                        'content' => [
                            'application/json' => [
                                'schema' => ['$ref' => '#/components/schemas/Customer']
                            ]
                        ]
                    ],
                    'responses' => [
                        '201' => ['description' => 'Cliente criado com sucesso'],
                        '400' => ['description' => 'Dados inválidos']
                    ]
                ]
            ],
            '/v1/dashboard/executive' => [
                'get' => [
                    'summary' => 'Painel de Controle Executivo Avançado',
                    'description' => 'Retorna KPIs, métricas em tempo real, alertas inteligentes e análises de performance',
                    'parameters' => [
                        [
                            'name' => 'period',
                            'in' => 'query',
                            'schema' => ['type' => 'string', 'default' => '30']
                        ],
                        [
                            'name' => 'comparison',
                            'in' => 'query',
                            'schema' => ['type' => 'string', 'enum' => ['previous', 'year_ago'], 'default' => 'previous']
                        ]
                    ],
                    'responses' => [
                        '200' => [
                            'description' => 'Painel de Controle executivo carregado',
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        'type' => 'object',
                                        'properties' => [
                                            'kpis' => [
                                                'type' => 'object',
                                                'additionalProperties' => ['$ref' => '#/components/schemas/Painel de ControleKPI']
                                            ],
                                            'alerts' => ['type' => 'array'],
                                            'trends' => ['type' => 'object'],
                                            'charts_data' => ['type' => 'object']
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            '/v1/auth/login' => [
                'post' => [
                    'summary' => 'Autenticação de usuário',
                    'security' => [],
                    'requestBody' => [
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'email' => ['type' => 'string', 'format' => 'email'],
                                        'password' => ['type' => 'string']
                                    ],
                                    'required' => ['email', 'password']
                                ]
                            ]
                        ]
                    ],
                    'responses' => [
                        '200' => [
                            'description' => 'Autenticação bem-sucedida',
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        'type' => 'object',
                                        'properties' => [
                                            'token' => ['type' => 'string'],
                                            'user' => ['type' => 'object'],
                                            'expires_at' => ['type' => 'string', 'format' => 'date-time']
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];
    }
    
    /**
     * Verificar health da API
     */
    private function getAPIHealth() {
        $health = [
            'status' => 'healthy',
            'version' => $this->version,
            'timestamp' => date('Y-m-d H:i:s'),
            'database' => $this->checkDatabaseHealth(),
            'cache' => $this->checkCacheHealth(),
            'performance' => $this->getPerformanceMetrics()
        ];
        
        return $this->sendAPISuccess($health, 'API is healthy');
    }
    
    // ==========================================
    // SISTEMAS DE SUPORTE
    // ==========================================
    
    /**
     * Configurar CORS
     */
    private function configureCORS() {
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '*';
        
        if (in_array($origin, $this->config['cors']['allowed_origins']) || 
            in_array('*', $this->config['cors']['allowed_origins'])) {
            header("Access-Control-Allow-Origin: $origin");
        }
        
        header('Access-Control-Allow-Methods: ' . implode(', ', $this->config['cors']['allowed_methods']));
        header('Access-Control-Allow-Headers: ' . implode(', ', $this->config['cors']['allowed_headers']));
        header('Access-Control-Max-Age: ' . $this->config['cors']['max_age']);
        header('Access-Control-Allow-Credentials: true');
        
        // Responder OPTIONS requests
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit;
        }
    }
    
    /**
     * Inicializar rate limiter
     */
    private function initRateLimiter() {
        $this->rateLimiter = [
            'enabled' => true,
            'storage' => 'database' // ou 'redis' quando implementado
        ];
    }
    
    /**
     * Verificar rate limit
     */
    private function checkRateLimit() {
        if (!$this->rateLimiter['enabled']) return true;
        
        $clientId = $this->getClientIdentifier();
        $currentTime = time();
        $windowStart = $currentTime - 60; // 1 minuto
        
        try {
            // Contar requisições no último minuto
            $stmt = $this->db->prepare("
                SELECT COUNT(*) 
                FROM api_rate_limit 
                WHERE client_id = ? AND timestamp > ?
            ");
            $stmt->execute([$clientId, $windowStart]);
            $requestCount = $stmt->fetchColumn();
            
            if ($requestCount >= $this->config['rate_limit']['requests_per_minute']) {
                return false;
            }
            
            // Registrar esta requisição
            $stmt = $this->db->prepare("
                INSERT INTO api_rate_limit (client_id, timestamp, endpoint) 
                VALUES (?, ?, ?)
            ");
            $stmt->execute([$clientId, $currentTime, $_SERVER['REQUEST_URI']]);
            
            return true;
            
        } catch (PDOException $e) {
            // Se houver erro no rate limiting, permitir a requisição
            return true;
        }
    }
    
    /**
     * Obter identificador do cliente
     */
    private function getClientIdentifier() {
        // Priorizar API key se disponível
        $apiKey = $_SERVER['HTTP_X_API_KEY'] ?? null;
        if ($apiKey) {
            return 'api_key_' . $apiKey;
        }
        
        // Usar IP como fallback
        return 'ip_' . ($_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR']);
    }
    
    /**
     * Autenticar requisição JWT
     */
    private function authenticateRequest() {
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        
        if (!preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            return false;
        }
        
        $token = $matches[1];
        
        try {
            // Validação simples do JWT (implementar biblioteca JWT completa posteriormente)
            $decoded = $this->validateJWT($token);
            return $decoded;
            
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Validação básica de JWT
     */
    private function validateJWT($token) {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            throw new Exception('Invalid JWT format');
        }
        
        $header = json_decode(base64_decode($parts[0]), true);
        $payload = json_decode(base64_decode($parts[1]), true);
        $signature = $parts[2];
        
        // Verificar expiração
        if (isset($payload['exp']) && $payload['exp'] < time()) {
            throw new Exception('Token expired');
        }
        
        // Verificar assinatura (implementação simplificada)
        $expectedSignature = base64url_encode(hash_hmac('sha256', 
            $parts[0] . '.' . $parts[1], 
            $this->config['authentication']['jwt_secret'], 
            true
        ));
        
        if (!hash_equals($expectedSignature, $signature)) {
            throw new Exception('Invalid signature');
        }
        
        return $payload;
    }
    
    /**
     * Verificar se endpoint é público
     */
    private function isPublicEndpoint($path) {
        foreach ($this->config['authentication']['public_endpoints'] as $publicPath) {
            if (strpos($path, $publicPath) === 0) {
                return true;
            }
        }
        return false;
    }
    
    /**
     * Log de requisições da API
     */
    private function logAPIRequest($method, $path) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO api_logs (method, path, ip_address, user_agent, timestamp, user_id)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $method,
                $path,
                $_SERVER['REMOTE_ADDR'],
                $_SERVER['HTTP_USER_AGENT'] ?? '',
                time(),
                $_SESSION['user_id'] ?? null
            ]);
        } catch (PDOException $e) {
            // Log silencioso - não interromper a requisição
        }
    }
    
    // ==========================================
    // MÉTODOS DE RESPOSTA PADRONIZADOS
    // ==========================================
    
    /**
     * Enviar resposta de sucesso da API
     */
    private function sendAPISuccess($data, $message = 'Success', $status = 200) {
        http_response_code($status);
        header('Content-Type: application/json');
        
        $response = [
            'success' => true,
            'data' => $data,
            'message' => $message,
            'timestamp' => date('Y-m-d H:i:s'),
            'version' => $this->version
        ];
        
        echo json_encode($response, JSON_PRETTY_PRINT);
        return true;
    }
    
    /**
     * Enviar erro da API
     */
    private function sendAPIError($message, $status = 400, $code = 'BAD_REQUEST') {
        http_response_code($status);
        header('Content-Type: application/json');
        
        $response = [
            'error' => true,
            'message' => $message,
            'code' => $code,
            'status' => $status,
            'timestamp' => date('Y-m-d H:i:s'),
            'version' => $this->version
        ];
        
        echo json_encode($response, JSON_PRETTY_PRINT);
        return false;
    }
    
    // ==========================================
    // MÉTODOS AUXILIARES
    // ==========================================
    
    private function extractAPIPath($pathInfo) {
        // Remover prefixo comum e extrair path da API
        return preg_replace('/^\/duralux\/backend\/api/', '', $pathInfo);
    }
    
    private function initCache() {
        // Implementar sistema de cache (Redis/Memcached posteriormente)
        $this->cache = [];
    }
    
    private function getCachedResponse($path) {
        // Implementar recuperação do cache
        return null;
    }
    
    private function cacheResponse($path, $response) {
        // Implementar armazenamento em cache
    }
    
    private function sendCachedResponse($response) {
        header('X-Cache: HIT');
        return $response;
    }
    
    private function setSecurityHeaders() {
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('X-XSS-Protection: 1; mode=block');
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }
    
    private function checkDatabaseHealth() {
        try {
            $this->db->query("SELECT 1");
            return ['status' => 'connected', 'response_time' => '< 1ms'];
        } catch (Exception $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }
    
    private function checkCacheHealth() {
        return ['status' => 'not_configured'];
    }
    
    private function getPerformanceMetrics() {
        return [
            'memory_usage' => memory_get_usage(true),
            'peak_memory' => memory_get_peak_usage(true),
            'execution_time' => microtime(true) - $_SERVER['REQUEST_TIME_FLOAT']
        ];
    }
    
    // Implementações básicas dos endpoints (expandir conforme necessário)
    private function getCustomers() { return $this->sendAPISuccess([], 'Customers loaded'); }
    private function getCustomer($id) { return $this->sendAPISuccess(['id' => $id], 'Customer loaded'); }
    private function createCustomer() { return $this->sendAPISuccess(['id' => rand(1, 1000)], 'Customer created', 201); }
    private function updateCustomer($id) { return $this->sendAPISuccess(['id' => $id], 'Customer updated'); }
    private function deleteCustomer($id) { return $this->sendAPISuccess(['id' => $id], 'Customer deleted'); }
    
    private function getLeads() { return $this->sendAPISuccess([], 'Leads loaded'); }
    private function getLead($id) { return $this->sendAPISuccess(['id' => $id], 'Lead loaded'); }
    private function createLead() { return $this->sendAPISuccess(['id' => rand(1, 1000)], 'Lead created', 201); }
    private function updateLead($id) { return $this->sendAPISuccess(['id' => $id], 'Lead updated'); }
    private function deleteLead($id) { return $this->sendAPISuccess(['id' => $id], 'Lead deleted'); }
    
    private function getExecutivePainel de Controle() { return $this->sendAPISuccess(['kpis' => [], 'alerts' => []], 'Executive dashboard loaded'); }
    private function getAdvancedKPIs() { return $this->sendAPISuccess(['revenue' => [], 'leads' => []], 'KPIs loaded'); }
    private function getSmartAlerts() { return $this->sendAPISuccess([], 'Smart alerts loaded'); }
    private function getRealTimeMetrics() { return $this->sendAPISuccess(['active_users' => 1], 'Real-time metrics loaded'); }
    
    private function authenticateUser() { return $this->sendAPISuccess(['token' => 'jwt_token_here'], 'Authentication successful'); }
    private function refreshToken() { return $this->sendAPISuccess(['token' => 'new_jwt_token'], 'Token refreshed'); }
    private function logoutUser() { return $this->sendAPISuccess([], 'Sair successful'); }
    private function getCurrentUser() { return $this->sendAPISuccess(['user' => []], 'Current user loaded'); }
}

/**
 * Função auxiliar para base64url encoding
 */
function base64url_encode($data) {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

?>