<?php
/**
 * DURALUX CRM - Router da API REST v2.0
 * Roteador principal para todas as requisições da API
 * 
 * Features:
 * - Roteamento automático baseado em URL
 * - Versionamento de API (/api/v1/, /api/v2/)
 * - Middleware de autenticação
 * - Rate limiting integrado
 * - CORS automático
 * - Logging de requisições
 * - Documentação Swagger integrada
 * 
 * @author Duralux Development Team
 * @version 2.0.0
 */

// Headers de segurança e CORS
header('Content-Type: application/json; charset=utf-8');
header('X-Powered-By: Duralux API v2.0');

// Configuração de erro reporting para produção
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

require_once __DIR__ . '/../init.php';
require_once __DIR__ . '/../classes/AuthMiddleware.php';
require_once __DIR__ . '/../classes/APIController.php';

try {
    // Verificar se a requisição é para a API REST v2.0
    $requestUri = $_SERVER['REQUEST_URI'];
    $scriptName = $_SERVER['SCRIPT_NAME'];
    
    // Extrair o path da API
    $basePath = dirname($scriptName);
    $apiPath = str_replace($basePath, '', $requestUri);
    
    // Verificar se é uma requisição da API REST (/api/v1/, /api/v2/)
    if (preg_match('/^\/api\/v[12]\//', $apiPath)) {
        // Verificar se é requisição para documentação Swagger UI
        if (preg_match('/^\/api\/v1\/docs\/?$/', $apiPath)) {
            serveSwaggerUI();
            exit;
        }
        
        // Verificar se é requisição para JSON da documentação
        if (preg_match('/^\/api\/v1\/docs\.json\/?$/', $apiPath)) {
            $apiController = new APIController();
            echo $apiController->generateSwaggerDocs();
            exit;
        }
        
        // Processar através do novo APIController
        $apiController = new APIController();
        $apiController->handleAPIRequest();
        exit;
    }

// Verificar se é requisição para dashboard ou API direta (sistema legado)
$data = json_decode(file_get_contents('php://input'), true) ?: [];
$action = $data['action'] ?? $_GET['action'] ?? '';

// Se a requisição tem uma action, usar o sistema de controllers direto
if ($action) {
    // Aplicar middleware de segurança
    AuthMiddleware::handle();
    
    // Roteamento baseado em action
    switch ($action) {
        // Painel de Controle actions
        case 'get_dashboard_stats':
        case 'get_revenue_data':
        case 'get_leads_analytics':
        case 'get_projects_analytics':
        case 'get_recent_activities':
        case 'check_auth':
            $controller = new Painel de ControleController();
            $controller->handleRequest();
            break;
            
        // Customer actions
        case 'get_customers':
        case 'get_customer':
        case 'create_customer':
        case 'update_customer':
        case 'delete_customer':
        case 'search_customers':
            $controller = new CustomerController();
            $controller->handleRequest();
            break;
            
        // Product actions
        case 'get_products':
        case 'get_product':
        case 'create_product':
        case 'update_product':
        case 'delete_product':
        case 'search_products':
            $controller = new ProductController();
            $controller->handleRequest();
            break;
            
        // Leads actions
        case 'get_leads':
        case 'get_lead':
        case 'create_lead':
        case 'update_lead':
        case 'delete_lead':
        case 'convert_lead':
        case 'get_leads_options':
        case 'get_pipeline_stats':
        case 'search_leads':
            $controller = new LeadsController();
            $controller->handleRequest();
            break;
            
            // Projects actions
        case 'get_projects':
        case 'get_project':
        case 'create_project':
        case 'update_project':
        case 'delete_project':
        case 'manage_tasks':
        case 'get_project_stats':
        case 'get_project_options':
        case 'get_project_customers':
            $controller = new ProjectController();
            $controller->handleRequest();
            break;
            
        // Orders actions
        case 'get_orders':
        case 'get_order':
        case 'create_order':
        case 'update_order':
        case 'delete_order':
        case 'get_order_stats':
        case 'generate_invoice':
            $controller = new OrderController();
            $controller->handleRequest();
            break;
            
        // Reports actions
        case 'get_dashboard_report':
        case 'get_sales_report':
        case 'get_leads_report':
        case 'get_projects_report':
        case 'get_customers_report':
        case 'get_financial_report':
        case 'export_report':
        case 'get_chart_data':
            $controller = new ReportsController();
            $controller->handleRequest();
            break;
            
        // Performance Monitor actions (v4.0)
        case 'get_performance_dashboard':
        case 'get_performance_overview':
        case 'get_performance_trends':
        case 'get_active_alerts':
        case 'execute_optimization':
        case 'get_system_resources':
        case 'get_optimization_recommendations':
            require_once __DIR__ . '/../classes/PerformancePainel de ControleController.php';
            $controller = new PerformancePainel de ControleController();
            $controller->handleRequest();
            break;
            
        // Workflow Automation v5.0 - API Endpoints
        case 'create_workflow':
        case 'get_workflows':
        case 'get_workflow':
        case 'update_workflow':
        case 'delete_workflow':
        case 'toggle_workflow':
        case 'execute_workflow':
        case 'get_executions':
        case 'get_execution_details':
        case 'create_template':
        case 'get_templates':
        case 'use_template':
        case 'get_workflow_dashboard':
        case 'get_workflow_stats':
        case 'process_triggers':
        case 'test_trigger':
            require_once __DIR__ . '/../classes/WorkflowController.php';
            $controller = new WorkflowController($db);
            $controller->handleRequest();
            break;
            
        // Auth actions
        case 'login':
        case 'logout':
        case 'register':
        case 'forgot_password':
        case 'check_session':
            $controller = new AuthController();
            $controller->handleRequest();
            break;
            
        default:
            jsonResponse(['success' => false, 'message' => 'Ação não encontrada'], 404);
    }
    exit;
}

// Router para URLs RESTful (manter compatibilidade)
class Router {
    private $routes = [];
    
    public function __construct() {
        $this->setupRoutes();
    }
    
    private function setupRoutes() {
        // Rotas de autenticação
        $this->routes['POST']['/auth/login'] = 'AuthController@login';
        $this->routes['POST']['/auth/logout'] = 'AuthController@logout';
        $this->routes['POST']['/auth/register'] = 'AuthController@register';
        $this->routes['POST']['/auth/forgot'] = 'AuthController@forgotPassword';
        $this->routes['GET']['/auth/me'] = 'AuthController@me';
        
        // Rotas de clientes
        $this->routes['GET']['/customers'] = 'CustomerController@index';
        $this->routes['GET']['/customers/{id}'] = 'CustomerController@show';
        $this->routes['POST']['/customers'] = 'CustomerController@store';
        $this->routes['PUT']['/customers/{id}'] = 'CustomerController@update';
        $this->routes['DELETE']['/customers/{id}'] = 'CustomerController@delete';
        
        // Rotas de produtos
        $this->routes['GET']['/products'] = 'ProductController@index';
        $this->routes['GET']['/products/{id}'] = 'ProductController@show';
        $this->routes['POST']['/products'] = 'ProductController@store';
        $this->routes['PUT']['/products/{id}'] = 'ProductController@update';
        $this->routes['DELETE']['/products/{id}'] = 'ProductController@delete';
        
        // Rotas de leads
        $this->routes['GET']['/leads'] = 'LeadsController@index';
        $this->routes['GET']['/leads/{id}'] = 'LeadsController@show';
        $this->routes['POST']['/leads'] = 'LeadsController@store';
        $this->routes['PUT']['/leads/{id}'] = 'LeadsController@update';
        $this->routes['DELETE']['/leads/{id}'] = 'LeadsController@delete';
        $this->routes['POST']['/leads/{id}/convert'] = 'LeadsController@convertToCustomer';
        $this->routes['GET']['/leads/options'] = 'LeadsController@getOptions';
        $this->routes['GET']['/leads/pipeline'] = 'LeadsController@pipeline';
        
        // Rotas de projetos
        $this->routes['GET']['/projects'] = 'ProjectController@index';
        $this->routes['GET']['/projects/{id}'] = 'ProjectController@show';
        $this->routes['POST']['/projects'] = 'ProjectController@store';
        $this->routes['PUT']['/projects/{id}'] = 'ProjectController@update';
        $this->routes['DELETE']['/projects/{id}'] = 'ProjectController@delete';
        $this->routes['GET']['/projects/stats'] = 'ProjectController@getStats';
        $this->routes['GET']['/projects/options'] = 'ProjectController@getOptions';
        $this->routes['GET']['/projects/customers'] = 'ProjectController@getCustomers';
        $this->routes['POST']['/projects/{id}/tasks'] = 'ProjectController@manageTasks';
        $this->routes['GET']['/projects/{id}/tasks'] = 'ProjectController@manageTasks';
        
        // Rotas de Workflows (Workflow Automation Engine v5.0)
        $this->routes['GET']['/workflows'] = 'WorkflowController@index';
        $this->routes['GET']['/workflows/list'] = 'WorkflowController@getWorkflows';
        $this->routes['POST']['/workflows'] = 'WorkflowController@store';
        $this->routes['POST']['/workflows/create'] = 'WorkflowController@createWorkflow';
        $this->routes['GET']['/workflows/{id}'] = 'WorkflowController@show';
        $this->routes['PUT']['/workflows/{id}'] = 'WorkflowController@update';
        $this->routes['PUT']['/workflows/update'] = 'WorkflowController@updateWorkflow';
        $this->routes['DELETE']['/workflows/{id}'] = 'WorkflowController@destroy';
        $this->routes['DELETE']['/workflows/delete'] = 'WorkflowController@deleteWorkflow';
        $this->routes['POST']['/workflows/{id}/execute'] = 'WorkflowController@execute';
        $this->routes['POST']['/workflows/execute'] = 'WorkflowController@executeWorkflow';
        $this->routes['GET']['/workflows/{id}/executions'] = 'WorkflowController@getExecutions';
        $this->routes['GET']['/workflows/stats'] = 'WorkflowController@getWorkflowStats';
        $this->routes['GET']['/workflows/templates'] = 'WorkflowController@getTemplates';
        $this->routes['POST']['/workflows/templates'] = 'WorkflowController@createTemplate';
        $this->routes['POST']['/workflows/export'] = 'WorkflowController@exportWorkflows';
        $this->routes['POST']['/workflows/import'] = 'WorkflowController@importWorkflows';
        
        // Rotas de pedidos
        $this->routes['GET']['/orders'] = 'OrderController@index';
        $this->routes['GET']['/orders/{id}'] = 'OrderController@view';
        $this->routes['POST']['/orders'] = 'OrderController@create';
        $this->routes['PUT']['/orders/{id}'] = 'OrderController@update';
        $this->routes['DELETE']['/orders/{id}'] = 'OrderController@delete';
        $this->routes['GET']['/orders/statistics'] = 'OrderController@statistics';
        $this->routes['POST']['/orders/{id}/invoice'] = 'OrderController@generateInvoice';
        
        // Rotas de dashboard
        $this->routes['GET']['/dashboard/stats'] = 'Painel de ControleController@getPainel de ControleStats';
        $this->routes['GET']['/dashboard/revenue'] = 'Painel de ControleController@getReceitaData';
        $this->routes['GET']['/dashboard/leads'] = 'Painel de ControleController@getLeadsAnalytics';
        $this->routes['GET']['/dashboard/projects'] = 'Painel de ControleController@getProjectsAnalytics';
        $this->routes['GET']['/dashboard/activities'] = 'Painel de ControleController@getRecentActivities';
        
        // Rotas de relatórios
        $this->routes['GET']['/reports/dashboard'] = 'ReportsController@getPainel de Controle';
        $this->routes['GET']['/reports/sales'] = 'ReportsController@getVendas';
        $this->routes['GET']['/reports/leads'] = 'ReportsController@getLeads';
        $this->routes['GET']['/reports/projects'] = 'ReportsController@getProjects';
        $this->routes['GET']['/reports/customers'] = 'ReportsController@getCustomers';
        $this->routes['GET']['/reports/financial'] = 'ReportsController@getFinancial';
        $this->routes['POST']['/reports/export'] = 'ReportsController@export';
        $this->routes['GET']['/reports/charts/{type}'] = 'ReportsController@getChartData';
        
        // Rotas de upload
        $this->routes['POST']['/upload'] = 'UploadController@upload';
        
        // Rota de teste
        $this->routes['GET']['/test'] = function() {
            return ['message' => 'API funcionando!', 'timestamp' => date('Y-m-d H:i:s')];
        };
    }
    
    public function handle() {
        $method = $_SERVER['REQUEST_METHOD'];
        $uri = $this->getUri();
        
        // Lidar com requisições OPTIONS (CORS)
        if ($method === 'OPTIONS') {
            http_response_code(200);
            exit;
        }
        
        // Aplicar middlewares de segurança
        applyMiddleware($method, $uri);
        
        // Procurar rota correspondente
        $route = $this->matchRoute($method, $uri);
        
        if (!$route) {
            jsonResponse(['error' => 'Rota não encontrada'], 404);
        }
        
        try {
            $result = $this->executeRoute($route);
            
            if (is_array($result) || is_object($result)) {
                jsonResponse($result);
            } else {
                echo $result;
            }
        } catch (Exception $e) {
            logError("Erro na execução da rota: " . $e->getMessage(), __FILE__, __LINE__);
            jsonResponse(['error' => 'Erro interno do servidor'], 500);
        }
    }
    
    private function getUri() {
        $uri = $_SERVER['REQUEST_URI'];
        
        // Remover query string
        if (($pos = strpos($uri, '?')) !== false) {
            $uri = substr($uri, 0, $pos);
        }
        
        // Remover prefix se existir (exemplo: /duralux/backend/api)
        $prefix = '/duralux/backend/api';
        if (strpos($uri, $prefix) === 0) {
            $uri = substr($uri, strlen($prefix));
        }
        
        return $uri ?: '/';
    }
    
    private function matchRoute($method, $uri) {
        if (!isset($this->routes[$method])) {
            return null;
        }
        
        foreach ($this->routes[$method] as $route_pattern => $handler) {
            $params = [];
            
            // Converter {id} para regex
            $pattern = preg_replace('/\{(\w+)\}/', '([^/]+)', $route_pattern);
            $pattern = '#^' . $pattern . '$#';
            
            if (preg_match($pattern, $uri, $matches)) {
                array_shift($matches); // Remove o match completo
                
                // Extrair nomes dos parâmetros
                preg_match_all('/\{(\w+)\}/', $route_pattern, $param_names);
                
                foreach ($param_names[1] as $i => $name) {
                    $params[$name] = $matches[$i] ?? null;
                }
                
                return [
                    'handler' => $handler,
                    'params' => $params
                ];
            }
        }
        
        return null;
    }
    
    private function executeRoute($route) {
        $handler = $route['handler'];
        $params = $route['params'];
        
        if (is_callable($handler)) {
            return call_user_func($handler);
        }
        
        if (is_string($handler) && strpos($handler, '@') !== false) {
            list($controller, $method) = explode('@', $handler);
            
            $controller_file = __DIR__ . '/../classes/' . $controller . '.php';
            
            if (!file_exists($controller_file)) {
                throw new Exception("Controller não encontrado: $controller");
            }
            
            require_once $controller_file;
            
            if (!class_exists($controller)) {
                throw new Exception("Classe do controller não encontrada: $controller");
            }
            
            $controller_instance = new $controller();
            
            if (!method_exists($controller_instance, $method)) {
                throw new Exception("Método não encontrado: $controller@$method");
            }
            
            return call_user_func_array([$controller_instance, $method], [$params]);
        }
        
        throw new Exception("Handler inválido para a rota");
    }
}

// Executar roteador (sistema legado)
$router = new Router();
$router->handle();

} catch (Exception $e) {
    // Log do erro
    error_log('API Error: ' . $e->getMessage());
    
    // Resposta de erro genérica
    http_response_code(500);
    echo json_encode([
        'error' => true,
        'message' => 'Internal server error',
        'code' => 'INTERNAL_SERVER_ERROR',
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}

/**
 * Servir interface Swagger UI
 */
function serveSwaggerUI() {
    $swaggerHTML = '<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Duralux CRM API Documentation</title>
    <link rel="stylesheet" type="text/css" href="https://unpkg.com/swagger-ui-dist@4.15.5/swagger-ui.css" />
    <link rel="icon" type="image/png" href="https://unpkg.com/swagger-ui-dist@4.15.5/favicon-32x32.png" sizes="32x32" />
    <style>
        html {
            box-sizing: border-box;
            overflow: -moz-scrollbars-vertical;
            overflow-y: scroll;
        }
        *, *:before, *:after {
            box-sizing: inherit;
        }
        body {
            margin:0;
            background: #fafafa;
        }
        .swagger-ui .topbar {
            background-color: #2c3e50;
        }
        .swagger-ui .topbar .download-url-wrapper .select-label {
            color: white;
        }
        .duralux-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            text-align: center;
            margin-bottom: 0;
        }
        .duralux-header h1 {
            margin: 0;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        }
        .duralux-header p {
            margin: 10px 0 0 0;
            opacity: 0.9;
        }
    </style>
</head>
<body>
    <div class="duralux-header">
        <h1>🏢 Duralux CRM API Documentation</h1>
        <p>API REST completa com funcionalidades avançadas de dashboard, analytics e automação</p>
        <p><strong>Versão:</strong> 2.0 | <strong>OpenAPI:</strong> 3.0</p>
    </div>
    
    <div id="swagger-ui"></div>

    <script src="https://unpkg.com/swagger-ui-dist@4.15.5/swagger-ui-bundle.js"></script>
    <script src="https://unpkg.com/swagger-ui-dist@4.15.5/swagger-ui-standalone-preset.js"></script>
    <script>
        window.onload = function() {
            const ui = SwaggerUIBundle({
                url: "/duralux/backend/api/v1/docs.json",
                dom_id: "#swagger-ui",
                deepLinking: true,
                presets: [
                    SwaggerUIBundle.presets.apis,
                    SwaggerUIStandalonePreset
                ],
                plugins: [
                    SwaggerUIBundle.plugins.DownloadUrl
                ],
                layout: "StandaloneLayout",
                validatorUrl: null,
                docExpansion: "list",
                filter: true,
                showRequestHeaders: true,
                showCommonExtensions: true,
                defaultModelsExpandDepth: 2,
                defaultModelExpandDepth: 2,
                requestInterceptor: function(request) {
                    // Adicionar headers padrão
                    request.headers["X-API-Client"] = "SwaggerUI";
                    return request;
                },
                responseInterceptor: function(response) {
                    // Log das respostas para debug
                    console.log("API Response:", response);
                    return response;
                }
            });
            
            // Personalização adicional
            setTimeout(() => {
                const logo = document.querySelector(".topbar-wrapper img");
                if (logo) {
                    logo.style.display = "none";
                }
                
                const title = document.querySelector(".topbar-wrapper .link");
                if (title) {
                    title.innerHTML = "Duralux CRM API v2.0";
                    title.style.color = "white";
                    title.style.fontWeight = "bold";
                }
            }, 1000);
        }
    </script>
</body>
</html>';

    header('Content-Type: text/html; charset=utf-8');
    echo $swaggerHTML;
}

?>