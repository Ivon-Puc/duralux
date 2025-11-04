<?php
/**
 * Roteador Principal da API - Duralux CRM
 * Gerencia todas as rotas da aplicação
 */

require_once __DIR__ . '/../init.php';
require_once __DIR__ . '/../classes/AuthMiddleware.php';

// Verificar se é requisição para dashboard ou API direta
$data = json_decode(file_get_contents('php://input'), true) ?: [];
$action = $data['action'] ?? $_GET['action'] ?? '';

// Se a requisição tem uma action, usar o sistema de controllers direto
if ($action) {
    // Aplicar middleware de segurança
    AuthMiddleware::handle();
    
    // Roteamento baseado em action
    switch ($action) {
        // Dashboard actions
        case 'get_dashboard_stats':
        case 'get_revenue_data':
        case 'get_leads_analytics':
        case 'get_projects_analytics':
        case 'get_recent_activities':
        case 'check_auth':
            $controller = new DashboardController();
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
            break;        // Auth actions
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
        
        // Rotas de pedidos
        $this->routes['GET']['/orders'] = 'OrderController@index';
        $this->routes['GET']['/orders/{id}'] = 'OrderController@view';
        $this->routes['POST']['/orders'] = 'OrderController@create';
        $this->routes['PUT']['/orders/{id}'] = 'OrderController@update';
        $this->routes['DELETE']['/orders/{id}'] = 'OrderController@delete';
        $this->routes['GET']['/orders/statistics'] = 'OrderController@statistics';
        $this->routes['POST']['/orders/{id}/invoice'] = 'OrderController@generateInvoice';
        
        // Rotas de dashboard
        $this->routes['GET']['/dashboard/stats'] = 'DashboardController@getDashboardStats';
        $this->routes['GET']['/dashboard/revenue'] = 'DashboardController@getRevenueData';
        $this->routes['GET']['/dashboard/leads'] = 'DashboardController@getLeadsAnalytics';
        $this->routes['GET']['/dashboard/projects'] = 'DashboardController@getProjectsAnalytics';
        $this->routes['GET']['/dashboard/activities'] = 'DashboardController@getRecentActivities';
        
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

// Executar roteador
$router = new Router();
$router->handle();
?>